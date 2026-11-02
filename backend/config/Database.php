<?php
require_once __DIR__ . '/Settings.php';

// Creates one shared PDO connection for the whole request (singleton).
class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $db  = Settings::get('db');
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";

            self::$connection = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on SQL errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows as ['column' => value]
                PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepared statements
            ]);
        }
        return self::$connection;
    }
}
