<?php
// backend/index.php - FlightPHP bootstrap (development)
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

// Allow CORS for dev (restrict in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

Flight::route('GET /api/ping', function(){
    Flight::json(['pong' => 'ok']);
});

// Add route files later (backend/routes)
