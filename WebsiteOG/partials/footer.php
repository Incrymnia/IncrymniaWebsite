<?php
declare(strict_types=1);

$site = $cfg['site'];
?>
</main>

<footer class="site-footer">
    <div class="container footer__grid">
        <div class="footer__brand">
            <a class="logo" href="#top" aria-label="<?= e($site['name']) ?>, back to top">
                <?= logo_mark() ?>
                <span class="logo__text"><?= e($site['name']) ?></span>
            </a>
            <p><?= e($site['tagline']) ?></p>
        </div>

        <nav aria-label="Builds" class="footer__col">
            <h2>Builds</h2>
            <ul>
                <?php foreach ($cfg['builds'] as $key => $b): ?>
                    <li><a href="?build=<?= e($key) ?>#quote" data-quote-link data-build="<?= e($key) ?>"><?= e($b['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <nav aria-label="Company" class="footer__col">
            <h2>Company</h2>
            <ul>
                <li><a href="#services">Services</a></li>
                <li><a href="#process">How it works</a></li>
                <li><a href="#gallery">Gallery</a></li>
                <li><a href="#about">About us</a></li>
                <li><a href="#faq">FAQ</a></li>
            </ul>
        </nav>

        <div class="footer__col">
            <h2>Contact</h2>
            <ul>
                <li><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></li>
                <li><?= e($site['phone']) ?></li>
                <li><?= e($site['address']) ?></li>
            </ul>
        </div>
    </div>

    <div class="container footer__base">
        <p>&copy; <?= date('Y') ?> <?= e($site['name']) ?>. All rights reserved.</p>
        <a class="to-top" href="#top"><?= icon('up') ?> Back to top</a>
    </div>
</footer>

<script src="<?= asset('assets/js/main.js') ?>" defer></script>
</body>
</html>
