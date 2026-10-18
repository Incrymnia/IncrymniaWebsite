<?php
/** Change password, view recent sign-in activity. */

declare(strict_types=1);

$errors = [];
$data   = admin_data();

if (a_is_post()) {
    a_require_post();
    $current = post_str('current');
    $new     = post_str('new');
    $confirm = post_str('confirm');
    $wait    = admin_lockout_remaining();

    if ($wait > 0) {
        $errors[] = 'Too many failed attempts. Try again in ' . ceil($wait / 60) . ' minute(s).';
    } elseif (!admin_verify_password($current, (string) ($data['hash'] ?? ''))) {
        admin_record_failure();
        admin_log('Password change FAILED (wrong current password)');
        $errors[] = 'Your current password is not right.';
    } else {
        $problem = admin_password_problem($new, (string) $data['username']);
        if ($problem !== null) {
            $errors[] = $problem;
        }
        if ($new !== $confirm) {
            $errors[] = 'The new passwords do not match.';
        }
        if ($new === $current) {
            $errors[] = 'Choose a password different from the current one.';
        }
        if ($errors === []) {
            if (admin_save_credentials((string) $data['username'], $new)) {
                admin_clear_failures();
                admin_log('Password changed');
                session_regenerate_id(true);
                a_flash('ok', 'Password changed.');
                a_redirect('account');
            }
            $errors[] = 'The new password could not be saved. Check that the "storage" folder is writable.';
        }
    }
}

$updated = isset($data['updated']) ? date('j M Y, g:i a', (int) strtotime((string) $data['updated'])) : 'unknown';
$log     = admin_log_tail(15);

a_head('Account and security', 'account');
echo a_errors_html($errors);
?>
<?= a_card_start('Change password', 'Signed in as ' . (string) $data['username'] . '. Password last changed: ' . $updated . '.') ?>
<form method="post" action="<?= e(a_url('account')) ?>" autocomplete="off">
    <?= admin_csrf_field() ?>
    <div class="a-field">
        <label for="f-current">Current password</label>
        <input class="a-input" type="password" id="f-current" name="current" autocomplete="current-password" required>
    </div>
    <div class="a-field">
        <label for="f-password">New password</label>
        <input class="a-input" type="password" id="f-password" name="new" autocomplete="new-password" required minlength="<?= PASSWORD_MIN_LEN ?>" data-strength>
        <p class="a-hint" data-strength-out>At least <?= PASSWORD_MIN_LEN ?> characters. A few random words joined together works well.</p>
    </div>
    <div class="a-field">
        <label for="f-confirm">Repeat new password</label>
        <input class="a-input" type="password" id="f-confirm" name="confirm" autocomplete="new-password" required>
    </div>
    <button type="submit" class="a-btn a-btn--primary">Change password</button>
</form>
<?= a_card_end() ?>

<?= a_card_start('How your password is protected') ?>
<ul class="a-list">
    <li>Your password is never stored. Only a one-way hash is saved on the server, made with <strong><?= defined('PASSWORD_ARGON2ID') ? 'Argon2id' : 'bcrypt' ?></strong> and a unique random salt.</li>
    <li>Before hashing, the password is also mixed with a secret server-side key (a "pepper") kept in a separate file.</li>
    <li>5 wrong attempts lock that device out for 15 minutes. You are logged out after 30 minutes of inactivity.</li>
    <li>Forgotten password? Run <code>php tools/admin-password.php</code> on the server, or delete <code>storage/admin.json</code> to run first-time setup again.</li>
</ul>
<?= a_card_end() ?>

<?= a_card_start('Recent sign-in activity') ?>
<?php if ($log === []): ?>
    <p class="a-hint">Nothing recorded yet.</p>
<?php else: ?>
    <table class="a-table">
        <thead><tr><th>When</th><th>IP address</th><th>Event</th></tr></thead>
        <tbody>
        <?php foreach ($log as [$when, $ip, $event]): ?>
            <tr><td><?= e(date('j M Y, g:i a', (int) strtotime($when))) ?></td><td><?= e($ip) ?></td><td><?= e($event) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?= a_card_end() ?>
<?php
a_foot();
