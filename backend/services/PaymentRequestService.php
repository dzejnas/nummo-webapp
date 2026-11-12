<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/TransactionService.php';
require_once __DIR__ . '/../dao/PaymentRequestDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

// A user (requester) asks another user (payer) for money.
// When the payer accepts (status "paid"), a completed transaction payer -> requester is created and
// its id is saved in payment_requests.transaction_id (unique), all in one DB transaction.
// Who may do what ($user is the logged-in user, admins may do everything):
// - see: requester and payer
// - pay or decline: only the payer. Paying an already paid request again returns it unchanged
//   (a safe retry: no second debit)
// - change amount/note or delete: only the requester, and only while it is not paid
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
        $requester = $users->getById($requesterId);
        $payer     = $users->getById($payerId);
        if ($requester === null || $payer === null) {
            throw new ApiException('User does not exist');
        }
        if ($requester['closed_at'] !== null || $payer['closed_at'] !== null) {
            throw new ApiException('This account is closed', 409);
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
        // The request row stays locked until commit, so pay / decline / delete / edit of the same
        // request run one after the other and each sees the result of the previous one
        $this->dao->beginTransaction();
        try {
            $request = $this->lockFor($id, $user);

            if (($data['status'] ?? null) === 'paid' && $request['status'] === 'paid') {
                // Already settled (e.g. a retry by the payer): change nothing, return the original result
                $this->assertCanAccess($user, $request['payer_id'] === $user['id']);
                $changes = [];
            } else {
                $changes = $this->validChanges($request, $data, $user);
            }
            if (($changes['status'] ?? null) === 'paid') {
                $changes['transaction_id'] = $this->pay(array_merge($request, $changes));
            }

            $this->dao->update($id, $changes);
            $this->dao->commit();
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
        return $this->getById($id);
    }

    // Paid requests are kept as a record of the payment
    public function deleteFor(int $id, array $user): void
    {
        $this->dao->beginTransaction();
        try {
            $request = $this->lockFor($id, $user);
            $this->assertCanAccess($user, $request['requester_id'] === $user['id']);
            if ($request['status'] === 'paid') {
                throw new ApiException('Paid requests cannot be deleted', 409);
            }
            $this->dao->delete($id);
            $this->dao->commit();
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
    }

    // Checks who may change what on a pending request and returns the columns to update
    private function validChanges(array $request, array $data, array $user): array
    {
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
        return $changes;
    }

    // Locks the request and checks the user takes part in it. Must run inside a DB transaction.
    private function lockFor(int $id, array $user): array
    {
        $request = $this->dao->getByIdForUpdate($id);
        if ($request === null) {
            throw new ApiException('Payment request not found', 404);
        }
        $this->assertCanAccess($user, in_array($user['id'], [$request['requester_id'], $request['payer_id']], true));
        return $request;
    }

    // Creates the completed payment payer -> requester and returns its id
    private function pay(array $request): int
    {
        $transactions = new TransactionService();
        $note = 'Payment request #' . $request['id'] . ($request['note'] ? ': ' . $request['note'] : '');
        $note = mb_substr($note, 0, 255); // the note column holds at most 255 characters

        return $transactions->record($transactions->validateNew([
            'sender_id'   => $request['payer_id'],
            'receiver_id' => $request['requester_id'],
            'amount'      => $request['amount'],
            'note'        => $note,
            'status'      => 'completed',
        ]));
    }
}
