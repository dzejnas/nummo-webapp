<?php
require_once __DIR__ . '/../config/Database.php';


class UserDao {
private \PDO $db;


public function __construct() { $this->db = Database::getConnection(); }


public function getAll(): array {
$stmt = $this->db->query("SELECT id, name, email, role, created_at FROM users ORDER BY id ASC");
return $stmt->fetchAll();
}


public function getById(int $id): ?array {
$stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
return $row ?: null;
}


public function create(array $data): int {
$sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
$stmt = $this->db->prepare($sql);
$stmt->execute([
':name' => $data['name'],
':email' => $data['email'],
':password' => $data['password'], // hashed string expected
':role' => $data['role'] ?? 'user'
]);
return (int)$this->db->lastInsertId();
}


public function update(int $id, array $data): bool {
$fields = [];
$params = [':id' => $id];
foreach (['name','email','password','role'] as $col) {
if (isset($data[$col])) { $fields[] = "$col = :$col"; $params[":$col"] = $data[$col]; }
}
if (!$fields) return false;
$sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
$stmt = $this->db->prepare($sql);
return $stmt->execute($params);
}


public function delete(int $id): bool {
$stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
return $stmt->execute([$id]);
}
}