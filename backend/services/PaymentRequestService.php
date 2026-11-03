<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/TransactionService.php';
require_once __DIR__ . '/../dao/PaymentRequestDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

// A user (requester) asks another user (payer) for money.
// When the payer accepts (status "paid"), a completed transaction payer -> requester is created.
class PaymentRequestService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new PaymentRequestDao(), 'Payment request');
    }

    public function getForUser(int $userId): array
    {
        return $this->dao->getForUser($userId);
    }

    public function create(array $data): array
    {
        $requesterId = Validator::id($data, 'requester_id');
        $payerId     = Validator::id($data, 'payer_id');

        if ($requesterId === $payerId) {
            throw new ApiException('You cannot request money from yourself');
        }
        $users = new UserDao();
        if ($users->getById($requesterId) === null || $users->getById($payerId) === null) {
            throw new ApiException('User does not exist');
        }

        $id = $this->dao->insert([
            'requester_id' => $requesterId,
            'payer_id'     => $payerId,
            'amount'       => Validator::amount($data),
            'note'         => Validator::optionalString($data, 'note'),
            'status'       => 'pending',
        ]);
        return $this->getById($id);
    }

    // While pending: amount and note can change, and status can become "paid" or "declined"
    public function update(int $id, array $data): array
    {
        $request = $this->getById($id);
        if ($request['status'] !== 'pending') {
            throw new ApiException('This request is already ' . $request['status'], 409);
        }

        $changes = [];
        if (isset($data['amount'])) {
            $changes['amount'] = Validator::amount($data);
        }
        if (array_key_exists('note', $data)) {
            $changes['note'] = Validator::optionalString($data, 'note');
        }
        if (isset($data['status'])) {
            $changes['status'] = Validator::oneOf($data, 'status', ['pending', 'paid', 'declined']);
        }

        // Paying creates a transaction, so the request and the payment are saved together
        $this->dao->beginTransaction();
        try {
            if (($changes['status'] ?? null) === 'paid') {
                $this->pay(array_merge($request, $changes));
            }
            $this->dao->update($id, $changes);
            $this->dao->commit();
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
        return $this->getById($id);
    }

    private function pay(array $request): void
    {
        $transactions = new TransactionService();
        $note = 'Payment request #' . $request['id'] . ($request['note'] ? ': ' . $request['note'] : '');
        $note = mb_substr($note, 0, 255); // the note column holds at most 255 characters

        $transactions->record($transactions->validateNew([
            'sender_id'   => $request['payer_id'],
            'receiver_id' => $request['requester_id'],
            'amount'      => $request['amount'],
            'note'        => $note,
            'status'      => 'completed',
        ]));
    }
}
