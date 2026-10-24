<?php
declare(strict_types=1);

$site   = $cfg['site'];
$quoteHref = $quoteHref ?? url('contact/');
$social = [];
foreach ((array) ($site['social'] ?? []) as $network => $link) {
    if (is_string($link) && preg_match('#^https?://#i', $link)) {
        $social[$network] = $link;
    }
}
$socialNames = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'x' => 'X', 'linkedin' => 'LinkedIn'];
$areas = array_values(array_filter((array) ($site['service_areas'] ?? []), 'is_string'));
?>
</main>

<footer class="site-footer">
    <div class="container footer__grid">
        <div class="footer__brand">
            <a class="logo" href="<?= e(url('')) ?>" aria-label="<?= e($site['name']) ?> home">
                <?= logo_mark() ?>
                <span class="logo__text"><?= e($site['name']) ?></span>
            </a>
            <p><?= e($site['tagline']) ?></p>
            <?php if ($areas !== []): ?>
                <p class="footer__areas">Serving <?= e(implode(', ', $areas)) ?> and surrounds.</p>
            <?php endif; ?>
            <?php if ($social !== []): ?>
                <ul class="footer__social">
                    <?php foreach ($social as $network => $link): ?>
                        <li><a href="<?= e($link) ?>" rel="me noopener" target="_blank"><?= e($socialNames[$network] ?? ucfirst((string) $network)) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <nav aria-label="Builds" class="footer__col">
            <h2>Builds</h2>
            <ul>
                <?php foreach ($cfg['builds'] as $key => $b): ?>
                    <li><a href="<?= !empty($b['slug']) ? e(url($b['slug'] . '/')) : e(url('contact/') . '?build=' . $key) ?>"><?= e($b['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <nav aria-label="Company" class="footer__col">
            <h2>Company</h2>
            <ul>
                <li><a href="<?= e(url('')) ?>">Home</a></li>
                <li><a href="<?= e(url('gallery/')) ?>">Gallery</a></li>
                <li><a href="<?= e(url('services/')) ?>">Services</a></li>
                <li><a href="<?= e(url('about/')) ?>">About us</a></li>
                <li><a href="<?= e(url('faq/')) ?>">FAQ</a></li>
                <li><a href="<?= e(url('contact/')) ?>">Contact and quote</a></li>
            </ul>
        </nav>

        <div class="footer__col">
            <h2>Contact</h2>
            <ul>
                <li><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></li>
                <li><?php if (!empty($site['phone_href'])): ?><a href="tel:<?= e($site['phone_href']) ?>"><?= e($site['phone']) ?></a><?php else: ?><?= e($site['phone']) ?><?php endif; ?></li>
                <li><?= e($site['address']) ?></li>
            </ul>
        </div>
    </div>

    <div class="container footer__base">
        <p>&copy; <?= date('Y') ?> <?= e($site['name']) ?>. All rights reserved. - ABN: 37 807 541 901</p>
        <a class="to-top" href="#top"><?= icon('up') ?> Back to top</a>
    </div>
</footer>

<script src="<?= asset('assets/js/main.js') ?>" defer></script>
</body>
</html>
