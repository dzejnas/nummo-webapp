<?php
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/Validation.php';

/**
 * UserService
 * ------------------------------------------
 * Handles business logic for User management.
 */
class UserService {
    private UserDao $dao;

    public function __construct() {
        $this->dao = new UserDao();
    }

    // --------------------------------------------------
    public function get_all(): array {
        return $this->dao->getAll();
    }

    // --------------------------------------------------
    public function get_by_id(int $id): array {
        $user = $this->dao->getById($id);
        if (!$user) {
            throw new Exception("User not found", 404);
        }
        return $user;
    }

    // --------------------------------------------------
    public function create(array $data): array {
        $name  = Validation::str_required($data, 'name', 2, 120);
        $email = Validation::email_required($data, 'email');

        $existing = $this->dao->getByEmail($email);
        if ($existing) {
            throw new Exception("Email already in use", 400);
        }

        $role = $data['role'] ?? 'USER';
        $id = $this->dao->create(['name' => $name, 'email' => $email, 'role' => $role]);
        return $this->dao->getById($id);
    }

    // --------------------------------------------------
    public function update(int $id, array $data): array {
        $user = $this->dao->getById($id);
        if (!$user) {
            throw new Exception("User not found", 404);
        }

        if (isset($data['email'])) {
            $existing = $this->dao->getByEmail($data['email']);
            if ($existing && $existing['id'] != $id) {
                throw new Exception("Another user with this email already exists", 400);
            }
        }

        $this->dao->update($id, $data);
        return $this->dao->getById($id);
    }

    // --------------------------------------------------
    public function delete(int $id): bool {
        $user = $this->dao->getById($id);
        if (!$user) {
            throw new Exception("User not found", 404);
        }
        return $this->dao->delete($id);
    }
}
?>
