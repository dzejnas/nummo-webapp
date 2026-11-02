<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/ContactDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

// $user is the logged-in user (from the JWT). Users only see and change their own contacts.
class ContactService extends BaseService
{
    private const STATUSES = ['pending', 'accepted', 'blocked'];

    public function __construct()
    {
        parent::__construct(new ContactDao(), 'Contact');
    }

    public function getAllFor(array $user): array
    {
        return $this->isAdmin($user) ? $this->dao->getAll() : $this->dao->getForUser($user['id']);
    }

    public function getByIdFor(int $id, array $user): array
    {
        $contact = $this->getById($id);
        $this->assertCanAccess($user, $contact['user_id'] === $user['id']);
        return $contact;
    }

    // The friend can be given by id (friend_user_id) or by email
    public function create(array $data, array $user): array
    {
        $userId = $this->isAdmin($user) && isset($data['user_id']) ? Validator::id($data, 'user_id') : $user['id'];
        $users  = new UserDao();

        if (!empty($data['email'])) {
            $friend = $users->getByEmail(Validator::email($data));
            if ($friend === null) {
                throw new ApiException('No Nummo user has this email', 404);
            }
            $friendUserId = $friend['id'];
        } else {
            $friendUserId = Validator::id($data, 'friend_user_id');
        }

        if ($userId === $friendUserId) {
            throw new ApiException('You cannot add yourself as a contact');
        }
        if ($users->getById($userId) === null || $users->getById($friendUserId) === null) {
            throw new ApiException('User does not exist');
        }
        if ($this->dao->getByUsers($userId, $friendUserId) !== null) {
            throw new ApiException('This contact already exists', 409);
        }

        $id = $this->dao->insert([
            'user_id'        => $userId,
            'friend_user_id' => $friendUserId,
            'status'         => isset($data['status']) ? Validator::oneOf($data, 'status', self::STATUSES) : 'accepted',
        ]);
        return $this->getById($id);
    }

    // Only the status of a contact can change (accept / block)
    public function update(int $id, array $data, array $user): array
    {
        $this->getByIdFor($id, $user);
        $status = Validator::oneOf($data, 'status', self::STATUSES);

        $this->dao->update($id, ['status' => $status]);
        return $this->getById($id);
    }

    public function deleteFor(int $id, array $user): void
    {
        $this->getByIdFor($id, $user);
        $this->dao->delete($id);
    }
}
