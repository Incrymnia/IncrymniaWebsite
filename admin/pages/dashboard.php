<?php
/** Dashboard: shortcuts, SEO health checks and server checks. */

declare(strict_types=1);

$site = $cfg['site'];
$seo  = $cfg['seo'];

$quotesFile  = STORAGE . '/quotes.jsonl';
$quoteCount  = 0;
if (is_file($quotesFile)) {
    $quoteCount = count(file($quotesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
}

$titleLen = mb_strlen((string) $seo['title']);
$descLen  = mb_strlen(plain_text(fill((string) $seo['description'], $site)));
$altMissing = 0;
foreach ($cfg['gallery'] as $g) {
    if (trim((string) ($g['alt'] ?? '')) === '') {
        $altMissing++;
    }
}
$socialCount = count(array_filter((array) ($site['social'] ?? []), static fn ($l) => is_string($l) && $l !== ''));
$siteUrl = (string) ($site['url'] ?? '');

/** [ok, label, advice, page] */
$seoChecks = [
    [str_starts_with($siteUrl, 'https://'), 'Website address (Site URL) uses https', 'Set your real address, e.g. https://incrymnia.com, under Business details.', 'site'],
    [$titleLen >= 30 && $titleLen <= 65, 'Home page title is a good length (' . $titleLen . ' characters)', 'Aim for 30 to 65 characters so Google shows all of it.', 'seo'],
    [$descLen >= 70 && $descLen <= 160, 'Home page description is a good length (' . $descLen . ' characters)', 'Aim for 70 to 160 characters.', 'seo'],
    [(string) ($seo['og_image'] ?? '') !== '', 'Custom social sharing image chosen', 'Optional. A photo of your best build makes links look great on Facebook, X and messages.', 'seo'],
    [(string) ($seo['google_verification'] ?? '') !== '', 'Google Search Console verification added', 'Needed to submit your sitemap and see which searches find you.', 'seo'],
    [empty($seo['noindex']), 'Search engines are allowed to index the site', 'Untick "Hide this site from search engines" under Search and SEO.', 'seo'],
    [$altMissing === 0, 'Every gallery photo has a description (alt text)', $altMissing . ' photo(s) have none. Alt text helps Google Images and accessibility.', 'gallery'],
    [$socialCount > 0, 'Social media profiles linked (' . $socialCount . ')', 'Linking your profiles helps Google connect them to your business name.', 'site'],
    [(bool) preg_match('/^\+[1-9][0-9]{7,14}$/', (string) ($site['phone_href'] ?? '')), 'Phone number is in international format', 'Use a format like +61485673647 (no spaces, no leading 0 after +61).', 'site'],
];

$server = [
    [PHP_VERSION_ID >= 80100, 'PHP version ' . PHP_VERSION . ' (8.1 or newer needed)'],
    [function_exists('imagecreatetruecolor'), 'Image library (GD) available for uploads'],
    [function_exists('imagewebp'), 'WebP support available (smaller, faster images)'],
    [is_writable(STORAGE), 'Storage folder is writable'],
    [is_dir(ROOT . '/uploads') && is_writable(ROOT . '/uploads'), 'Uploads folder is writable'],
    [smtp_configured((array) ($cfg['smtp'] ?? [])), 'Gmail sending is set up for quote emails'],
];

$quickLinks = [
    ['gallery', 'Add a build to the gallery', 'Upload photos of your latest builds.'],
    ['site', 'Business details', 'Phone, email, opening hours, areas you serve.'],
    ['text', 'Page text', 'Headlines and paragraphs across the site.'],
    ['builds', 'Build types and pages', 'Edit the Gaming, Study, Workstation and Server pages.'],
    ['seo', 'Search and SEO', 'Titles, descriptions, Google verification.'],
    ['quotes', 'Quote requests', $quoteCount . ' received so far.'],
];

a_head('Dashboard', 'dashboard');
?>
<div class="a-grid a-grid--3">
    <?php foreach ($quickLinks as [$page, $title, $text]): ?>
        <a class="a-card a-tile" href="<?= e(a_url($page)) ?>"><strong><?= e($title) ?></strong><span><?= e($text) ?></span></a>
    <?php endforeach; ?>
</div>

<?= a_card_start('Search engine checklist', 'Quick wins that help Google find and rank your site. Changes you save here go live straight away.') ?>
<ul class="a-checks">
    <?php foreach ($seoChecks as [$ok, $label, $advice, $target]): ?>
        <li class="<?= $ok ? 'is-ok' : 'is-todo' ?>">
            <span class="a-checks__mark" aria-hidden="true"><?= $ok ? '✓' : '!' ?></span>
            <span><strong><?= e($label) ?></strong>
                <?php if (!$ok): ?><br><span class="a-hint"><?= e($advice) ?> <a href="<?= e(a_url($target)) ?>">Fix this</a></span><?php endif; ?>
            </span>
        </li>
    <?php endforeach; ?>
</ul>
<p class="a-hint">Your sitemap for Google Search Console: <code><?= e(abs_url($cfg, 'sitemap.xml')) ?></code></p>
<?= a_card_end() ?>

<?= a_card_start('Server checks') ?>
<ul class="a-checks">
    <?php foreach ($server as [$ok, $label]): ?>
        <li class="<?= $ok ? 'is-ok' : 'is-todo' ?>"><span class="a-checks__mark" aria-hidden="true"><?= $ok ? '✓' : '!' ?></span><span><?= e($label) ?></span></li>
    <?php endforeach; ?>
</ul>
<?= a_card_end() ?>
<?php
a_foot();
