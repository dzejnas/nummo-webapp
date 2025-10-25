<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Database.php';

// Enable CORS for frontend access
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Base route for testing
Flight::route('GET /', function () {
    Flight::json(['status' => 'Nummo API running 🚀']);
});

// ✅ Load all route definitions BEFORE Flight::start()
require_once __DIR__ . '/../routes/UserRoutes.php';
require_once __DIR__ . '/../routes/TransactionRoutes.php';

// ✅ Start FlightPHP framework
Flight::set('flight.log_errors', true);
Flight::map('error', function(Exception $ex) {
    echo "<pre>❌ " . $ex->getMessage() . "\n" . $ex->getTraceAsString() . "</pre>";
});

Flight::start();
