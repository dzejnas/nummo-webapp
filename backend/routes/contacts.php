<?php
require_once __DIR__ . '/../services/ContactService.php';
require_once __DIR__ . '/../middleware/Guards.php';

// Users manage their own contacts; admins see all of them.
Flight::register('contactService', 'ContactService');

Flight::route('GET /api/contacts', function () {
    Flight::json(Flight::contactService()->getAllFor(Flight::get('user')));
})->addMiddleware(loggedIn());

Flight::route('GET /api/contacts/@id:[0-9]+', function ($id) {
    Flight::json(Flight::contactService()->getByIdFor((int) $id, Flight::get('user')));
})->addMiddleware(loggedIn());

// Body: {"email": "tarik@nummo.com"} or {"friend_user_id": 3}
Flight::route('POST /api/contacts', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::contactService()->create($data, Flight::get('user')), 201);
})->addMiddleware(loggedIn());

Flight::route('PUT /api/contacts/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::contactService()->update((int) $id, $data, Flight::get('user')));
})->addMiddleware(loggedIn());

Flight::route('DELETE /api/contacts/@id:[0-9]+', function ($id) {
    Flight::contactService()->deleteFor((int) $id, Flight::get('user'));
    Flight::json(['message' => 'Contact deleted']);
})->addMiddleware(loggedIn());
