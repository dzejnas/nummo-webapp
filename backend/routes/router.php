<?php
/**
 * Nummo API Router
 * ----------------------------------------------------------
 * Uses FlightPHP to define all API routes for the application.
 * Each route will map to its Service or DAO logic.
 */

require_once __DIR__ . '/../vendor/autoload.php';  // if you later use Composer
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/TransactionDao.php';
require_once __DIR__ . '/../dao/ContactDao.php';
require_once __DIR__ . '/../dao/MerchantDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';

// You can load JWT middleware here later for protected routes
// require_once __DIR__ . '/../middleware/AuthMiddleware.php';

Flight::set('flight.log_errors', true);

// ----------------------------------------------------------
// BASIC HEALTH CHECK / ROOT ENDPOINT
// ----------------------------------------------------------
Flight::route('GET /', function() {
    Flight::json([
        'status' => 'ok',
        'message' => 'Nummo API is running 🚀'
    ]);
});

// ----------------------------------------------------------
// USERS ROUTES
// ----------------------------------------------------------
Flight::route('GET /users', function() {
    $dao = new UserDao();
    Flight::json($dao->getAll());
});

Flight::route('GET /users/@id', function($id) {
    $dao = new UserDao();
    $user = $dao->getById($id);
    if ($user) {
        Flight::json($user);
    } else {
        Flight::json(['error' => 'User not found'], 404);
    }
});

// ----------------------------------------------------------
// TRANSACTIONS ROUTES (example)
// ----------------------------------------------------------
Flight::route('GET /transactions/@user_id', function($user_id) {
    $dao = new TransactionDao();
    $transactions = $dao->getAllForUser($user_id);
    Flight::json($transactions);
});

// ----------------------------------------------------------
// FUTURE AUTH ROUTES
// ----------------------------------------------------------
// Flight::route('POST /auth/login', 'AuthService::login');
// Flight::route('POST /auth/register', 'AuthService::register');


// ----------------------------------------------------------
// RUN FLIGHTPHP
// ----------------------------------------------------------
Flight::start();
