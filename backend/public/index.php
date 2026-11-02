<?php
// Front controller: every API request goes through this file.
// Local run: php -S localhost:8080 -t backend/public backend/public/index.php

// With PHP's built-in server, let real files and folders (like the Swagger UI page in docs/)
// be served directly. On Apache, .htaccess does the same.
if (PHP_SAPI === 'cli-server' && file_exists(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
    && parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/') {
    return false;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../middleware/RequestLogger.php';
require_once __DIR__ . '/../middleware/ErrorHandler.php';

// CORS: the frontend runs on a different port/domain, so the browser needs these headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Middleware registered for every request:
// - RequestLogger writes each request to logs/info.log
// - ErrorHandler turns exceptions into JSON errors and logs them to logs/error.log
// Route-level middleware (AuthMiddleware, RoleMiddleware) is added in the route files.
RequestLogger::register();
Flight::map('error', [ErrorHandler::class, 'handle']);

Flight::map('notFound', function () {
    Flight::json(['error' => 'Endpoint not found'], 404);
});

Flight::route('GET /', function () {
    Flight::json(['name' => 'Nummo API', 'docs' => '/docs/']);
});

foreach (glob(__DIR__ . '/../routes/*.php') as $routeFile) {
    require_once $routeFile;
}

Flight::start();
