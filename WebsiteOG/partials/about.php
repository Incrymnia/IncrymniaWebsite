<?php declare(strict_types=1); ?>
<section class="section" id="about" aria-labelledby="about-title">
    <div class="container about">
        <div class="about__copy">
            <h2 id="about-title">A small workshop that builds computers the way they should be</h2>
            <p>
                We build Gaming PCs, study and office machines, workstations and storage servers for people who want a
                system that fits how they use it, not whatever happens to be on a shelf.
            </p>
            <p>
                The heart of every computer starts with a requirement, whether you plan to game, study or require a powerhouse of a system,
                each computer is specifically designed with your intentions and requirements in mind.
            </p>
            <p>
                Every system isn't just built, its designed from the ground up, using the best possible components for your needs, and rigorously tested,
                to ensure the smoothest and most reliable operation possible.
            </p>

            <h3 class="about__brands-title">Parts we commonly build with</h3>
            <ul class="chips">
                <?php foreach ($cfg['brands'] as $brand): ?>
                    <li><?= e($brand) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card standards">
            <h3 class="standards__title">How we work</h3>
            <ul class="rows">
                <?php foreach ($cfg['standards'] as $i => $s): ?>
                    <li class="row">
                        <span class="row__dot row__dot--<?= ['high', 'medium', 'medium', 'low'][$i % 4] ?>" aria-hidden="true"></span>
                        <span class="row__text">
                            <span class="row__title"><?= e($s['title']) ?></span>
                            <span class="row__note"><?= e($s['text']) ?></span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
