<?php
require_once __DIR__ . '/../dao/TransactionDao.php';

error_log("✅ TransactionRoutes.php loaded successfully");

// ====================================================
// TRANSACTIONS API ROUTES
// ====================================================

// 🔹 GET all transactions (optionally filtered by user, status, or category)
// 🔹 GET all transactions (optionally filtered by user, status, or category)
Flight::route('GET /api/transactions', function() {
    $dao = new TransactionDao();

    $userId   = Flight::request()->query->user_id;
    $status   = Flight::request()->query->status;
    $category = Flight::request()->query->category;

    try {
        if (!empty($userId)) {
            // User-specific transactions
            $transactions = $dao->getAllForUserFiltered((int)$userId, $status, $category);
        } else {
            // Admin/global view
            $transactions = $dao->getAllFiltered($status, $category);
        }

        Flight::json($transactions);
    } catch (Throwable $e) { // ✅ use Throwable instead of Exception
        error_log("❌ Transaction DAO failed: " . $e->getMessage());
        Flight::json(['error' => $e->getMessage()], 500); // ✅ show real SQL error
    }
});


// 🔹 GET single transaction by ID
Flight::route('GET /api/transactions/@id', function($id) {
    $dao = new TransactionDao();

    try {
        $tx = $dao->getById((int)$id);
        if ($tx) {
            Flight::json($tx);
        } else {
            Flight::json(['error' => 'Transaction not found'], 404);
        }
    } catch (Exception $e) {
        error_log("❌ Error fetching transaction ID {$id}: " . $e->getMessage());
        Flight::json(['error' => 'Server error while fetching transaction'], 500);
    }
});

// 🔹 POST create new transaction
Flight::route('POST /api/transactions', function() {
    $data = Flight::request()->data->getData();

    // Basic validation
    if (empty($data['sender_id']) || empty($data['receiver_id']) || empty($data['amount'])) {
        Flight::json(['error' => 'Missing required fields: sender_id, receiver_id, amount'], 400);
        return;
    }

    try {
        $dao = new TransactionDao();
        $id = $dao->create([
            'sender_id'   => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'merchant_id' => $data['merchant_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'amount'      => $data['amount'],
            'note'        => $data['note'] ?? null,
            'status'      => $data['status'] ?? 'pending',
        ]);

        Flight::json(['success' => true, 'id' => $id], 201);
    } catch (Exception $e) {
        error_log("❌ Error creating transaction: " . $e->getMessage());
        Flight::json(['error' => 'Server error while creating transaction'], 500);
    }
});
