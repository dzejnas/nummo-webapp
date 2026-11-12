<?php
// Creates an admin account, or makes an existing account an admin with a NEW password
// (use this to rotate the password of a seeded account such as admin@nummo.com).
//
//   php backend/bin/create_admin.php you@yourdomain.com "Your Name"
//
// The password is asked for without echo, or read from NUMMO_ADMIN_PASSWORD (for scripts).
// It is never a command-line argument, so it does not end up in the shell history.
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../dao/UserDao.php';

[$script, $email, $name] = $argv + [null, null, null];
if ($email === null) {
    fwrite(STDERR, "Usage: php $script <email> [name]\n");
    exit(1);
}

$password = getenv('NUMMO_ADMIN_PASSWORD');
if (!$password) {
    echo 'New password (min. 6 characters, not shown): ';
    shell_exec('stty -echo 2>/dev/null');
    $password = trim((string) fgets(STDIN));
    shell_exec('stty echo 2>/dev/null');
    echo PHP_EOL;
}

try {
    $users = new UserService();
    $existing = (new UserDao())->getByEmail(strtolower(trim($email)));
    if ($existing === null) {
        $user = $users->create(['name' => $name ?? 'Administrator', 'email' => $email,
                                'password' => $password, 'role' => 'admin']);
        echo "Created admin #{$user['id']} {$user['email']}" . PHP_EOL;
    } else {
        $user = $users->update($existing['id'], ['password' => $password, 'role' => 'admin']);
        echo "Updated #{$user['id']} {$user['email']}: role admin, new password set" . PHP_EOL;
    }
} catch (ApiException $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
