<?php
/**
 * Admin authentication.
 *
 * - Passwords are never stored. storage/admin.json holds only a salted hash:
 *   password -> HMAC-SHA256 with a secret server-side "pepper" -> Argon2id (or bcrypt),
 *   where PHP generates a unique random salt for every hash.
 * - Failed logins are rate-limited per IP address and logged.
 * - Sessions are regenerated on login, expire when idle, and are bound to the browser.
 * - Every state-changing request needs a CSRF token.
 */

declare(strict_types=1);

const ADMIN_FILE      = STORAGE . '/admin.json';
const PEPPER_FILE     = STORAGE . '/secret.key';
const ATTEMPTS_FILE   = STORAGE . '/login-attempts.json';
const SETUP_CODE_FILE = STORAGE . '/setup-code.txt';
const ADMIN_LOG_FILE  = STORAGE . '/admin-log.txt';

const LOGIN_MAX_FAILS   = 5;
const LOGIN_LOCK_SECS   = 900;     // 15 minutes
const SESSION_IDLE_SECS = 1800;    // 30 minutes
const SESSION_MAX_SECS  = 28800;   // 8 hours
const PASSWORD_MIN_LEN  = 12;

/* ----------------------------------------------------------------------
 * Session and headers
 * -------------------------------------------------------------------- */

function admin_is_https(): bool
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('incrymnia_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => admin_is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function admin_headers(): void
{
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
    if (admin_is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function admin_client_ip(): string
{
    // REMOTE_ADDR only: forwarded headers can be forged by the visitor.
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function admin_ua_hash(): string
{
    return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

/* ----------------------------------------------------------------------
 * Storage helpers
 * -------------------------------------------------------------------- */

function admin_log(string $event): void
{
    $line = date('c') . "\t" . admin_client_ip() . "\t" . preg_replace('/[\r\n\t]+/', ' ', $event) . PHP_EOL;
    @file_put_contents(ADMIN_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

function admin_log_tail(int $limit = 15): array
{
    if (!is_file(ADMIN_LOG_FILE)) {
        return [];
    }
    $lines = @file(ADMIN_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $lines = array_slice($lines, -$limit);
    $rows  = [];
    foreach (array_reverse($lines) as $line) {
        $parts = explode("\t", $line, 3);
        if (count($parts) === 3) {
            $rows[] = $parts;
        }
    }
    return $rows;
}

function admin_exists(): bool
{
    $data = read_json(ADMIN_FILE);
    return isset($data['username'], $data['hash']) && is_string($data['hash']) && $data['hash'] !== '';
}

function admin_data(): array
{
    return read_json(ADMIN_FILE);
}

/** Server-side secret mixed into every password before hashing. Created on first use. */
function admin_pepper(): string
{
    if (is_file(PEPPER_FILE)) {
        $key = trim((string) @file_get_contents(PEPPER_FILE));
        if (strlen($key) >= 32) {
            return $key;
        }
    }
    $key = bin2hex(random_bytes(32));
    if (@file_put_contents(PEPPER_FILE, $key, LOCK_EX) === false) {
        throw new RuntimeException('The storage folder is not writable, so the admin account cannot be created.');
    }
    @chmod(PEPPER_FILE, 0600);
    return $key;
}

/* ----------------------------------------------------------------------
 * Password hashing
 * -------------------------------------------------------------------- */

function admin_prehash(string $password): string
{
    // Also sidesteps bcrypt's 72-byte limit
    return base64_encode(hash_hmac('sha256', $password, admin_pepper(), true));
}

function admin_hash_algo(): string|int
{
    return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
}

function admin_hash_options(): array
{
    return defined('PASSWORD_ARGON2ID')
        ? ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1]
        : ['cost' => 12];
}

function admin_hash_password(string $password): string
{
    return password_hash(admin_prehash($password), admin_hash_algo(), admin_hash_options());
}

function admin_verify_password(string $password, string $hash): bool
{
    return password_verify(admin_prehash($password), $hash);
}

/** Returns an error message, or null if the password is acceptable. */
function admin_password_problem(string $password, string $username = ''): ?string
{
    if (strlen($password) < PASSWORD_MIN_LEN) {
        return 'Use at least ' . PASSWORD_MIN_LEN . ' characters. A few random words joined together works well.';
    }
    if (strlen($password) > 200) {
        return 'That password is too long (200 characters maximum).';
    }
    if ($username !== '' && stripos($password, $username) !== false) {
        return 'The password should not contain your username.';
    }
    if (count(array_unique(str_split(strtolower($password)))) < 6) {
        return 'That password is too repetitive. Use a wider mix of characters.';
    }
    $common = ['password1234', '123456789012', 'qwertyuiop12', 'incrymnia123', 'incrymniapc1', 'letmein12345', 'administrator'];
    if (in_array(strtolower($password), $common, true)) {
        return 'That password is too easy to guess.';
    }
    return null;
}

function admin_save_credentials(string $username, string $password): bool
{
    return write_json(ADMIN_FILE, [
        'username' => $username,
        'hash'     => admin_hash_password($password),
        'updated'  => date('c'),
    ]);
}

/* ----------------------------------------------------------------------
 * Rate limiting
 * -------------------------------------------------------------------- */

function admin_attempt_key(): string
{
    return hash('sha256', admin_client_ip());
}

/** Seconds the current IP must still wait, or 0. */
function admin_lockout_remaining(): int
{
    $all = read_json(ATTEMPTS_FILE);
    $key = admin_attempt_key();
    $now = time();
    $fails = array_values(array_filter((array) ($all[$key] ?? []), static fn ($t) => is_int($t) && $t > $now - LOGIN_LOCK_SECS));
    if (count($fails) >= LOGIN_MAX_FAILS) {
        return max(0, $fails[0] + LOGIN_LOCK_SECS - $now);
    }
    return 0;
}

function admin_record_failure(): void
{
    $all = read_json(ATTEMPTS_FILE);
    $key = admin_attempt_key();
    $now = time();
    // Drop stale entries for everyone so the file stays small
    foreach ($all as $k => $times) {
        $all[$k] = array_values(array_filter((array) $times, static fn ($t) => is_int($t) && $t > $now - LOGIN_LOCK_SECS));
        if ($all[$k] === []) {
            unset($all[$k]);
        }
    }
    $all[$key][] = $now;
    write_json(ATTEMPTS_FILE, $all);
}

function admin_clear_failures(): void
{
    $all = read_json(ATTEMPTS_FILE);
    unset($all[admin_attempt_key()]);
    write_json(ATTEMPTS_FILE, $all);
}

/* ----------------------------------------------------------------------
 * Login / logout / guards
 * -------------------------------------------------------------------- */

/** @return array{0:bool,1:string} [success, message] */
function admin_attempt_login(string $username, string $password): array
{
    $wait = admin_lockout_remaining();
    if ($wait > 0) {
        return [false, 'Too many failed attempts. Try again in ' . ceil($wait / 60) . ' minute(s).'];
    }

    $data  = admin_data();
    $known = isset($data['username'], $data['hash']);
    // Always run a verification so response time does not reveal whether the username exists
    $hash  = $known ? (string) $data['hash'] : '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
    $passOk = admin_verify_password($password, $hash);
    $userOk = $known && hash_equals((string) $data['username'], $username);

    if ($passOk && $userOk) {
        admin_clear_failures();
        if (password_needs_rehash($hash, admin_hash_algo(), admin_hash_options())) {
            admin_save_credentials((string) $data['username'], $password);
        }
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'user'  => (string) $data['username'],
            'ua'    => admin_ua_hash(),
            'login' => time(),
            'last'  => time(),
        ];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        admin_log('Login OK for "' . $data['username'] . '"');
        return [true, ''];
    }

    admin_record_failure();
    admin_log('Login FAILED');
    usleep(700000);
    return [false, 'That username or password is not right.'];
}

function admin_logged_in(): bool
{
    $s = $_SESSION['admin'] ?? null;
    if (!is_array($s)) {
        return false;
    }
    $now = time();
    if (($s['ua'] ?? '') !== admin_ua_hash()
        || $now - (int) ($s['last'] ?? 0) > SESSION_IDLE_SECS
        || $now - (int) ($s['login'] ?? 0) > SESSION_MAX_SECS) {
        unset($_SESSION['admin']);
        return false;
    }
    $_SESSION['admin']['last'] = $now;
    return true;
}

function admin_logout(bool $log = true): void
{
    if ($log && isset($_SESSION['admin'])) {
        admin_log('Logout');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => (bool) $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/* ----------------------------------------------------------------------
 * CSRF
 * -------------------------------------------------------------------- */

function admin_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
}

function admin_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(admin_csrf()) . '">';
}

function admin_csrf_ok(): bool
{
    $sent = $_POST['_csrf'] ?? '';
    return is_string($sent) && isset($_SESSION['csrf']) && hash_equals((string) $_SESSION['csrf'], $sent);
}

/* ----------------------------------------------------------------------
 * First-run setup
 * -------------------------------------------------------------------- */

/** One-time code the owner reads from storage/setup-code.txt to prove they have file access. */
function admin_setup_code(): string
{
    if (is_file(SETUP_CODE_FILE)) {
        return trim((string) @file_get_contents(SETUP_CODE_FILE));
    }
    $code = strtoupper(bin2hex(random_bytes(5)));
    if (@file_put_contents(SETUP_CODE_FILE, $code . PHP_EOL, LOCK_EX) === false) {
        return '';
    }
    return $code;
}
