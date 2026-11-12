<?php
// Reads the settings. Usage: Settings::get('db'), Settings::get('jwt_secret')
// - Local development: from config/config.php (not committed to Git)
// - Deployed server: from environment variables, if there is no config.php
//   (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, JWT_SECRET, RATES_API_URL, RATES_API_TIMEOUT)

class Settings
{
    private static ?array $values = null;

    public static function get(string $key)
    {
        if (self::$values === null) {
            $file = __DIR__ . '/config.php';
            self::$values = file_exists($file) ? require $file : self::fromEnvironment();
            // backend/tests run against a throwaway database (never the real one)
            if (getenv('NUMMO_TEST_DB')) {
                self::$values['db']['name'] = getenv('NUMMO_TEST_DB');
            }
        }
        return self::$values[$key] ?? null;
    }

    private static function fromEnvironment(): array
    {
        if (!getenv('DB_HOST') || !getenv('JWT_SECRET')) {
            throw new RuntimeException('No config.php and no DB_HOST/JWT_SECRET environment variables');
        }
        return [
            'db' => [
                'host' => getenv('DB_HOST'),
                'port' => (int) (getenv('DB_PORT') ?: 3306),
                'name' => getenv('DB_NAME') ?: 'nummo_db',
                'user' => getenv('DB_USER'),
                'pass' => getenv('DB_PASS'),
            ],
            'jwt_secret'        => getenv('JWT_SECRET'),
            'rates_api_url'     => getenv('RATES_API_URL') ?: 'https://api.frankfurter.dev/v1',
            'rates_api_timeout' => (int) (getenv('RATES_API_TIMEOUT') ?: 5),
        ];
    }
}
