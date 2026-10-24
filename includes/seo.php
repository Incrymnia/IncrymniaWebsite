<?php
/**
 * SEO helpers: canonical URLs, meta data, JSON-LD structured data, sitemap and robots.txt.
 */

declare(strict_types=1);

/** https://example.com (no trailing slash). Uses Site URL from the admin, else the current request. */
function site_origin(array $cfg): string
{
    $configured = rtrim(trim((string) ($cfg['site']['url'] ?? '')), '/');
    if ($configured !== '' && preg_match('#^https?://[a-z0-9.\-]+(:\d+)?$#i', $configured)) {
        return $configured;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host  = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return ($https ? 'https' : 'http') . '://' . ($host === '' ? 'localhost' : $host);
}

/** Absolute URL for a path inside the site, e.g. abs_url($cfg, 'gaming-pcs/'). */
function abs_url(array $cfg, string $path = ''): string
{
    return site_origin($cfg) . url($path);
}

/** Pages with their own address: /gallery/, /services/, /about/, /faq/, /contact/ */
const STATIC_PAGES = ['gallery', 'services', 'about', 'faq', 'contact'];

/** Old address => new address (answered with a permanent redirect). */
const LEGACY_REDIRECTS = ['study-pcs' => 'office-study-pcs'];

/** schema.org page type for each static page */
const STATIC_SCHEMA_TYPES = ['gallery' => 'CollectionPage', 'services' => 'WebPage', 'about' => 'AboutPage', 'faq' => 'WebPage', 'contact' => 'ContactPage'];

/** Map of slug => build key for the service landing pages. */
function build_slugs(array $cfg): array
{
    $map = [];
    foreach ($cfg['builds'] as $key => $b) {
        $slug = (string) ($b['slug'] ?? '');
        if ($slug !== '' && preg_match('/^[a-z0-9-]+$/', $slug)) {
            $map[$slug] = $key;
        }
    }
    return $map;
}

function plain_text(string $html): string
{
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/', ' ', $text) ?? '');
}

/** Trim to a sensible meta-description length without cutting mid-word. */
function meta_trim(string $text, int $max = 158): string
{
    $text = plain_text($text);
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max - 1);
    $pos = mb_strrpos($cut, ' ');
    return rtrim($pos !== false && $pos > 80 ? mb_substr($cut, 0, $pos) : $cut, " ,.;:-") . '…';
}

/** Open Graph / share image: [url, w, h, mime]. */
function share_image(array $cfg): array
{
    $id = (string) ($cfg['seo']['og_image'] ?? '');
    if ($id !== '') {
        $m = media_share($id);
        if ($m !== null) {
            return [site_origin($cfg) . $m[0], $m[1], $m[2], $m[3]];
        }
    }
    return [abs_url($cfg, 'assets/img/og-default.png'), 1200, 630, 'image/png'];
}

/* ----------------------------------------------------------------------
 * JSON-LD
 * -------------------------------------------------------------------- */

function schema_business(array $cfg): array
{
    $site    = $cfg['site'];
    $origin  = abs_url($cfg, '');
    $share   = share_image($cfg);

    $areas = [];
    foreach (($site['service_areas'] ?? []) as $area) {
        if (is_string($area) && $area !== '') {
            $areas[] = ['@type' => 'AdministrativeArea', 'name' => $area];
        }
    }

    $sameAs = [];
    foreach (($site['social'] ?? []) as $link) {
        if (is_string($link) && preg_match('#^https?://#i', $link)) {
            $sameAs[] = $link;
        }
    }

    $offers = [];
    foreach ($cfg['builds'] as $b) {
        $slug = (string) ($b['slug'] ?? '');
        $offers[] = [
            '@type'       => 'Offer',
            'itemOffered' => array_filter([
                '@type'       => 'Service',
                'name'        => (string) $b['label'],
                'description' => plain_text(fill((string) $b['summary'], $site)),
                'url'         => $slug !== '' ? abs_url($cfg, $slug . '/') : null,
                'provider'    => ['@id' => $origin . '#business'],
            ]),
        ];
    }

    $biz = [
        '@type'         => ['LocalBusiness', 'ComputerStore'],
        '@id'           => $origin . '#business',
        'name'          => (string) $site['name'],
        'alternateName' => array_values(array_filter((array) ($site['alternate_names'] ?? []), 'is_string')),
        'url'           => $origin,
        'description'   => plain_text((string) $site['description']),
        'slogan'        => (string) $site['tagline'],
        'logo'          => [
            '@type'  => 'ImageObject',
            'url'    => abs_url($cfg, 'assets/img/logo-512.png'),
            'width'  => 512,
            'height' => 512,
        ],
        'image'         => [$share[0]],
        'email'         => (string) $site['email'],
        'address'       => [
            '@type'           => 'PostalAddress',
            'addressLocality' => 'Melbourne',
            'addressRegion'   => 'VIC',
            'addressCountry'  => 'AU',
        ],
        'areaServed'    => $areas,
        'knowsAbout'    => ['Custom PC building', 'Gaming PCs', 'Workstations', 'Servers', 'Computer upgrades and repairs'],
        'hasOfferCatalog' => [
            '@type'           => 'OfferCatalog',
            'name'            => 'Custom computer builds',
            'itemListElement' => $offers,
        ],
    ];

    $phone = trim((string) ($site['phone_href'] ?? ''));
    if ($phone !== '') {
        $biz['telephone'] = $phone;
    }
    $hours = hours_schema((array) ($site['schedule'] ?? []));
    if ($hours !== []) {
        $biz['openingHoursSpecification'] = $hours;
    }
    if ($sameAs !== []) {
        $biz['sameAs'] = $sameAs;
    }
    if ($areas === []) {
        unset($biz['areaServed']);
    }
    if ($biz['alternateName'] === []) {
        unset($biz['alternateName']);
    }
    return $biz;
}

/** FAQPage node from [['q' => ..., 'a' => ...], ...] */
function schema_faq(array $cfg, array $items, string $pageUrl): ?array
{
    $entities = [];
    foreach ($items as $item) {
        $q = plain_text((string) ($item['q'] ?? ''));
        $a = plain_text(fill((string) ($item['a'] ?? ''), $cfg['site']));
        if ($q !== '' && $a !== '') {
            $entities[] = [
                '@type'          => 'Question',
                'name'           => $q,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
            ];
        }
    }
    if ($entities === []) {
        return null;
    }
    return ['@type' => 'FAQPage', '@id' => $pageUrl . '#faq', 'mainEntity' => $entities];
}

/**
 * Complete JSON-LD document for a page.
 * $page keys: url (absolute), title, description, breadcrumbs [[name, absoluteUrl]], faq [], service (build key|null)
 */
function schema_json(array $cfg, array $page): string
{
    $site   = $cfg['site'];
    $origin = abs_url($cfg, '');
    $share  = share_image($cfg);
    $url    = (string) $page['url'];

    $website = [
        '@type'         => 'WebSite',
        '@id'           => $origin . '#website',
        'url'           => $origin,
        'name'          => (string) $site['name'],
        'inLanguage'    => (string) ($site['locale'] ?? 'en-AU'),
        'publisher'     => ['@id' => $origin . '#business'],
    ];
    if (!empty($site['alternate_names'])) {
        $website['alternateName'] = array_values(array_filter((array) $site['alternate_names'], 'is_string'));
    }

    $webpage = [
        '@type'              => (string) ($page['schema_type'] ?? 'WebPage'),
        '@id'                => $url . '#webpage',
        'url'                => $url,
        'name'               => (string) $page['title'],
        'description'        => (string) $page['description'],
        'isPartOf'           => ['@id' => $origin . '#website'],
        'about'              => ['@id' => $origin . '#business'],
        'inLanguage'         => (string) ($site['locale'] ?? 'en-AU'),
        'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $share[0], 'width' => $share[1], 'height' => $share[2]],
    ];

    $graph = [schema_business($cfg), $website];

    if (!empty($page['breadcrumbs'])) {
        $items = [];
        foreach ($page['breadcrumbs'] as $i => [$name, $link]) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => $link];
        }
        $graph[] = ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items];
        $webpage['breadcrumb'] = ['@id' => $url . '#breadcrumb'];
    }
    $graph[] = $webpage;

    if (!empty($page['service']) && isset($cfg['builds'][$page['service']])) {
        $b = $cfg['builds'][$page['service']];
        $graph[] = [
            '@type'       => 'Service',
            '@id'         => $url . '#service',
            'name'        => (string) $b['label'],
            'serviceType' => 'Custom computer building',
            'description' => plain_text(fill((string) $b['summary'], $site)),
            'url'         => $url,
            'provider'    => ['@id' => $origin . '#business'],
            'areaServed'  => ['@type' => 'AdministrativeArea', 'name' => 'Melbourne, Victoria, Australia'],
        ];
    }

    if (!empty($page['faq'])) {
        $faq = schema_faq($cfg, (array) $page['faq'], $url);
        if ($faq !== null) {
            $graph[] = $faq;
        }
    }

    return (string) json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
    );
}

/* ----------------------------------------------------------------------
 * sitemap.xml and robots.txt
 * -------------------------------------------------------------------- */

function sitemap_xml(array $cfg): string
{
    $lastmod = date('c', content_updated_at() ?: time());
    $origin  = site_origin($cfg);

    $images = '';
    foreach (($cfg['gallery'] ?? []) as $g) {
        $share = media_share((string) ($g['image'] ?? ''));
        if ($share !== null) {
            $images .= '    <image:image><image:loc>' . htmlspecialchars($origin . $share[0], ENT_XML1 | ENT_QUOTES, 'UTF-8')
                . '</image:loc><image:title>' . htmlspecialchars((string) $g['title'], ENT_XML1 | ENT_QUOTES, 'UTF-8')
                . "</image:title></image:image>\n";
        }
    }

    $urls = [[abs_url($cfg, ''), '1.0', 'weekly', '']];
    foreach (build_slugs($cfg) as $slug => $key) {
        $urls[] = [abs_url($cfg, $slug . '/'), '0.9', 'monthly', ''];
    }
    $staticPriority = ['gallery' => '0.7', 'services' => '0.7', 'about' => '0.6', 'faq' => '0.6', 'contact' => '0.8'];
    foreach (STATIC_PAGES as $slug) {
        $urls[] = [abs_url($cfg, $slug . '/'), $staticPriority[$slug], 'monthly', $slug === 'gallery' ? $images : ''];
    }

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    foreach ($urls as [$loc, $priority, $freq, $imgs]) {
        $xml .= "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n"
            . "    <lastmod>{$lastmod}</lastmod>\n    <changefreq>{$freq}</changefreq>\n    <priority>{$priority}</priority>\n"
            . $imgs . "  </url>\n";
    }
    return $xml . "</urlset>\n";
}

function robots_txt(array $cfg): string
{
    $lines = ['User-agent: *'];
    if (!empty($cfg['seo']['noindex'])) {
        $lines[] = 'Disallow: /';
    } else {
        $base = rtrim(url(''), '/');
        $lines[] = 'Allow: /';
        foreach (['admin', 'storage', 'includes', 'partials', 'tools'] as $dir) {
            $lines[] = 'Disallow: ' . $base . '/' . $dir . '/';
        }
    }
    $lines[] = '';
    $lines[] = 'Sitemap: ' . abs_url($cfg, 'sitemap.xml');
    return implode("\n", $lines) . "\n";
}
