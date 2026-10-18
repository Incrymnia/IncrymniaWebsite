<?php
/** Image library: upload and delete images. */

declare(strict_types=1);

$errors = [];

if (a_is_post()) {
    a_require_post();
    $action = post_str('_action');

    if ($action === 'upload') {
        $files = $_FILES['images'] ?? null;
        $added = 0;
        if (is_array($files) && is_array($files['name'] ?? null)) {
            foreach (array_keys($files['name']) as $i) {
                if ((int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $file  = [
                    'name'     => (string) $files['name'][$i],
                    'tmp_name' => (string) $files['tmp_name'][$i],
                    'error'    => (int) $files['error'][$i],
                    'size'     => (int) $files['size'][$i],
                ];
                $label = pathinfo($file['name'], PATHINFO_FILENAME);
                try {
                    media_process_upload($file, $label);
                    $added++;
                } catch (RuntimeException $e) {
                    $errors[] = $file['name'] . ': ' . $e->getMessage();
                }
            }
        }
        if ($added > 0) {
            a_flash('ok', $added . ' image(s) uploaded.');
        }
        if ($errors === []) {
            if ($added === 0) {
                a_flash('error', 'No images were selected.');
            }
            a_redirect('media');
        }
        if ($added > 0) {
            foreach ($errors as $err) {
                a_flash('error', $err);
            }
            a_redirect('media');
        }
    } elseif ($action === 'delete') {
        $id = post_str('id');
        $used = media_usage($id, $cfg);
        if ($used !== []) {
            a_flash('error', 'That image is in use (' . implode(', ', $used) . '). Remove it there first.');
        } elseif (media_delete($id)) {
            a_flash('ok', 'Image deleted.');
        } else {
            a_flash('error', 'Could not delete that image.');
        }
        a_redirect('media');
    }
}

$all = media_all();
uasort($all, static fn ($a, $b) => strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? '')));

a_head('Image library', 'media');
echo a_errors_html($errors);
?>
<form method="post" action="<?= e(a_url('media')) ?>" enctype="multipart/form-data">
    <?= admin_csrf_field() ?>
    <input type="hidden" name="_action" value="upload">
    <?= a_card_start('Upload images', 'JPG, PNG or WebP up to 15 MB each. Every image is checked, stripped of hidden data and converted into small, fast versions automatically.') ?>
    <div class="a-field">
        <label for="f-images">Choose one or more images</label>
        <input class="a-input" type="file" id="f-images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
    </div>
    <button type="submit" class="a-btn a-btn--primary">Upload</button>
    <?= a_card_end() ?>
</form>

<?= a_card_start('Library (' . count($all) . ')', 'To put a photo on the gallery use the Gallery page. To use one as your social sharing image use Search and SEO.') ?>
<?php if ($all === []): ?>
    <p class="a-hint">No images yet.</p>
<?php else: ?>
    <div class="a-media">
        <?php foreach ($all as $id => $m):
            $used  = media_usage((string) $id, $cfg);
            $bytes = 0;
            foreach (array_merge((array) ($m['webp'] ?? []), (array) ($m['fallback']['variants'] ?? [])) as $v) {
                $bytes += (int) @filesize(ROOT . '/' . $v['path']);
            }
        ?>
            <figure class="a-media__item">
                <div class="a-media__thumb"><?= media_picture((string) $id, (string) ($m['name'] ?? ''), '200px') ?></div>
                <figcaption>
                    <strong><?= e((string) ($m['name'] ?? $id)) ?></strong>
                    <span class="a-hint"><?= (int) ($m['width'] ?? 0) ?> x <?= (int) ($m['height'] ?? 0) ?> px, <?= e(a_human_bytes($bytes)) ?> on disk</span>
                    <?php if ($used !== []): ?>
                        <span class="a-hint">Used in: <?= e(implode(', ', $used)) ?></span>
                    <?php else: ?>
                        <form method="post" action="<?= e(a_url('media')) ?>" data-confirm="Delete this image permanently?">
                            <?= admin_csrf_field() ?>
                            <input type="hidden" name="_action" value="delete">
                            <input type="hidden" name="id" value="<?= e((string) $id) ?>">
                            <button type="submit" class="a-mini a-mini--danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= a_card_end() ?>
<?php
a_foot();
