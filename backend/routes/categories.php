<?php
// =========================================================
// 📦 Category Routes — Nummo WebApp
// =========================================================

require_once __DIR__ . '/../services/CategoryService.php';

// Register the service globally in Flight’s container
Flight::map('category_service', function() {
    static $service = null;
    if ($service === null) {
        $service = new CategoryService();
    }
    return $service;
});

// ---------------------------------------------------------
// 🟢 GET /api/categories
// ---------------------------------------------------------
Flight::route('GET /api/categories', function() {
    try {
        $data = Flight::category_service()->get_all();
        Flight::json(['data' => $data, 'error' => null]);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

// ---------------------------------------------------------
// 🟢 GET /api/categories/{id}
// ---------------------------------------------------------
Flight::route('GET /api/categories/@id', function($id) {
    try {
        $data = Flight::category_service()->get_by_id((int)$id);
        Flight::json(['data' => $data, 'error' => null]);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 404);
    }
});

// ---------------------------------------------------------
// 🟡 POST /api/categories
// ---------------------------------------------------------
Flight::route('POST /api/categories', function() {
    try {
        $payload = Flight::request()->data->getData();
        $created = Flight::category_service()->create($payload);
        Flight::json(['data' => $created, 'error' => null], 201);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

// ---------------------------------------------------------
// 🟠 PUT /api/categories/{id}
// ---------------------------------------------------------
Flight::route('PUT /api/categories/@id', function($id) {
    try {
        $payload = Flight::request()->data->getData();
        $updated = Flight::category_service()->update((int)$id, $payload);
        Flight::json(['data' => $updated, 'error' => null]);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

// ---------------------------------------------------------
// 🔴 DELETE /api/categories/{id}
// ---------------------------------------------------------
Flight::route('DELETE /api/categories/@id', function($id) {
    try {
        $ok = Flight::category_service()->delete((int)$id);
        Flight::json(['success' => $ok, 'error' => null]);
    } catch (Throwable $e) {
        Flight::json(['success' => false, 'error' => $e->getMessage()], 400);
    }
});
