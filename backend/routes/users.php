<?php
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/TransactionService.php';

Flight::register('userService', 'UserService');

Flight::route('GET /api/users', function () {
    Flight::json(Flight::userService()->getAll());
});

Flight::route('GET /api/users/@id:[0-9]+', function ($id) {
    Flight::json(Flight::userService()->getById((int) $id));
});

// Spending per category for the dashboard chart
Flight::route('GET /api/users/@id:[0-9]+/spending', function ($id) {
    Flight::json((new TransactionService())->getSpendingByCategory((int) $id));
});

Flight::route('POST /api/users', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::userService()->create($data), 201);
});

Flight::route('PUT /api/users/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::userService()->update((int) $id, $data));
});

Flight::route('DELETE /api/users/@id:[0-9]+', function ($id) {
    Flight::userService()->delete((int) $id);
    Flight::json(['message' => 'User deleted']);
});
