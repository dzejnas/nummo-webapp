<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/CategoryDao.php';

class CategoryService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new CategoryDao(), 'Category');
    }

    public function create(array $data): array
    {
        $name = Validator::string($data, 'name', 2, 60);
        $this->assertNameIsFree($name);

        $id = $this->dao->insert(['name' => $name]);
        return $this->getById($id);
    }

    public function update(int $id, array $data): array
    {
        $this->getById($id);
        $name = Validator::string($data, 'name', 2, 60);
        $this->assertNameIsFree($name, $id);

        $this->dao->update($id, ['name' => $name]);
        return $this->getById($id);
    }

    private function assertNameIsFree(string $name, ?int $exceptId = null): void
    {
        $existing = $this->dao->getByName($name);
        if ($existing !== null && $existing['id'] !== $exceptId) {
            throw new ApiException('A category with this name already exists', 409);
        }
    }
}
