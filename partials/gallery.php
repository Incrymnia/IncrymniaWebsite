<?php
declare(strict_types=1);

$builds = $cfg['builds'];
?>
<section class="section" id="gallery" aria-labelledby="gallery-title">
    <div class="container">
        <div class="gallery-head">
            <div class="section-head section-head--tight">
                <h2 id="gallery-title">Recent builds</h2>
                <p>A look at the kinds of systems we put together. Select a build for the full spec.</p>
            </div>

            <div class="seg" role="group" aria-label="Filter builds by type" id="gallery-filter">
                <button type="button" class="seg__btn is-active" data-filter="all" aria-pressed="true">All</button>
                <?php foreach ($builds as $key => $b): ?>
                    <button type="button" class="seg__btn" data-filter="<?= e($key) ?>" aria-pressed="false"><?= e($b['short']) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="sr-only" id="gallery-status" role="status" aria-live="polite"></p>

        <ul class="gallery" id="gallery-grid">
            <?php foreach ($cfg['gallery'] as $i => $g):
                $typeLabel = $builds[$g['type']]['short'] ?? ucfirst($g['type']);
                $hasImage  = !empty($g['image']) && is_file(__DIR__ . '/../' . $g['image']);
            ?>
                <li class="gallery__item" data-type="<?= e($g['type']) ?>">
                    <button type="button" class="tile" aria-haspopup="dialog"
                            data-title="<?= e($g['title']) ?>"
                            data-type-label="<?= e($typeLabel) ?>"
                            data-spec="<?= e($g['spec']) ?>"
                            data-desc="<?= e($g['desc']) ?>">
                        <span class="tile__art">
                            <?php if ($hasImage): ?>
                                <img src="<?= e($g['image']) ?>" alt="<?= e($g['title'] . '. ' . $g['desc']) ?>" loading="lazy">
                            <?php else: ?>
                                <?= build_art($g['type'], (int) ($g['variant'] ?? 0), 'g' . $i) ?>
                            <?php endif; ?>
                        </span>
                        <span class="tile__meta">
                            <span class="tile__title"><?= e($g['title']) ?></span>
                            <span class="pill pill--<?= $g['type'] === 'gaming' ? 'high' : ($g['type'] === 'workstation' ? 'medium' : 'low') ?>"><?= e($typeLabel) ?></span>
                        </span>
                        <span class="tile__spec"><?= e($g['spec']) ?></span>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <dialog class="lightbox" id="lightbox" aria-labelledby="lightbox-title">
        <div class="lightbox__inner">
            <div class="lightbox__art" id="lightbox-art"></div>
            <div class="lightbox__body">
                <span class="pill" id="lightbox-type"></span>
                <h3 id="lightbox-title"></h3>
                <p class="lightbox__spec" id="lightbox-spec"></p>
                <p id="lightbox-desc"></p>
                <a class="btn btn-primary" id="lightbox-quote" href="#quote">Quote something like this</a>
            </div>
            <div class="lightbox__controls">
                <button type="button" class="icon-btn" id="lightbox-prev" aria-label="Previous build"><?= icon('left') ?></button>
                <button type="button" class="icon-btn" id="lightbox-next" aria-label="Next build"><?= icon('right') ?></button>
                <button type="button" class="icon-btn" id="lightbox-close" aria-label="Close"><?= icon('close') ?></button>
            </div>
        </div>
    </dialog>
</section>
