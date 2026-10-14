<?php declare(strict_types=1); ?>
<section class="section" id="about" aria-labelledby="about-title">
    <div class="container about">
        <div class="about__copy">
            <h2 id="about-title">A small workshop that builds computers the way we would want ours built.</h2>
            <p>
                We build gaming PCs, study machines, workstations and storage servers for people who want a
                system that fits how they use it, not whatever happens to be on a shelf.
            </p>
            <p>
                Every build starts with a conversation. We ask what you run, how loud your room is and what you
                plan to add later, then choose parts that make sense together. You get an itemised quote with the
                reasoning behind it, not a mystery bundle.
            </p>
            <p>
                Before a system leaves the bench it is assembled, cable-managed and stress-tested. If something
                is not right, we fix it and tell you what we found.
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
