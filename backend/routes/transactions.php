<?php
require_once __DIR__ . '/../services/TransactionService.php';

Flight::register('transactionService', 'TransactionService');

// Optional filters: ?user_id=2&status=completed&category_id=1
Flight::route('GET /api/transactions', function () {
    $filters = Flight::request()->query->getData();
    Flight::json(Flight::transactionService()->getFiltered($filters));
});

Flight::route('GET /api/transactions/@id:[0-9]+', function ($id) {
    Flight::json(Flight::transactionService()->getById((int) $id));
});

Flight::route('POST /api/transactions', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::transactionService()->create($data), 201);
});

Flight::route('PUT /api/transactions/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::transactionService()->update((int) $id, $data));
});

Flight::route('DELETE /api/transactions/@id:[0-9]+', function ($id) {
    Flight::transactionService()->delete((int) $id);
    Flight::json(['message' => 'Transaction deleted']);
});
