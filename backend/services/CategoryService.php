<?php
require_once __DIR__ . '/../dao/CategoryDao.php';
require_once __DIR__ . '/Validation.php';

class CategoryService {
    private CategoryDao $dao;

    public function __construct() {
        $this->dao = new CategoryDao();
    }

    // --------------------------------------------------
    // 🔹 GET ALL CATEGORIES (route calls get_all())
    // --------------------------------------------------
    public function get_all(): array {
        return $this->dao->getAll(); 
    }

    // (Optional but recommended)
    // Keep your original camelCase version too:
    public function getAll(): array {
        return $this->dao->getAll();
    }

    // --------------------------------------------------
    // 🔹 Get category by ID
    // --------------------------------------------------
    public function get_by_id(int $id): array {
        $category = $this->dao->getById($id);
        if (!$category) {
            throw new Exception("Category not found", 404);
        }
        return $category;
    }

    // --------------------------------------------------
    // 🔹 Create new category
    // --------------------------------------------------
    public function create(array $data): array {
        if (empty($data['name'])) {
            throw new Exception("Category name is required", 400);
        }

        // Check for duplicate by name
        if (method_exists($this->dao, 'getByName')) {
            $existing = $this->dao->getByName($data['name']);
            if ($existing) {
                throw new Exception("Category with this name already exists", 400);
            }
        }

        $id = $this->dao->create($data);
        return $this->dao->getById($id);
    }

    // --------------------------------------------------
    // 🔹 Update category
    // --------------------------------------------------
    public function update(int $id, array $data): array {
        $category = $this->dao->getById($id);
        if (!$category) {
            throw new Exception("Category not found", 404);
        }

        if (!empty($data['name']) && method_exists($this->dao, 'getByName')) {
            $existing = $this->dao->getByName($data['name']);
            if ($existing && $existing['id'] != $id) {
                throw new Exception("Another category with this name exists", 400);
            }
        }

        $this->dao->update($id, $data);
        return $this->dao->getById($id);
    }

    // --------------------------------------------------
    // 🔹 Delete category
    // --------------------------------------------------
    public function delete(int $id): bool {
        $category = $this->dao->getById($id);
        if (!$category) {
            throw new Exception("Category not found", 404);
        }
        return $this->dao->delete($id);
    }
}
?>
