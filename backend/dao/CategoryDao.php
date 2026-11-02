<?php
require_once __DIR__ . '/BaseDao.php';

class CategoryDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('categories', ['name']);
    }

    public function getAll(): array
    {
        return $this->query("SELECT * FROM categories ORDER BY name");
    }

    public function getByName(string $name): ?array
    {
        return $this->queryOne("SELECT * FROM categories WHERE name = :name", ['name' => $name]);
    }
}
