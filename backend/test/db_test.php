<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query("SELECT DATABASE() AS dbname");
    $row = $stmt->fetch();
    echo "Connected to DB: " . $row['dbname'] . PHP_EOL;

    $users = $pdo->query("SELECT * FROM users")->fetchAll();
    echo "Users count: " . count($users) . PHP_EOL;
    print_r($users);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
