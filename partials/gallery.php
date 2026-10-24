<?php
declare(strict_types=1);

$builds = $cfg['builds'];
$galleryLimit = $galleryLimit ?? 0;          // >0 shows only the first N builds (home page preview)
$galleryMoreHref = $galleryMoreHref ?? '';
$galleryType = $galleryType ?? null;        // service pages show only their own build type
$galleryHeading = $galleryHeading ?? txt($cfg, 'gallery_title');
$galleryIntro = $galleryIntro ?? txt($cfg, 'gallery_intro');
$galleryId = $galleryType === null ? 'gallery' : 'gallery-' . $galleryType;

$galleryItems = [];
foreach ($cfg['gallery'] as $i => $g) {
    if ($galleryType === null || ($g['type'] ?? '') === $galleryType) {
        $galleryItems[$i] = $g;
    }
}
if ($galleryItems === []) {
    return;
}
if ($galleryLimit > 0) {
    $galleryItems = array_slice($galleryItems, 0, $galleryLimit, true);
}
?>
<section class="section" id="<?= e($galleryId) ?>" aria-labelledby="<?= e($galleryId) ?>-title">
    <div class="container">
        <div class="gallery-head">
            <div class="section-head section-head--tight">
                <h2 id="<?= e($galleryId) ?>-title"><?= e($galleryHeading) ?></h2>
                <p><?= e($galleryIntro) ?></p>
            </div>

            <?php if ($galleryType === null && $galleryLimit === 0): ?>
                <div class="seg" role="group" aria-label="Filter builds by type" id="gallery-filter">
                    <button type="button" class="seg__btn is-active" data-filter="all" aria-pressed="true">All</button>
                    <?php foreach ($builds as $key => $b): ?>
                        <button type="button" class="seg__btn" data-filter="<?= e($key) ?>" aria-pressed="false"><?= e($b['short']) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <p class="sr-only" id="gallery-status" role="status" aria-live="polite"></p>

        <ul class="gallery" id="gallery-grid">
            <?php foreach ($galleryItems as $i => $g):
                $typeLabel = $builds[$g['type']]['short'] ?? ucfirst((string) $g['type']);
                $alt       = trim((string) ($g['alt'] ?? '')) !== '' ? (string) $g['alt'] : $g['title'] . '. ' . $g['desc'];
                $mediaId   = (string) ($g['image'] ?? '');
                $media     = $mediaId !== '' ? media_get($mediaId) : null;
                $picture   = $media !== null ? media_picture($mediaId, $alt, '(min-width: 1240px) 380px, (min-width: 720px) 33vw, 100vw') : '';
                $contain   = $media !== null && !empty($media['alpha']);

                // Older entries may still point at a plain image file
                if ($picture === '' && $mediaId !== '' && preg_match('#^assets/img/[\w./-]+$#', $mediaId) && is_file(ROOT . '/' . $mediaId)) {
                    $size    = @getimagesize(ROOT . '/' . $mediaId) ?: [800, 600];
                    $picture = '<img src="' . e(url($mediaId)) . '" width="' . (int) $size[0] . '" height="' . (int) $size[1] . '" alt="' . e($alt) . '" loading="lazy" decoding="async">';
                }
            ?>
                <li class="gallery__item" data-type="<?= e((string) $g['type']) ?>">
                    <button type="button" class="tile" aria-haspopup="dialog"
                            data-title="<?= e((string) $g['title']) ?>"
                            data-type-label="<?= e($typeLabel) ?>"
                            data-spec="<?= e((string) $g['spec']) ?>"
                            data-desc="<?= e((string) $g['desc']) ?>">
                        <span class="tile__art<?= $contain ? ' is-contain' : '' ?>">
                            <?= $picture !== '' ? $picture : build_art((string) $g['type'], (int) ($g['variant'] ?? 0), 'g' . $i) ?>
                        </span>
                        <span class="tile__meta">
                            <span class="tile__title"><?= e((string) $g['title']) ?></span>
                            <span class="pill pill--<?= $g['type'] === 'gaming' ? 'high' : ($g['type'] === 'workstation' ? 'medium' : 'low') ?>"><?= e($typeLabel) ?></span>
                        </span>
                        <span class="tile__spec"><?= e((string) $g['spec']) ?></span>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($galleryMoreHref !== ''): ?>
            <p class="gallery-more"><a class="btn btn-ghost" href="<?= e($galleryMoreHref) ?>">View the full gallery</a></p>
        <?php endif; ?>
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
