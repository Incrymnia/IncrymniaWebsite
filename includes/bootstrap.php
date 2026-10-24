<?php
/**
 * Shared bootstrap. Loaded by index.php, page.php, sitemap.php, robots.php and the admin panel.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('STORAGE', ROOT . '/storage');

require_once ROOT . '/includes/functions.php';
require_once ROOT . '/includes/content.php';
require_once ROOT . '/includes/media.php';
require_once ROOT . '/includes/seo.php';

/**
 * Start a public page: session (for the quote form), security headers and the optional
 * redirect to the canonical domain. Returns the merged site configuration.
 */
function public_boot(): array
{
    session_cache_limiter('');   // keep pages cacheable: PHP would otherwise send "no-store"
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    $cfg = load_config();

    if (!empty($cfg['seo']['redirect_to_canonical']) && PHP_SAPI !== 'cli') {
        $origin = site_origin($cfg);
        $parts  = parse_url($origin);
        $canonicalHost = strtolower((string) ($parts['host'] ?? ''));
        $requestHost   = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
        $isLocal       = in_array($requestHost, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
                         || filter_var($requestHost, FILTER_VALIDATE_IP) !== false;
        $isHttps       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                         || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $wantsHttps    = ($parts['scheme'] ?? 'https') === 'https';

        if (!$isLocal && $canonicalHost !== '' && ($requestHost !== $canonicalHost || ($wantsHttps && !$isHttps))) {
            $target = $origin . preg_replace('/[^\x21-\x7e]/', '', (string) ($_SERVER['REQUEST_URI'] ?? '/'));
            header('Location: ' . $target, true, 301);
            exit;
        }
    }

    return $cfg;
}
