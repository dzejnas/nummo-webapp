<?php
require_once __DIR__ . '/BaseDao.php';

// Remembers which payment an Idempotency-Key created, per user (see TransactionService::create).
// shortcut: keys are kept forever; add a cleanup job (e.g. delete rows older than 30 days) if the table grows.
class IdempotencyKeyDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('idempotency_keys', ['user_id', 'idem_key', 'request_hash', 'transaction_id']);
    }

    // A locking read always sees the latest committed row (a plain read could see an older snapshot)
    public function findForUpdate(int $userId, string $key): ?array
    {
        return $this->queryOne(
            "SELECT * FROM idempotency_keys WHERE user_id = :user_id AND idem_key = :idem_key FOR UPDATE",
            ['user_id' => $userId, 'idem_key' => $key]
        );
    }
}
