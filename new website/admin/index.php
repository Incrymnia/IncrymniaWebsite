<?php
/**
 * Admin panel front controller.  /admin/?p=dashboard
 */

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT . '/includes/auth.php';
require ROOT . '/includes/admin-ui.php';

admin_headers();
admin_session_start();

/* No admin account yet: first-run setup */
if (!admin_exists()) {
    require __DIR__ . '/pages/setup.php';
    exit;
}

$page = isset($_GET['p']) && is_string($_GET['p']) ? $_GET['p'] : 'dashboard';

if ($page === 'logout') {
    if (a_is_post() && admin_csrf_ok()) {
        admin_logout();
    }
    header('Location: ' . url('admin/'));
    exit;
}

if (!admin_logged_in()) {
    require __DIR__ . '/pages/login.php';
    exit;
}

if (!isset(ADMIN_NAV[$page])) {
    $page = 'dashboard';
}

$cfg = load_config();
require __DIR__ . '/pages/' . $page . '.php';
