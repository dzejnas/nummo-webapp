<?php
require_once __DIR__ . '/../config/Database.php';


class ContactDao {
private \PDO $db;
public function __construct() { $this->db = Database::getConnection(); }


public function getAllForUser(int $userId, ?string $status = null): array {
$sql = "SELECT c.id, c.user_id, c.friend_user_id, c.status, c.created_at,
u.name AS friend_name, u.email AS friend_email
FROM contacts c
JOIN users u ON u.id = c.friend_user_id
WHERE c.user_id = :uid" . ($status ? " AND c.status = :status" : "") .
" ORDER BY c.created_at DESC";
$stmt = $this->db->prepare($sql);
$params = [':uid' => $userId];
if ($status) $params[':status'] = $status;
$stmt->execute($params);
return $stmt->fetchAll();
}


public function getById(int $id): ?array {
$stmt = $this->db->prepare("SELECT id, user_id, friend_user_id, status, created_at FROM contacts WHERE id = ?");
$stmt->execute([$id]);
return $stmt->fetch() ?: null;
}


public function create(array $data): int {
$sql = "INSERT INTO contacts (user_id, friend_user_id, status) VALUES (:user_id, :friend_user_id, :status)";
$stmt = $this->db->prepare($sql);
$stmt->execute([
':user_id' => $data['user_id'],
':friend_user_id' => $data['friend_user_id'],
':status' => $data['status'] ?? 'pending',
]);
return (int)$this->db->lastInsertId();
}


public function update(int $id, array $data): bool {
if (!isset($data['status'])) return false;
$stmt = $this->db->prepare("UPDATE contacts SET status = :status WHERE id = :id");
return $stmt->execute([':status' => $data['status'], ':id' => $id]);
}


public function delete(int $id): bool {
$stmt = $this->db->prepare("DELETE FROM contacts WHERE id = ?");
return $stmt->execute([$id]);
}
}