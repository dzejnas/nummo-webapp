<?php
// Thrown by services when a request cannot be completed.
// The global error handler (public/index.php) turns it into a JSON error with this HTTP status.
class ApiException extends Exception
{
    public function __construct(string $message, int $status = 400)
    {
        parent::__construct($message, $status);
    }

    public function getStatus(): int
    {
        return $this->getCode();
    }
}
