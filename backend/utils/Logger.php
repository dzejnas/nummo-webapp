<?php
// Writes one line per event to backend/logs/info.log or backend/logs/error.log.
// Example: [2026-11-02 14:05:12] ERROR Exchange rate API failed: timeout
class Logger
{
    public static function info(string $message): void
    {
        self::write('info.log', 'INFO', $message);
    }

    public static function error(string $message): void
    {
        self::write('error.log', 'ERROR', $message);
    }

    private static function write(string $file, string $level, string $message): void
    {
        $line = sprintf("[%s] %s %s\n", date('Y-m-d H:i:s'), $level, $message);
        file_put_contents(__DIR__ . '/../logs/' . $file, $line, FILE_APPEND | LOCK_EX);
    }
}
