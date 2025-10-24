<?php
// Simple PDO connector for Nummo (Milestone 2)
// Reads from env vars if present, otherwise uses local defaults.

class Database {
    private static ?\PDO $conn = null;

    public static function getConnection(): \PDO {
        if (self::$conn !== null) {
            return self::$conn;
        }

        // ✅ Local defaults
        $host = getenv('DB_HOST') ?: '127.0.0.1';   // force TCP (not socket)
        $port = getenv('DB_PORT') ?: '3306';
        $db   = getenv('DB_NAME') ?: 'nummo_db';
        $user = getenv('DB_USER') ?: 'nummo_user';
        $pass = getenv('DB_PASS') ?: 'StrongPass#2025!';  // ✅ corrected password
        $charset = 'utf8mb4';

        // ✅ Full DSN for MySQL
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            self::$conn = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new RuntimeException('DB connection failed: ' . $e->getMessage());
        }

        return self::$conn;
    }
}
