<?php
require_once __DIR__ . '/../utils/Logger.php';

// Writes every request to logs/info.log when the response has been sent, e.g.
// [2026-12-01 14:05:12] INFO GET /api/transactions 200 user=2 role=user 35ms
// It runs at shutdown, so requests that ended with an error are logged too.
class RequestLogger
{
    public static function register(): void
    {
        $start = microtime(true);

        register_shutdown_function(function () use ($start) {
            $request = Flight::request();
            $user = Flight::has('user') ? Flight::get('user') : null;

            Logger::info(sprintf('%s %s %d user=%s role=%s %dms',
                $request->method,
                $request->url,
                http_response_code(),
                $user['id'] ?? 'guest',
                $user['role'] ?? 'guest',
                (microtime(true) - $start) * 1000
            ));
        });
    }
}
