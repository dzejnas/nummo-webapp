<?php
// OpenAPI specification (YAML) used by the Swagger UI page at /docs/
Flight::route('GET /api/openapi.yaml', function () {
    Flight::response()->header('Content-Type', 'application/yaml');
    Flight::response()->write(file_get_contents(__DIR__ . '/../docs/openapi.yaml'));
});

Flight::route('GET /docs', function () {
    Flight::redirect('/docs/');
});
