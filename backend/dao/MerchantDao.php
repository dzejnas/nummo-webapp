<?php
require_once __DIR__ . '/BaseDao.php';

class MerchantDao extends BaseDao {
    public function __construct() {
        parent::__construct('merchants');
    }

    // ====================================================
    // 🔹 Get all merchants
    // ====================================================
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT id, name, category, contact_email, created_at 
            FROM {$this->table}
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ====================================================
    // 🔹 Create new merchant
    // ====================================================
    public function create(array $data): int {
        $sql = "INSERT INTO {$this->table} (name, category, contact_email)
                VALUES (:name, :category, :contact_email)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name'          => $data['name'],
            ':category'      => $data['category'] ?? null,
            ':contact_email' => $data['contact_email'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ====================================================
    // 🔹 Update merchant
    // ====================================================
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'category', 'contact_email'] as $col) {
            if (isset($data[$col])) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (!$fields) return false;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ====================================================
    // 🔹 Optional: Search merchants by name or category
    // ====================================================
    public function search(string $query): array {
        $stmt = $this->db->prepare("
            SELECT id, name, category, contact_email, created_at
            FROM {$this->table}
            WHERE name LIKE :q OR category LIKE :q
            ORDER BY name ASC
        ");
        $stmt->execute([':q' => "%$query%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
