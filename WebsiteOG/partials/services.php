<?php declare(strict_types=1); ?>
<section class="section" id="services" aria-labelledby="services-title">
    <div class="container services">
        <div class="services__intro">
            <div class="section-head section-head--tight">
                <h2 id="services-title">More than just Desktop and Server Systems</h2>
                <p>Looking for a new computer but need something portable? We can also build your perfect Laptop.</p>
            </div>

            <div class="card card--ember promo">
                <h3>Not sure what you need?</h3>
                <p>Book a free consultation. We will talk through your budget, your software and your space, then recommend a build.</p>
                <a class="btn btn-light" href="#quote">Request a quote</a>
            </div>
        </div>

        <ul class="card service-list">
            <?php foreach ($cfg['services'] as $s): ?>
                <li class="service">
                    <span class="icon-tile"><?= icon($s['icon']) ?></span>
                    <span class="service__text">
                        <span class="service__title"><?= e($s['title']) ?></span>
                        <span class="service__desc"><?= e(fill($s['text'], $cfg['site'])) ?></span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
