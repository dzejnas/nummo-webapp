<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/TransactionDao.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/MerchantDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';

// Business rules for payments:
// - a payment goes from a sender to EITHER another user OR a merchant
// - a completed payment moves money: sender balance goes down, receiver balance goes up
// - the sender must have enough balance
// - completed payments cannot be edited or deleted (they are financial records)
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

    public function getFiltered(array $filters): array
    {
        $userId     = isset($filters['user_id']) ? Validator::id($filters, 'user_id') : null;
        $status     = isset($filters['status']) ? Validator::oneOf($filters, 'status', self::STATUSES) : null;
        $categoryId = isset($filters['category_id']) ? Validator::id($filters, 'category_id') : null;

        return $this->dao->getFiltered($userId, $status, $categoryId);
    }

    public function getSpendingByCategory(int $userId): array
    {
        if ($this->users->getById($userId) === null) {
            throw new ApiException('User not found', 404);
        }
        return $this->dao->getSpendingByCategory($userId);
    }

    public function create(array $data): array
    {
        $transaction = $this->validateNew($data);

        $this->dao->beginTransaction();
        try {
            $id = $this->record($transaction);
            $this->dao->commit();
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
        return $this->getById($id);
    }

    // Saves a validated transaction and moves the money if it is completed.
    // The caller must have started a DB transaction (also used by PaymentRequestService).
    public function record(array $transaction): int
    {
        if ($transaction['status'] === 'completed') {
            $this->moveMoney($transaction);
        }
        return $this->dao->insert($transaction);
    }

    // Only note, category and status can change. Amount and people are fixed once created.
    public function update(int $id, array $data): array
    {
        $current = $this->getById($id);
        if ($current['status'] !== 'pending') {
            throw new ApiException('Only pending transactions can be changed', 409);
        }

        $changes = [];
        if (array_key_exists('note', $data)) {
            $changes['note'] = Validator::optionalString($data, 'note');
        }
        if (array_key_exists('category_id', $data)) {
            $changes['category_id'] = $this->validCategoryId($data);
        }

        $this->dao->beginTransaction();
        try {
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

    public function delete(int $id): void
    {
        if ($this->getById($id)['status'] === 'completed') {
            throw new ApiException('Completed transactions cannot be deleted', 409);
        }
        $this->dao->delete($id);
    }

    // Checks the request body for a new transaction and returns the row to insert
    public function validateNew(array $data): array
    {
        $senderId = Validator::id($data, 'sender_id');
        if ($this->users->getById($senderId) === null) {
            throw new ApiException('Sender does not exist');
        }

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
            if ($this->users->getById($transaction['receiver_id']) === null) {
                throw new ApiException('Receiver does not exist');
            }
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

    private function moveMoney(array $transaction): void
    {
        $sender = $this->users->getById($transaction['sender_id']);
        if ((float) $sender['balance'] < (float) $transaction['amount']) {
            throw new ApiException('Insufficient balance');
        }

        $this->users->changeBalance($transaction['sender_id'], -$transaction['amount']);
        if ($transaction['receiver_id'] !== null) {
            $this->users->changeBalance($transaction['receiver_id'], (float) $transaction['amount']);
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
