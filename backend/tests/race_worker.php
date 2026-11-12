<?php
// One side of a race, started by payments_check.php. Runs ONE service call in its own PHP process,
// so it has its own MySQL connection. Reads the job as JSON on stdin, prints the result as JSON.
require_once __DIR__ . '/../services/TransactionService.php';
require_once __DIR__ . '/../services/PaymentRequestService.php';

$job = json_decode(stream_get_contents(STDIN), true);
$db  = Database::getConnection(); // connect before waiting, so all workers start the call together
$row = (new UserDao())->getById($job['actor']);
$actor = ['id' => $row['id'], 'name' => $row['name'], 'email' => $row['email'], 'role' => $row['role']];

time_sleep_until($job['start']);
$t0 = microtime(true);
try {
    switch ($job['op']) {
        case 'pay':
            $result = (new TransactionService())->create($job['data'], $actor, $job['key'] ?? null);
            $out = ['status' => $result['replayed'] ? 200 : 201, 'body' => $result['transaction']];
            break;
        case 'completeTx':
            $out = ['status' => 200, 'body' => (new TransactionService())->update($job['id'], ['status' => 'completed'], $actor)];
            break;
        case 'deleteTx':
            (new TransactionService())->deleteFor($job['id'], $actor);
            $out = ['status' => 200];
            break;
        case 'payRequest':
        case 'declineRequest':
            $status = $job['op'] === 'payRequest' ? 'paid' : 'declined';
            $out = ['status' => 200, 'body' => (new PaymentRequestService())->update($job['id'], ['status' => $status], $actor)];
            break;
        case 'deleteRequest':
            (new PaymentRequestService())->deleteFor($job['id'], $actor);
            $out = ['status' => 200];
            break;
        case 'naiveDebit':
            // Control: the v1 "read balance, check, write" pattern WITHOUT locking. Used only to show
            // that the harness really runs the workers at the same time.
            $db->beginTransaction();
            $balance = $db->query("SELECT balance FROM users WHERE id = {$actor['id']}")->fetchColumn();
            usleep(100000);
            if ($balance >= $job['amount']) {
                $db->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$balance - $job['amount'], $actor['id']]);
                $out = ['status' => 201];
            } else {
                $out = ['status' => 400];
            }
            $db->commit();
            break;
        default:
            $out = ['status' => 'bad job'];
    }
} catch (ApiException $e) {
    $out = ['status' => $e->getStatus(), 'error' => $e->getMessage()];
} catch (Throwable $e) {
    $out = ['status' => 500, 'error' => get_class($e) . ': ' . $e->getMessage()];
}
echo json_encode($out + ['t0' => $t0, 't1' => microtime(true)]);
