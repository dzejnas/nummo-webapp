<?php
require_once __DIR__ . '/../services/TransactionService.php';

Flight::map('transaction_service', fn() => new TransactionService());

Flight::route('GET /api/transactions', fn() =>
    Flight::json(['data' => Flight::transaction_service()->get_all(), 'error' => null])
);

Flight::route('GET /api/transactions/@id', fn($id) =>
    Flight::json(['data' => Flight::transaction_service()->get_by_id((int)$id), 'error' => null])
);

Flight::route('POST /api/transactions', function() {
    try {
        $data = Flight::request()->data->getData();
        $created = Flight::transaction_service()->create($data);
        Flight::json(['data' => $created, 'error' => null], 201);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

Flight::route('PUT /api/transactions/@id', function($id) {
    try {
        $data = Flight::request()->data->getData();
        $updated = Flight::transaction_service()->update((int)$id, $data);
        Flight::json(['data' => $updated, 'error' => null]);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

Flight::route('DELETE /api/transactions/@id', fn($id) =>
    Flight::json(['success' => Flight::transaction_service()->delete((int)$id), 'error' => null])
);
