<?php
/** Quote requests received through the website form. */

declare(strict_types=1);

$file = STORAGE . '/quotes.jsonl';

$readQuotes = static function () use ($file): array {
    if (!is_file($file)) {
        return [];
    }
    $out = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $row = json_decode($line, true);
        if (is_array($row) && isset($row['ref'])) {
            $out[] = $row;
        }
    }
    return array_reverse($out);   // newest first
};

$buildLabel = static function (string $key) use ($cfg): string {
    return $key === 'other' ? 'Not sure yet' : (string) ($cfg['builds'][$key]['label'] ?? $key);
};

/* Delete one request */
if (a_is_post()) {
    a_require_post();
    $ref = post_str('ref');
    if (preg_match('/^Q-\d{8}-[0-9A-F]{4}$/', $ref) && is_file($file)) {
        $kept = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $row = json_decode($line, true);
            if (!(is_array($row) && ($row['ref'] ?? '') === $ref)) {
                $kept[] = $line;
            }
        }
        @file_put_contents($file, $kept === [] ? '' : implode(PHP_EOL, $kept) . PHP_EOL, LOCK_EX);
        a_flash('ok', 'Quote request ' . $ref . ' deleted.');
    }
    a_redirect('quotes');
}

$quotes = $readQuotes();

/* CSV export (opens in Excel / Google Sheets) */
if (($_GET['export'] ?? '') === 'csv') {
    $safe = static function (string $v): string {
        // stop spreadsheet formula injection
        return $v !== '' && strpos('=+-@', $v[0]) !== false ? "'" . $v : $v;
    };
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="quote-requests-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Reference', 'Received', 'Name', 'Email', 'Phone', 'Build', 'Budget', 'Timeline', 'Extras', 'Message']);
    foreach ($quotes as $q) {
        fputcsv($out, [
            $safe((string) $q['ref']), $safe((string) ($q['created'] ?? '')), $safe((string) ($q['name'] ?? '')),
            $safe((string) ($q['email'] ?? '')), $safe((string) ($q['phone'] ?? '')), $safe($buildLabel((string) ($q['build'] ?? ''))),
            $safe((string) ($cfg['quote']['budgets'][$q['budget'] ?? ''] ?? '')), $safe((string) ($cfg['quote']['timelines'][$q['timeline'] ?? ''] ?? '')),
            $safe(implode(', ', array_map(static fn ($k) => (string) ($cfg['quote']['extras'][$k] ?? $k), (array) ($q['extras'] ?? [])))),
            $safe(str_replace(["\r", "\n"], ' ', (string) ($q['message'] ?? ''))),
        ]);
    }
    fclose($out);
    exit;
}

a_head('Quote requests', 'quotes');
?>
<p class="a-intro">Every request sent from the website is saved here and emailed to <strong><?= e((string) $cfg['site']['quote_to']) ?></strong>.
    <?php if ($quotes !== []): ?><a href="<?= e(a_url('quotes', ['export' => 'csv'])) ?>">Download as spreadsheet (CSV)</a><?php endif; ?></p>

<?php if ($quotes === []): ?>
    <?= a_card_start() ?><p class="a-hint">No quote requests yet.</p><?= a_card_end() ?>
<?php endif; ?>

<?php foreach ($quotes as $q):
    $when = isset($q['created']) ? date('j M Y, g:i a', (int) strtotime((string) $q['created'])) : '';
    $extras = array_map(static fn ($k) => (string) ($cfg['quote']['extras'][$k] ?? $k), (array) ($q['extras'] ?? []));
?>
    <details class="a-card a-acc">
        <summary>
            <span><strong><?= e((string) ($q['name'] ?? '')) ?></strong> wants <?= e($buildLabel((string) ($q['build'] ?? ''))) ?></span>
            <span class="a-hint"><?= e($when) ?> &middot; <?= e((string) $q['ref']) ?></span>
        </summary>
        <dl class="a-dl">
            <div><dt>Email</dt><dd><a href="mailto:<?= e((string) ($q['email'] ?? '')) ?>"><?= e((string) ($q['email'] ?? '')) ?></a></dd></div>
            <div><dt>Phone</dt><dd><?= e((string) (($q['phone'] ?? '') !== '' ? $q['phone'] : 'Not given')) ?></dd></div>
            <div><dt>Budget</dt><dd><?= e((string) ($cfg['quote']['budgets'][$q['budget'] ?? ''] ?? '')) ?></dd></div>
            <div><dt>Needed</dt><dd><?= e((string) ($cfg['quote']['timelines'][$q['timeline'] ?? ''] ?? '')) ?></dd></div>
            <div><dt>Extras</dt><dd><?= e($extras === [] ? 'None' : implode(', ', $extras)) ?></dd></div>
        </dl>
        <p class="a-quote-msg"><?= nl2br(e((string) ($q['message'] ?? ''))) ?></p>
        <form method="post" action="<?= e(a_url('quotes')) ?>" data-confirm="Delete this quote request permanently?">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="ref" value="<?= e((string) $q['ref']) ?>">
            <button type="submit" class="a-mini a-mini--danger">Delete</button>
        </form>
    </details>
<?php endforeach; ?>
<?php
a_foot();
