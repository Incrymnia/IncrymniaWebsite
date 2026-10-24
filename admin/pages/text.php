<?php
/** Page text: headlines and paragraphs across the home page. */

declare(strict_types=1);

/** key => [label, type (line|area|lines|paras), max, hint] */
const TEXT_GROUPS = [
    'Top of the home page' => [
        'hero_title'  => ['Main headline (H1)', 'line', 100, 'The most important line for Google. Keep "Melbourne" and what you build in it.'],
        'hero_lead'   => ['Intro paragraph', 'area', 400, ''],
        'hero_ticks'  => ['Three short selling points', 'lines', 60, 'One per line.'],
    ],
    'Builds section' => [
        'builds_title' => ['Heading', 'line', 80, ''],
        'builds_intro' => ['Intro line', 'line', 200, ''],
    ],
    'Services section' => [
        'services_title' => ['Heading', 'line', 100, ''],
        'services_intro' => ['Intro line', 'line', 220, ''],
        'promo_title'    => ['Call-out box heading', 'line', 80, ''],
        'promo_text'     => ['Call-out box text', 'area', 300, ''],
    ],
    'Process section' => [
        'process_title' => ['Heading', 'line', 80, ''],
        'process_intro' => ['Intro line', 'line', 200, ''],
    ],
    'Gallery section' => [
        'gallery_title' => ['Heading', 'line', 80, ''],
        'gallery_intro' => ['Intro line', 'line', 220, ''],
    ],
    'About section' => [
        'about_title'      => ['Heading', 'line', 120, ''],
        'about_paragraphs' => ['Paragraphs', 'paras', 1200, 'Leave a blank line between paragraphs.'],
        'brands_title'     => ['Brands heading', 'line', 80, ''],
        'standards_title'  => ['"How we work" heading', 'line', 80, ''],
    ],
    'Call-to-action band (bottom of most pages)' => [
        'cta_title' => ['Heading', 'line', 90, ''],
        'cta_text'  => ['Text', 'area', 300, ''],
    ],
    'FAQ and quote form' => [
        'faq_title'   => ['FAQ heading', 'line', 80, ''],
        'faq_intro'   => ['FAQ intro (your email address is added after it)', 'line', 160, ''],
        'quote_title' => ['Quote form heading', 'line', 80, ''],
        'quote_intro' => ['Quote form intro', 'area', 300, '{reply} becomes your reply time from Business details.'],
    ],
];

$text   = (array) $cfg['text'];
$errors = [];

$toForm = static function (string $type, mixed $value): string {
    if (is_array($value)) {
        return implode($type === 'paras' ? "\n\n" : "\n", array_map('strval', $value));
    }
    return (string) $value;
};

$form = [];
foreach (TEXT_GROUPS as $fields) {
    foreach ($fields as $key => [$label, $type]) {
        $form[$key] = $toForm($type, $text[$key] ?? '');
    }
}

if (a_is_post()) {
    a_require_post();
    $save = [];
    foreach (TEXT_GROUPS as $fields) {
        foreach ($fields as $key => [$label, $type, $max]) {
            $raw = $_POST[$key] ?? '';
            if ($type === 'lines') {
                $save[$key] = p_lines($raw, 6, $max);
                $form[$key] = implode("\n", $save[$key]);
            } elseif ($type === 'paras') {
                $save[$key] = p_paragraphs($raw, 10, $max);
                $form[$key] = implode("\n\n", $save[$key]);
            } elseif ($type === 'area') {
                $save[$key] = str_replace("\n", ' ', p_text($raw, $max));
                $form[$key] = $save[$key];
            } else {
                $save[$key] = p_line($raw, $max);
                $form[$key] = $save[$key];
            }
            if ($save[$key] === '' || $save[$key] === []) {
                $errors[] = '"' . $label . '" cannot be empty.';
            }
        }
    }
    if ($errors === []) {
        if (save_content_sections(['text' => $save])) {
            a_flash('ok', 'Page text saved.');
        } else {
            a_flash('error', 'Could not save. Check that the "storage" folder is writable.');
        }
        a_redirect('text');
    }
}

a_head('Page text', 'text');
echo a_errors_html($errors);
?>
<p class="a-intro">Edit the words used across the website. Changes go live as soon as you save. Headlines and Google titles for Gallery, Services, About, FAQ and Contact are under "Search and SEO"; the four build pages are under "Build types and pages".</p>
<form method="post" action="<?= e(a_url('text')) ?>">
    <?= admin_csrf_field() ?>
    <?php foreach (TEXT_GROUPS as $group => $fields): ?>
        <?= a_card_start($group) ?>
        <?php foreach ($fields as $key => [$label, $type, $max, $hint]): ?>
            <?php if ($type === 'line'): ?>
                <?= f_text($key, $label, $form[$key], ['max' => $max, 'hint' => $hint]) ?>
            <?php elseif ($type === 'area'): ?>
                <?= f_area($key, $label, $form[$key], ['rows' => 3, 'max' => $max, 'hint' => $hint]) ?>
            <?php elseif ($type === 'paras'): ?>
                <?= f_area($key, $label, $form[$key], ['rows' => 10, 'hint' => $hint]) ?>
            <?php else: ?>
                <?= f_area($key, $label, $form[$key], ['rows' => 4, 'hint' => $hint]) ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <?= a_card_end() ?>
    <?php endforeach; ?>
    <?= a_save_bar() ?>
</form>
<?php
a_foot();
