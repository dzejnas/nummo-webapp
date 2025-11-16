<?php
require_once __DIR__ . '/../dao/TransactionDao.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';
require_once __DIR__ . '/../dao/MerchantDao.php';
require_once __DIR__ . '/Validation.php';

class TransactionService {
    private TransactionDao $dao;
    private UserDao $userDao;
    private CategoryDao $catDao;
    private MerchantDao $merchDao;

    public function __construct() {
        $this->dao = new TransactionDao();
        $this->userDao = new UserDao();
        $this->catDao = new CategoryDao();
        $this->merchDao = new MerchantDao();
    }

    public function get_all(): array {
        return $this->dao->getAll();
    }

    public function get_by_id(int $id): array {
        $txn = $this->dao->getById($id);
        if (!$txn) throw new Exception("Transaction not found", 404);
        return $txn;
    }

    public function create(array $data): array {
        $user_id = Validation::int_required($data, 'user_id', 1);
        $amount = Validation::num_required($data, 'amount', 0.0);
        $currency = strtoupper(Validation::str_required($data, 'currency', 3, 3));
        $category_id = Validation::int_required($data, 'category_id', 1);

        if (!$this->userDao->getById($user_id)) throw new Exception("user_id not found", 400);
        if (!$this->catDao->getById($category_id)) throw new Exception("category_id not found", 400);

        $merchant_id = $data['merchant_id'] ?? null;
        if ($merchant_id && !$this->merchDao->getById($merchant_id))
            throw new Exception("merchant_id not found", 400);

        $id = $this->dao->create($data);
        return $this->dao->getById($id);
    }

    public function update(int $id, array $data): array {
        $txn = $this->dao->getById($id);
        if (!$txn) throw new Exception("Transaction not found", 404);

        $this->dao->update($id, $data);
        return $this->dao->getById($id);
    }

    public function delete(int $id): bool {
        $txn = $this->dao->getById($id);
        if (!$txn) throw new Exception("Transaction not found", 404);
        return $this->dao->delete($id);
    }
}
?>
