<?php
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../utils/ApiException.php';

// Protects a route: the request must carry a valid JWT in the header
//   Authorization: Bearer <token>
// Missing, invalid or expired token -> 401. On success the logged-in user is stored with
// Flight::set('user', ...) so routes and the request logger can read it with Flight::get('user').
class AuthMiddleware
{
    public function before(array $params): void
    {
        $header = Flight::request()->getHeader('Authorization');
        if (!preg_match('/^Bearer\s+(\S+)$/', $header, $matches)) {
            throw new ApiException('You need to log in first', 401);
        }

        $user = (new AuthService())->userFromToken($matches[1]);
        Flight::set('user', $user);
    }
}
