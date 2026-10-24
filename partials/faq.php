<?php
declare(strict_types=1);

$faqItems = $faqItems ?? $cfg['faq'];
$faqHeading = $faqHeading ?? txt($cfg, 'faq_title');
$faqIntro = $faqIntro ?? true;
?>
<section class="section" id="faq" aria-labelledby="faq-title">
    <div class="container faq">
        <div class="section-head section-head--tight">
            <h2 id="faq-title"><?= e($faqHeading) ?></h2>
            <?php if ($faqIntro): ?>
                <p><?= e(txt($cfg, 'faq_intro')) ?> <a href="mailto:<?= e($cfg['site']['email']) ?>"><?= e($cfg['site']['email']) ?></a>.</p>
            <?php endif; ?>
        </div>

        <div class="card faq__list">
            <?php foreach ($faqItems as $item): ?>
                <details class="faq__item">
                    <summary>
                        <span><?= e((string) $item['q']) ?></span>
                        <?= icon('chevron', 'icon faq__chevron') ?>
                    </summary>
                    <p><?= e(fill((string) $item['a'], $cfg['site'])) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
