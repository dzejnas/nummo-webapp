<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/TransactionService.php';
require_once __DIR__ . '/../dao/PaymentRequestDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

// A user (requester) asks another user (payer) for money.
// When the payer accepts (status "paid"), a completed transaction payer -> requester is created.
// Who may do what ($user is the logged-in user, admins may do everything):
// - see: requester and payer
// - pay or decline: only the payer
// - change amount/note or delete: only the requester
class PaymentRequestService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new PaymentRequestDao(), 'Payment request');
    }

    public function getAllFor(array $user): array
    {
        return $this->isAdmin($user) ? $this->dao->getAll() : $this->dao->getForUser($user['id']);
    }

    public function getByIdFor(int $id, array $user): array
    {
        $request = $this->getById($id);
        $this->assertCanAccess($user, in_array($user['id'], [$request['requester_id'], $request['payer_id']], true));
        return $request;
    }

    public function create(array $data, array $user): array
    {
        if (!$this->isAdmin($user)) {
            $data['requester_id'] = $user['id'];
        }
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
    public function update(int $id, array $data, array $user): array
    {
        $request = $this->getByIdFor($id, $user);
        if ($request['status'] !== 'pending') {
            throw new ApiException('This request is already ' . $request['status'], 409);
        }

        $changes = [];
        if (isset($data['amount']) || array_key_exists('note', $data)) {
            $this->assertCanAccess($user, $request['requester_id'] === $user['id']);
        }
        if (isset($data['amount'])) {
            $changes['amount'] = Validator::amount($data);
        }
        if (array_key_exists('note', $data)) {
            $changes['note'] = Validator::optionalString($data, 'note');
        }
        if (isset($data['status'])) {
            $this->assertCanAccess($user, $request['payer_id'] === $user['id']);
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

    public function deleteFor(int $id, array $user): void
    {
        $request = $this->getByIdFor($id, $user);
        $this->assertCanAccess($user, $request['requester_id'] === $user['id']);
        $this->dao->delete($id);
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
