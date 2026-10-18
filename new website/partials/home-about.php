<?php
/** Short "about" teaser for the home page. */

declare(strict_types=1);

$paragraphs = txt_list($cfg, 'about_paragraphs');
?>
<section class="section" aria-labelledby="home-about-title">
    <div class="container about">
        <div class="about__copy">
            <h2 id="home-about-title"><?= e(txt($cfg, 'about_title')) ?></h2>
            <?php if ($paragraphs !== []): ?>
                <p><?= e($paragraphs[0]) ?></p>
            <?php endif; ?>
            <p><a class="btn btn-ghost" href="<?= e(url('about/')) ?>">More about <?= e((string) $cfg['site']['name']) ?></a></p>
        </div>

        <div class="card standards">
            <h3 class="standards__title"><?= e(txt($cfg, 'standards_title')) ?></h3>
            <ul class="rows">
                <?php foreach (array_slice((array) $cfg['standards'], 0, 4) as $i => $s): ?>
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
