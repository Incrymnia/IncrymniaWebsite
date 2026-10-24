<?php
/**
 * Router for PHP's built-in development server (it ignores .htaccess):
 *
 *     php -S localhost:8000 router.php
 *
 * It mirrors the rewrite and protection rules of .htaccess.
 */

declare(strict_types=1);

$path = rawurldecode((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH));
$file = __DIR__ . $path;

// Same protection as .htaccess
if (preg_match('#^/(storage|includes|partials|tools)(/|$)#', $path)
    || preg_match('#^/config(\.local|\.local\.example)?\.php$#', $path)
    || preg_match('#(^|/)\.[^/]#', $path)
    || preg_match('#\.(jsonl|log|key|tmp|bak|md)$#', $path)
    || preg_match('#^/uploads/.*\.(php[0-9]?|phtml|phar|html?|js)$#i', $path)) {
    http_response_code(404);
    echo 'Not found';
    return true;
}

if ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}
if ($path === '/robots.txt') {
    require __DIR__ . '/robots.php';
    return true;
}

if ($path !== '/' && is_file($file)) {
    return false;                       // real file (css, js, images, admin/index.php ...)
}
if (is_dir($file)) {
    return false;                       // directory index (/admin/)
}

if (preg_match('#^/([a-z0-9-]+)$#', $path, $m)) {
    header('Location: /' . $m[1] . '/', true, 301);
    return true;
}
if (preg_match('#^/([a-z0-9-]+)/$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/page.php';
    return true;
}

http_response_code(404);
$_GET['slug'] = '__missing__';
require __DIR__ . '/page.php';
return true;
