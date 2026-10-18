<?php
/**
 * Incrymnia home page.
 * Requires PHP 8.1 or newer.
 */

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
$cfg = public_boot();

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
        'url'     => !empty($b['slug']) ? url($b['slug'] . '/') : url('contact/'),
        'profile' => $profile,
        'rows'    => $rows,
    ];
}

$isHome = true;
$page = [
    'kind'        => 'home',
    'nav'         => 'home',
    'has_quote'   => false,
    'title'       => (string) $cfg['seo']['title'],
    'description' => meta_trim(fill((string) $cfg['seo']['description'], $cfg['site'])),
    'url'         => abs_url($cfg, ''),
    'breadcrumbs' => [],
    'faq'         => [],            // the FAQ lives on /faq/, structured data must match visible content
    'service'     => null,
];

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/hero.php';
include __DIR__ . '/partials/builds.php';

$galleryLimit    = 3;
$galleryMoreHref = url('gallery/');
include __DIR__ . '/partials/gallery.php';

include __DIR__ . '/partials/home-about.php';
include __DIR__ . '/partials/cta.php';
?>
<script type="application/json" id="build-data"><?= json_encode($profileData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php
include __DIR__ . '/partials/footer.php';
