<?php
require_once __DIR__ . '/../services/TransactionService.php';
require_once __DIR__ . '/../middleware/Guards.php';

// Users see the payments they sent or received; admins see all.
Flight::register('transactionService', 'TransactionService');

// Optional filters: ?status=completed&category_id=1 (admins can also use ?user_id=2)
Flight::route('GET /api/transactions', function () {
    $filters = Flight::request()->query->getData();
    Flight::json(Flight::transactionService()->getFiltered($filters, Flight::get('user')));
})->addMiddleware(loggedIn());

Flight::route('GET /api/transactions/@id:[0-9]+', function ($id) {
    Flight::json(Flight::transactionService()->getByIdFor((int) $id, Flight::get('user')));
})->addMiddleware(loggedIn());

// Optional header Idempotency-Key: a retry with the same key and body returns the first payment (200)
Flight::route('POST /api/transactions', function () {
    $data = Flight::request()->data->getData();
    $key  = Flight::request()->getHeader('Idempotency-Key') ?: null;
    $result = Flight::transactionService()->create($data, Flight::get('user'), $key);
    Flight::json($result['transaction'], $result['replayed'] ? 200 : 201);
})->addMiddleware(loggedIn());

Flight::route('PUT /api/transactions/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::transactionService()->update((int) $id, $data, Flight::get('user')));
})->addMiddleware(loggedIn());

Flight::route('DELETE /api/transactions/@id:[0-9]+', function ($id) {
    Flight::transactionService()->deleteFor((int) $id, Flight::get('user'));
    Flight::json(['message' => 'Transaction deleted']);
})->addMiddleware(loggedIn());
