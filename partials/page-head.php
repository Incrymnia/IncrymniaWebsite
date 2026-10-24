<?php
/** Heading band for the static pages. Expects $cfg and $staticSlug. */

declare(strict_types=1);

$meta = (array) ($cfg['pages'][$staticSlug] ?? []);
?>
<section class="page-head">
    <div class="container">
        <nav aria-label="Breadcrumb">
            <ol class="crumbs">
                <li><a href="<?= e(url('')) ?>">Home</a></li>
                <li aria-current="page"><?= e((string) ($meta['label'] ?? '')) ?></li>
            </ol>
        </nav>
        <h1><?= e(fill((string) ($meta['h1'] ?? ''), $cfg['site'])) ?></h1>
        <?php if (!empty($meta['intro'])): ?>
            <p class="lead"><?= e(fill((string) $meta['intro'], $cfg['site'])) ?></p>
        <?php endif; ?>
    </div>
</section>
