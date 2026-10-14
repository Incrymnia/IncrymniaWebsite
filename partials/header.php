<?php
declare(strict_types=1);

$site = $cfg['site'];
$nav  = [
    '#builds'   => 'Builds',
    '#services' => 'Services',
    '#process'  => 'Process',
    '#gallery'  => 'Gallery',
    '#about'    => 'About',
    '#faq'      => 'FAQ',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($site['name']) ?> | Custom Gaming PCs, Powerful Workstations and Efficient Servers</title>
    <meta name="description" content="<?= e($site['description']) ?>">
    <meta name="theme-color" content="#10050a">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($site['name']) ?> | Custom PC builds">
    <meta property="og:description" content="<?= e($site['description']) ?>">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&amp;display=swap">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header" id="site-header">
    <div class="container site-header__bar">
        <a class="logo" href="#top" aria-label="<?= e($site['name']) ?>, back to top">
            <?= logo_mark() ?>
            <span class="logo__text"><?= e($site['name']) ?></span>
        </a>

        <nav class="nav" id="site-nav" aria-label="Main">
            <ul class="nav__list">
                <?php foreach ($nav as $href => $label): ?>
                    <li><a href="<?= e($href) ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn-primary nav__cta-mobile" href="#quote">Get a quote</a>
        </nav>

        <a class="btn btn-primary site-header__cta" href="#quote">Get a quote</a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Menu">
            <span class="nav-toggle__open"><?= icon('menu') ?></span>
            <span class="nav-toggle__close"><?= icon('close') ?></span>
        </button>
    </div>
</header>

<main id="main">
