<?php
require_once __DIR__ . '/BaseDao.php';

class UserDao extends BaseDao
{
    // Columns returned to the API. The password hash is never included.
    private const PUBLIC_COLUMNS = 'id, name, email, role, balance, currency, created_at';

    public function __construct()
    {
        parent::__construct('users', ['name', 'email', 'password', 'role', 'balance', 'currency']);
    }

    public function getAll(): array
    {
        return $this->query("SELECT " . self::PUBLIC_COLUMNS . " FROM users ORDER BY id");
    }

    public function getById(int $id): ?array
    {
        return $this->queryOne("SELECT " . self::PUBLIC_COLUMNS . " FROM users WHERE id = :id", ['id' => $id]);
    }

    // Includes the password hash — only used to check a login
    public function getByEmail(string $email): ?array
    {
        return $this->queryOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    }

    // Adds $amount to the balance (use a negative amount to subtract)
    public function changeBalance(int $id, float $amount): void
    {
        $stmt = $this->db->prepare("UPDATE users SET balance = balance + :amount WHERE id = :id");
        $stmt->execute(['amount' => $amount, 'id' => $id]);
    }
}
