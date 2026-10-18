<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
$cfg = load_config();

header('Content-Type: text/plain; charset=UTF-8');
echo robots_txt($cfg);
