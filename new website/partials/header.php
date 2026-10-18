<?php
/**
 * Document head + site header.
 * Expects $cfg and $page: kind (home|service|notfound), title, description, url, breadcrumbs, faq, service, noindex
 */

declare(strict_types=1);

$site    = $cfg['site'];
$seo     = $cfg['seo'];
$isHome  = ($page['kind'] ?? 'home') === 'home';
$quoteHref = !empty($page['has_quote']) ? '#quote' : url('contact/');
$current   = (string) ($page['nav'] ?? '');
$noindex = !empty($seo['noindex']) || !empty($page['noindex']);
$share   = share_image($cfg);
$locale  = (string) ($site['locale'] ?? 'en-AU');
$ogLocale = str_replace('-', '_', $locale);

$navLinks = [
    'gallery'  => 'Gallery',
    'services' => 'Services',
    'about'    => 'About',
    'faq'      => 'FAQ',
    'contact'  => 'Contact',
];
$buildLinks = [];
foreach ($cfg['builds'] as $bKey => $bItem) {
    if (!empty($bItem['slug'])) {
        $buildLinks[(string) $bItem['slug']] = (string) $bItem['label'];
    }
}

$analytics = (string) ($seo['analytics_id'] ?? '');
$analyticsOk = (bool) preg_match('/^G-[A-Z0-9]{6,14}$/', $analytics);
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e((string) $page['title']) ?></title>
    <meta name="description" content="<?= e((string) $page['description']) ?>">
    <link rel="canonical" href="<?= e((string) $page['url']) ?>">
    <link rel="alternate" hreflang="<?= e($locale) ?>" href="<?= e((string) $page['url']) ?>">
    <meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' ?>">
    <meta name="author" content="<?= e($site['name']) ?>">
    <meta name="theme-color" content="#10050a">
<?php if (!empty($seo['google_verification'])): ?>
    <meta name="google-site-verification" content="<?= e((string) $seo['google_verification']) ?>">
<?php endif; ?>
<?php if (!empty($seo['bing_verification'])): ?>
    <meta name="msvalidate.01" content="<?= e((string) $seo['bing_verification']) ?>">
<?php endif; ?>

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($site['name']) ?>">
    <meta property="og:locale" content="<?= e($ogLocale) ?>">
    <meta property="og:title" content="<?= e((string) $page['title']) ?>">
    <meta property="og:description" content="<?= e((string) $page['description']) ?>">
    <meta property="og:url" content="<?= e((string) $page['url']) ?>">
    <meta property="og:image" content="<?= e($share[0]) ?>">
    <meta property="og:image:width" content="<?= (int) $share[1] ?>">
    <meta property="og:image:height" content="<?= (int) $share[2] ?>">
    <meta property="og:image:alt" content="<?= e($site['name']) ?> custom PC builder, Melbourne">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e((string) $page['title']) ?>">
    <meta name="twitter:description" content="<?= e((string) $page['description']) ?>">
    <meta name="twitter:image" content="<?= e($share[0]) ?>">

    <link rel="icon" href="<?= e(url('favicon.ico')) ?>" sizes="48x48">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="icon" href="<?= asset('assets/img/icon-192.png') ?>" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="<?= asset('assets/img/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= asset('site.webmanifest') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&amp;display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&amp;display=swap"></noscript>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <script>document.documentElement.classList.add('js');</script>

    <script type="application/ld+json"><?= schema_json($cfg, $page) ?></script>
<?php if ($analyticsOk): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($analytics) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($analytics) ?>');</script>
<?php endif; ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header" id="site-header">
    <div class="container site-header__bar">
        <a class="logo" href="<?= e(url('')) ?>" aria-label="<?= e($site['name']) ?> home">
            <?= logo_mark() ?>
            <span class="logo__text"><?= e($site['name']) ?></span>
        </a>

        <nav class="nav" id="site-nav" aria-label="Main">
            <ul class="nav__list">
                <li><a href="<?= e(url('')) ?>"<?= $current === 'home' ? ' class="is-active" aria-current="page"' : '' ?>>Home</a></li>
                <li class="nav__item nav__item--sub">
                    <button type="button" class="nav__sub-toggle<?= $current === 'builds' ? ' is-active' : '' ?>" aria-expanded="false" aria-controls="nav-builds">
                        Builds <?= icon('chevron', 'icon nav__chevron') ?>
                    </button>
                    <ul class="nav__sub" id="nav-builds">
                        <?php foreach ($buildLinks as $slug => $label): ?>
                            <li><a href="<?= e(url($slug . '/')) ?>"<?= (string) ($page['slug'] ?? '') === $slug ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php foreach ($navLinks as $slug => $label): ?>
                    <li><a href="<?= e(url($slug . '/')) ?>"<?= $current === $slug ? ' class="is-active" aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn-primary nav__cta-mobile" href="<?= e($quoteHref) ?>">Get a quote</a>
        </nav>

        <a class="btn btn-primary site-header__cta" href="<?= e($quoteHref) ?>">Get a quote</a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Menu">
            <span class="nav-toggle__open"><?= icon('menu') ?></span>
            <span class="nav-toggle__close"><?= icon('close') ?></span>
        </button>
    </div>
</header>

<main id="main">
