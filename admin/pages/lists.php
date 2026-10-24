<?php
/** Services, process steps, standards, FAQ, hero stats and brands. */

declare(strict_types=1);

$site = $cfg['site'];

/** Each tab: label, intro, config key, repeater fields, max items */
$tabs = [
    'services' => ['Services', 'The list next to "More than just Desktop and Server Systems".', 'services', [
        'icon'  => ['label' => 'Icon', 'type' => 'select', 'options' => ADMIN_ICONS, 'default' => 'check'],
        'title' => ['label' => 'Title', 'max' => 80, 'required' => true],
        'text'  => ['label' => 'Description', 'type' => 'textarea', 'max' => 320, 'rows' => 2, 'required' => true],
    ], 12, 'Service'],
    'process' => ['Process steps', 'The four steps. Keep it to four for the best layout.', 'process', [
        'title' => ['label' => 'Step title', 'max' => 60, 'required' => true],
        'text'  => ['label' => 'Description', 'type' => 'textarea', 'max' => 360, 'rows' => 3, 'required' => true],
        'tag'   => ['label' => 'Small label', 'max' => 50],
    ], 6, 'Step'],
    'standards' => ['How we work', 'The "How we work" panel in the About section.', 'standards', [
        'title' => ['label' => 'Title', 'max' => 60, 'required' => true],
        'text'  => ['label' => 'Description', 'type' => 'textarea', 'max' => 320, 'rows' => 3, 'required' => true],
    ], 6, 'Point'],
    'faq' => ['FAQ', 'Questions on the home page. Good answers get you found for the questions people type into Google.', 'faq', [
        'q' => ['label' => 'Question', 'max' => 160, 'required' => true],
        'a' => ['label' => 'Answer', 'type' => 'textarea', 'max' => 700, 'rows' => 3, 'required' => true],
    ], 20, 'Question'],
    'stats' => ['Headline numbers', 'The four number cards under the top of the home page. Only use figures that are true.', 'stats', [
        'label'  => ['label' => 'Label', 'max' => 50, 'required' => true],
        'value'  => ['label' => 'Number', 'type' => 'number', 'min' => 0, 'maxv' => 100000, 'default' => 0, 'class' => 'a-narrow'],
        'suffix' => ['label' => 'After the number', 'max' => 12, 'hint' => 'e.g. +, mths, days'],
        'note'   => ['label' => 'Small print', 'max' => 100],
    ], 4, 'Number'],
    'brands' => ['Brands', 'The "Parts we commonly build with" chips.', 'brands', [], 40, 'Brand'],
];

$tab = isset($_GET['s']) && is_string($_GET['s']) && isset($tabs[$_GET['s']]) ? $_GET['s'] : 'services';
[$tabLabel, $tabIntro, $configKey, $fields, $maxItems, $itemLabel] = $tabs[$tab];

$errors = [];
$items  = array_values((array) $cfg[$configKey]);

if (a_is_post()) {
    a_require_post();
    if ($configKey === 'brands') {
        $items = p_lines($_POST['brands'] ?? '', 40, 40);
        $new   = $items;
    } else {
        [$new, $errors] = a_parse_list($_POST['items'] ?? [], $fields, $maxItems);
        $items = $new;
        if ($new === []) {
            $errors[] = 'Keep at least one entry. To clear a section, edit it instead of deleting everything.';
        }
    }
    if ($errors === []) {
        if (save_content_sections([$configKey => $new])) {
            a_flash('ok', $tabLabel . ' saved.');
        } else {
            a_flash('error', 'Could not save. Check that the "storage" folder is writable.');
        }
        a_redirect('lists', ['s' => $tab]);
    }
}

a_head('Services, FAQ and lists', 'lists');
echo a_errors_html($errors);
?>
<nav class="a-tabs" aria-label="Lists">
    <?php foreach ($tabs as $key => $t): ?>
        <a href="<?= e(a_url('lists', ['s' => $key])) ?>"<?= $key === $tab ? ' class="is-active" aria-current="page"' : '' ?>><?= e($t[0]) ?></a>
    <?php endforeach; ?>
</nav>

<form method="post" action="<?= e(a_url('lists', ['s' => $tab])) ?>">
    <?= admin_csrf_field() ?>
    <?= a_card_start($tabLabel, $tabIntro) ?>
    <?php if ($configKey === 'brands'): ?>
        <?= f_area('brands', 'Brands (one per line)', implode("\n", array_map('strval', $items)), ['rows' => 12]) ?>
    <?php else: ?>
        <?= a_repeater('items', $items, $fields, $itemLabel, 'Add ' . strtolower($itemLabel), $maxItems) ?>
        <?php if (in_array($tab, ['services', 'process', 'faq'], true)): ?>
            <p class="a-hint">You can use {warranty}, {days} and {reply} in the text. They are replaced with the values from Business details.</p>
        <?php endif; ?>
    <?php endif; ?>
    <?= a_card_end() ?>
    <?= a_save_bar() ?>
</form>
<?php
a_foot();
