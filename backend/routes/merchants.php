<?php
require_once __DIR__ . '/../services/MerchantService.php';

Flight::map('merchant_service', fn() => new MerchantService());

Flight::route('GET /api/merchants', fn() =>
    Flight::json(['data' => Flight::merchant_service()->get_all(), 'error' => null])
);

Flight::route('GET /api/merchants/@id', fn($id) =>
    Flight::json(['data' => Flight::merchant_service()->get_by_id((int)$id), 'error' => null])
);

Flight::route('POST /api/merchants', function() {
    try {
        $data = Flight::request()->data->getData();
        $created = Flight::merchant_service()->create($data);
        Flight::json(['data' => $created, 'error' => null], 201);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

Flight::route('PUT /api/merchants/@id', function($id) {
    try {
        $data = Flight::request()->data->getData();
        $updated = Flight::merchant_service()->update((int)$id, $data);
        Flight::json(['data' => $updated, 'error' => null]);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

Flight::route('DELETE /api/merchants/@id', fn($id) =>
    Flight::json(['success' => Flight::merchant_service()->delete((int)$id), 'error' => null])
);
