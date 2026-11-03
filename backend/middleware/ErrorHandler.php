<?php
require_once __DIR__ . '/../utils/ApiException.php';
require_once __DIR__ . '/../utils/Logger.php';

// Turns every exception into a JSON error response.
// - ApiException: expected errors (400, 401, 403, 404, 409, 503) -> its message and status.
//   Rejected requests (401/403) are also written to the error log.
// - Anything else (PDOException, bugs): the details go to logs/error.log and the client only
//   gets a generic message — never SQL, file paths or stack traces.
// jsonHalt sends the response right away; plain json() would not be sent after an exception.
class ErrorHandler
{
    public static function handle(Throwable $e): void
    {
        $request = Flight::request();
        $user = Flight::has('user') ? Flight::get('user') : null;
        $who = 'user=' . ($user['id'] ?? 'guest') . ' role=' . ($user['role'] ?? 'guest');

        if ($e instanceof ApiException) {
            if (in_array($e->getStatus(), [401, 403], true)) {
                Logger::error("{$request->method} {$request->url} {$e->getStatus()} $who {$e->getMessage()}");
            }
            Flight::jsonHalt(['error' => $e->getMessage()], $e->getStatus());
            return;
        }

        Logger::error(sprintf('%s %s 500 %s %s: %s (%s:%d)',
            $request->method, $request->url, $who,
            get_class($e), $e->getMessage(), basename($e->getFile()), $e->getLine()));
        Flight::jsonHalt(['error' => 'Something went wrong. Please try again later.'], 500);
    }
}
