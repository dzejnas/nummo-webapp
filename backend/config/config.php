<?php
// DB config - replace placeholders or use env vars
$DB_HOST = '127.0.0.1';
$DB_NAME = 'nummo';
$DB_USER = 'nummo_user';
$DB_PASS = 'replace_with_password';

try{
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}catch(Exception $e){
    error_log($e->getMessage());
    // don't echo sensitive info in production
}
