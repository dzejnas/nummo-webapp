<?php
require_once __DIR__ . '/BaseDao.php';

class TransactionDao extends BaseDao
{
    // Transaction row + readable names of sender, receiver, merchant and category
    private const SELECT = "SELECT t.*,
                                   s.name AS sender_name,
                                   r.name AS receiver_name,
                                   m.name AS merchant_name,
                                   c.name AS category_name
                            FROM transactions t
                            JOIN users s           ON s.id = t.sender_id
                            LEFT JOIN users r      ON r.id = t.receiver_id
                            LEFT JOIN merchants m  ON m.id = t.merchant_id
                            LEFT JOIN categories c ON c.id = t.category_id";

    public function __construct()
    {
        parent::__construct('transactions', [
            'sender_id', 'receiver_id', 'merchant_id', 'category_id', 'amount', 'currency', 'note', 'status',
        ]);
    }

    public function getById(int $id): ?array
    {
        return $this->queryOne(self::SELECT . " WHERE t.id = :id", ['id' => $id]);
    }

    // All transactions, optionally filtered. Pass $userId to get only that user's transactions.
    public function getFiltered(?int $userId = null, ?string $status = null, ?int $categoryId = null): array
    {
        $sql    = self::SELECT . " WHERE 1 = 1";
        $params = [];

        if ($userId !== null) {
            // Native prepared statements cannot reuse one placeholder twice, so we use two names
            $sql .= " AND (t.sender_id = :sender_id OR t.receiver_id = :receiver_id)";
            $params['sender_id']   = $userId;
            $params['receiver_id'] = $userId;
        }
        if ($status !== null) {
            $sql .= " AND t.status = :status";
            $params['status'] = $status;
        }
        if ($categoryId !== null) {
            $sql .= " AND t.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        return $this->query($sql . " ORDER BY t.created_at DESC, t.id DESC", $params);
    }

    public function getAll(): array
    {
        return $this->getFiltered();
    }

    // Total spent per category by one user (for the dashboard chart)
    public function getSpendingByCategory(int $userId): array
    {
        return $this->query(
            "SELECT COALESCE(c.name, 'Uncategorized') AS category, SUM(t.amount) AS total
             FROM transactions t
             LEFT JOIN categories c ON c.id = t.category_id
             WHERE t.sender_id = :user_id AND t.status = 'completed'
             GROUP BY category
             ORDER BY total DESC",
            ['user_id' => $userId]
        );
    }
}
