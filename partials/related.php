<?php
/** Links to the other build types (internal linking). Expects $cfg and $key. */

declare(strict_types=1);
?>
<section class="section" aria-labelledby="related-title">
    <div class="container">
        <div class="section-head">
            <h2 id="related-title">Other computers we build</h2>
        </div>
        <div class="related">
            <?php foreach ($cfg['builds'] as $otherKey => $other):
                if ($otherKey === $key || empty($other['slug'])) {
                    continue;
                } ?>
                <a class="card related__item" href="<?= e(url($other['slug'] . '/')) ?>">
                    <span class="icon-tile"><?= icon((string) $other['icon']) ?></span>
                    <span class="related__title"><?= e((string) $other['label']) ?></span>
                    <span class="related__text"><?= e(fill((string) $other['summary'], $cfg['site'])) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
