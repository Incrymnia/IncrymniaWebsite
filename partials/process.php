<?php declare(strict_types=1); ?>
<section class="section" id="process" aria-labelledby="process-title">
    <div class="container">
        <div class="section-head">
            <h2 id="process-title">How a build works</h2>
            <p>From first message to handover, in four steps.</p>
        </div>

        <ol class="steps">
            <?php foreach ($cfg['process'] as $i => $step): ?>
                <li class="step card">
                    <span class="step__num"><?= $i + 1 ?></span>
                    <h3><?= e($step['title']) ?></h3>
                    <p><?= e(fill($step['text'], $cfg['site'])) ?></p>
                    <span class="pill pill--low"><?= e($step['tag']) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
