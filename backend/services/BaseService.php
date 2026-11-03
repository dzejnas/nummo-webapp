<?php
require_once __DIR__ . '/../utils/ApiException.php';
require_once __DIR__ . '/../utils/Validator.php';

// Read and delete work the same for every entity, so they live here.
// Each child service adds create() and update() with its own validation rules.
abstract class BaseService
{
    protected BaseDao $dao;
    protected string $entityName; // used in error messages, e.g. "Merchant not found"

    public function __construct(BaseDao $dao, string $entityName)
    {
        $this->dao        = $dao;
        $this->entityName = $entityName;
    }

    public function getAll(): array
    {
        return $this->dao->getAll();
    }

    public function getById(int $id): array
    {
        $row = $this->dao->getById($id);
        if ($row === null) {
            throw new ApiException("{$this->entityName} not found", 404);
        }
        return $row;
    }

    public function delete(int $id): void
    {
        $this->getById($id); // 404 if it does not exist
        $this->dao->delete($id);
    }

    // ---- access checks for data that belongs to a user ----

    protected function isAdmin(array $user): bool
    {
        return $user['role'] === 'admin';
    }

    // Admins may access everything; other users only what $isOwner says
    protected function assertCanAccess(array $user, bool $isOwner): void
    {
        if (!$this->isAdmin($user) && !$isOwner) {
            throw new ApiException('You do not have access to this ' . strtolower($this->entityName), 403);
        }
    }
}
