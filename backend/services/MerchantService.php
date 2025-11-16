<?php
require_once __DIR__ . '/../dao/MerchantDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';
require_once __DIR__ . '/Validation.php';

class MerchantService {
    private MerchantDao $dao;
    private CategoryDao $catDao;

    public function __construct() {
        $this->dao = new MerchantDao();
        $this->catDao = new CategoryDao();
    }

    public function get_all(): array {
        return $this->dao->getAll();
    }

    public function get_by_id(int $id): array {
        $merchant = $this->dao->getById($id);
        if (!$merchant) throw new Exception("Merchant not found", 404);
        return $merchant;
    }

    public function create(array $data): array {
        $name = Validation::str_required($data, 'name', 2, 120);
        if ($this->dao->getByName($name)) throw new Exception("Merchant with this name exists", 400);

        if (!empty($data['category_id']) && !$this->catDao->getById((int)$data['category_id']))
            throw new Exception("Invalid category_id", 400);

        $id = $this->dao->create($data);
        return $this->dao->getById($id);
    }

    public function update(int $id, array $data): array {
        $merchant = $this->dao->getById($id);
        if (!$merchant) throw new Exception("Merchant not found", 404);

        $this->dao->update($id, $data);
        return $this->dao->getById($id);
    }

    public function delete(int $id): bool {
        $merchant = $this->dao->getById($id);
        if (!$merchant) throw new Exception("Merchant not found", 404);
        return $this->dao->delete($id);
    }
}
?>
