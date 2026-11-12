<?php
// Payment reliability checks against a real MySQL server.
// - Creates a throwaway database "nummo_test" from sql/schema.sql (dropped and recreated each run;
//   the real database is never touched). Uses the MySQL server and user from config.php.
// - Races run in separate PHP processes (race_worker.php), each with its own MySQL connection.
// Run: php backend/tests/payments_check.php
const TEST_DB = 'nummo_test';
const ROUNDS  = 5; // every race is repeated, because a single run may not overlap

putenv('NUMMO_TEST_DB=' . TEST_DB);
require_once __DIR__ . '/../config/Settings.php';

// ---- fresh test database ----
$cfg  = Settings::get('db');
$root = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};charset=utf8mb4", $cfg['user'], $cfg['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$root->exec('DROP DATABASE IF EXISTS ' . TEST_DB);
$root->exec('CREATE DATABASE ' . TEST_DB . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$root->exec('USE ' . TEST_DB);
$root->exec(file_get_contents(__DIR__ . '/../../sql/schema.sql'));
// Test-only trigger: fails the transaction INSERT after the money was already moved (partial failure)
$root->exec("CREATE TRIGGER fail_on_marker BEFORE INSERT ON transactions FOR EACH ROW
             BEGIN IF NEW.note LIKE '%FAIL_AFTER_DEBIT%' THEN
               SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'simulated failure'; END IF; END");

require_once __DIR__ . '/../services/TransactionService.php';
require_once __DIR__ . '/../services/PaymentRequestService.php';
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../dao/ContactDao.php';

$pdo = Database::getConnection();
$failures = 0;
$overlaps = 0;
$races    = 0;

function check(string $label, bool $ok, string $detail = ''): void
{
    global $failures;
    echo ($ok ? '  ok    ' : '  FAIL  ') . $label . ($ok || $detail === '' ? '' : "  ($detail)") . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
}

function makeUser(string $balance, string $role = 'user'): array
{
    static $n = 0;
    $n++;
    $id = (new UserDao())->insert(['name' => "User $n", 'email' => "user$n@test.example", 'role' => $role,
                                   'password' => password_hash('secret123', PASSWORD_BCRYPT), 'balance' => $balance]);
    return ['id' => $id, 'name' => "User $n", 'email' => "user$n@test.example", 'role' => $role];
}

function value(string $sql, array $params = [])
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function balance(array $user): string
{
    return value('SELECT balance FROM users WHERE id = ?', [$user['id']]);
}

// Starts all jobs at the same moment in separate processes and returns their results
function race(array $jobs): array
{
    global $overlaps, $races;
    $start = microtime(true) + 0.4;
    $procs = $pipes = $results = [];
    foreach ($jobs as $i => $job) {
        $procs[$i] = proc_open([PHP_BINARY, __DIR__ . '/race_worker.php'],
                               [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$i]);
        fwrite($pipes[$i][0], json_encode($job + ['start' => $start]));
        fclose($pipes[$i][0]);
    }
    foreach ($jobs as $i => $job) {
        $out = stream_get_contents($pipes[$i][1]);
        $err = stream_get_contents($pipes[$i][2]);
        proc_close($procs[$i]);
        $results[$i] = json_decode($out, true) ?? ['status' => 'crash', 'error' => trim($out . $err)];
    }
    // Did the calls really run at the same time? (each started before the other finished)
    $races++;
    if (count($results) >= 2 && max(array_column($results, 't0')) < min(array_column($results, 't1'))) {
        $overlaps++;
    }
    return $results;
}

function statuses(array $results): array
{
    $s = array_map(fn($r) => $r['status'], $results);
    sort($s);
    return $s;
}

$tx  = new TransactionService();
$req = new PaymentRequestService();

echo "control: unlocked read-check-write (v1 pattern) under the same harness\n";
$lost = 0;
for ($i = 0; $i < ROUNDS; $i++) {
    $a = makeUser('100.00');
    $r = race([['op' => 'naiveDebit', 'actor' => $a['id'], 'amount' => 70],
               ['op' => 'naiveDebit', 'actor' => $a['id'], 'amount' => 70]]);
    $lost += statuses($r) === [201, 201] ? 1 : 0;
}
echo "  info  both 70.00 debits 'succeeded' from 100.00 in $lost/" . ROUNDS . " rounds (shows the races are real)\n";

echo "two payments that together exceed the balance\n";
for ($i = 0; $i < ROUNDS; $i++) {
    $a = makeUser('100.00');
    $b = makeUser('0.00');
    $r = race([['op' => 'pay', 'actor' => $a['id'], 'data' => ['receiver_id' => $b['id'], 'amount' => '70.00']],
               ['op' => 'pay', 'actor' => $a['id'], 'data' => ['receiver_id' => $b['id'], 'amount' => '70.00']]]);
    $completed = value("SELECT COUNT(*) FROM transactions WHERE sender_id = ? AND status = 'completed'", [$a['id']]);
    check("round $i: one 201 and one 400", statuses($r) === [201, 400], json_encode(statuses($r)));
    check("round $i: sender 30.00, receiver 70.00, one transaction",
          balance($a) === '30.00' && balance($b) === '70.00' && (int) $completed === 1,
          balance($a) . ' / ' . balance($b) . " / $completed");
}

echo "three payments, two fit\n";
$a = makeUser('100.00');
$b = makeUser('0.00');
$r = race(array_fill(0, 3, ['op' => 'pay', 'actor' => $a['id'], 'data' => ['receiver_id' => $b['id'], 'amount' => '40.00']]));
check('two 201, one 400', statuses($r) === [201, 201, 400], json_encode(statuses($r)));
check('sender 20.00, receiver 80.00', balance($a) === '20.00' && balance($b) === '80.00');

echo "A pays B while B pays A (opposite lock order)\n";
for ($i = 0; $i < ROUNDS; $i++) {
    $a = makeUser('50.00');
    $b = makeUser('50.00');
    $r = race([['op' => 'pay', 'actor' => $a['id'], 'data' => ['receiver_id' => $b['id'], 'amount' => '10.00']],
               ['op' => 'pay', 'actor' => $b['id'], 'data' => ['receiver_id' => $a['id'], 'amount' => '20.00']]]);
    check("round $i: both succeed without deadlock", statuses($r) === [201, 201], json_encode($r));
    check("round $i: A 60.00, B 40.00", balance($a) === '60.00' && balance($b) === '40.00');
}

echo "completing the same pending transaction twice\n";
for ($i = 0; $i < ROUNDS; $i++) {
    $a = makeUser('100.00');
    $b = makeUser('0.00');
    $id = $tx->create(['receiver_id' => $b['id'], 'amount' => '60.00', 'status' => 'pending'], $a)['transaction']['id'];
    $r = race([['op' => 'completeTx', 'actor' => $a['id'], 'id' => $id], ['op' => 'completeTx', 'actor' => $a['id'], 'id' => $id]]);
    check("round $i: one 200 and one 409", statuses($r) === [200, 409], json_encode(statuses($r)));
    check("round $i: one debit (40.00) and one credit (60.00)", balance($a) === '40.00' && balance($b) === '60.00');
}

echo "completing a pending transaction while it is deleted\n";
for ($i = 0; $i < ROUNDS; $i++) {
    $a = makeUser('100.00');
    $b = makeUser('0.00');
    $id = $tx->create(['receiver_id' => $b['id'], 'amount' => '25.00', 'status' => 'pending'], $a)['transaction']['id'];
    $r = race([['op' => 'completeTx', 'actor' => $a['id'], 'id' => $id], ['op' => 'deleteTx', 'actor' => $a['id'], 'id' => $id]]);
    $row = value('SELECT status FROM transactions WHERE id = ?', [$id]);
    $completedWon = $r[0]['status'] === 200 && $r[1]['status'] === 409 && $row === 'completed'
                    && balance($a) === '75.00' && balance($b) === '25.00';
    $deleteWon    = $r[1]['status'] === 200 && $r[0]['status'] === 404 && $row === false
                    && balance($a) === '100.00' && balance($b) === '0.00';
    check("round $i: exactly one wins and balances match", $completedWon xor $deleteWon, json_encode($r) . " row=$row");
}

echo "paying the same payment request twice (retry by the payer)\n";
for ($i = 0; $i < ROUNDS; $i++) {
    $requester = makeUser('0.00');
    $payer     = makeUser('100.00');
    $rid = $req->create(['payer_id' => $payer['id'], 'amount' => '30.00'], $requester)['id'];
    $r = race([['op' => 'payRequest', 'actor' => $payer['id'], 'id' => $rid], ['op' => 'payRequest', 'actor' => $payer['id'], 'id' => $rid]]);
    $txCount = value('SELECT COUNT(*) FROM transactions WHERE sender_id = ?', [$payer['id']]);
    $linked  = value('SELECT transaction_id FROM payment_requests WHERE id = ?', [$rid]);
    check("round $i: both 200 with the same transaction", statuses($r) === [200, 200]
          && $r[0]['body']['transaction_id'] === $r[1]['body']['transaction_id'] && $r[0]['body']['transaction_id'] !== null,
          json_encode($r));
    check("round $i: one debit, one credit, one settlement record",
          balance($payer) === '70.00' && balance($requester) === '30.00' && (int) $txCount === 1 && $linked !== null);
}

echo "paying a request while it is declined\n";
for ($i = 0; $i < ROUNDS; $i++) {
    $requester = makeUser('0.00');
    $payer     = makeUser('100.00');
    $rid = $req->create(['payer_id' => $payer['id'], 'amount' => '30.00'], $requester)['id'];
    $r = race([['op' => 'payRequest', 'actor' => $payer['id'], 'id' => $rid], ['op' => 'declineRequest', 'actor' => $payer['id'], 'id' => $rid]]);
    $status  = value('SELECT status FROM payment_requests WHERE id = ?', [$rid]);
    $paidWon     = $r[0]['status'] === 200 && $r[1]['status'] === 409 && $status === 'paid' && balance($payer) === '70.00';
    $declinedWon = $r[1]['status'] === 200 && $r[0]['status'] === 409 && $status === 'declined' && balance($payer) === '100.00';
    check("round $i: exactly one wins and balances match", $paidWon xor $declinedWon, json_encode($r) . " status=$status");
}

echo "paying a request while the requester deletes it\n";
for ($i = 0; $i < ROUNDS; $i++) {
    $requester = makeUser('0.00');
    $payer     = makeUser('100.00');
    $rid = $req->create(['payer_id' => $payer['id'], 'amount' => '30.00'], $requester)['id'];
    $r = race([['op' => 'payRequest', 'actor' => $payer['id'], 'id' => $rid], ['op' => 'deleteRequest', 'actor' => $requester['id'], 'id' => $rid]]);
    $status = value('SELECT status FROM payment_requests WHERE id = ?', [$rid]);
    $paidWon    = $r[0]['status'] === 200 && $r[1]['status'] === 409 && $status === 'paid' && balance($payer) === '70.00';
    $deletedWon = $r[1]['status'] === 200 && $r[0]['status'] === 404 && $status === false && balance($payer) === '100.00';
    check("round $i: exactly one wins and balances match", $paidWon xor $deletedWon, json_encode($r) . " status=$status");
}

echo "idempotency keys\n";
for ($i = 0; $i < ROUNDS; $i++) {
    $a = makeUser('100.00');
    $b = makeUser('0.00');
    $job = ['op' => 'pay', 'actor' => $a['id'], 'key' => "same-key-$i-abc", 'data' => ['receiver_id' => $b['id'], 'amount' => '10.00']];
    $r = race([$job, $job, $job]);
    $ids = array_unique(array_map(fn($x) => $x['body']['id'] ?? null, $r));
    check("round $i: concurrent retries: one 201, two 200, same payment", statuses($r) === [200, 200, 201] && count($ids) === 1, json_encode($r));
    check("round $i: concurrent retries: one debit", balance($a) === '90.00' && balance($b) === '10.00');
}
$a = makeUser('100.00');
$b = makeUser('0.00');
$c = makeUser('100.00');
$first  = $tx->create(['receiver_id' => $b['id'], 'amount' => '12.5'], $a, 'replay-key-0001');
$replay = $tx->create(['receiver_id' => $b['id'], 'amount' => 12.50], $a, 'replay-key-0001');
check('replay returns the same payment (200)', !$first['replayed'] && $replay['replayed'] && $first['transaction']['id'] === $replay['transaction']['id']);
check('replay does not debit again', balance($a) === '87.50');
try {
    $tx->create(['receiver_id' => $b['id'], 'amount' => '13.00'], $a, 'replay-key-0001');
    check('same key, different payload -> 409', false);
} catch (ApiException $e) {
    check('same key, different payload -> 409', $e->getStatus() === 409 && balance($a) === '87.50');
}
$other = $tx->create(['receiver_id' => $b['id'], 'amount' => '12.50'], $c, 'replay-key-0001');
check('the same key from another user is a new payment', !$other['replayed'] && $other['transaction']['id'] !== $first['transaction']['id']);
try {
    $tx->create(['receiver_id' => $b['id'], 'amount' => '1.00'], $a, 'short');
    check('too short key -> 400', false);
} catch (ApiException $e) {
    check('too short key -> 400', $e->getStatus() === 400);
}

echo "insufficient funds\n";
$a = makeUser('5.00');
$b = makeUser('0.00');
try {
    $tx->create(['receiver_id' => $b['id'], 'amount' => '5.01'], $a);
    check('5.01 from 5.00 -> 400', false);
} catch (ApiException $e) {
    check('5.01 from 5.00 -> 400', $e->getStatus() === 400 && balance($a) === '5.00' && balance($b) === '0.00');
}
$tx->create(['receiver_id' => $b['id'], 'amount' => '5.00'], $a);
check('exactly the whole balance is allowed', balance($a) === '0.00' && balance($b) === '5.00');

echo "rollback on partial failure (transaction insert fails after the debit)\n";
$a = makeUser('100.00');
$b = makeUser('0.00');
try {
    $tx->create(['receiver_id' => $b['id'], 'amount' => '40.00', 'note' => 'FAIL_AFTER_DEBIT'], $a, 'fail-key-0001');
    check('the failure is reported', false);
} catch (PDOException $e) {
    check('the failure is reported', true);
}
check('debit and credit were rolled back', balance($a) === '100.00' && balance($b) === '0.00');
check('no transaction and no idempotency key saved',
      (int) value('SELECT COUNT(*) FROM transactions WHERE sender_id = ?', [$a['id']]) === 0
      && (int) value('SELECT COUNT(*) FROM idempotency_keys WHERE user_id = ?', [$a['id']]) === 0);
$retry = $tx->create(['receiver_id' => $b['id'], 'amount' => '40.00', 'note' => 'ok now'], $a, 'fail-key-0001');
check('the same key can be retried after a failure', !$retry['replayed'] && balance($a) === '60.00');
$rid = $req->create(['payer_id' => $a['id'], 'amount' => '10.00', 'note' => 'FAIL_AFTER_DEBIT'], $b)['id'];
try {
    $req->update($rid, ['status' => 'paid'], $a);
} catch (PDOException $e) {
}
check('a failed request payment leaves the request pending and the money in place',
      value('SELECT status FROM payment_requests WHERE id = ?', [$rid]) === 'pending' && balance($a) === '60.00');

echo "exact decimal amounts\n";
$a = makeUser('0.30');
$b = makeUser('0.00');
$tx->create(['receiver_id' => $b['id'], 'amount' => 0.1], $a);
$tx->create(['receiver_id' => $b['id'], 'amount' => 0.2], $a);
check('0.10 + 0.20 leaves exactly 0.00', balance($a) === '0.00' && balance($b) === '0.30');
foreach (['10.555', '1e3', '-5', '0', '1000000.01', 'abc', ''] as $bad) {
    try {
        Validator::amount(['amount' => $bad]);
        check("amount '$bad' rejected", false);
    } catch (ApiException $e) {
        check("amount '$bad' rejected", $e->getStatus() === 400);
    }
}
check('amount 19.99 (JSON number) kept exact', Validator::amount(['amount' => 19.99]) === '19.99');

echo "receipts (who can open a transaction)\n";
$a = makeUser('50.00');
$b = makeUser('0.00');
$stranger = makeUser('0.00');
$admin = makeUser('0.00', 'admin');
$t = $tx->create(['receiver_id' => $b['id'], 'amount' => '7.00'], $a)['transaction'];
check('receipt has a reference like NUM-00000001', (bool) preg_match('/^NUM-\d{8}$/', $t['reference']));
check('sender and receiver can open it', $tx->getByIdFor($t['id'], $a)['id'] === $t['id'] && $tx->getByIdFor($t['id'], $b)['id'] === $t['id']);
check('admin can open it', $tx->getByIdFor($t['id'], $admin)['id'] === $t['id']);
try {
    $tx->getByIdFor($t['id'], $stranger);
    check('another user gets 403', false);
} catch (ApiException $e) {
    check('another user gets 403', $e->getStatus() === 403);
}

echo "closing an account keeps history\n";
$a = makeUser('50.00');
$b = makeUser('20.00');
(new ContactDao())->insert(['user_id' => $a['id'], 'friend_user_id' => $b['id'], 'status' => 'accepted']);
$done    = $tx->create(['receiver_id' => $b['id'], 'amount' => '5.00'], $a)['transaction'];
$pending = $tx->create(['receiver_id' => $b['id'], 'amount' => '5.00', 'status' => 'pending'], $a)['transaction'];
$openReq = $req->create(['payer_id' => $b['id'], 'amount' => '3.00'], $a);
$token   = (new AuthService())->login(['email' => $b['email'], 'password' => 'secret123'])['token'];
(new UserService())->close($b['id'], $admin);
check('completed payment is kept', value('SELECT status FROM transactions WHERE id = ?', [$done['id']]) === 'completed');
check('pending payment became failed', value('SELECT status FROM transactions WHERE id = ?', [$pending['id']]) === 'failed');
check('open request became declined', value('SELECT status FROM payment_requests WHERE id = ?', [$openReq['id']]) === 'declined');
check('contacts removed', (int) value('SELECT COUNT(*) FROM contacts WHERE friend_user_id = ?', [$b['id']]) === 0);
check('balance is frozen, not deleted', balance($b) === '25.00');
try {
    $tx->create(['receiver_id' => $b['id'], 'amount' => '1.00'], $a);
    check('paying a closed account -> 409', false);
} catch (ApiException $e) {
    check('paying a closed account -> 409', $e->getStatus() === 409 && balance($a) === '45.00');
}
foreach (['login' => fn() => (new AuthService())->login(['email' => $b['email'], 'password' => 'secret123']),
          'existing token' => fn() => (new AuthService())->userFromToken($token)] as $what => $call) {
    try {
        $call();
        check("closed account: $what -> 401", false);
    } catch (ApiException $e) {
        check("closed account: $what -> 401", $e->getStatus() === 401);
    }
}
try {
    (new UserService())->close($admin['id'], $admin);
    check('admin cannot close their own account', false);
} catch (ApiException $e) {
    check('admin cannot close their own account', $e->getStatus() === 409);
}

echo PHP_EOL . "Races where the calls really ran at the same time: $overlaps/$races" . PHP_EOL;
echo ($failures === 0 ? 'All payment checks passed.' : "$failures check(s) failed.") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
