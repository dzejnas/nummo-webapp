<?php
require_once __DIR__ . '/../services/UserService.php';

Flight::map('user_service', fn() => new UserService());

// GET all users
Flight::route('GET /api/users', fn() =>
    Flight::json(['data' => Flight::user_service()->get_all(), 'error' => null])
);

// GET user by ID
Flight::route('GET /api/users/@id', fn($id) =>
    Flight::json(['data' => Flight::user_service()->get_by_id((int)$id), 'error' => null])
);

// POST create user
Flight::route('POST /api/users', function() {
    try {
        $data = Flight::request()->data->getData();
        $created = Flight::user_service()->create($data);
        Flight::json(['data' => $created, 'error' => null], 201);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

// PUT update user
Flight::route('PUT /api/users/@id', function($id) {
    try {
        $data = Flight::request()->data->getData();
        $updated = Flight::user_service()->update((int)$id, $data);
        Flight::json(['data' => $updated, 'error' => null]);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

// DELETE user
Flight::route('DELETE /api/users/@id', fn($id) =>
    Flight::json(['success' => Flight::user_service()->delete((int)$id), 'error' => null])
);
