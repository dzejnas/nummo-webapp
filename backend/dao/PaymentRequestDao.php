<?php
require_once __DIR__ . '/BaseDao.php';

class PaymentRequestDao extends BaseDao
{
    private const SELECT = "SELECT p.*, rq.name AS requester_name, py.name AS payer_name
                            FROM payment_requests p
                            JOIN users rq ON rq.id = p.requester_id
                            JOIN users py ON py.id = p.payer_id";

    public function __construct()
    {
        parent::__construct('payment_requests', ['requester_id', 'payer_id', 'amount', 'note', 'status', 'transaction_id']);
    }

    // Locks the request until the DB transaction ends (see TransactionDao::getByIdForUpdate)
    public function getByIdForUpdate(int $id): ?array
    {
        return $this->queryOne("SELECT * FROM payment_requests WHERE id = :id FOR UPDATE", ['id' => $id]);
    }

    public function getAll(): array
    {
        return $this->query(self::SELECT . " ORDER BY p.created_at DESC, p.id DESC");
    }

    public function getById(int $id): ?array
    {
        return $this->queryOne(self::SELECT . " WHERE p.id = :id", ['id' => $id]);
    }

    // Requests the user sent or has to pay
    public function getForUser(int $userId): array
    {
        return $this->query(
            self::SELECT . " WHERE p.requester_id = :requester_id OR p.payer_id = :payer_id
                             ORDER BY p.created_at DESC, p.id DESC",
            ['requester_id' => $userId, 'payer_id' => $userId]
        );
    }
}
