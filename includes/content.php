<?php
/**
 * Editable content layer.
 *
 * config.php holds the defaults. Anything saved from the admin panel is stored in
 * storage/content.json and merged on top. Lists (services, FAQ, gallery...) are
 * replaced as a whole; keyed groups (site, text, builds...) are merged key by key.
 */

declare(strict_types=1);

/** Sections the admin panel is allowed to change. smtp is deliberately NOT here. */
const EDITABLE_SECTIONS = [
    'site', 'seo', 'text', 'pages', 'stats', 'builds', 'gallery',
    'services', 'process', 'standards', 'brands', 'faq',
];

const DAY_KEYS  = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
const DAY_NAMES = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday',
                   'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];

function read_json(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Atomic JSON write (temp file + rename). */
function write_json(string $path, array $data): bool
{
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
        return false;
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $path)) {
        // Windows can refuse to rename over an existing file
        $ok = @copy($tmp, $path);
        @unlink($tmp);
        return $ok;
    }
    return true;
}

function content_path(): string
{
    return STORAGE . '/content.json';
}

/** Merge $over onto $base. Associative arrays merge recursively, lists are replaced. */
function merge_content(array $base, array $over): array
{
    foreach ($over as $key => $value) {
        if (is_array($value) && isset($base[$key]) && is_array($base[$key])
            && !array_is_list($value) && !array_is_list($base[$key])) {
            $base[$key] = merge_content($base[$key], $value);
        } else {
            $base[$key] = $value;
        }
    }
    return $base;
}

/** The full site configuration: config.php defaults + saved admin edits. */
function load_config(bool $fresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$fresh) {
        return $cache;
    }

    $config  = require ROOT . '/config.php';
    $content = read_json(content_path());

    // Page addresses are fixed by the code (they are not editable), so always use the built-in ones
    $slugs = [];
    foreach ((array) ($config['builds'] ?? []) as $key => $b) {
        $slugs[$key] = (string) ($b['slug'] ?? '');
    }

    foreach (EDITABLE_SECTIONS as $section) {
        if (array_key_exists($section, $content) && is_array($content[$section])) {
            $config[$section] = isset($config[$section]) && is_array($config[$section])
                ? merge_content($config[$section], $content[$section])
                : $content[$section];
        }
    }

    foreach ($slugs as $key => $slug) {
        if ($slug !== '' && isset($config['builds'][$key])) {
            $config['builds'][$key]['slug'] = $slug;
        }
    }

    $cache = $config;
    return $cache;
}

function content_updated_at(): int
{
    $times = [@filemtime(ROOT . '/config.php') ?: 0, @filemtime(content_path()) ?: 0];
    return max($times);
}

/* ----------------------------------------------------------------------
 * Saving and backups
 * -------------------------------------------------------------------- */

function backups_dir(): string
{
    return STORAGE . '/backups';
}

function backup_content(): void
{
    $path = content_path();
    if (!is_file($path)) {
        return;
    }
    $dir = backups_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
        return;
    }
    @copy($path, $dir . '/content-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.json');

    $files = glob($dir . '/content-*.json') ?: [];
    rsort($files);
    foreach (array_slice($files, 40) as $old) {
        @unlink($old);
    }
}

/** Save one or more sections. Returns false if the file could not be written. */
function save_content_sections(array $sections): bool
{
    $content = read_json(content_path());
    foreach ($sections as $name => $value) {
        if (in_array($name, EDITABLE_SECTIONS, true)) {
            $content[$name] = $value;
        }
    }
    backup_content();
    $ok = write_json(content_path(), $content);
    load_config(true);
    return $ok;
}

/* ----------------------------------------------------------------------
 * Opening hours
 * -------------------------------------------------------------------- */

function fmt_time(string $hhmm): string
{
    [$h, $m] = array_map('intval', explode(':', $hhmm) + [0, 0]);
    $suffix  = $h >= 12 ? 'pm' : 'am';
    $h12     = $h % 12 === 0 ? 12 : $h % 12;
    return $h12 . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . ' ' . $suffix;
}

/** Group consecutive days with identical hours: returns [[dayKeys[], opens|null, closes|null], ...] */
function hours_groups(array $schedule): array
{
    $groups = [];
    foreach (DAY_KEYS as $day) {
        $entry = $schedule[$day] ?? null;
        $open  = is_array($entry) && count($entry) === 2 ? (string) $entry[0] : null;
        $close = is_array($entry) && count($entry) === 2 ? (string) $entry[1] : null;
        $last  = count($groups) - 1;
        if ($last >= 0 && $groups[$last][1] === $open && $groups[$last][2] === $close) {
            $groups[$last][0][] = $day;
        } else {
            $groups[] = [[$day], $open, $close];
        }
    }
    return $groups;
}

/** Human readable hours for the page: [[label, time], ...] */
function hours_display(array $schedule): array
{
    $rows = [];
    foreach (hours_groups($schedule) as [$days, $open, $close]) {
        $count = count($days);
        if ($count === 1) {
            $label = DAY_NAMES[$days[0]];
        } elseif ($count === 2) {
            $label = DAY_NAMES[$days[0]] . ' and ' . DAY_NAMES[$days[1]];
        } else {
            $label = DAY_NAMES[$days[0]] . ' to ' . DAY_NAMES[$days[$count - 1]];
        }
        $rows[] = [$label, $open === null ? 'Closed' : fmt_time($open) . ' to ' . fmt_time((string) $close)];
    }
    return $rows;
}

/** schema.org openingHoursSpecification */
function hours_schema(array $schedule): array
{
    $specs = [];
    foreach (hours_groups($schedule) as [$days, $open, $close]) {
        if ($open === null) {
            continue;
        }
        $specs[] = [
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => array_map(static fn ($d) => DAY_NAMES[$d], $days),
            'opens'     => $open,
            'closes'    => $close,
        ];
    }
    return $specs;
}
