<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../dao/UserDao.php';

error_log("✅ UserRoutes.php loaded successfully");

// ------------------------------------
// USERS API ROUTES
// ------------------------------------

// GET all users
Flight::route('GET /api/users', function() {
    error_log("🔥 /api/users route hit");
    $dao = new UserDao();
    $users = $dao->getAll();
    Flight::json($users);
});

// GET user by ID
Flight::route('GET /api/users/@id', function($id) {
    $dao = new UserDao();
    $user = $dao->getById((int)$id);
    if ($user) {
        Flight::json($user);
    } else {
        Flight::json(['error' => 'User not found'], 404);
    }
});

// POST new user
Flight::route('POST /api/users', function() {
    $data = Flight::request()->data->getData();

    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        Flight::json(['error' => 'Missing required fields'], 400);
        return;
    }

    $dao = new UserDao();
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    $id = $dao->create($data);
    Flight::json(['success' => true, 'id' => $id], 201);
});
