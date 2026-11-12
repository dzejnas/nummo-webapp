<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/TransactionDao.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/MerchantDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';
require_once __DIR__ . '/../dao/IdempotencyKeyDao.php';

// Business rules for payments:
// - a payment goes from a sender to EITHER another user OR a merchant
// - a completed payment moves money: sender balance goes down, receiver balance goes up
// - the sender must have enough balance; closed accounts cannot send or receive
// - completed payments cannot be edited or deleted (they are financial records)
// - users see payments they sent or received; only the sender (or an admin) can change one
// $user is the logged-in user (from the JWT).
//
// Concurrency: every state change runs in one DB transaction on the shared PDO connection.
// The row being changed is locked first (SELECT ... FOR UPDATE), then the users' rows in id order,
// and the debit is a single conditional UPDATE, so two requests can never both spend the same money.
class TransactionService extends BaseService
{
    // All balances in Nummo are kept in euros. users.currency is only the display currency.
    public const CURRENCY = 'EUR';
    private const STATUSES = ['pending', 'completed', 'failed'];

    private UserDao $users;

    public function __construct()
    {
        parent::__construct(new TransactionDao(), 'Transaction');
        $this->users = new UserDao();
    }

    public function getFiltered(array $filters, array $user): array
    {
        // Normal users always get only their own transactions
        if (!$this->isAdmin($user)) {
            $filters['user_id'] = $user['id'];
        }
        $userId     = isset($filters['user_id']) ? Validator::id($filters, 'user_id') : null;
        $status     = isset($filters['status']) ? Validator::oneOf($filters, 'status', self::STATUSES) : null;
        $categoryId = isset($filters['category_id']) ? Validator::id($filters, 'category_id') : null;

        return $this->dao->getFiltered($userId, $status, $categoryId);
    }

    // Also used for receipts: only the sender, the receiver or an admin may see a transaction
    public function getByIdFor(int $id, array $user): array
    {
        $transaction = $this->getById($id);
        $this->assertCanAccess($user, in_array($user['id'], [$transaction['sender_id'], $transaction['receiver_id']], true));
        return $transaction;
    }

    public function getSpendingByCategory(int $userId): array
    {
        if ($this->users->getById($userId) === null) {
            throw new ApiException('User not found', 404);
        }
        return $this->dao->getSpendingByCategory($userId);
    }

    // Returns ['transaction' => ..., 'replayed' => bool]. With an Idempotency-Key, a retry with the
    // same key and the same payment returns the first payment instead of paying again.
    public function create(array $data, array $user, ?string $idempotencyKey = null): array
    {
        // Users can only send their own money; admins may record a payment for anyone
        if (!$this->isAdmin($user)) {
            $data['sender_id'] = $user['id'];
        }
        $transaction = $this->validateNew($data);
        if ($idempotencyKey !== null) {
            $idempotencyKey = Validator::string(['Idempotency-Key' => $idempotencyKey], 'Idempotency-Key', 8, 100);
        }
        $keys = new IdempotencyKeyDao();
        $hash = hash('sha256', json_encode($transaction));

        $existing = null;
        $this->dao->beginTransaction();
        try {
            // Locking the sender first puts retries of the same payment in a queue: the second one
            // only reads the key after the first has committed, so it finds it and replays it.
            $this->users->lockForUpdate(array_filter([$transaction['sender_id'], $transaction['receiver_id']]));
            if ($idempotencyKey !== null) {
                $existing = $keys->findForUpdate($user['id'], $idempotencyKey);
            }
            if ($existing === null) {
                $id = $this->record($transaction);
                if ($idempotencyKey !== null) {
                    $keys->insert(['user_id' => $user['id'], 'idem_key' => $idempotencyKey,
                                   'request_hash' => $hash, 'transaction_id' => $id]);
                }
            }
            $this->dao->commit();
        } catch (Throwable $e) {
            $this->dao->rollBack(); // nothing is saved, so the same key can be retried
            throw $e;
        }
        if ($existing !== null) {
            return $this->replay($existing, $hash);
        }
        return ['transaction' => $this->getById($id), 'replayed' => false];
    }

    private function replay(?array $key, string $hash): array
    {
        if ($key === null || $key['request_hash'] !== $hash) {
            throw new ApiException('This Idempotency-Key was already used for a different payment', 409);
        }
        if ($key['transaction_id'] === null) {
            throw new ApiException('The payment made with this Idempotency-Key was deleted', 409);
        }
        return ['transaction' => $this->getById($key['transaction_id']), 'replayed' => true];
    }

    // Saves a validated transaction and moves the money if it is completed. Returns the new id.
    // The caller must have started a DB transaction (also used by PaymentRequestService).
    public function record(array $transaction): int
    {
        if ($transaction['status'] === 'completed') {
            $this->moveMoney($transaction);
        }
        return $this->dao->insert($transaction);
    }

    // Only note, category and status can change. Amount and people are fixed once created.
    public function update(int $id, array $data, array $user): array
    {
        $this->dao->beginTransaction();
        try {
            $current = $this->lockPendingOwnedBy($id, $user);

            $changes = [];
            if (array_key_exists('note', $data)) {
                $changes['note'] = Validator::optionalString($data, 'note');
            }
            if (array_key_exists('category_id', $data)) {
                $changes['category_id'] = $this->validCategoryId($data);
            }
            if (isset($data['status'])) {
                $changes['status'] = Validator::oneOf($data, 'status', self::STATUSES);
                if ($changes['status'] === 'completed') {
                    $this->moveMoney($current);
                }
            }
            $this->dao->update($id, $changes);
            $this->dao->commit();
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
        return $this->getById($id);
    }

    // Pending and failed payments can be deleted; completed ones are records and cannot.
    public function deleteFor(int $id, array $user): void
    {
        $this->dao->beginTransaction();
        try {
            $transaction = $this->dao->getByIdForUpdate($id);
            if ($transaction === null) {
                throw new ApiException('Transaction not found', 404);
            }
            $this->assertCanAccess($user, $transaction['sender_id'] === $user['id']);
            if ($transaction['status'] === 'completed') {
                throw new ApiException('Completed transactions cannot be deleted', 409);
            }
            $this->dao->delete($id);
            $this->dao->commit();
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
    }

    // Checks the request body for a new transaction and returns the row to insert
    public function validateNew(array $data): array
    {
        $senderId = Validator::id($data, 'sender_id');
        $this->assertOpenAccount($senderId, 'Sender');

        $hasReceiver = !empty($data['receiver_id']);
        $hasMerchant = !empty($data['merchant_id']);
        if ($hasReceiver === $hasMerchant) {
            throw new ApiException('Set either receiver_id (pay a user) or merchant_id (pay a merchant)');
        }

        $transaction = [
            'sender_id'   => $senderId,
            'receiver_id' => null,
            'merchant_id' => null,
            'category_id' => $this->validCategoryId($data),
            'amount'      => Validator::amount($data),
            'currency'    => self::CURRENCY,
            'note'        => Validator::optionalString($data, 'note'),
            'status'      => isset($data['status']) ? Validator::oneOf($data, 'status', ['pending', 'completed']) : 'completed',
        ];

        if ($hasReceiver) {
            $transaction['receiver_id'] = Validator::id($data, 'receiver_id');
            if ($transaction['receiver_id'] === $senderId) {
                throw new ApiException('You cannot send money to yourself');
            }
            $this->assertOpenAccount($transaction['receiver_id'], 'Receiver');
        } else {
            $transaction['merchant_id'] = Validator::id($data, 'merchant_id');
            $merchant = (new MerchantDao())->getById($transaction['merchant_id']);
            if ($merchant === null) {
                throw new ApiException('Merchant does not exist');
            }
            // Payments to a merchant get the merchant's category if none is given
            $transaction['category_id'] ??= $merchant['category_id'];
        }

        return $transaction;
    }

    // Locks the transaction row and checks it can still change. Must run inside a DB transaction.
    private function lockPendingOwnedBy(int $id, array $user): array
    {
        $transaction = $this->dao->getByIdForUpdate($id);
        if ($transaction === null) {
            throw new ApiException('Transaction not found', 404);
        }
        $this->assertCanAccess($user, $transaction['sender_id'] === $user['id']);
        if ($transaction['status'] !== 'pending') {
            throw new ApiException('Only pending transactions can be changed', 409);
        }
        return $transaction;
    }

    // Debit + credit. Must run inside a DB transaction.
    private function moveMoney(array $transaction): void
    {
        $ids = array_filter([$transaction['sender_id'], $transaction['receiver_id']]);
        foreach ($this->users->lockForUpdate($ids) as $account) {
            if ($account['closed_at'] !== null) {
                throw new ApiException('This account is closed', 409);
            }
        }
        if (!$this->users->debit($transaction['sender_id'], $transaction['amount'])) {
            throw new ApiException('Insufficient balance');
        }
        if ($transaction['receiver_id'] !== null) {
            $this->users->credit($transaction['receiver_id'], $transaction['amount']);
        }
    }

    private function assertOpenAccount(int $userId, string $who): void
    {
        $account = $this->users->getById($userId);
        if ($account === null) {
            throw new ApiException("$who does not exist");
        }
        if ($account['closed_at'] !== null) {
            throw new ApiException("$who account is closed", 409);
        }
    }

    private function validCategoryId(array $data): ?int
    {
        if (empty($data['category_id'])) {
            return null;
        }
        $categoryId = Validator::id($data, 'category_id');
        if ((new CategoryDao())->getById($categoryId) === null) {
            throw new ApiException('Category does not exist');
        }
        return $categoryId;
    }
}
