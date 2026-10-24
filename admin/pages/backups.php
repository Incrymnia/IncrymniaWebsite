<?php
/** Automatic backups of every change made in the admin panel. */

declare(strict_types=1);

$dir   = backups_dir();
$files = glob($dir . '/content-*.json') ?: [];
rsort($files);

if (a_is_post()) {
    a_require_post();
    $action = post_str('_action');

    if ($action === 'restore') {
        $name = post_str('file');
        $path = $dir . '/' . $name;
        if (preg_match('/^content-\d{8}-\d{6}-[a-f0-9]{4}\.json$/', $name) && is_file($path)) {
            $data = json_decode((string) file_get_contents($path), true);
            if (is_array($data)) {
                backup_content();
                if (write_json(content_path(), $data)) {
                    load_config(true);
                    a_flash('ok', 'Restored the version from ' . date('j M Y, g:i a', (int) filemtime($path)) . '. Your previous version was backed up first.');
                } else {
                    a_flash('error', 'Could not restore. Check that the "storage" folder is writable.');
                }
            } else {
                a_flash('error', 'That backup file is damaged.');
            }
        }
    } elseif ($action === 'reset') {
        if (is_file(content_path())) {
            backup_content();
            @unlink(content_path());
            load_config(true);
        }
        a_flash('ok', 'All edits cleared. The site now shows the original built-in content. You can bring your edits back from the list below.');
    }
    a_redirect('backups');
}

a_head('Backups', 'backups');
?>
<p class="a-intro">A copy of your content is saved automatically before every change. If you save something by mistake, restore an earlier version here. The last 40 versions are kept. Uploaded images are not affected.</p>

<?= a_card_start('Saved versions (' . count($files) . ')') ?>
<?php if ($files === []): ?>
    <p class="a-hint">No backups yet. One is created the first time you save a change.</p>
<?php else: ?>
    <table class="a-table">
        <thead><tr><th>Saved</th><th>Size</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($files as $f): ?>
            <tr>
                <td><?= e(date('j M Y, g:i a', (int) filemtime($f))) ?></td>
                <td><?= e(a_human_bytes((int) filesize($f))) ?></td>
                <td>
                    <form method="post" action="<?= e(a_url('backups')) ?>" data-confirm="Restore this version? Your current version will be backed up first.">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="_action" value="restore">
                        <input type="hidden" name="file" value="<?= e(basename($f)) ?>">
                        <button type="submit" class="a-mini">Restore</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?= a_card_end() ?>

<?= a_card_start('Start again', 'Clear every edit made in the admin panel and show the original built-in content. A backup is made first, so this can be undone.') ?>
<form method="post" action="<?= e(a_url('backups')) ?>" data-confirm="Clear all edits and go back to the original content?">
    <?= admin_csrf_field() ?>
    <input type="hidden" name="_action" value="reset">
    <button type="submit" class="a-btn a-btn--danger">Clear all edits</button>
</form>
<?= a_card_end() ?>
<?php
a_foot();
