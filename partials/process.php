<?php declare(strict_types=1); ?>
<section class="section" id="process" aria-labelledby="process-title">
    <div class="container">
        <div class="section-head">
            <h2 id="process-title"><?= e(txt($cfg, 'process_title')) ?></h2>
            <p><?= e(txt($cfg, 'process_intro')) ?></p>
        </div>

        <ol class="steps">
            <?php foreach ($cfg['process'] as $i => $step): ?>
                <li class="step card">
                    <span class="step__num"><?= $i + 1 ?></span>
                    <h3><?= e((string) $step['title']) ?></h3>
                    <p><?= e(fill((string) $step['text'], $cfg['site'])) ?></p>
                    <span class="pill pill--low"><?= e((string) $step['tag']) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
