<?php
// Runs create / read / update / delete on every DAO against the local database.
// Everything happens inside one DB transaction that is rolled back at the end,
// so the sample data is left unchanged.
// Run: php backend/tests/dao_check.php

foreach (glob(__DIR__ . '/../dao/*Dao.php') as $file) {
    require_once $file;
}

$failures = 0;

function check(string $label, bool $ok): void
{
    global $failures;
    echo ($ok ? '  ok    ' : '  FAIL  ') . $label . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
}

$users        = new UserDao();
$categories   = new CategoryDao();
$merchants    = new MerchantDao();
$contacts     = new ContactDao();
$transactions = new TransactionDao();
$requests     = new PaymentRequestDao();

// Unique names, so the check also works on a database that already has data
$tag = substr(uniqid(), -6);

$users->beginTransaction();

try {
    echo "users\n";
    $email  = "test-$tag@nummo.com";
    $userId = $users->insert(['name' => 'Test User', 'email' => $email,
                              'password' => password_hash('secret123', PASSWORD_BCRYPT)]);
    check('insert + getById', $users->getById($userId)['email'] === $email);
    check('getById hides password', !isset($users->getById($userId)['password']));
    check('getByEmail returns hash', password_verify('secret123', $users->getByEmail($email)['password']));
    $users->update($userId, ['name' => 'Renamed', 'not_a_column' => 'ignored']);
    check('update', $users->getById($userId)['name'] === 'Renamed');
    $users->credit($userId, '25.50');
    check('credit', $users->getById($userId)['balance'] === '25.50');
    check('debit refuses more than the balance', !$users->debit($userId, '25.51'));
    check('debit', $users->debit($userId, '0.50') && $users->getById($userId)['balance'] === '25.00');
    check('getAll', count($users->getAll()) >= 4);

    echo "categories\n";
    $categoryId = $categories->insert(['name' => "Travel $tag"]);
    check('insert + getByName', $categories->getByName("Travel $tag")['id'] === $categoryId);
    $categories->update($categoryId, ['name' => "Trips $tag"]);
    check('update', $categories->getById($categoryId)['name'] === "Trips $tag");

    echo "merchants\n";
    $merchantId = $merchants->insert(['name' => "Test Shop $tag", 'category_id' => $categoryId]);
    check('insert + join category', $merchants->getById($merchantId)['category_name'] === "Trips $tag");
    $merchants->update($merchantId, ['contact_email' => 'shop@example.com']);
    check('update', $merchants->getById($merchantId)['contact_email'] === 'shop@example.com');

    echo "contacts\n";
    $contactId = $contacts->insert(['user_id' => $userId, 'friend_user_id' => 2, 'status' => 'pending']);
    check('insert + join friend', $contacts->getById($contactId)['friend_name'] === $users->getById(2)['name']);
    $contacts->update($contactId, ['status' => 'accepted']);
    check('update', $contacts->getById($contactId)['status'] === 'accepted');
    check('getForUser', count($contacts->getForUser($userId)) === 1);
    check('getByUsers', $contacts->getByUsers($userId, 2)['id'] === $contactId);

    echo "transactions\n";
    $txId = $transactions->insert(['sender_id' => $userId, 'merchant_id' => $merchantId,
                                   'category_id' => $categoryId, 'amount' => 9.99, 'note' => 'Test']);
    check('insert + joins', $transactions->getById($txId)['merchant_name'] === "Test Shop $tag");
    $transactions->update($txId, ['status' => 'failed']);
    check('update', $transactions->getById($txId)['status'] === 'failed');
    check('getFiltered by user', count($transactions->getFiltered($userId)) === 1);
    check('getFiltered by status', count($transactions->getFiltered(null, 'pending')) >= 1);
    check('getSpendingByCategory', count($transactions->getSpendingByCategory(2)) >= 1);

    echo "payment_requests\n";
    $requestId = $requests->insert(['requester_id' => $userId, 'payer_id' => 2, 'amount' => 5]);
    check('insert + joins', $requests->getById($requestId)['payer_name'] === $users->getById(2)['name']);
    $requests->update($requestId, ['status' => 'declined']);
    check('update', $requests->getById($requestId)['status'] === 'declined');
    check('getForUser', count($requests->getForUser($userId)) === 1);

    echo "delete\n";
    check('delete payment request', $requests->delete($requestId));
    check('delete transaction', $transactions->delete($txId));
    check('delete contact', $contacts->delete($contactId));
    check('delete merchant', $merchants->delete($merchantId));
    check('delete category', $categories->delete($categoryId));
    check('delete user', $users->delete($userId));
    check('getById after delete is null', $users->getById($userId) === null);
    check('delete missing id returns false', !$users->delete($userId));
} finally {
    $users->rollBack();
}

echo PHP_EOL . ($failures === 0 ? 'All DAO checks passed.' : "$failures check(s) failed.") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
