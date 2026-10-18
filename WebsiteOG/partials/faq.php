<?php declare(strict_types=1); ?>
<section class="section" id="faq" aria-labelledby="faq-title">
    <div class="container faq">
        <div class="section-head section-head--tight">
            <h2 id="faq-title">Questions we get asked</h2>
            <p>Can’t find your answer? Send the quote form or email <a href="mailto:<?= e($cfg['site']['email']) ?>"><?= e($cfg['site']['email']) ?></a>.</p>
        </div>

        <div class="card faq__list">
            <?php foreach ($cfg['faq'] as $item): ?>
                <details class="faq__item">
                    <summary>
                        <span><?= e($item['q']) ?></span>
                        <?= icon('chevron', 'icon faq__chevron') ?>
                    </summary>
                    <p><?= e(fill($item['a'], $cfg['site'])) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
