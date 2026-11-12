<?php
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/TransactionService.php';
require_once __DIR__ . '/../middleware/Guards.php';

// User management — admins only. Users edit themselves through /api/me (routes/auth.php).
Flight::register('userService', 'UserService');

Flight::route('GET /api/users', function () {
    Flight::json(Flight::userService()->getAll());
})->addMiddleware(adminOnly());

Flight::route('GET /api/users/@id:[0-9]+', function ($id) {
    Flight::json(Flight::userService()->getById((int) $id));
})->addMiddleware(adminOnly());

Flight::route('GET /api/users/@id:[0-9]+/spending', function ($id) {
    Flight::json((new TransactionService())->getSpendingByCategory((int) $id));
})->addMiddleware(adminOnly());

Flight::route('POST /api/users', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::userService()->create($data), 201);
})->addMiddleware(adminOnly());

Flight::route('PUT /api/users/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::userService()->update((int) $id, $data));
})->addMiddleware(adminOnly());

// Closes the account (see UserService::close) — payment history is kept
Flight::route('DELETE /api/users/@id:[0-9]+', function ($id) {
    Flight::userService()->close((int) $id, Flight::get('user'));
    Flight::json(['message' => 'User account closed']);
})->addMiddleware(adminOnly());
