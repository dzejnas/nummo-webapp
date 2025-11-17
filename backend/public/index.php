<?php
// =========================================================
// Nummo Backend — Main Entry Point (Milestone 3)
// =========================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Database.php';

// ---------------------------------------------------------
// 🌐 CORS (dev-friendly; tighten in prod)
// ---------------------------------------------------------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Proper preflight handler for all routes
Flight::route('OPTIONS *', function () {
    http_response_code(200);
    exit();
});

// Optional: default JSON content-type for API routes
// (Flight::json sets it per-response; this just helps for errors)
header("Content-Type: application/json");

// ---------------------------------------------------------
// ⚙️ Flight settings + error/404 maps
// ---------------------------------------------------------
Flight::set('flight.log_errors', true);

Flight::map('error', function (Throwable $ex) {
    // Ensure JSON error shape
    http_response_code(500);
    echo json_encode([
        'error' => [
            'code'    => 500,
            'message' => $ex->getMessage(),
        ],
        'data' => null
    ]);
});

Flight::map('notFound', function () {
    http_response_code(404);
    echo json_encode([
        'error' => [
            'code'    => 404,
            'message' => 'Not found'
        ],
        'data' => null
    ]);
});

// ---------------------------------------------------------
// 🩺 Root Health Check (simple HTML or JSON)
// ---------------------------------------------------------
Flight::route('GET /', function () {
    // Keep JSON to be consistent with API; change to HTML if you prefer
    echo json_encode([
        'status'  => 'ok',
        'message' => '🚀 Nummo API is up and running!',
        'version' => '3.0.0',
        'time'    => date('c')
    ]);
});

// ---------------------------------------------------------
// 🧠 Route modules (match your lowercase route filenames)
// Add/keep only the ones that exist
// ---------------------------------------------------------
$routes = [
    __DIR__ . '/../routes/categories.php',
    __DIR__ . '/../routes/merchants.php',
    __DIR__ . '/../routes/users.php',
    __DIR__ . '/../routes/contacts.php',
    __DIR__ . '/../routes/transactions.php',
    __DIR__ . '/../routes/docs.php',      // only if you used the /api/docs/openapi.yaml route
    __DIR__ . '/../routes/health.php',    // only if you made a separate HTML health page
];

foreach ($routes as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// ---------------------------------------------------------
// 🏁 Start FlightPHP
// ---------------------------------------------------------
Flight::start();
