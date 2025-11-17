<?php
require_once __DIR__ . '/../dao/ContactDao.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/Validation.php';

class ContactService {
    private ContactDao $dao;
    private UserDao $userDao;

    public function __construct() {
        $this->dao = new ContactDao();
        $this->userDao = new UserDao();
    }

    public function get_all(): array {
        return $this->dao->getAll();
    }

    public function get_by_id(int $id): array {
        $contact = $this->dao->getById($id);
        if (!$contact) throw new Exception("Contact not found", 404);
        return $contact;
    }

    public function create(array $data): array {
        $user_id = Validation::int_required($data, 'user_id', 1);
        $contact_id = Validation::int_required($data, 'contact_id', 1);
        if ($user_id === $contact_id) throw new Exception("user_id and contact_id cannot be the same", 400);

        if (!$this->userDao->getById($user_id) || !$this->userDao->getById($contact_id))
            throw new Exception("User or contact not found", 400);

        $alias = Validation::str_optional($data, 'alias', 1, 60);

        $id = $this->dao->create(['user_id' => $user_id, 'contact_id' => $contact_id, 'alias' => $alias]);
        return $this->dao->getById($id);
    }

    public function update(int $id, array $data): array {
        $contact = $this->dao->getById($id);
        if (!$contact) throw new Exception("Contact not found", 404);

        $this->dao->update($id, $data);
        return $this->dao->getById($id);
    }

    public function delete(int $id): bool {
        $contact = $this->dao->getById($id);
        if (!$contact) throw new Exception("Contact not found", 404);
        return $this->dao->delete($id);
    }
}
?>
