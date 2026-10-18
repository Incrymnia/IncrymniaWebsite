<?php
/** First-run setup: create the admin account. Included by admin/index.php when no account exists. */

declare(strict_types=1);

$errors = [];
$username = '';
$code = admin_setup_code();   // creates storage/setup-code.txt on first visit

if (a_is_post()) {
    $username = p_line($_POST['username'] ?? '', 32);
    $password = post_str('password');
    $confirm  = post_str('password2');
    $entered  = strtoupper(preg_replace('/\s+/', '', post_str('setup_code')) ?? '');

    $wait = admin_lockout_remaining();

    if (!admin_csrf_ok()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($wait > 0) {
        $errors[] = 'Too many attempts. Try again in ' . ceil($wait / 60) . ' minute(s).';
    } elseif ($code === '') {
        $errors[] = 'The storage folder is not writable, so the setup code could not be created. Make the "storage" folder writable and reload.';
    } elseif (!hash_equals($code, $entered)) {
        admin_record_failure();
        $errors[] = 'That setup code is not right. Open storage/setup-code.txt on your server and copy the code from there.';
    } else {
        if (!preg_match('/^[A-Za-z0-9._-]{3,32}$/', $username)) {
            $errors[] = 'Choose a username of 3 to 32 letters, numbers, dots, dashes or underscores.';
        }
        $problem = admin_password_problem($password, $username);
        if ($problem !== null) {
            $errors[] = $problem;
        }
        if ($password !== $confirm) {
            $errors[] = 'The two passwords do not match.';
        }
        if ($errors === []) {
            try {
                if (!admin_save_credentials($username, $password)) {
                    $errors[] = 'The account could not be saved. Check that the "storage" folder is writable.';
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($errors === []) {
            @unlink(SETUP_CODE_FILE);
            admin_log('Admin account created for "' . $username . '"');
            $_SESSION = [];
            a_flash('ok', 'Admin account created. Log in below.');
            header('Location: ' . url('admin/'));
            exit;
        }
    }
}

a_bare_head('Set up');
?>
<div class="a-login a-card a-login--wide">
    <h1>Set up your admin account</h1>
    <p class="a-intro">Choose a username and a strong password. The password is stored only as a salted Argon2id hash, never as plain text.</p>
    <?= a_errors_html($errors) ?>
    <div class="a-flash a-flash--info">
        <strong>Setup code:</strong> to prove this is your server, open the file <code>storage/setup-code.txt</code>
        (via your hosting file manager or FTP) and type the code that is inside it. The file is deleted when setup finishes.
    </div>
    <form method="post" action="<?= e(url('admin/')) ?>" autocomplete="off">
        <?= admin_csrf_field() ?>
        <?= f_text('setup_code', 'Setup code', '', ['required' => true]) ?>
        <?= f_text('username', 'Username', $username, ['required' => true, 'hint' => '3 to 32 characters. Avoid "admin".']) ?>
        <div class="a-field">
            <label for="f-password">Password</label>
            <input class="a-input" type="password" id="f-password" name="password" autocomplete="new-password" required minlength="<?= PASSWORD_MIN_LEN ?>" data-strength>
            <p class="a-hint" data-strength-out>At least <?= PASSWORD_MIN_LEN ?> characters. A few random words joined together works well.</p>
        </div>
        <div class="a-field">
            <label for="f-password2">Repeat password</label>
            <input class="a-input" type="password" id="f-password2" name="password2" autocomplete="new-password" required>
        </div>
        <button type="submit" class="a-btn a-btn--primary a-btn--block">Create admin account</button>
    </form>
</div>
<?php
a_bare_foot();
