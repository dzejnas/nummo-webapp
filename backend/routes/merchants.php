<?php
require_once __DIR__ . '/../services/MerchantService.php';
require_once __DIR__ . '/../middleware/Guards.php';

// Every logged-in user can read; only admins can create, change or delete.
Flight::register('merchantService', 'MerchantService');

Flight::route('GET /api/merchants', function () {
    Flight::json(Flight::merchantService()->getAll());
})->addMiddleware(loggedIn());

Flight::route('GET /api/merchants/@id:[0-9]+', function ($id) {
    Flight::json(Flight::merchantService()->getById((int) $id));
})->addMiddleware(loggedIn());

Flight::route('POST /api/merchants', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::merchantService()->create($data), 201);
})->addMiddleware(adminOnly());

Flight::route('PUT /api/merchants/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::merchantService()->update((int) $id, $data));
})->addMiddleware(adminOnly());

Flight::route('DELETE /api/merchants/@id:[0-9]+', function ($id) {
    Flight::merchantService()->delete((int) $id);
    Flight::json(['message' => 'Merchant deleted']);
})->addMiddleware(adminOnly());
