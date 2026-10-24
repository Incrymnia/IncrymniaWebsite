<?php
/**
 * Create or reset the admin login from the command line:
 *
 *     php tools/admin-password.php
 *
 * Use this if you forget your password. It works on the server only (never in a browser).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT . '/includes/auth.php';

function ask(string $prompt, bool $hidden = false): string
{
    echo $prompt;
    $canHide = $hidden && DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec') && @shell_exec('stty -echo 2>/dev/null') !== null;
    $line = fgets(STDIN);
    if ($canHide) {
        @shell_exec('stty echo 2>/dev/null');
        echo PHP_EOL;
    }
    return $line === false ? '' : rtrim($line, "\r\n");
}

echo "Incrymnia admin account\n-----------------------\n";
$existing = admin_data();
$defaultUser = (string) ($existing['username'] ?? '');

$username = ask('Username' . ($defaultUser !== '' ? " [{$defaultUser}]" : '') . ': ');
if ($username === '') {
    $username = $defaultUser;
}
if (!preg_match('/^[A-Za-z0-9._-]{3,32}$/', $username)) {
    fwrite(STDERR, "Username must be 3 to 32 letters, numbers, dots, dashes or underscores.\n");
    exit(1);
}

if (DIRECTORY_SEPARATOR === '\\') {
    echo "(Note: on Windows the password is visible as you type.)\n";
}
$password = ask('New password (min ' . PASSWORD_MIN_LEN . ' characters): ', true);
$repeat   = ask('Repeat password: ', true);

if ($password !== $repeat) {
    fwrite(STDERR, "The passwords do not match.\n");
    exit(1);
}
$problem = admin_password_problem($password, $username);
if ($problem !== null) {
    fwrite(STDERR, $problem . "\n");
    exit(1);
}

if (!admin_save_credentials($username, $password)) {
    fwrite(STDERR, "Could not write storage/admin.json. Check that the storage folder is writable.\n");
    exit(1);
}
@unlink(SETUP_CODE_FILE);
admin_clear_failures();
echo "Done. Log in at /admin/ with username \"{$username}\".\n";
