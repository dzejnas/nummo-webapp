<?php
require_once __DIR__ . '/BaseDao.php';

class TransactionDao extends BaseDao {
    public function __construct() {
        parent::__construct('transactions');
    }

    // ====================================================
    // 🔹 Get all transactions for a given user
    // ====================================================
    public function getAllForUser(int $userId, ?string $status = null, int $limit = 50, int $offset = 0): array {
        $sql = "SELECT 
                    t.id, t.sender_id, t.receiver_id, t.merchant_id, t.category_id,
                    t.amount, t.note, t.status, t.created_at,
                    s.name AS sender_name, r.name AS receiver_name,
                    m.name AS merchant_name, c.name AS category_name
                FROM {$this->table} t
                JOIN users s ON s.id = t.sender_id
                JOIN users r ON r.id = t.receiver_id
                LEFT JOIN merchants m ON m.id = t.merchant_id
                LEFT JOIN categories c ON c.id = t.category_id
                WHERE (t.sender_id = :sender_uid OR t.receiver_id = :receiver_uid)";

        $params = [
            'sender_uid' => $userId,
            'receiver_uid' => $userId
        ];

        if (!empty($status)) {
            $sql .= " AND t.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':sender_uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':receiver_uid', $userId, PDO::PARAM_INT);
        if (!empty($status)) $stmt->bindValue(':status', $status);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ====================================================
    // 🔹 Create a new transaction
    // ====================================================
    public function create(array $data): int {
        $sql = "INSERT INTO {$this->table} 
                    (sender_id, receiver_id, merchant_id, category_id, amount, note, status)
                VALUES 
                    (:sender_id, :receiver_id, :merchant_id, :category_id, :amount, :note, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'sender_id'   => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'merchant_id' => $data['merchant_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'amount'      => $data['amount'],
            'note'        => $data['note'] ?? null,
            'status'      => $data['status'] ?? 'pending',
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ====================================================
    // 🔹 Update a transaction
    // ====================================================
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];

        foreach (['sender_id','receiver_id','merchant_id','category_id','amount','note','status'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[$col] = $data[$col];
            }
        }

        if (!$fields) return false;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ====================================================
    // 🔹 Filtered queries (Admin or User)
    // ====================================================
    public function getAllFiltered(?string $status = null, ?string $category = null): array {
        $sql = "SELECT 
                    t.id, t.sender_id, t.receiver_id, t.merchant_id, t.category_id,
                    t.amount, t.note, t.status, t.created_at,
                    s.name AS sender_name, r.name AS receiver_name,
                    m.name AS merchant_name, c.name AS category_name
                FROM {$this->table} t
                JOIN users s ON s.id = t.sender_id
                JOIN users r ON r.id = t.receiver_id
                LEFT JOIN merchants m ON m.id = t.merchant_id
                LEFT JOIN categories c ON c.id = t.category_id
                WHERE 1=1";

        $params = [];
        if (!empty($status) && $status !== 'All') {
            $sql .= " AND t.status = :status";
            $params['status'] = $status;
        }
        if (!empty($category) && $category !== 'All') {
            $sql .= " AND c.name = :category";
            $params['category'] = $category;
        }

        $sql .= " ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllForUserFiltered(int $userId, ?string $status = null, ?string $category = null): array {
        $sql = "SELECT 
                    t.id, t.sender_id, t.receiver_id, t.merchant_id, t.category_id,
                    t.amount, t.note, t.status, t.created_at,
                    s.name AS sender_name, r.name AS receiver_name,
                    m.name AS merchant_name, c.name AS category_name
                FROM {$this->table} t
                JOIN users s ON s.id = t.sender_id
                JOIN users r ON r.id = t.receiver_id
                LEFT JOIN merchants m ON m.id = t.merchant_id
                LEFT JOIN categories c ON c.id = t.category_id
                WHERE (t.sender_id = :sender_uid OR t.receiver_id = :receiver_uid)";
    
        $params = [
            ':sender_uid' => $userId,
            ':receiver_uid' => $userId
        ];
    
        if (!empty($status) && $status !== 'All') {
            $sql .= " AND t.status = :status";
            $params[':status'] = $status;
        }
        if (!empty($category) && $category !== 'All') {
            $sql .= " AND c.name = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
