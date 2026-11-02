<?php
require_once __DIR__ . '/BaseDao.php';

class ContactDao extends BaseDao
{
    // Contact row + the friend's name and email
    private const SELECT = "SELECT c.*, u.name AS friend_name, u.email AS friend_email
                            FROM contacts c
                            JOIN users u ON u.id = c.friend_user_id";

    public function __construct()
    {
        parent::__construct('contacts', ['user_id', 'friend_user_id', 'status']);
    }

    public function getAll(): array
    {
        return $this->query(self::SELECT . " ORDER BY c.id");
    }

    public function getById(int $id): ?array
    {
        return $this->queryOne(self::SELECT . " WHERE c.id = :id", ['id' => $id]);
    }

    public function getForUser(int $userId): array
    {
        return $this->query(self::SELECT . " WHERE c.user_id = :user_id ORDER BY u.name", ['user_id' => $userId]);
    }

    public function getByUsers(int $userId, int $friendUserId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM contacts WHERE user_id = :user_id AND friend_user_id = :friend_user_id",
            ['user_id' => $userId, 'friend_user_id' => $friendUserId]
        );
    }
}
