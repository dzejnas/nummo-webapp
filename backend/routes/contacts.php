<?php
require_once __DIR__ . '/../services/ContactService.php';

Flight::map('contact_service', fn() => new ContactService());

Flight::route('GET /api/contacts', fn() =>
    Flight::json(['data' => Flight::contact_service()->get_all(), 'error' => null])
);

Flight::route('GET /api/contacts/@id', fn($id) =>
    Flight::json(['data' => Flight::contact_service()->get_by_id((int)$id), 'error' => null])
);

Flight::route('POST /api/contacts', function() {
    try {
        $data = Flight::request()->data->getData();
        $created = Flight::contact_service()->create($data);
        Flight::json(['data' => $created, 'error' => null], 201);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

Flight::route('PUT /api/contacts/@id', function($id) {
    try {
        $data = Flight::request()->data->getData();
        $updated = Flight::contact_service()->update((int)$id, $data);
        Flight::json(['data' => $updated, 'error' => null]);
    } catch (Throwable $e) {
        Flight::json(['data' => null, 'error' => $e->getMessage()], 400);
    }
});

Flight::route('DELETE /api/contacts/@id', fn($id) =>
    Flight::json(['success' => Flight::contact_service()->delete((int)$id), 'error' => null])
);
