<?php
/** Build types (Gaming, Study, Workstation, Server) and their landing pages. */

declare(strict_types=1);

const PRIORITIES = ['High' => 'High', 'Medium' => 'Medium', 'Low' => 'Low'];

$rowFields = [
    'title'    => ['label' => 'Component', 'max' => 60, 'required' => true],
    'note'     => ['label' => 'What we do', 'max' => 140],
    'priority' => ['label' => 'Budget priority', 'type' => 'select', 'options' => PRIORITIES, 'default' => 'Medium'],
];
$sectionFields = [
    'heading' => ['label' => 'Heading', 'max' => 80, 'required' => true],
    'text'    => ['label' => 'Paragraph', 'type' => 'textarea', 'max' => 800, 'rows' => 4, 'required' => true],
];
$faqFields = [
    'q' => ['label' => 'Question', 'max' => 160, 'required' => true],
    'a' => ['label' => 'Answer', 'type' => 'textarea', 'max' => 700, 'rows' => 3, 'required' => true],
];

$labels  = (array) $cfg['profile_labels'];
$errors  = [];

/** Turn stored config for one build into form values. */
$toForm = static function (array $b): array {
    $page = (array) ($b['page'] ?? []);
    return [
        'label'   => (string) $b['label'],
        'short'   => (string) $b['short'],
        'icon'    => (string) $b['icon'],
        'summary' => (string) $b['summary'],
        'points'  => implode("\n", (array) $b['points']),
        'focus'   => (string) $b['focus'],
        'profile' => array_map('intval', (array) $b['profile']),
        'rows'    => array_map(static fn ($r) => ['title' => (string) ($r[0] ?? ''), 'note' => (string) ($r[1] ?? ''), 'priority' => (string) ($r[2] ?? 'Medium')], (array) $b['rows']),
        'slug'    => (string) ($b['slug'] ?? ''),
        'seo_title'       => (string) ($page['seo_title'] ?? ''),
        'seo_description' => (string) ($page['seo_description'] ?? ''),
        'h1'      => (string) ($page['h1'] ?? ''),
        'intro'   => implode("\n\n", (array) ($page['intro'] ?? [])),
        'sections' => array_values((array) ($page['sections'] ?? [])),
        'faq'     => array_values((array) ($page['faq'] ?? [])),
    ];
};

$form = [];
foreach ($cfg['builds'] as $key => $b) {
    $form[$key] = $toForm($b);
}

if (a_is_post()) {
    a_require_post();
    $save = [];

    foreach ($cfg['builds'] as $key => $existing) {
        $raw = $_POST['builds'][$key] ?? null;
        if (!is_array($raw)) {
            continue;
        }
        $name = (string) $existing['label'];
        $iconRaw = is_string($raw['icon'] ?? null) ? $raw['icon'] : '';
        $icon    = isset(ADMIN_ICONS[$iconRaw]) ? $iconRaw : (string) $existing['icon'];

        $profile = [];
        foreach ($labels as $i => $label) {
            $profile[] = p_int(is_array($raw['profile'] ?? null) ? ($raw['profile'][$i] ?? '') : '', 0, 100, 50);
        }

        [$rows, $e1]     = a_parse_list($raw['rows'] ?? [], $rowFields, 6);
        [$sections, $e2] = a_parse_list($raw['sections'] ?? [], $sectionFields, 8);
        [$faq, $e3]      = a_parse_list($raw['faq'] ?? [], $faqFields, 10);
        foreach (array_merge($e1, $e2, $e3) as $err) {
            $errors[] = $name . ': ' . $err;
        }

        $entry = [
            'label'   => p_line($raw['label'] ?? '', 80),
            'short'   => p_line($raw['short'] ?? '', 30),
            'icon'    => $icon,
            'summary' => str_replace("\n", ' ', p_text($raw['summary'] ?? '', 420)),
            'points'  => p_lines($raw['points'] ?? '', 6, 140),
            'focus'   => p_line($raw['focus'] ?? '', 220),
            'profile' => $profile,
            'rows'    => array_map(static fn ($r) => [$r['title'], $r['note'], $r['priority']], $rows),
            'slug'    => (string) ($existing['slug'] ?? ''),
            'page'    => [
                'seo_title'       => p_line($raw['seo_title'] ?? '', 90),
                'seo_description' => str_replace("\n", ' ', p_text($raw['seo_description'] ?? '', 260)),
                'h1'              => p_line($raw['h1'] ?? '', 110),
                'intro'           => p_paragraphs($raw['intro'] ?? '', 4, 900),
                'sections'        => $sections,
                'faq'             => $faq,
            ],
        ];
        if ($entry['label'] === '' || $entry['short'] === '' || $entry['summary'] === '') {
            $errors[] = $name . ': name, short name and summary are required.';
        }
        if ($entry['page']['h1'] === '' || $entry['page']['seo_title'] === '') {
            $errors[] = $name . ': the page headline and Google title are required.';
        }

        $save[$key] = $entry;

        // keep what was typed if validation fails
        $form[$key] = $toForm($entry);
        $form[$key]['rows']     = $rows;
        $form[$key]['sections'] = $sections;
        $form[$key]['faq']      = $faq;
    }

    if ($errors === []) {
        if (save_content_sections(['builds' => $save])) {
            a_flash('ok', 'Build types and pages saved.');
        } else {
            a_flash('error', 'Could not save. Check that the "storage" folder is writable.');
        }
        a_redirect('builds');
    }
}

a_head('Build types and pages', 'builds');
echo a_errors_html($errors);
?>
<p class="a-intro">Each build type has a card on the home page and its own page on the website, which is what lets Google show the right page when someone searches for, say, "gaming PC builder Melbourne". Write for customers in plain language.</p>
<form method="post" action="<?= e(a_url('builds')) ?>">
    <?= admin_csrf_field() ?>
    <?php $n = 0; foreach ($cfg['builds'] as $key => $existing): $f = $form[$key]; $p = 'builds[' . $key . ']'; ?>
        <details class="a-card a-acc"<?= $n === 0 || $errors !== [] ? ' open' : '' ?>>
            <summary><span><?= e($f['label']) ?></span><span class="a-hint"><?= $f['slug'] !== '' ? e('/' . $f['slug'] . '/') : '' ?></span></summary>

            <h3>Card on the home page</h3>
            <div class="a-grid a-grid--3">
                <?= f_text($p . '[label]', 'Name', $f['label'], ['max' => 80, 'required' => true]) ?>
                <?= f_text($p . '[short]', 'Short name (tabs and filters)', $f['short'], ['max' => 30, 'required' => true]) ?>
                <?= f_select($p . '[icon]', 'Icon', $f['icon'], ADMIN_ICONS) ?>
            </div>
            <?= f_area($p . '[summary]', 'Summary', $f['summary'], ['rows' => 3, 'max' => 420, 'hint' => 'Shown on the card and used by Google as a description of the service.']) ?>
            <?= f_area($p . '[points]', 'Key points (one per line, up to 6)', $f['points'], ['rows' => 4]) ?>

            <h3>"Where the budget goes" panel</h3>
            <?= f_text($p . '[focus]', 'One-line summary', $f['focus'], ['max' => 220]) ?>
            <div class="a-grid a-grid--5">
                <?php foreach ($labels as $i => $label): ?>
                    <?= f_text($p . '[profile][' . $i . ']', $label . ' (0-100)', (string) ($f['profile'][$i] ?? 50), ['type' => 'number']) ?>
                <?php endforeach; ?>
            </div>
            <?= a_repeater($p . '[rows]', $f['rows'], $rowFields, 'Component', 'Add a component', 6) ?>

            <h3>This build's own page <?= $f['slug'] !== '' ? '(' . e('/' . $f['slug'] . '/') . ')' : '' ?></h3>
            <p class="a-hint">The web address of this page is fixed, so existing links and Google results keep working.
                <?php if ($f['slug'] !== ''): ?><a href="<?= e(url($f['slug'] . '/')) ?>" target="_blank" rel="noopener">View the page</a><?php endif; ?></p>
            <?= f_text($p . '[seo_title]', 'Google title', $f['seo_title'], ['max' => 90, 'hint' => 'Aim for 30 to 65 characters. Start with what you build, then "Melbourne", then your name.']) ?>
            <?= f_area($p . '[seo_description]', 'Google description', $f['seo_description'], ['rows' => 3, 'max' => 260, 'hint' => 'Aim for 70 to 160 characters.']) ?>
            <?= f_text($p . '[h1]', 'Page headline', $f['h1'], ['max' => 110]) ?>
            <?= f_area($p . '[intro]', 'Introduction (blank line between paragraphs)', $f['intro'], ['rows' => 7]) ?>

            <h4>Content sections</h4>
            <?= a_repeater($p . '[sections]', $f['sections'], $sectionFields, 'Section', 'Add a section', 8) ?>
            <h4>Questions and answers</h4>
            <?= a_repeater($p . '[faq]', $f['faq'], $faqFields, 'Question', 'Add a question', 10) ?>
        </details>
    <?php $n++; endforeach; ?>
    <?= a_save_bar() ?>
</form>
<?php
a_foot();
