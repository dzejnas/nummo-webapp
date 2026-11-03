<?php
require_once __DIR__ . '/../utils/ApiException.php';

// Reusable role check. Must run after AuthMiddleware.
// Example: ->addMiddleware([new AuthMiddleware(), new RoleMiddleware('admin')])
class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(string ...$allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function before(array $params): void
    {
        $user = Flight::get('user');
        if ($user === null || !in_array($user['role'], $this->allowedRoles, true)) {
            throw new ApiException('You do not have permission to do this', 403);
        }
    }
}
