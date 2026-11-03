<?php
require_once __DIR__ . '/../config/Database.php';

// Reusable CRUD methods shared by every DAO.
// Each child DAO passes its table name and the list of columns that may be inserted/updated.
// Only those columns are ever put into SQL, so user input can never choose a column name.
abstract class BaseDao
{
    protected PDO $db;
    protected string $table;
    protected array $columns;

    public function __construct(string $table, array $columns)
    {
        $this->db      = Database::getConnection();
        $this->table   = $table;
        $this->columns = $columns;
    }

    public function getAll(): array
    {
        return $this->query("SELECT * FROM {$this->table} ORDER BY id");
    }

    public function getById(int $id): ?array
    {
        return $this->queryOne("SELECT * FROM {$this->table} WHERE id = :id", ['id' => $id]);
    }

    // Returns the id of the new row
    public function insert(array $data): int
    {
        $data = $this->onlyAllowedColumns($data);
        $columns      = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $stmt = $this->db->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    // Updates only the columns present in $data
    public function update(int $id, array $data): void
    {
        $data = $this->onlyAllowedColumns($data);
        if (empty($data)) {
            return;
        }

        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "$column = :$column";
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE id = :id");
        $stmt->execute($data + ['id' => $id]);
    }

    // Returns true if a row was deleted
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // Database transactions: used when several queries must succeed or fail together
    public function beginTransaction(): void { $this->db->beginTransaction(); }
    public function commit(): void           { $this->db->commit(); }
    public function rollBack(): void         { $this->db->rollBack(); }

    // ---- helpers for child DAOs ----

    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    private function onlyAllowedColumns(array $data): array
    {
        return array_intersect_key($data, array_flip($this->columns));
    }
}
