<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/UserDao.php';

class UserService extends BaseService
{
    private const ROLES = ['user', 'admin'];

    public function __construct()
    {
        parent::__construct(new UserDao(), 'User');
    }

    public function create(array $data): array
    {
        $user = [
            'name'     => Validator::string($data, 'name', 2, 100),
            'email'    => Validator::email($data),
            'password' => password_hash(Validator::string($data, 'password', 6, 72), PASSWORD_BCRYPT),
            'role'     => isset($data['role']) ? Validator::oneOf($data, 'role', self::ROLES) : 'user',
            'currency' => isset($data['currency']) ? Validator::currency($data) : 'EUR',
            'balance'  => isset($data['balance']) ? $this->balance($data) : 0,
        ];

        $this->assertEmailIsFree($user['email']);
        $id = $this->dao->insert($user);
        return $this->getById($id);
    }

    // Only the fields that are sent are changed
    public function update(int $id, array $data): array
    {
        if ($this->getById($id)['closed_at'] !== null) {
            throw new ApiException('This account is closed', 409);
        }
        $changes = [];

        if (isset($data['name'])) {
            $changes['name'] = Validator::string($data, 'name', 2, 100);
        }
        if (isset($data['email'])) {
            $changes['email'] = Validator::email($data);
            $this->assertEmailIsFree($changes['email'], $id);
        }
        if (isset($data['password'])) {
            $changes['password'] = password_hash(Validator::string($data, 'password', 6, 72), PASSWORD_BCRYPT);
        }
        if (isset($data['role'])) {
            $changes['role'] = Validator::oneOf($data, 'role', self::ROLES);
        }
        if (isset($data['currency'])) {
            $changes['currency'] = Validator::currency($data);
        }
        if (isset($data['balance'])) {
            $changes['balance'] = $this->balance($data);
        }

        $this->dao->update($id, $changes);
        return $this->getById($id);
    }

    // Admins close accounts instead of deleting them, so completed payments keep both sides.
    // A closed user cannot log in (existing tokens stop working), send, receive or be added as a
    // contact. Their contacts are removed, their pending payments become "failed" and their pending
    // requests "declined". Completed payments, paid requests and the frozen balance stay as records.
    public function close(int $id, array $admin): void
    {
        if ($id === $admin['id']) {
            throw new ApiException('You cannot close your own account', 409);
        }
        $this->dao->beginTransaction();
        try {
            // Locking the user row waits for any payment that has already locked it to finish
            $account = $this->dao->lockForUpdate([$id])[0] ?? null;
            if ($account === null) {
                throw new ApiException('User not found', 404);
            }
            if ($account['closed_at'] === null) {
                $this->dao->close($id);
            }
            $this->dao->commit();
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
    }

    // A user editing their own profile cannot change their role or balance
    public function updateProfile(int $id, array $data): array
    {
        unset($data['role'], $data['balance']);
        return $this->update($id, $data);
    }

    private function assertEmailIsFree(string $email, ?int $exceptUserId = null): void
    {
        $existing = $this->dao->getByEmail($email);
        if ($existing !== null && $existing['id'] !== $exceptUserId) {
            throw new ApiException('Email is already in use', 409);
        }
    }

    private function balance(array $data): string
    {
        return Validator::amount($data, 'balance', true);
    }
}
