<?php
declare(strict_types=1);

$builds = $cfg['builds'];
$labels = $cfg['profile_labels'];
$first  = array_key_first($builds);
$active = $builds[$first];

/** Indexes of the highest and second highest values. */
$rank = static function (array $values): array {
    $order = array_keys($values);
    usort($order, static fn ($a, $b) => $values[$b] <=> $values[$a] ?: $a <=> $b);
    return [$order[0], $order[1]];
};
[$top, $second] = $rank($active['profile']);
?>
<section class="hero" id="top">
    <div class="container hero__grid">

        <div class="hero__copy">
            <h1><?= e(txt($cfg, 'hero_title')) ?></h1>
            <p class="lead"><?= e(txt($cfg, 'hero_lead')) ?></p>
            <div class="hero__actions">
                <a class="btn btn-primary btn-lg" href="<?= e(url('contact/')) ?>">Get a quote</a>
                <a class="btn btn-ghost btn-lg" href="<?= e(url('gallery/')) ?>">See recent builds</a>
            </div>
            <ul class="ticks">
                <?php foreach (txt_list($cfg, 'hero_ticks') as $tick): ?>
                    <li><?= icon('check') ?> <?= e($tick) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="profile card" id="profile">
            <div class="profile__head">
                <h2 class="profile__title">Where the budget goes</h2>
                <div class="seg" role="tablist" aria-label="Build type">
                    <?php foreach ($builds as $key => $b): ?>
                        <button type="button" role="tab" class="seg__btn<?= $key === $first ? ' is-active' : '' ?>"
                                id="tab-<?= e($key) ?>" data-build="<?= e($key) ?>"
                                aria-selected="<?= $key === $first ? 'true' : 'false' ?>"
                                aria-controls="profile-panel"
                                tabindex="<?= $key === $first ? '0' : '-1' ?>"><?= e($b['short']) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="profile-panel" role="tabpanel" aria-labelledby="tab-<?= e($first) ?>">
                <p class="profile__focus" data-focus><?= e($active['focus']) ?></p>

                <div class="bars" data-bars>
                    <?php foreach ($labels as $i => $label):
                        $v     = $active['profile'][$i];
                        $state = $i === $top ? ' is-top' : ($i === $second ? ' is-second' : '');
                    ?>
                        <div class="bar<?= $state ?>" style="--v:<?= (int) $v ?>">
                            <span class="bar__col"><span class="bar__fill"></span></span>
                            <span class="bar__label"><?= e($label) ?></span>
                            <span class="sr-only"><?= e($label) ?> priority: <?= (int) $v ?> out of 100</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <ul class="rows" data-rows aria-live="polite">
                    <?php foreach ($active['rows'] as [$title, $note, $priority]): ?>
                        <li class="row">
                            <span class="row__dot row__dot--<?= e(strtolower($priority)) ?>" aria-hidden="true"></span>
                            <span class="row__text">
                                <span class="row__title"><?= e($title) ?></span>
                                <span class="row__note"><?= e($note) ?></span>
                            </span>
                            <span class="pill pill--<?= e(strtolower($priority)) ?>"><?= e($priority) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <a class="profile__cta" href="<?= e(url((string) ($active['slug'] ?? '') . '/')) ?>">Explore <?= e((string) $active['label']) ?></a>
            </div>
        </div>
    </div>

    <div class="container">
        <ul class="stats">
            <?php foreach ($cfg['stats'] as $i => $s): ?>
                <li class="stat card<?= $i === 0 ? ' card--hot' : '' ?>">
                    <span class="stat__label"><?= e($s['label']) ?></span>
                    <span class="stat__value" data-count="<?= (int) $s['value'] ?>" data-suffix="<?= e($s['suffix']) ?>"><?= (int) $s['value'] . e($s['suffix']) ?></span>
                    <span class="stat__note"><?= e($s['note']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
