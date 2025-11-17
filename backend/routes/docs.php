<?php
// =========================================================
// 📘 Swagger / OpenAPI Docs Routes
// =========================================================

// 1️⃣ Return the OpenAPI YAML file
Flight::route('GET /api/docs', function() {
    $file = __DIR__ . '/../docs/openapi.yaml';

    if (!file_exists($file)) {
        Flight::json(['error' => 'openapi.yaml not found'], 404);
        return;
    }

    header('Content-Type: application/yaml'); // perfect for Swagger
    readfile($file);
});

// 2️⃣ Serve the Swagger UI (front-end)
Flight::route('GET /docs', function() {
    Flight::redirect('/docs/index.html');
});
