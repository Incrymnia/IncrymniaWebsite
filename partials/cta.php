<?php
/** Call-to-action band that sends visitors to the contact page. */

declare(strict_types=1);
?>
<section class="section cta-section" aria-labelledby="cta-title">
    <div class="container">
        <div class="card card--ember cta">
            <div class="cta__text">
                <h2 id="cta-title"><?= e(txt($cfg, 'cta_title')) ?></h2>
                <p><?= e(txt($cfg, 'cta_text')) ?></p>
            </div>
            <a class="btn btn-light btn-lg" href="<?= e(url('contact/')) ?>">Get a free quote</a>
        </div>
    </div>
</section>
