<?php
/**
 * Sends a test email using the same settings as the quote form and prints
 * exactly what happened. Run from a terminal in the project folder:
 *
 *     php tools/test-mail.php
 *
 * Command line only (it refuses to run in a browser).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$cfg = require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/includes/functions.php';

$smtp = $cfg['smtp'];
echo "Checking setup...\n";
echo '  PHP version:        ' . PHP_VERSION . "\n";
echo '  openssl extension:  ' . (extension_loaded('openssl') ? 'enabled' : 'MISSING (enable extension=openssl in php.ini)') . "\n";
echo '  mbstring extension: ' . (extension_loaded('mbstring') ? 'enabled' : 'MISSING (enable extension=mbstring in php.ini)') . "\n";
echo '  SMTP login set:     ' . (smtp_configured($smtp) ? 'yes (' . $smtp['username'] . ')' : 'NO. Create config.local.php from config.local.example.php') . "\n\n";

if (!smtp_configured($smtp)) {
    exit(1);
}

$to = $cfg['site']['quote_to'];
echo "Sending a test email to {$to} ...\n\n";

$debug = [];
$ok = deliver_mail(
    $cfg,
    $to,
    $to,
    'Website test',
    'Test email from your website',
    "If you can read this, quote requests from your website will reach this inbox.\n\nSent " . date('c') . "\n",
    $debug
);

echo "Server conversation:\n";
foreach ($debug as $line) {
    echo '  ' . $line . "\n";
}
echo "\n" . ($ok ? "SUCCESS. Check the inbox (and the Spam folder) for {$to}.\n" : "FAILED. See storage/mail.log or the conversation above.\n");
exit($ok ? 0 : 1);
