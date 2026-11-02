<?php
require_once __DIR__ . '/../services/CategoryService.php';
require_once __DIR__ . '/../middleware/Guards.php';

// Every logged-in user can read; only admins can create, change or delete.
Flight::register('categoryService', 'CategoryService');

Flight::route('GET /api/categories', function () {
    Flight::json(Flight::categoryService()->getAll());
})->addMiddleware(loggedIn());

Flight::route('GET /api/categories/@id:[0-9]+', function ($id) {
    Flight::json(Flight::categoryService()->getById((int) $id));
})->addMiddleware(loggedIn());

Flight::route('POST /api/categories', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::categoryService()->create($data), 201);
})->addMiddleware(adminOnly());

Flight::route('PUT /api/categories/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::categoryService()->update((int) $id, $data));
})->addMiddleware(adminOnly());

Flight::route('DELETE /api/categories/@id:[0-9]+', function ($id) {
    Flight::categoryService()->delete((int) $id);
    Flight::json(['message' => 'Category deleted']);
})->addMiddleware(adminOnly());
