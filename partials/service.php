<?php
/** Hero + content for a service landing page. Expects $cfg, $b, $key, $landing, $slug. */

declare(strict_types=1);

$site  = $cfg['site'];
$h1    = (string) ($landing['h1'] ?? $b['label']);
$intro = array_values(array_filter((array) ($landing['intro'] ?? []), 'is_string'));
$sections = array_values(array_filter((array) ($landing['sections'] ?? []), 'is_array'));
?>
<section class="page-hero">
    <div class="container hero__grid">
        <div class="hero__copy">
            <nav aria-label="Breadcrumb">
                <ol class="crumbs">
                    <li><a href="<?= e(url('')) ?>">Home</a></li>
                    <li aria-current="page"><?= e((string) $b['label']) ?></li>
                </ol>
            </nav>
            <h1><?= e(fill($h1, $site)) ?></h1>
            <?php foreach ($intro as $para): ?>
                <p class="lead"><?= e(fill($para, $site)) ?></p>
            <?php endforeach; ?>
            <div class="hero__actions">
                <a class="btn btn-primary btn-lg" href="#quote">Get a free quote</a>
                <a class="btn btn-ghost btn-lg" href="<?= e(url('gallery/')) ?>">See recent builds</a>
            </div>
        </div>

        <aside class="card page-hero__card" aria-label="<?= e((string) $b['label']) ?> at a glance">
            <span class="icon-tile"><?= icon((string) $b['icon']) ?></span>
            <h2><?= e((string) $b['label']) ?> at a glance</h2>
            <p><?= e(fill((string) $b['focus'], $site)) ?></p>
            <ul class="rows">
                <?php foreach ((array) $b['rows'] as [$title, $note, $priority]): ?>
                    <li class="row">
                        <span class="row__dot row__dot--<?= e(strtolower((string) $priority)) ?>" aria-hidden="true"></span>
                        <span class="row__text">
                            <span class="row__title"><?= e((string) $title) ?></span>
                            <span class="row__note"><?= e((string) $note) ?></span>
                        </span>
                        <span class="pill pill--<?= e(strtolower((string) $priority)) ?>"><?= e((string) $priority) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
    </div>
</section>

<?php if ($sections !== []): ?>
<section class="section" aria-labelledby="topics-title">
    <div class="container">
        <div class="section-head">
            <h2 id="topics-title">How we build your <?= e(strtolower((string) $b['short'])) ?> system</h2>
        </div>
        <div class="topics">
            <?php foreach ($sections as $sec): ?>
                <article class="topic card">
                    <h3><?= e(fill((string) ($sec['heading'] ?? ''), $site)) ?></h3>
                    <p><?= e(fill((string) ($sec['text'] ?? ''), $site)) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
