<?php
/**
 * Emberforge – single-page site for a custom PC and server builder.
 * Requires PHP 8.1 or newer.
 */

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$cfg = require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/quote-handler.php';

/* Data for the interactive build profile (read by assets/js/main.js) */
$labels      = $cfg['profile_labels'];
$profileData = ['builds' => []];
foreach ($cfg['builds'] as $key => $b) {
    $profile = [];
    foreach ($labels as $i => $label) {
        $profile[] = ['label' => $label, 'value' => $b['profile'][$i]];
    }
    $rows = [];
    foreach ($b['rows'] as [$title, $note, $priority]) {
        $rows[] = ['title' => $title, 'note' => $note, 'priority' => $priority];
    }
    $profileData['builds'][$key] = [
        'label'   => $b['label'],
        'focus'   => $b['focus'],
        'profile' => $profile,
        'rows'    => $rows,
    ];
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/hero.php';
include __DIR__ . '/partials/builds.php';
include __DIR__ . '/partials/services.php';
include __DIR__ . '/partials/process.php';
include __DIR__ . '/partials/gallery.php';
include __DIR__ . '/partials/about.php';
include __DIR__ . '/partials/faq.php';
include __DIR__ . '/partials/quote.php';
?>
<script type="application/json" id="build-data"><?= json_encode($profileData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php
include __DIR__ . '/partials/footer.php';
