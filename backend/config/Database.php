<?php
// ==========================================================
// Nummo — Database Connector (Milestone 2)
// ==========================================================

class Database {
    private static ?\PDO $conn = null;

    public static function getConnection(): \PDO {
        if (self::$conn !== null) {
            return self::$conn;
        }

        // Try to load .env file if it exists
        $envPath = __DIR__ . '/../.env';
        if (file_exists($envPath)) {
            $vars = parse_ini_file($envPath);
            foreach ($vars as $key => $value) {
                putenv("$key=$value");
            }
        }

        // Local default fallback for testing
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $db   = getenv('DB_NAME') ?: 'nummo_db';
        $user = getenv('DB_USER') ?: 'nummo_user';
        $pass = getenv('DB_PASS') ?: 'StrongPass#2025!';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            self::$conn = new PDO($dsn, $user, $pass, $options);
            error_log("✅ Database connected (Milestone 2)");
        } catch (PDOException $e) {
            error_log("❌ DB connection failed: " . $e->getMessage());
            throw new RuntimeException('DB connection failed: ' . $e->getMessage());
        }

        return self::$conn;
    }
}
