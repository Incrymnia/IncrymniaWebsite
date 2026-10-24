<?php
/** Search and SEO settings. */

declare(strict_types=1);

$seo    = $cfg['seo'];
$errors = [];
$values = [
    'title'               => (string) $seo['title'],
    'description'         => (string) $seo['description'],
    'og_image'            => (string) ($seo['og_image'] ?? ''),
    'google_verification' => (string) ($seo['google_verification'] ?? ''),
    'bing_verification'   => (string) ($seo['bing_verification'] ?? ''),
    'analytics_id'        => (string) ($seo['analytics_id'] ?? ''),
    'noindex'             => !empty($seo['noindex']),
    'redirect_to_canonical' => !empty($seo['redirect_to_canonical']),
];
$media = media_all();
$pagesForm = [];
foreach (STATIC_PAGES as $slug) {
    $m = (array) ($cfg['pages'][$slug] ?? []);
    $pagesForm[$slug] = [
        'label' => (string) ($m['label'] ?? ucfirst($slug)), 'title' => (string) ($m['title'] ?? ''),
        'description' => (string) ($m['description'] ?? ''), 'h1' => (string) ($m['h1'] ?? ''), 'intro' => (string) ($m['intro'] ?? ''),
    ];
}

/** Accept either the bare token or the whole pasted meta tag. */
$extractToken = static function (string $raw): string {
    if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $raw, $m)) {
        $raw = $m[1];
    }
    return trim($raw);
};

if (a_is_post()) {
    a_require_post();

    $values = [
        'title'               => p_line($_POST['title'] ?? '', 90),
        'description'         => p_text($_POST['description'] ?? '', 260),
        'og_image'            => p_line($_POST['og_image'] ?? '', 80),
        'google_verification' => $extractToken(p_line($_POST['google_verification'] ?? '', 300)),
        'bing_verification'   => $extractToken(p_line($_POST['bing_verification'] ?? '', 300)),
        'analytics_id'        => strtoupper(p_line($_POST['analytics_id'] ?? '', 20)),
        'noindex'             => !empty($_POST['noindex']),
        'redirect_to_canonical' => !empty($_POST['redirect_to_canonical']),
    ];
    $values['description'] = str_replace("\n", ' ', $values['description']);

    if ($values['title'] === '') {
        $errors[] = 'The home page title is required.';
    }
    if ($values['description'] === '') {
        $errors[] = 'The home page description is required.';
    }
    if ($values['og_image'] !== '' && !isset($media[$values['og_image']])) {
        $errors[] = 'Choose a sharing image from the image library, or choose "Default".';
    }
    foreach (['google_verification' => 'Google verification code', 'bing_verification' => 'Bing verification code'] as $key => $label) {
        if ($values[$key] !== '' && !preg_match('/^[A-Za-z0-9_\-]{10,120}$/', $values[$key])) {
            $errors[] = $label . ' looks wrong. Paste just the code (letters, numbers, dashes).';
        }
    }
    if ($values['analytics_id'] !== '' && !preg_match('/^G-[A-Z0-9]{6,14}$/', $values['analytics_id'])) {
        $errors[] = 'Google Analytics ID should look like G-ABC123XYZ4.';
    }

    $pagesPost = is_array($_POST['pages'] ?? null) ? $_POST['pages'] : [];
    $pagesSave = [];
    foreach (STATIC_PAGES as $slug) {
        $row = is_array($pagesPost[$slug] ?? null) ? $pagesPost[$slug] : [];
        $entry = [
            'label'       => $pagesForm[$slug]['label'],
            'title'       => p_line($row['title'] ?? '', 90),
            'description' => str_replace("\n", ' ', p_text($row['description'] ?? '', 260)),
            'h1'          => p_line($row['h1'] ?? '', 110),
            'intro'       => str_replace("\n", ' ', p_text($row['intro'] ?? '', 260)),
        ];
        if ($entry['title'] === '' || $entry['description'] === '' || $entry['h1'] === '') {
            $errors[] = $pagesForm[$slug]['label'] . ' page: the Google title, description and headline are all required.';
        }
        $pagesSave[$slug] = $entry;
        $pagesForm[$slug] = $entry;
    }

    if ($errors === []) {
        if (save_content_sections(['seo' => $values, 'pages' => $pagesSave])) {
            a_flash('ok', 'Search settings saved.');
        } else {
            a_flash('error', 'Could not save. Check that the "storage" folder is writable.');
        }
        a_redirect('seo');
    }
}

$mediaOptions = ['' => 'Default (Incrymnia logo banner)'];
foreach ($media as $id => $m) {
    $mediaOptions[$id] = (string) ($m['name'] ?? $id);
}
$previewUrl = rtrim(preg_replace('#^https?://#', '', abs_url($cfg, '')) ?? '', '/');

a_head('Search and SEO', 'seo');
echo a_errors_html($errors);
?>
<form method="post" action="<?= e(a_url('seo')) ?>">
    <?= admin_csrf_field() ?>

    <?= a_card_start('How your home page appears on Google', 'Write these for people, not robots. Put what you do and where first: that is what searchers type.') ?>
    <?= f_text('title', 'Page title', $values['title'], ['max' => 90, 'preview' => 'title', 'hint' => 'Aim for 30 to 65 characters. Include "Incrymnia", what you build and "Melbourne".']) ?>
    <?= f_area('description', 'Description', $values['description'], ['rows' => 3, 'max' => 260, 'preview' => 'desc', 'hint' => 'Aim for 70 to 160 characters. {warranty}, {days} and {reply} are filled in automatically.']) ?>
    <div class="a-serp" aria-label="Google result preview">
        <div class="a-serp__url"><?= e($previewUrl) ?></div>
        <div class="a-serp__title" data-preview-out="title"><?= e($values['title']) ?></div>
        <div class="a-serp__desc" data-preview-out="desc"><?= e(fill($values['description'], $cfg['site'])) ?></div>
    </div>
    <p class="a-hint">The four build pages (Gaming, Office and Study, Workstations, Servers) have their own titles under "Build types and pages".</p>
    <?= a_card_end() ?>

    <?= a_card_start('Other pages', 'Each page has its own Google title and description, plus the headline shown at the top of the page.') ?>
    <?php foreach ($pagesForm as $slug => $pf): ?>
        <details class="a-acc a-sub">
            <summary><span><?= e($pf['label']) ?></span><span class="a-hint"><?= e('/' . $slug . '/') ?></span></summary>
            <?= f_text('pages[' . $slug . '][title]', 'Google title', $pf['title'], ['max' => 90, 'hint' => 'Aim for 30 to 65 characters.']) ?>
            <?= f_area('pages[' . $slug . '][description]', 'Google description', $pf['description'], ['rows' => 3, 'max' => 260, 'hint' => 'Aim for 70 to 160 characters.']) ?>
            <?= f_text('pages[' . $slug . '][h1]', 'Page headline', $pf['h1'], ['max' => 110]) ?>
            <?= f_area('pages[' . $slug . '][intro]', 'Intro line under the headline', $pf['intro'], ['rows' => 2, 'max' => 260]) ?>
        </details>
    <?php endforeach; ?>
    <?= a_card_end() ?>

    <?= a_card_start('Social sharing', 'The picture shown when your link is shared on Facebook, X, WhatsApp or iMessage. 1200 x 630 pixels works best.') ?>
    <?= f_select('og_image', 'Sharing image', $values['og_image'], $mediaOptions, ['hint' => 'Upload pictures in "Image library", then choose one here.']) ?>
    <?= a_card_end() ?>

    <?= a_card_start('Google and Bing', 'Free tools that show which searches find you. After verifying, submit your sitemap address to both.') ?>
    <?= f_text('google_verification', 'Google Search Console verification code', $values['google_verification'], ['hint' => 'In Search Console choose "HTML tag" and paste the code (or the whole tag) here.']) ?>
    <?= f_text('bing_verification', 'Bing Webmaster Tools verification code', $values['bing_verification'], ['hint' => 'Optional. Bing also powers DuckDuckGo and Yahoo results.']) ?>
    <?= f_text('analytics_id', 'Google Analytics measurement ID', $values['analytics_id'], ['placeholder' => 'G-ABC123XYZ4', 'hint' => 'Optional. Leave blank to run without analytics.']) ?>
    <p class="a-hint">Sitemap address: <code><?= e(abs_url($cfg, 'sitemap.xml')) ?></code></p>
    <?= a_card_end() ?>

    <?= a_card_start('Advanced') ?>
    <?= f_check('redirect_to_canonical', 'Redirect other addresses (www, http) to my Site URL', $values['redirect_to_canonical'], 'Turn on only after your SSL certificate works and the Site URL is correct. A wrong Site URL with this ticked would make the site unreachable.') ?>
    <?= f_check('noindex', 'Hide this site from search engines', $values['noindex'], 'Only tick while the site is under construction. Leaving this on removes you from Google.') ?>
    <?= a_card_end() ?>

    <?= a_save_bar() ?>
</form>
<?php
a_foot();
