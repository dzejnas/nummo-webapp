<?php
require_once __DIR__ . '/../config/Database.php';

class BaseDao {
    protected \PDO $db;
    protected string $table;

    public function __construct(string $table) {
        $this->db = Database::getConnection();
        $this->table = $table;
    }

    // ====================================================
    // 🔹 Generic: Fetch record by ID
    // ====================================================
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ====================================================
    // 🔹 Generic: Fetch all records (with optional limit & offset)
    // ====================================================
    public function getAll(int $limit = 1000, int $offset = 0): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ====================================================
    // 🔹 Generic: Delete by ID
    // ====================================================
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ====================================================
    // 🔹 Generic: Insert (dynamic columns)
    // ====================================================
    public function insert(array $data): int {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(',', $columns),
            implode(',', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        foreach ($data as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    // ====================================================
    // 🔹 Generic: Update (dynamic columns)
    // ====================================================
    public function update(int $id, array $data): bool {
        if (empty($data)) return false;
        $fields = [];
        foreach ($data as $key => $val) {
            $fields[] = "$key = :$key";
        }
        $sql = sprintf("UPDATE %s SET %s WHERE id = :id", $this->table, implode(', ', $fields));

        $stmt = $this->db->prepare($sql);
        foreach ($data as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ====================================================
    // 🔹 Helper: Count all records
    // ====================================================
    public function count(): int {
        $stmt = $this->db->query("SELECT COUNT(*) AS cnt FROM {$this->table}");
        return (int)$stmt->fetchColumn();
    }
}
?>
