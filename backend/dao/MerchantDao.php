<?php
require_once __DIR__ . '/../config/Database.php';


class MerchantDao {
private \PDO $db;
public function __construct() { $this->db = Database::getConnection(); }


public function getAll(): array {
$stmt = $this->db->query("SELECT id, name, category, contact_email, created_at FROM merchants ORDER BY name ASC");
return $stmt->fetchAll();
}


public function getById(int $id): ?array {
$stmt = $this->db->prepare("SELECT id, name, category, contact_email, created_at FROM merchants WHERE id = ?");
$stmt->execute([$id]);
return $stmt->fetch() ?: null;
}


public function create(array $data): int {
$sql = "INSERT INTO merchants (name, category, contact_email) VALUES (:name, :category, :contact_email)";
$stmt = $this->db->prepare($sql);
$stmt->execute([
':name' => $data['name'],
':category' => $data['category'] ?? null,
':contact_email' => $data['contact_email'] ?? null,
]);
return (int)$this->db->lastInsertId();
}


public function update(int $id, array $data): bool {
$fields = [];
$params = [':id' => $id];
foreach (['name','category','contact_email'] as $col) {
if (array_key_exists($col, $data)) { $fields[] = "$col = :$col"; $params[":$col"] = $data[$col]; }
}
if (!$fields) return false;
$sql = "UPDATE merchants SET " . implode(', ', $fields) . " WHERE id = :id";
$stmt = $this->db->prepare($sql);
return $stmt->execute($params);
}


public function delete(int $id): bool {
$stmt = $this->db->prepare("DELETE FROM merchants WHERE id = ?");
return $stmt->execute([$id]);
}
}