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
        $this->getById($id);
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

    private function assertEmailIsFree(string $email, ?int $exceptUserId = null): void
    {
        $existing = $this->dao->getByEmail($email);
        if ($existing !== null && $existing['id'] !== $exceptUserId) {
            throw new ApiException('Email is already in use', 409);
        }
    }

    private function balance(array $data): float
    {
        if (!is_numeric($data['balance']) || $data['balance'] < 0) {
            throw new ApiException('balance must be 0 or more');
        }
        return round((float) $data['balance'], 2);
    }
}
