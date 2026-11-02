<?php
// Copy this file to config.php and fill in your own values.
// config.php is listed in .gitignore and must never be committed.

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'nummo_db',
        'user' => 'root',
        'pass' => '',
    ],

    // Secret used to sign JWT tokens (Milestone 4). Use a long random string.
    'jwt_secret' => 'change-me',

    // External API: Frankfurter exchange rates (no API key needed)
    'rates_api_url'     => 'https://api.frankfurter.dev/v1',
    'rates_api_timeout' => 5, // seconds
];
