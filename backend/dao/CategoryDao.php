<?php
require_once __DIR__ . '/../config/Database.php';


class CategoryDao {
private \PDO $db;
public function __construct() { $this->db = Database::getConnection(); }


public function getAll(): array {
$stmt = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC");
return $stmt->fetchAll();
}


public function getById(int $id): ?array {
$stmt = $this->db->prepare("SELECT id, name FROM categories WHERE id = ?");
$stmt->execute([$id]);
return $stmt->fetch() ?: null;
}


public function create(array $data): int {
$stmt = $this->db->prepare("INSERT INTO categories (name) VALUES (:name)");
$stmt->execute([':name' => $data['name']]);
return (int)$this->db->lastInsertId();
}


public function update(int $id, array $data): bool {
if (!isset($data['name'])) return false;
$stmt = $this->db->prepare("UPDATE categories SET name = :name WHERE id = :id");
return $stmt->execute([':name' => $data['name'], ':id' => $id]);
}


public function delete(int $id): bool {
$stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
return $stmt->execute([$id]);
}
}