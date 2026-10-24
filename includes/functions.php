<?php
declare(strict_types=1);

require_once __DIR__ . '/smtp.php';

/* ----------------------------------------------------------------------
 * Output helpers
 * -------------------------------------------------------------------- */

/** Escape a string for HTML output. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL path prefix when the site lives in a sub-folder ('' when it is at the domain root). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $base = '';
        $root = str_replace('\\', '/', (string) (realpath(dirname(__DIR__)) ?: dirname(__DIR__)));
        $doc  = isset($_SERVER['DOCUMENT_ROOT']) ? (string) (realpath((string) $_SERVER['DOCUMENT_ROOT']) ?: '') : '';
        $doc  = rtrim(str_replace('\\', '/', $doc), '/');
        if ($doc !== '' && stripos($root, $doc) === 0) {
            $base = rtrim(substr($root, strlen($doc)), '/');
        }
    }
    return $base;
}

/** Root-relative URL for a path inside the site, e.g. url('gaming-pcs/'). */
function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** Versioned, escaped asset URL (cache-busts when the file changes). */
function asset(string $path): string
{
    $file    = dirname(__DIR__) . '/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return e(url($path) . '?v=' . $version);
}

/** Replace {warranty} and {days} placeholders in content strings. */
function fill(string $text, array $site): string
{
    return strtr($text, [
        '{warranty}' => (string) $site['warranty_months'],
        '{reply}'    => (string) ($site['reply_time'] ?? 'one business day'),
        '{days}'     => (string) $site['build_days'],
    ]);
}

/** Page text from the admin-editable 'text' section, with {warranty}/{days}/{reply} filled in. */
function txt(array $cfg, string $key, string $default = ''): string
{
    $value = $cfg['text'][$key] ?? $default;
    return is_string($value) ? fill($value, $cfg['site']) : $default;
}

/** A list-type text setting (e.g. hero ticks, about paragraphs). */
function txt_list(array $cfg, string $key): array
{
    $value = $cfg['text'][$key] ?? [];
    $out = [];
    foreach ((array) $value as $item) {
        if (is_string($item) && trim($item) !== '') {
            $out[] = fill($item, $cfg['site']);
        }
    }
    return $out;
}

/** Read a previously submitted value. */
function old(array $old, string $key, string $default = ''): string
{
    return isset($old[$key]) && is_string($old[$key]) ? $old[$key] : $default;
}

/* ----------------------------------------------------------------------
 * Security helpers
 * -------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/** Single line of text with control characters removed. */
function clean_line(mixed $value): string
{
    $value = is_string($value) ? $value : '';
    $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
    return trim($value);
}

/** Multi-line text with control characters removed (newlines kept). */
function clean_text(mixed $value): string
{
    $value = is_string($value) ? $value : '';
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[^\P{C}\n]+/u', '', $value) ?? '';
    return trim($value);
}

/* ----------------------------------------------------------------------
 * Quote storage and email
 * -------------------------------------------------------------------- */

function save_quote(array $record): bool
{
    $dir = dirname(__DIR__) . '/storage';
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
        return false;
    }
    $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        return false;
    }
    return @file_put_contents($dir . '/quotes.jsonl', $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
}

/** Append a line to storage/mail.log (private, never shown to visitors). */
function log_mail(string $message): void
{
    $dir = dirname(__DIR__) . '/storage';
    if (is_dir($dir)) {
        @file_put_contents($dir . '/mail.log', '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

/** True when SMTP credentials have been filled in (config.local.php). */
function smtp_configured(array $smtp): bool
{
    return !empty($smtp['enabled'])
        && (string) ($smtp['username'] ?? '') !== ''
        && (string) ($smtp['password'] ?? '') !== '';
}

/**
 * Send a plain-text email. Uses Gmail/SMTP when configured, otherwise falls
 * back to PHP mail() (which usually does nothing on a local machine).
 * Returns the SmtpMailer transcript through $debug for the test tool.
 */
function deliver_mail(array $cfg, string $to, string $replyEmail, string $replyName, string $subject, string $body, ?array &$debug = null): bool
{
    $site = $cfg['site'];
    $smtp = $cfg['smtp'] ?? [];

    if (smtp_configured($smtp)) {
        $mailer = new SmtpMailer($smtp);
        // Gmail only lets you send as the account you log in with, so From = that account.
        $ok = $mailer->send(
            (string) $smtp['username'],
            clean_line($site['name']),
            $to,
            $replyEmail,
            $replyName,
            $subject,
            $body
        );
        $debug = $mailer->transcript;
        if (!$ok) {
            log_mail('SMTP failed: ' . $mailer->error);
        }
        return $ok;
    }

    log_mail('SMTP is not configured (see config.local.php). Falling back to PHP mail(), which often fails on local machines.');

    $headers = [
        'From: ' . clean_line($site['name']) . ' <' . clean_line($site['mail_from']) . '>',
        'Reply-To: ' . clean_line($replyEmail),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];
    $encodedSubject = '=?UTF-8?B?' . base64_encode(clean_line($subject)) . '?=';
    $ok = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    if (!$ok) {
        log_mail('PHP mail() failed.');
    }
    return $ok;
}

function send_quote_mail(array $cfg, array $r, string $buildLabel): bool
{
    $budgets   = $cfg['quote']['budgets'];
    $timelines = $cfg['quote']['timelines'];
    $extras    = $cfg['quote']['extras'];

    $shortName = mb_substr($r['name'], 0, 40);
    $subject   = 'New quote request: ' . $buildLabel . ' from ' . $shortName . ' (' . $r['ref'] . ')';

    $extraLabels = [];
    foreach ($r['extras'] as $key) {
        $extraLabels[] = $extras[$key] ?? $key;
    }

    $body  = "New quote request {$r['ref']}\n";
    $body .= str_repeat('-', 40) . "\n";
    $body .= "Name:      {$r['name']}\n";
    $body .= "Email:     {$r['email']}\n";
    $body .= 'Phone:     ' . ($r['phone'] !== '' ? $r['phone'] : '-') . "\n";
    $body .= "Build:     {$buildLabel}\n";
    $body .= 'Budget:    ' . ($budgets[$r['budget']] ?? '-') . "\n";
    $body .= 'Timeline:  ' . ($timelines[$r['timeline']] ?? '-') . "\n";
    $body .= 'Extras:    ' . ($extraLabels ? implode(', ', $extraLabels) : '-') . "\n\n";
    $body .= "Details\n" . str_repeat('-', 40) . "\n{$r['message']}\n\n";
    $body .= "Reply to this email to answer the customer directly.\n";

    return deliver_mail($cfg, (string) $cfg['site']['quote_to'], $r['email'], $r['name'], $subject, $body);
}

/* ----------------------------------------------------------------------
 * Icons (24x24, stroke based)
 * -------------------------------------------------------------------- */

function icon(string $name, string $class = 'icon'): string
{
    static $paths = [
        'gamepad' => '<path d="M7 7h10a4 4 0 0 1 4 4v3a3 3 0 0 1-5.2 2L14.500 15h-5L8.200 16A3 3 0 0 1 3 14v-3a4 4 0 0 1 4-4z"/><path d="M8 10v4M6 12h4"/><circle cx="15.500" cy="11" r=".8"/><circle cx="17.500" cy="13" r=".8"/>',
        'cap'     => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11.500V16c0 1.500 2.700 3 6 3s6-1.500 6-3v-4.500"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'server'  => '<rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 7h.01M7 17h.01"/>',
        'wrench'  => '<path d="M14.700 6.300a4 4 0 0 0-5.400 5.400L3 18l3 3 6.300-6.300a4 4 0 0 0 5.400-5.400l-2.600 2.600-2.400-.6-.6-2.400z"/>',
        'swap'    => '<path d="M4 7h13l-3-3M20 17H7l3 3"/>',
        'box'     => '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'users'   => '<circle cx="9" cy="8" r="3.500"/><path d="M2.500 20c.5-3.500 3.200-5.500 6.500-5.500s6 2 6.500 5.500"/><path d="M16 4.500a3.500 3.500 0 0 1 0 7M18 14.800c1.800.7 3.200 2.300 3.500 5.200"/>',
        'shield'  => '<path d="M12 3l8 3v6c0 5-3.500 8-8 9-4.500-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'phone'   => '<path d="M5 4h4l2 5-2.500 1.500a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'pin'     => '<path d="M12 21s7-6 7-11a7 7 0 0 0-14 0c0 5 7 11 7 11z"/><circle cx="12" cy="10" r="2.500"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'help'    => '<circle cx="12" cy="12" r="9"/><path d="M9.500 9.500a2.500 2.500 0 1 1 3.500 2.300c-.7.400-1 .900-1 1.700M12 17h.01"/>',
        'check'   => '<path d="M5 12.500l4.500 4.500L19 7.500"/>',
        'chevron' => '<path d="M6 9l6 6 6-6"/>',
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'   => '<path d="M6 6l12 12M18 6L6 18"/>',
        'left'    => '<path d="M15 5l-7 7 7 7"/>',
        'right'   => '<path d="M9 5l7 7-7 7"/>',
        'up'      => '<path d="M12 19V5M5 12l7-7 7 7"/>',
    ];

    $d = $paths[$name] ?? '';
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/** Brand mark: a flame on the ember tile. */
function logo_mark(): string
{
    return '<span class="logo__mark" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="14 -4 488 523" width="488" height="523" role="img" aria-label="Ember logo">
                                                          <defs>
                                                            <linearGradient id="stroke" gradientUnits="userSpaceOnUse" x1="258" y1="20" x2="258" y2="495">
                                                              <stop offset="0" stop-color="#FF6A45"/>
                                                              <stop offset="0.55" stop-color="#F0383F"/>
                                                              <stop offset="1" stop-color="#C8183A"/>
                                                            </linearGradient>
                                                            <linearGradient id="fill" gradientUnits="userSpaceOnUse" x1="258" y1="20" x2="258" y2="495">
                                                              <stop offset="0" stop-color="#FF6A45" stop-opacity="0.22"/>
                                                              <stop offset="1" stop-color="#C8183A" stop-opacity="0.12"/>
                                                            </linearGradient>
                                                          </defs>
                                                          <path d="M258 20 L478 142 L420 213 L297 140 L297 372 L420 292 L478 367 L258 495 L38 367 L98 292 L215 352 L215 140 L98 215 L38 142 Z"
                                                                fill="url(#fill)" stroke="url(#stroke)" stroke-width="32" stroke-linejoin="round" stroke-linecap="round"/>
                                                        </svg>
</span>';
}

/* ----------------------------------------------------------------------
 * Gallery illustration – an inline SVG stand-in for a build photo.
 * type: gaming | study | workstation | storage
 * -------------------------------------------------------------------- */

function build_art(string $type, int $variant, string $uid): string
{
    $palettes = [
        ['#ff5c3a', '#b3122e'],
        ['#ff8a63', '#d12038'],
        ['#ffb089', '#ff5c3a'],
        ['#ef3d5b', '#7a0c20'],
    ];
    [$c1, $c2] = $palettes[$variant % count($palettes)];
    $id = 'art' . preg_replace('/[^a-z0-9]/i', '', $uid);

    $fan = static function (float $x, float $y, float $r, string $c) : string {
        $blades = '';
        for ($i = 0; $i < 7; $i++) {
            $a       = $i * (360 / 7);
            $blades .= '<ellipse cx="' . round($r * 0.52, 2) . '" cy="0" rx="' . round($r * 0.38, 2) . '" ry="' . round($r * 0.13, 2) . '" transform="rotate(' . round($a, 2) . ')" fill="' . $c . '" opacity=".75"/>';
        }
        return '<g transform="translate(' . $x . ' ' . $y . ')">'
            . '<circle r="' . $r . '" fill="#10050a" stroke="' . $c . '" stroke-opacity=".55" stroke-width="1.5"/>'
            . $blades
            . '<circle r="' . round($r * 0.18, 2) . '" fill="#10050a" stroke="' . $c . '" stroke-width="1"/></g>';
    };

    $defs = '<defs>'
        . '<radialGradient id="' . $id . 'g" cx="50%" cy="52%" r="55%"><stop offset="0" stop-color="' . $c1 . '" stop-opacity=".42"/><stop offset="1" stop-color="' . $c1 . '" stop-opacity="0"/></radialGradient>'
        . '<linearGradient id="' . $id . 'c" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2d131b"/><stop offset="1" stop-color="#170a0f"/></linearGradient>'
        . '<linearGradient id="' . $id . 'a" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $c1 . '"/><stop offset="1" stop-color="' . $c2 . '"/></linearGradient>'
        . '</defs>';

    $label = [
        'gaming'      => 'Illustration of a gaming PC with a glass side panel',
        'study'       => 'Illustration of a compact study PC',
        'workstation' => 'Illustration of a workstation with liquid cooling',
        'server'     => 'Illustration of a specialised server system',
    ][$type] ?? 'Illustration of a custom PC build';

    $svg  = '<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="' . e($label) . '">' . $defs;
    $svg .= '<rect width="400" height="300" fill="url(#' . $id . 'g)"/>';
    $svg .= '<ellipse cx="200" cy="282" rx="120" ry="9" fill="#000" opacity=".45"/>';

    switch ($type) {
        case 'study':
            $svg .= '<rect x="140" y="64" width="120" height="208" rx="14" fill="url(#' . $id . 'c)" stroke="' . $c1 . '" stroke-opacity=".35"/>';
            $svg .= '<rect x="150" y="76" width="100" height="128" rx="8" fill="#0c0407" stroke="' . $c1 . '" stroke-opacity=".25"/>';
            $svg .= $fan(200, 136, 38, $c1);
            $svg .= '<rect x="150" y="214" width="100" height="46" rx="8" fill="#0c0407" stroke="' . $c1 . '" stroke-opacity=".2"/>';
            $svg .= '<rect x="160" y="228" width="50" height="5" rx="2.500" fill="url(#' . $id . 'a)"/>';
            $svg .= '<circle cx="232" cy="231" r="4" fill="' . $c1 . '"/>';
            $svg .= '<rect x="160" y="242" width="80" height="4" rx="2" fill="#fff" opacity=".07"/>';
            break;

        case 'workstation':
            $svg .= '<rect x="98" y="22" width="204" height="252" rx="16" fill="url(#' . $id . 'c)" stroke="' . $c1 . '" stroke-opacity=".35"/>';
            $svg .= '<rect x="108" y="32" width="184" height="232" rx="10" fill="#0c0407" stroke="' . $c1 . '" stroke-opacity=".25"/>';
            $svg .= '<rect x="118" y="42" width="164" height="30" rx="8" fill="#1a0b11" stroke="url(#' . $id . 'a)" stroke-width="1.500"/>';
            $svg .= $fan(145, 57, 11, $c1) . $fan(200, 57, 11, $c1) . $fan(255, 57, 11, $c1);
            $svg .= '<rect x="118" y="84" width="164" height="2" fill="' . $c1 . '" opacity=".25"/>';
            $svg .= $fan(160, 128, 30, $c1);
            for ($i = 0; $i < 4; $i++) {
                $svg .= '<rect x="' . (228 + $i * 11) . '" y="98" width="6" height="54" rx="2" fill="url(#' . $id . 'a)" opacity=".85"/>';
            }
            $svg .= '<rect x="118" y="178" width="164" height="46" rx="8" fill="#1a0b11" stroke="url(#' . $id . 'a)" stroke-width="1.500"/>';
            $svg .= $fan(150, 201, 15, $c1) . $fan(200, 201, 15, $c1) . $fan(250, 201, 15, $c1);
            $svg .= '<rect x="118" y="236" width="164" height="18" rx="5" fill="#1a0b11" stroke="' . $c1 . '" stroke-opacity=".2"/>';
            break;

        case 'server':
            $bays   = 4 + ($variant % 2) * 2;
            $top    = 78;
            $area   = 156;
            $gap    = 8;
            $bayH   = ($area - ($bays - 1) * $gap) / $bays;
            $svg   .= '<rect x="72" y="48" width="256" height="204" rx="16" fill="url(#' . $id . 'c)" stroke="' . $c1 . '" stroke-opacity=".35"/>';
            $svg   .= '<rect x="92" y="60" width="216" height="6" rx="3" fill="#fff" opacity=".06"/>';
            for ($i = 0; $i < $bays; $i++) {
                $y     = round($top + $i * ($bayH + $gap), 2);
                $svg  .= '<rect x="92" y="' . $y . '" width="216" height="' . round($bayH, 2) . '" rx="6" fill="#0c0407" stroke="' . $c1 . '" stroke-opacity=".3"/>';
                $svg  .= '<rect x="102" y="' . round($y + $bayH / 2 - 2, 2) . '" width="' . ($i % 2 ? 120 : 148) . '" height="4" rx="2" fill="#fff" opacity=".08"/>';
                $svg  .= '<circle cx="290" cy="' . round($y + $bayH / 2, 2) . '" r="3.500" fill="' . ($i === 1 ? $c2 : $c1) . '"/>';
            }
            $svg .= '<rect x="92" y="236" width="40" height="6" rx="3" fill="url(#' . $id . 'a)"/>';
            break;

        case 'gaming':
        default:
            $svg .= '<rect x="108" y="22" width="184" height="252" rx="16" fill="url(#' . $id . 'c)" stroke="' . $c1 . '" stroke-opacity=".45"/>';
            $svg .= '<rect x="118" y="32" width="164" height="232" rx="10" fill="#0c0407" stroke="url(#' . $id . 'a)" stroke-width="1.500" opacity=".95"/>';
            $svg .= $fan(150, 78, 20, $c1) . $fan(150, 128, 20, $c1) . $fan(150, 178, 20, $c1);
            $svg .= '<circle cx="230" cy="82" r="22" fill="#1a0b11" stroke="url(#' . $id . 'a)" stroke-width="2"/>';
            $svg .= '<circle cx="230" cy="82" r="9" fill="' . $c1 . '" opacity=".85"/>';
            for ($i = 0; $i < 3; $i++) {
                $svg .= '<rect x="' . (262 + $i * 6) . '" y="56" width="4" height="58" rx="2" fill="url(#' . $id . 'a)"/>';
            }
            $svg .= '<rect x="170" y="212" width="106" height="30" rx="6" fill="#1a0b11" stroke="url(#' . $id . 'a)" stroke-width="1.500"/>';
            $svg .= $fan(198, 227, 10, $c1) . $fan(246, 227, 10, $c1);
            $svg .= '<rect x="126" y="250" width="148" height="3" rx="1.500" fill="url(#' . $id . 'a)" opacity=".8"/>';
            break;
    }

    return $svg . '</svg>';
}
