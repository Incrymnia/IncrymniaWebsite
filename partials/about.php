<?php declare(strict_types=1); ?>
<section class="section" id="about" aria-labelledby="about-title">
    <div class="container about">
        <div class="about__copy">
            <h2 id="about-title"><?= e(txt($cfg, 'about_title')) ?></h2>
            <?php foreach (txt_list($cfg, 'about_paragraphs') as $para): ?>
                <p><?= e($para) ?></p>
            <?php endforeach; ?>

            <h3 class="about__brands-title"><?= e(txt($cfg, 'brands_title')) ?></h3>
            <ul class="chips">
                <?php foreach ($cfg['brands'] as $brand): ?>
                    <li><?= e((string) $brand) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card standards">
            <h3 class="standards__title"><?= e(txt($cfg, 'standards_title')) ?></h3>
            <ul class="rows">
                <?php foreach ($cfg['standards'] as $i => $s): ?>
                    <li class="row">
                        <span class="row__dot row__dot--<?= ['high', 'medium', 'medium', 'low'][$i % 4] ?>" aria-hidden="true"></span>
                        <span class="row__text">
                            <span class="row__title"><?= e((string) $s['title']) ?></span>
                            <span class="row__note"><?= e((string) $s['text']) ?></span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
