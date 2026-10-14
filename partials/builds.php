<?php declare(strict_types=1); ?>
<section class="section" id="builds" aria-labelledby="builds-title">
    <div class="container">
        <div class="section-head">
            <h2 id="builds-title">What we build</h2>
            <p>Four kinds of machine, each specified around how you will actually use it.</p>
        </div>

        <div class="builds">
            <?php $n = 0; foreach ($cfg['builds'] as $key => $b): ?>
                <article class="build card<?= $n === 0 ? ' card--hot' : '' ?>">
                    <span class="icon-tile"><?= icon($b['icon']) ?></span>
                    <h3><?= e($b['label']) ?></h3>
                    <p class="build__summary"><?= e($b['summary']) ?></p>
                    <ul class="checks">
                        <?php foreach ($b['points'] as $point): ?>
                            <li><?= icon('check') ?><span><?= e($point) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="build__link" href="?build=<?= e($key) ?>#quote" data-quote-link data-build="<?= e($key) ?>">Quote this build</a>
                </article>
            <?php $n++; endforeach; ?>
        </div>
    </div>
</section>
