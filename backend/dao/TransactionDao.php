<?php
require_once __DIR__ . '/../config/Database.php';

class TransactionDao {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // 🔹 Get all transactions for a given user
    public function getAllForUser(int $userId, ?string $status = null, int $limit = 50, int $offset = 0): array {
        $sql = "SELECT t.id, t.sender_id, t.receiver_id, t.merchant_id, t.category_id,
                       t.amount, t.note, t.status, t.created_at,
                       s.name AS sender_name, r.name AS receiver_name,
                       m.name AS merchant_name, c.name AS category_name
                FROM transactions t
                JOIN users s ON s.id = t.sender_id
                JOIN users r ON r.id = t.receiver_id
                LEFT JOIN merchants m ON m.id = t.merchant_id
                LEFT JOIN categories c ON c.id = t.category_id
                WHERE (t.sender_id = :sender_uid OR t.receiver_id = :receiver_uid)";

        $params = [
            ':sender_uid' => $userId,
            ':receiver_uid' => $userId,
        ];

        if (!empty($status)) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $status;
        }

        $limit = (int)$limit;
        $offset = (int)$offset;
        $sql .= " ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Get all transactions (admin / testing)
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT 
                t.id, t.sender_id, t.receiver_id, t.merchant_id, t.category_id,
                t.amount, t.note, t.status, t.created_at,
                s.name AS sender_name, r.name AS receiver_name,
                m.name AS merchant_name, c.name AS category_name
            FROM transactions t
            JOIN users s ON s.id = t.sender_id
            JOIN users r ON r.id = t.receiver_id
            LEFT JOIN merchants m ON m.id = t.merchant_id
            LEFT JOIN categories c ON c.id = t.category_id
            ORDER BY t.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Get one transaction by ID
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT id, sender_id, receiver_id, merchant_id, category_id,
                   amount, note, status, created_at
            FROM transactions WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // 🔹 Create a new transaction
    public function create(array $data): int {
        $sql = "INSERT INTO transactions (sender_id, receiver_id, merchant_id, category_id, amount, note, status)
                VALUES (:sender_id, :receiver_id, :merchant_id, :category_id, :amount, :note, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':sender_id' => $data['sender_id'],
            ':receiver_id' => $data['receiver_id'],
            ':merchant_id' => $data['merchant_id'] ?? null,
            ':category_id' => $data['category_id'] ?? null,
            ':amount' => $data['amount'],
            ':note' => $data['note'] ?? null,
            ':status' => $data['status'] ?? 'pending',
        ]);
        return (int)$this->db->lastInsertId();
    }

    // 🔹 Update a transaction
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        foreach (['sender_id','receiver_id','merchant_id','category_id','amount','note','status'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }
        if (!$fields) return false;
        $sql = "UPDATE transactions SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // 🔹 Delete a transaction
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ====================================================
    // 🔹 Fetch all transactions (global filter support)
    // ====================================================
    public function getAllFiltered(?string $status = null, ?string $category = null): array {
        $sql = "SELECT t.id, t.sender_id, t.receiver_id, t.merchant_id, t.category_id,
                       t.amount, t.note, t.status, t.created_at,
                       s.name AS sender_name, r.name AS receiver_name,
                       m.name AS merchant_name, c.name AS category_name
                FROM transactions t
                JOIN users s ON s.id = t.sender_id
                JOIN users r ON r.id = t.receiver_id
                LEFT JOIN merchants m ON m.id = t.merchant_id
                LEFT JOIN categories c ON c.id = t.category_id
                WHERE 1=1";

        $params = [];

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
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ====================================================
    // 🔹 Fetch transactions for a specific user with filters
    // ====================================================
    public function getAllForUserFiltered(int $userId, ?string $status = null, ?string $category = null): array {
        $sql = "SELECT 
                    t.id, t.sender_id, t.receiver_id, t.merchant_id, t.category_id,
                    t.amount, t.note, t.status, t.created_at,
                    s.name AS sender_name, r.name AS receiver_name,
                    m.name AS merchant_name, c.name AS category_name
                FROM transactions t
                JOIN users s ON s.id = t.sender_id
                JOIN users r ON r.id = t.receiver_id
                LEFT JOIN merchants m ON m.id = t.merchant_id
                LEFT JOIN categories c ON c.id = t.category_id
                WHERE (t.sender_id = :sender_uid OR t.receiver_id = :receiver_uid)";
    
        // ✅ Use two separate placeholders for clarity
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
    
        // ✅ Bind all params safely
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}   