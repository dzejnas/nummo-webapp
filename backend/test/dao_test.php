<?php
// Simple CLI test for your DAO classes
// Run with: php backend/tests/dao_test.php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/TransactionDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';
require_once __DIR__ . '/../dao/ContactDao.php';
require_once __DIR__ . '/../dao/MerchantDao.php';

echo "=== Testing DB Connection ===\n";
try {
    $conn = Database::getConnection();
    echo "Connected to DB successfully ✅\n\n";
} catch (Exception $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

echo "=== Testing UserDao ===\n";
$userDao = new UserDao();
$users = $userDao->getAll();
print_r($users);

echo "\n=== Testing CategoryDao ===\n";
$catDao = new CategoryDao();
print_r($catDao->getAll());

echo "\n=== Testing MerchantDao ===\n";
$merchDao = new MerchantDao();
print_r($merchDao->getAll());

echo "\n=== Testing ContactDao (for user 1) ===\n";
$contactDao = new ContactDao();
print_r($contactDao->getAllForUser(1));

echo "\n=== Testing TransactionDao (for user 1) ===\n";
$txDao = new TransactionDao();
print_r($txDao->getAllForUser(1));

echo "\n✅ DAO test complete.\n";
