<?php
require_once __DIR__ . '/BaseDao.php';

class ContactDao extends BaseDao {

    public function __construct() {
        parent::__construct('contacts');
    }

    // ====================================================
    // 🔹 Get all contacts for a specific user
    // ====================================================
    public function getAllForUser(int $userId, ?string $status = null): array {
        $sql = "SELECT 
                    c.id, c.user_id, c.friend_user_id, c.status, c.created_at,
                    u.name AS friend_name, u.email AS friend_email
                FROM {$this->table} c
                JOIN users u ON u.id = c.friend_user_id
                WHERE c.user_id = :uid";
        
        $params = [':uid' => $userId];
        if (!empty($status)) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY c.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ====================================================
    // 🔹 Create new contact
    // ====================================================
    public function create(array $data): int {
        $sql = "INSERT INTO {$this->table} (user_id, friend_user_id, status)
                VALUES (:user_id, :friend_user_id, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'        => $data['user_id'],
            ':friend_user_id' => $data['friend_user_id'],
            ':status'         => $data['status'] ?? 'pending',
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ====================================================
    // 🔹 Update contact status
    // ====================================================
    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }
}
?>
