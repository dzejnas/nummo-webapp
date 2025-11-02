<?php
// =========================================================
// Nummo Backend — Main Entry Point
// Handles API routing via FlightPHP
// =========================================================

// ✅ Enable full error visibility for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Database.php';

// =========================================================
// 🌐 CORS CONFIGURATION
// =========================================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight (CORS OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =========================================================
// 🧠 Load all route definitions
// =========================================================
require_once __DIR__ . '/../routes/UserRoutes.php';
require_once __DIR__ . '/../routes/TransactionRoutes.php';
// (Optional for later Milestones)
if (file_exists(__DIR__ . '/../routes/MerchantRoutes.php')) {
    require_once __DIR__ . '/../routes/MerchantRoutes.php';
}
if (file_exists(__DIR__ . '/../routes/CategoryRoutes.php')) {
    require_once __DIR__ . '/../routes/CategoryRoutes.php';
}
if (file_exists(__DIR__ . '/../routes/ContactRoutes.php')) {
    require_once __DIR__ . '/../routes/ContactRoutes.php';
}

// =========================================================
// 🩺 Root Health Check
// =========================================================
Flight::route('GET /', function () {
    Flight::json([
        'status'  => 'ok',
        'message' => '🚀 Nummo API is up and running!',
        'version' => '3.0.0',
        'time'    => date('Y-m-d H:i:s')
    ]);
});

// =========================================================
// ⚙️ FlightPHP Settings and Error Handling
// =========================================================
Flight::set('flight.log_errors', true);

Flight::map('error', function (Throwable $ex) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Server error',
        'message' => $ex->getMessage(),
        'trace'   => getenv('APP_ENV') === 'development' ? $ex->getTraceAsString() : null
    ]);
});

// =========================================================
// 🏁 Start FlightPHP
// =========================================================
Flight::start();
