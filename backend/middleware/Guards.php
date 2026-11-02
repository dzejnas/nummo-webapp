<?php
require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/RoleMiddleware.php';

// Short names for the middleware lists used in the route files:
//   Flight::route(...)->addMiddleware(loggedIn());   any logged-in user
//   Flight::route(...)->addMiddleware(adminOnly());  logged-in admin

function loggedIn(): array
{
    return [new AuthMiddleware()];
}

function adminOnly(): array
{
    return [new AuthMiddleware(), new RoleMiddleware('admin')];
}
