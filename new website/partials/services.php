<?php declare(strict_types=1); ?>
<section class="section" id="services" aria-labelledby="services-title">
    <div class="container services">
        <div class="services__intro">
            <div class="section-head section-head--tight">
                <h2 id="services-title"><?= e(txt($cfg, 'services_title')) ?></h2>
                <p><?= e(txt($cfg, 'services_intro')) ?></p>
            </div>

            <div class="card card--ember promo">
                <h3><?= e(txt($cfg, 'promo_title')) ?></h3>
                <p><?= e(txt($cfg, 'promo_text')) ?></p>
                <a class="btn btn-light" href="#quote">Request a quote</a>
            </div>
        </div>

        <ul class="card service-list">
            <?php foreach ($cfg['services'] as $s): ?>
                <li class="service">
                    <span class="icon-tile"><?= icon((string) $s['icon']) ?></span>
                    <span class="service__text">
                        <span class="service__title"><?= e((string) $s['title']) ?></span>
                        <span class="service__desc"><?= e(fill((string) $s['text'], $cfg['site'])) ?></span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
