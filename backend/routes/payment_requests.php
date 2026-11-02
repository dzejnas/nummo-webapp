<?php
require_once __DIR__ . '/../services/PaymentRequestService.php';

Flight::register('paymentRequestService', 'PaymentRequestService');

// GET /api/payment-requests?user_id=2 returns requests the user sent or has to pay
Flight::route('GET /api/payment-requests', function () {
    $userId = Flight::request()->query['user_id'];
    Flight::json($userId
        ? Flight::paymentRequestService()->getForUser((int) $userId)
        : Flight::paymentRequestService()->getAll());
});

Flight::route('GET /api/payment-requests/@id:[0-9]+', function ($id) {
    Flight::json(Flight::paymentRequestService()->getById((int) $id));
});

Flight::route('POST /api/payment-requests', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::paymentRequestService()->create($data), 201);
});

// Send {"status": "paid"} to pay the request, {"status": "declined"} to decline it
Flight::route('PUT /api/payment-requests/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::paymentRequestService()->update((int) $id, $data));
});

Flight::route('DELETE /api/payment-requests/@id:[0-9]+', function ($id) {
    Flight::paymentRequestService()->delete((int) $id);
    Flight::json(['message' => 'Payment request deleted']);
});
