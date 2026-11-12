<?php
require_once __DIR__ . '/BaseDao.php';

class UserDao extends BaseDao
{
    // Columns returned to the API. The password hash is never included.
    private const PUBLIC_COLUMNS = 'id, name, email, role, balance, currency, created_at, closed_at';

    public function __construct()
    {
        parent::__construct('users', ['name', 'email', 'password', 'role', 'balance', 'currency', 'closed_at']);
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

    // Locks the users' rows until the DB transaction ends. Always locked in id order, so two
    // payments between the same people (A->B and B->A) cannot deadlock each other.
    public function lockForUpdate(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        sort($ids);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id, balance, closed_at FROM users WHERE id IN ($placeholders) ORDER BY id FOR UPDATE");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    // Takes money only if the balance covers it, in one atomic statement.
    // Returns false (nothing changed) when the balance is too low. $amount is a decimal string.
    public function debit(int $id, string $amount): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET balance = balance - :amount
                                    WHERE id = :id AND balance >= :min_balance");
        $stmt->execute(['amount' => $amount, 'id' => $id, 'min_balance' => $amount]);
        return $stmt->rowCount() === 1;
    }

    public function credit(int $id, string $amount): void
    {
        $stmt = $this->db->prepare("UPDATE users SET balance = balance + :amount WHERE id = :id");
        $stmt->execute(['amount' => $amount, 'id' => $id]);
    }

    // Closing keeps the user's payment history: contacts are removed, open items are cancelled
    public function close(int $id): void
    {
        $this->db->prepare("UPDATE users SET closed_at = NOW() WHERE id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM contacts WHERE user_id = ? OR friend_user_id = ?")->execute([$id, $id]);
        $this->db->prepare("UPDATE payment_requests SET status = 'declined'
                            WHERE status = 'pending' AND (requester_id = ? OR payer_id = ?)")->execute([$id, $id]);
        $this->db->prepare("UPDATE transactions SET status = 'failed'
                            WHERE status = 'pending' AND (sender_id = ? OR receiver_id = ?)")->execute([$id, $id]);
    }
}
