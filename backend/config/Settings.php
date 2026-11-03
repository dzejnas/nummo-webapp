<?php
// Reads settings from config/config.php (which is not committed to Git).
// Usage: Settings::get('db'), Settings::get('jwt_secret')

class Settings
{
    private static ?array $values = null;

    public static function get(string $key)
    {
        if (self::$values === null) {
            $file = __DIR__ . '/config.php';
            if (!file_exists($file)) {
                throw new RuntimeException('Missing backend/config/config.php - copy config.example.php and fill it in');
            }
            self::$values = require $file;
        }
        return self::$values[$key] ?? null;
    }
}
