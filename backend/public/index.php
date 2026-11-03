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
require_once __DIR__ . '/../utils/ApiException.php';
require_once __DIR__ . '/../utils/Logger.php';

// CORS: the frontend runs on a different port/domain, so the browser needs these headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Errors: services throw ApiException with an HTTP status (400, 404, 409, ...).
// Anything else is unexpected: log the details, but only send a generic message to the client
// (never SQL, file paths or stack traces).
// jsonHalt sends the response right away; plain json() would not be sent after an exception.
Flight::map('error', function (Throwable $e) {
    if ($e instanceof ApiException) {
        Flight::jsonHalt(['error' => $e->getMessage()], $e->getStatus());
        return;
    }
    Logger::error(sprintf('%s %s 500 %s: %s (%s:%d)',
        Flight::request()->method, Flight::request()->url,
        get_class($e), $e->getMessage(), basename($e->getFile()), $e->getLine()));
    Flight::jsonHalt(['error' => 'Something went wrong. Please try again later.'], 500);
});

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
