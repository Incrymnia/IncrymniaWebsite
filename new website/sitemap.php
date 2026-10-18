<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
$cfg = load_config();

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');   // the sitemap itself should not appear in search results
echo sitemap_xml($cfg);
