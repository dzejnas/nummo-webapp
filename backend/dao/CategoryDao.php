<?php
require_once __DIR__ . '/BaseDao.php';

class CategoryDao extends BaseDao {

    public function __construct() {
        parent::__construct('categories');
    }

    // ====================================================
    // 🔹 Get all categories (ordered by name)
    // ====================================================
    public function getAll(): array {
        $stmt = $this->db->query("SELECT id, name FROM {$this->table} ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ====================================================
    // 🔹 Create a new category
    // ====================================================
    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (name) VALUES (:name)");
        $stmt->execute([':name' => $data['name']]);
        return (int)$this->db->lastInsertId();
    }

    // ====================================================
    // 🔹 Update category name
    // ====================================================
    public function update(int $id, array $data): bool {
        if (empty($data['name'])) return false;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET name = :name WHERE id = :id");
        return $stmt->execute([':name' => $data['name'], ':id' => $id]);
    }
}
?>
