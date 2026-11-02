<?php
require_once __DIR__ . '/../services/ContactService.php';

Flight::register('contactService', 'ContactService');

// GET /api/contacts?user_id=2 returns only that user's contacts
Flight::route('GET /api/contacts', function () {
    $userId = Flight::request()->query['user_id'];
    Flight::json($userId
        ? Flight::contactService()->getForUser((int) $userId)
        : Flight::contactService()->getAll());
});

Flight::route('GET /api/contacts/@id:[0-9]+', function ($id) {
    Flight::json(Flight::contactService()->getById((int) $id));
});

Flight::route('POST /api/contacts', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::contactService()->create($data), 201);
});

Flight::route('PUT /api/contacts/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::contactService()->update((int) $id, $data));
});

Flight::route('DELETE /api/contacts/@id:[0-9]+', function ($id) {
    Flight::contactService()->delete((int) $id);
    Flight::json(['message' => 'Contact deleted']);
});
