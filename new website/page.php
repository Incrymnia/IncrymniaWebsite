<?php
/**
 * Every page except the home page:
 *   build pages   /gaming-pcs/  /office-study-pcs/  /workstations/  /servers/
 *   static pages  /gallery/  /services/  /about/  /faq/  /contact/
 * Reached through the rewrite rules in .htaccess (or router.php on the PHP dev server).
 */

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
$cfg  = public_boot();
$site = $cfg['site'];

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? strtolower($_GET['slug']) : '';
$map  = build_slugs($cfg);

/* Old addresses keep working: permanent redirect to the new one */
if (isset(LEGACY_REDIRECTS[$slug])) {
    header('Location: ' . url(LEGACY_REDIRECTS[$slug] . '/'), true, 301);
    exit;
}

$fillText = static fn (string $s): string => fill($s, $site);
$isHome   = false;

/* ------------------------------------------------------------------
 * Static pages
 * ---------------------------------------------------------------- */
if (in_array($slug, STATIC_PAGES, true)) {
    $meta      = (array) ($cfg['pages'][$slug] ?? []);
    $staticSlug = $slug;
    $hasQuote  = $slug === 'contact';

    if ($hasQuote) {
        require ROOT . '/includes/quote-handler.php';
    }

    $page = [
        'kind'        => 'static',
        'nav'         => $slug,
        'slug'        => $slug,
        'schema_type' => STATIC_SCHEMA_TYPES[$slug] ?? 'WebPage',
        'has_quote'   => $hasQuote,
        'title'       => $fillText((string) ($meta['title'] ?? $slug)),
        'description' => meta_trim($fillText((string) ($meta['description'] ?? ''))),
        'url'         => abs_url($cfg, $slug . '/'),
        'breadcrumbs' => [['Home', abs_url($cfg, '')], [(string) ($meta['label'] ?? $slug), abs_url($cfg, $slug . '/')]],
        'faq'         => $slug === 'faq' ? $cfg['faq'] : [],
        'service'     => null,
    ];

    include __DIR__ . '/partials/header.php';
    include __DIR__ . '/partials/page-head.php';

    switch ($slug) {
        case 'gallery':
            $galleryHeading = 'All builds';
            $galleryIntro   = 'Select any build to see the full specification.';
            include __DIR__ . '/partials/gallery.php';
            break;
        case 'services':
            include __DIR__ . '/partials/services.php';
            include __DIR__ . '/partials/process.php';
            break;
        case 'about':
            include __DIR__ . '/partials/about.php';
            break;
        case 'faq':
            include __DIR__ . '/partials/faq.php';
            break;
        case 'contact':
            include __DIR__ . '/partials/quote.php';
            break;
    }

    if ($slug !== 'contact') {
        include __DIR__ . '/partials/cta.php';
    }
    include __DIR__ . '/partials/footer.php';
    exit;
}

/* ------------------------------------------------------------------
 * Unknown address: a real 404 so search engines drop it
 * ---------------------------------------------------------------- */
if (!isset($map[$slug])) {
    http_response_code(404);
    $page = [
        'kind'        => 'notfound',
        'nav'         => '',
        'has_quote'   => false,
        'noindex'     => true,
        'title'       => 'Page not found | ' . $site['name'],
        'description' => 'That page could not be found.',
        'url'         => abs_url($cfg, ''),
        'breadcrumbs' => [],
        'faq'         => [],
        'service'     => null,
    ];
    include __DIR__ . '/partials/header.php';
    ?>
    <section class="section notfound">
        <div class="container">
            <h1>That page could not be found</h1>
            <p>The link may be old or mistyped. Here are the places most people are looking for:</p>
            <ul class="notfound__links">
                <li><a href="<?= e(url('')) ?>">Home</a></li>
                <?php foreach ($cfg['builds'] as $b): if (!empty($b['slug'])): ?>
                    <li><a href="<?= e(url($b['slug'] . '/')) ?>"><?= e($b['label']) ?></a></li>
                <?php endif; endforeach; ?>
                <li><a href="<?= e(url('gallery/')) ?>">Gallery</a></li>
                <li><a href="<?= e(url('contact/')) ?>">Get a quote</a></li>
            </ul>
        </div>
    </section>
    <?php
    include __DIR__ . '/partials/footer.php';
    exit;
}

/* ------------------------------------------------------------------
 * Build page (gaming, office and study, workstations, servers)
 * ---------------------------------------------------------------- */
$key     = $map[$slug];
$b       = $cfg['builds'][$key];
$landing = (array) ($b['page'] ?? []);

require ROOT . '/includes/quote-handler.php';

$page = [
    'kind'        => 'service',
    'nav'         => 'builds',
    'slug'        => $slug,
    'has_quote'   => true,
    'title'       => $fillText((string) ($landing['seo_title'] ?? ($b['label'] . ' | ' . $site['name']))),
    'description' => meta_trim($fillText((string) ($landing['seo_description'] ?? $b['summary']))),
    'url'         => abs_url($cfg, $slug . '/'),
    'breadcrumbs' => [['Home', abs_url($cfg, '')], [(string) $b['label'], abs_url($cfg, $slug . '/')]],
    'faq'         => (array) ($landing['faq'] ?? []),
    'service'     => $key,
];

$quotePreselect = $key;
$galleryType    = $key;
$galleryHeading = 'Recent ' . strtolower((string) $b['label']) . ' we have built';
$galleryIntro   = 'A selection of ' . strtolower((string) $b['label']) . ' from our workshop. Select a build for the full spec.';
$faqItems       = (array) ($landing['faq'] ?? []);
$faqHeading     = (string) $b['label'] . ': common questions';
$faqIntro       = false;

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/service.php';
include __DIR__ . '/partials/gallery.php';
if ($faqItems !== []) {
    include __DIR__ . '/partials/faq.php';
}
include __DIR__ . '/partials/related.php';
include __DIR__ . '/partials/quote.php';
include __DIR__ . '/partials/footer.php';
