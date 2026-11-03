<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/MerchantDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';

class MerchantService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new MerchantDao(), 'Merchant');
    }

    public function create(array $data): array
    {
        $merchant = $this->validate($data);
        $this->assertNameIsFree($merchant['name']);

        $id = $this->dao->insert($merchant);
        return $this->getById($id);
    }

    // PUT replaces the merchant, so all fields are validated again
    public function update(int $id, array $data): array
    {
        $this->getById($id);
        $merchant = $this->validate($data);
        $this->assertNameIsFree($merchant['name'], $id);

        $this->dao->update($id, $merchant);
        return $this->getById($id);
    }

    private function validate(array $data): array
    {
        $merchant = [
            'name'          => Validator::string($data, 'name', 2, 120),
            'category_id'   => null,
            'contact_email' => null,
        ];

        if (!empty($data['category_id'])) {
            $merchant['category_id'] = Validator::id($data, 'category_id');
            if ((new CategoryDao())->getById($merchant['category_id']) === null) {
                throw new ApiException('Category does not exist');
            }
        }
        if (!empty($data['contact_email'])) {
            $merchant['contact_email'] = Validator::email($data, 'contact_email');
        }
        return $merchant;
    }

    private function assertNameIsFree(string $name, ?int $exceptId = null): void
    {
        $existing = $this->dao->getByName($name);
        if ($existing !== null && $existing['id'] !== $exceptId) {
            throw new ApiException('A merchant with this name already exists', 409);
        }
    }
}
