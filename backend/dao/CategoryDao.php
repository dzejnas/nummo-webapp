<?php
require_once __DIR__ . '/BaseDao.php';

class CategoryDao extends BaseDao {

    public function __construct() {
        parent::__construct('categories');
    }

    // Fixed signature to match BaseDao
    public function getAll(int $limit = 1000, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT id, name
            FROM {$this->table}
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (name) VALUES (:name)");
        $stmt->execute([':name' => $data['name']]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        if (empty($data['name'])) return false;

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET name = :name
            WHERE id = :id
        ");

        return $stmt->execute([
            ':name' => $data['name'],
            ':id'   => $id
        ]);
    }
}
?>
