<?php
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/TransactionService.php';
require_once __DIR__ . '/../middleware/Guards.php';

// Public: register and log in. Both return {"token": "...", "user": {...}}
Flight::route('POST /api/auth/register', function () {
    Flight::json((new AuthService())->register(Flight::request()->data->getData()), 201);
});

Flight::route('POST /api/auth/login', function () {
    Flight::json((new AuthService())->login(Flight::request()->data->getData()));
});

// The logged-in user's own profile
Flight::route('GET /api/me', function () {
    Flight::json((new UserService())->getById(Flight::get('user')['id']));
})->addMiddleware(loggedIn());

Flight::route('PUT /api/me', function () {
    $data = Flight::request()->data->getData();
    Flight::json((new UserService())->updateProfile(Flight::get('user')['id'], $data));
})->addMiddleware(loggedIn());

// Spending per category for the dashboard chart
Flight::route('GET /api/me/spending', function () {
    Flight::json((new TransactionService())->getSpendingByCategory(Flight::get('user')['id']));
})->addMiddleware(loggedIn());
