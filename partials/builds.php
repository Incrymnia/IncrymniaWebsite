<?php declare(strict_types=1); ?>
<section class="section" id="builds" aria-labelledby="builds-title">
    <div class="container">
        <div class="section-head">
            <h2 id="builds-title"><?= e(txt($cfg, 'builds_title')) ?></h2>
            <p><?= e(txt($cfg, 'builds_intro')) ?></p>
        </div>

        <div class="builds">
            <?php $n = 0; foreach ($cfg['builds'] as $key => $b): ?>
                <article class="build card<?= $n === 0 ? ' card--hot' : '' ?>">
                    <span class="icon-tile"><?= icon($b['icon']) ?></span>
                    <h3><?php if (!empty($b['slug'])): ?><a class="build__title-link" href="<?= e(url($b['slug'] . '/')) ?>"><?= e($b['label']) ?></a><?php else: ?><?= e($b['label']) ?><?php endif; ?></h3>
                    <p class="build__summary"><?= e(fill((string) $b['summary'], $cfg['site'])) ?></p>
                    <ul class="checks">
                        <?php foreach ($b['points'] as $point): ?>
                            <li><?= icon('check') ?><span><?= e((string) $point) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="build__links">
                        <?php if (!empty($b['slug'])): ?>
                            <a class="build__link" href="<?= e(url($b['slug'] . '/')) ?>">Explore <?= e((string) $b['short']) ?><span class="sr-only"> builds</span></a>
                        <?php endif; ?>
                        <a class="build__more" href="<?= e(url('contact/') . '?build=' . $key) ?>">Get a quote</a>
                    </div>
                </article>
            <?php $n++; endforeach; ?>
        </div>
    </div>
</section>
