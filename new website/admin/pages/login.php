<?php
/** Login form. Included by admin/index.php when nobody is logged in. */

declare(strict_types=1);

$error = '';
$username = '';

if (a_is_post()) {
    if (!admin_csrf_ok()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = p_line($_POST['username'] ?? '', 64);
        [$ok, $message] = admin_attempt_login($username, post_str('password'));
        if ($ok) {
            header('Location: ' . a_url('dashboard'));
            exit;
        }
        $error = $message;
    }
}

$locked = admin_lockout_remaining();

a_bare_head('Log in');
?>
<div class="a-login a-card">
    <h1>Incrymnia admin</h1>
    <p class="a-intro">Log in to edit your website.</p>
    <?= a_flash_html() ?>
    <?php if ($error !== ''): ?>
        <div class="a-flash a-flash--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(a_url('dashboard')) ?>" autocomplete="on">
        <?= admin_csrf_field() ?>
        <?= f_text('username', 'Username', $username, ['required' => true]) ?>
        <div class="a-field">
            <label for="f-password">Password</label>
            <input class="a-input" type="password" id="f-password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="a-btn a-btn--primary a-btn--block"<?= $locked > 0 ? ' disabled' : '' ?>>Log in</button>
    </form>
    <p class="a-hint">After 5 wrong attempts this device is locked out for 15 minutes.</p>
</div>
<?php
a_bare_foot();
