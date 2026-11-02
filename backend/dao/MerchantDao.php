<?php
require_once __DIR__ . '/BaseDao.php';

class MerchantDao extends BaseDao
{
    private const SELECT = "SELECT m.*, c.name AS category_name
                            FROM merchants m
                            LEFT JOIN categories c ON c.id = m.category_id";

    public function __construct()
    {
        parent::__construct('merchants', ['name', 'category_id', 'contact_email']);
    }

    public function getAll(): array
    {
        return $this->query(self::SELECT . " ORDER BY m.name");
    }

    public function getById(int $id): ?array
    {
        return $this->queryOne(self::SELECT . " WHERE m.id = :id", ['id' => $id]);
    }

    public function getByName(string $name): ?array
    {
        return $this->queryOne("SELECT * FROM merchants WHERE name = :name", ['name' => $name]);
    }
}
