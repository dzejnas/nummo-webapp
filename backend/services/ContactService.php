<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/ContactDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

class ContactService extends BaseService
{
    private const STATUSES = ['pending', 'accepted', 'blocked'];

    public function __construct()
    {
        parent::__construct(new ContactDao(), 'Contact');
    }

    public function getForUser(int $userId): array
    {
        return $this->dao->getForUser($userId);
    }

    public function create(array $data): array
    {
        $userId       = Validator::id($data, 'user_id');
        $friendUserId = Validator::id($data, 'friend_user_id');
        $status       = isset($data['status']) ? Validator::oneOf($data, 'status', self::STATUSES) : 'pending';

        if ($userId === $friendUserId) {
            throw new ApiException('You cannot add yourself as a contact');
        }

        $users = new UserDao();
        if ($users->getById($userId) === null || $users->getById($friendUserId) === null) {
            throw new ApiException('User does not exist');
        }
        if ($this->dao->getByUsers($userId, $friendUserId) !== null) {
            throw new ApiException('This contact already exists', 409);
        }

        $id = $this->dao->insert([
            'user_id'        => $userId,
            'friend_user_id' => $friendUserId,
            'status'         => $status,
        ]);
        return $this->getById($id);
    }

    // Only the status of a contact can change (accept / block)
    public function update(int $id, array $data): array
    {
        $this->getById($id);
        $status = Validator::oneOf($data, 'status', self::STATUSES);

        $this->dao->update($id, ['status' => $status]);
        return $this->getById($id);
    }
}
