<?php
/**
 * Admin panel helpers: layout, flash messages, form fields, repeating lists, input cleaning.
 */

declare(strict_types=1);

const ADMIN_ICONS = [
    'gamepad' => 'Gamepad', 'cap' => 'Graduation cap', 'monitor' => 'Monitor', 'server' => 'Server',
    'wrench' => 'Wrench', 'swap' => 'Swap arrows', 'box' => 'Box', 'users' => 'People',
    'shield' => 'Shield', 'mail' => 'Mail', 'phone' => 'Phone', 'pin' => 'Location pin',
    'clock' => 'Clock', 'check' => 'Tick', 'help' => 'Question mark',
];

const ADMIN_NAV = [
    'dashboard' => 'Dashboard',
    'site'      => 'Business details',
    'seo'       => 'Search and SEO',
    'text'      => 'Page text',
    'builds'    => 'Build types and pages',
    'gallery'   => 'Gallery',
    'media'     => 'Image library',
    'lists'     => 'Services, FAQ and lists',
    'quotes'    => 'Quote requests',
    'backups'   => 'Backups',
    'account'   => 'Account and security',
];

/* ----------------------------------------------------------------------
 * URLs, redirects, flash
 * -------------------------------------------------------------------- */

function a_url(string $page = 'dashboard', array $params = []): string
{
    $query = ['p' => $page] + $params;
    return url('admin/') . '?' . http_build_query($query);
}

function a_redirect(string $page, array $params = []): never
{
    header('Location: ' . a_url($page, $params));
    exit;
}

function a_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function a_flash_html(): string
{
    $html = '';
    foreach ((array) ($_SESSION['flash'] ?? []) as [$type, $message]) {
        $html .= '<div class="a-flash a-flash--' . e((string) $type) . '" role="' . ($type === 'error' ? 'alert' : 'status') . '">' . e((string) $message) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function a_errors_html(array $errors): string
{
    if ($errors === []) {
        return '';
    }
    $html = '<div class="a-flash a-flash--error" role="alert" tabindex="-1" id="form-errors"><strong>Nothing was saved yet.</strong><ul>';
    foreach ($errors as $err) {
        $html .= '<li>' . e((string) $err) . '</li>';
    }
    return $html . '</ul></div>';
}

/** Require a POST with a valid CSRF token, otherwise stop. */
function a_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        a_redirect('dashboard');
    }
    if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        http_response_code(413);
        exit('That upload is larger than this server allows (post_max_size is ' . ini_get('post_max_size') . '). Upload smaller images, or ask your host to raise post_max_size and upload_max_filesize to 32M.');
    }
    if (!admin_csrf_ok()) {
        http_response_code(400);
        exit('Your session expired or the request was not valid. Go back, reload the page and try again.');
    }
}

function a_is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/* ----------------------------------------------------------------------
 * Layout
 * -------------------------------------------------------------------- */

function a_head(string $title, string $current): void
{
    $user = (string) ($_SESSION['admin']['user'] ?? '');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title><?= e($title) ?> | Admin</title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('admin/assets/admin.css') ?>">
    <script src="<?= asset('admin/assets/admin.js') ?>" defer></script>
</head>
<body class="a-body">
<div class="a-shell">
    <aside class="a-side">
        <a class="a-brand" href="<?= e(a_url('dashboard')) ?>">Incrymnia <span>admin</span></a>
        <nav aria-label="Admin">
            <ul>
                <?php foreach (ADMIN_NAV as $key => $label): ?>
                    <li><a href="<?= e(a_url($key)) ?>"<?= $key === $current ? ' class="is-active" aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="a-side__foot">
            <a href="<?= e(url('')) ?>" target="_blank" rel="noopener">View website</a>
            <form method="post" action="<?= e(a_url('logout')) ?>">
                <?= admin_csrf_field() ?>
                <button type="submit" class="a-link">Log out<?= $user !== '' ? ' (' . e($user) . ')' : '' ?></button>
            </form>
        </div>
    </aside>
    <main class="a-main" id="main">
        <h1 class="a-title"><?= e($title) ?></h1>
        <?= a_flash_html() ?>
<?php
}

function a_foot(): void
{
    ?>
    </main>
</div>
</body>
</html>
<?php
}

/** Bare page (login, setup) without the sidebar. */
function a_bare_head(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title><?= e($title) ?> | Admin</title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('admin/assets/admin.css') ?>">
    <script src="<?= asset('admin/assets/admin.js') ?>" defer></script>
</head>
<body class="a-body a-body--bare">
<main class="a-bare" id="main">
<?php
}

function a_bare_foot(): void
{
    ?>
</main>
</body>
</html>
<?php
}

/* ----------------------------------------------------------------------
 * Input cleaning
 * -------------------------------------------------------------------- */

function p_line(mixed $value, int $max = 200): string
{
    return mb_substr(clean_line($value), 0, $max);
}

function p_text(mixed $value, int $max = 3000): string
{
    return mb_substr(clean_text($value), 0, $max);
}

/** One entry per non-empty line. */
function p_lines(mixed $value, int $maxItems = 30, int $maxLen = 160): array
{
    $out = [];
    foreach (preg_split('/\R/u', clean_text($value)) ?: [] as $line) {
        $line = p_line($line, $maxLen);
        if ($line !== '') {
            $out[] = $line;
        }
        if (count($out) >= $maxItems) {
            break;
        }
    }
    return $out;
}

/** Paragraphs separated by blank lines. */
function p_paragraphs(mixed $value, int $maxItems = 12, int $maxLen = 1200): array
{
    $out = [];
    foreach (preg_split('/\R{2,}/u', clean_text($value)) ?: [] as $para) {
        $para = p_text(str_replace("\n", ' ', $para), $maxLen);
        if ($para !== '') {
            $out[] = $para;
        }
        if (count($out) >= $maxItems) {
            break;
        }
    }
    return $out;
}

function p_int(mixed $value, int $min, int $max, int $default): int
{
    if (!is_string($value) && !is_int($value)) {
        return $default;
    }
    $value = trim((string) $value);
    if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
        return $default;
    }
    return max($min, min($max, (int) $value));
}

/** Read $_POST[$key] as a string ('' if missing or an array). */
function post_str(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? $v : '';
}

/* ----------------------------------------------------------------------
 * Form fields
 * -------------------------------------------------------------------- */

function a_id(string $name): string
{
    return 'f-' . trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-');
}

function a_field_wrap(string $id, string $label, string $control, string $hint = '', string $class = ''): string
{
    return '<div class="a-field ' . e($class) . '"><label for="' . e($id) . '">' . e($label) . '</label>' . $control
        . ($hint !== '' ? '<p class="a-hint">' . e($hint) . '</p>' : '') . '</div>';
}

function f_text(string $name, string $label, string $value, array $o = []): string
{
    $id   = a_id($name);
    $attr = '';
    if (!empty($o['max'])) {
        $attr .= ' maxlength="' . (int) $o['max'] . '" data-counter="' . (int) $o['max'] . '"';
    }
    if (!empty($o['placeholder'])) {
        $attr .= ' placeholder="' . e((string) $o['placeholder']) . '"';
    }
    if (!empty($o['required'])) {
        $attr .= ' required';
    }
    if (!empty($o['preview'])) {
        $attr .= ' data-preview="' . e((string) $o['preview']) . '"';
    }
    $type = (string) ($o['type'] ?? 'text');
    $control = '<input class="a-input" type="' . e($type) . '" id="' . e($id) . '" name="' . e($name) . '" value="' . e($value) . '"' . $attr . '>';
    return a_field_wrap($id, $label, $control, (string) ($o['hint'] ?? ''), (string) ($o['class'] ?? ''));
}

function f_area(string $name, string $label, string $value, array $o = []): string
{
    $id   = a_id($name);
    $attr = '';
    if (!empty($o['max'])) {
        $attr .= ' maxlength="' . (int) $o['max'] . '" data-counter="' . (int) $o['max'] . '"';
    }
    if (!empty($o['preview'])) {
        $attr .= ' data-preview="' . e((string) $o['preview']) . '"';
    }
    $control = '<textarea class="a-input" id="' . e($id) . '" name="' . e($name) . '" rows="' . (int) ($o['rows'] ?? 4) . '"' . $attr . '>' . e($value) . '</textarea>';
    return a_field_wrap($id, $label, $control, (string) ($o['hint'] ?? ''), (string) ($o['class'] ?? ''));
}

function f_select(string $name, string $label, string $value, array $options, array $o = []): string
{
    $id  = a_id($name);
    $opt = '';
    foreach ($options as $key => $text) {
        $opt .= '<option value="' . e((string) $key) . '"' . ((string) $key === $value ? ' selected' : '') . '>' . e((string) $text) . '</option>';
    }
    $control = '<select class="a-input" id="' . e($id) . '" name="' . e($name) . '">' . $opt . '</select>';
    return a_field_wrap($id, $label, $control, (string) ($o['hint'] ?? ''), (string) ($o['class'] ?? ''));
}

function f_check(string $name, string $label, bool $checked, string $hint = ''): string
{
    $id = a_id($name);
    return '<div class="a-field a-field--check"><label class="a-check" for="' . e($id) . '"><input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1"'
        . ($checked ? ' checked' : '') . '><span>' . e($label) . '</span></label>'
        . ($hint !== '' ? '<p class="a-hint">' . e($hint) . '</p>' : '') . '</div>';
}

/* ----------------------------------------------------------------------
 * Repeating lists (add, remove, move up/down handled by admin.js)
 * -------------------------------------------------------------------- */

function a_rep_item(string $name, string|int $index, array $values, array $fields, string $itemLabel): string
{
    $html  = '<div class="rep-item" data-item><div class="rep-item__head"><span class="rep-item__label">' . e($itemLabel) . '</span>'
        . '<span class="rep-item__tools">'
        . '<button type="button" class="a-mini" data-move="up" aria-label="Move up">Up</button>'
        . '<button type="button" class="a-mini" data-move="down" aria-label="Move down">Down</button>'
        . '<button type="button" class="a-mini a-mini--danger" data-remove>Remove</button></span></div><div class="rep-item__body">';
    foreach ($fields as $key => $f) {
        $type  = (string) ($f['type'] ?? 'text');
        $field = $name . '[' . $index . '][' . $key . ']';
        $value = $values[$key] ?? ($f['default'] ?? '');
        $value = is_scalar($value) ? (string) $value : '';
        $opts  = ['max' => $f['max'] ?? null, 'hint' => $f['hint'] ?? '', 'class' => $f['class'] ?? '', 'rows' => $f['rows'] ?? 3, 'placeholder' => $f['placeholder'] ?? ''];
        if ($type === 'textarea') {
            $html .= f_area($field, (string) $f['label'], $value, $opts);
        } elseif ($type === 'select') {
            $html .= f_select($field, (string) $f['label'], $value, (array) $f['options'], $opts);
        } elseif ($type === 'number') {
            $html .= f_text($field, (string) $f['label'], $value, ['type' => 'number', 'class' => $f['class'] ?? ''] + $opts);
        } else {
            $html .= f_text($field, (string) $f['label'], $value, $opts);
        }
    }
    return $html . '</div></div>';
}

/**
 * @param string $name  base name, e.g. "services" or "builds[gaming][rows]"
 * @param array  $items current values (list of assoc arrays)
 * @param array  $fields key => [label, type(text|textarea|select|number), max, options, rows, hint, default, class]
 */
function a_repeater(string $name, array $items, array $fields, string $itemLabel, string $addLabel, int $max = 30): string
{
    $html = '<div class="repeater" data-repeater data-max="' . $max . '"><div class="repeater__items" data-items>';
    $i = 0;
    foreach (array_values($items) as $item) {
        $html .= a_rep_item($name, $i, is_array($item) ? $item : [], $fields, $itemLabel);
        $i++;
    }
    $html .= '</div><template data-template>' . a_rep_item($name, '__i__', [], $fields, $itemLabel) . '</template>'
        . '<button type="button" class="a-btn a-btn--ghost" data-add>' . e($addLabel) . '</button></div>';
    return $html;
}

/**
 * Clean a submitted list. Rows where every text field is empty are dropped.
 * @return array{0: array, 1: array} [items, errors]
 */
function a_parse_list(mixed $raw, array $fields, int $max = 30): array
{
    $items  = [];
    $errors = [];
    if (!is_array($raw)) {
        return [[], []];
    }
    foreach ($raw as $row) {
        if (!is_array($row)) {
            continue;
        }
        $item  = [];
        $empty = true;
        foreach ($fields as $key => $f) {
            $type = (string) ($f['type'] ?? 'text');
            $val  = $row[$key] ?? '';
            $val  = is_string($val) ? $val : '';
            if ($type === 'textarea') {
                $v = p_text($val, (int) ($f['max'] ?? 2000));
            } elseif ($type === 'select') {
                $v = isset($f['options'][$val]) ? $val : (string) ($f['default'] ?? array_key_first($f['options']));
            } elseif ($type === 'number') {
                $v = p_int($val, (int) ($f['min'] ?? 0), (int) ($f['maxv'] ?? 100000), (int) ($f['default'] ?? 0));
            } else {
                $v = p_line($val, (int) ($f['max'] ?? 200));
            }
            if (($type === 'text' || $type === 'textarea') && $v !== '') {
                $empty = false;
            }
            $item[$key] = $v;
        }
        if ($empty) {
            continue;
        }
        foreach ($fields as $key => $f) {
            if (!empty($f['required']) && ($item[$key] ?? '') === '') {
                $errors[] = '"' . $f['label'] . '" is required for every entry.';
            }
        }
        $items[] = $item;
        if (count($items) >= $max) {
            break;
        }
    }
    return [$items, array_values(array_unique($errors))];
}

function a_card_start(string $title = '', string $intro = ''): string
{
    return '<section class="a-card">' . ($title !== '' ? '<h2>' . e($title) . '</h2>' : '')
        . ($intro !== '' ? '<p class="a-intro">' . e($intro) . '</p>' : '');
}

function a_card_end(): string
{
    return '</section>';
}

function a_save_bar(string $label = 'Save changes'): string
{
    return '<div class="a-savebar"><button type="submit" class="a-btn a-btn--primary">' . e($label) . '</button></div>';
}

function a_human_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}
