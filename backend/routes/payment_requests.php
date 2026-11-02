<?php
require_once __DIR__ . '/../services/PaymentRequestService.php';
require_once __DIR__ . '/../middleware/Guards.php';

// Users see requests they sent or have to pay; admins see all.
Flight::register('paymentRequestService', 'PaymentRequestService');

Flight::route('GET /api/payment-requests', function () {
    Flight::json(Flight::paymentRequestService()->getAllFor(Flight::get('user')));
})->addMiddleware(loggedIn());

Flight::route('GET /api/payment-requests/@id:[0-9]+', function ($id) {
    Flight::json(Flight::paymentRequestService()->getByIdFor((int) $id, Flight::get('user')));
})->addMiddleware(loggedIn());

Flight::route('POST /api/payment-requests', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::paymentRequestService()->create($data, Flight::get('user')), 201);
})->addMiddleware(loggedIn());

// The payer sends {"status": "paid"} or {"status": "declined"}
Flight::route('PUT /api/payment-requests/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::paymentRequestService()->update((int) $id, $data, Flight::get('user')));
})->addMiddleware(loggedIn());

Flight::route('DELETE /api/payment-requests/@id:[0-9]+', function ($id) {
    Flight::paymentRequestService()->deleteFor((int) $id, Flight::get('user'));
    Flight::json(['message' => 'Payment request deleted']);
})->addMiddleware(loggedIn());
