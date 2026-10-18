<?php
/** Gallery manager: add builds with photos, edit, reorder, remove. */

declare(strict_types=1);

$buildTypes = [];
foreach ($cfg['builds'] as $key => $b) {
    $buildTypes[$key] = (string) $b['label'];
}
$media   = media_all();
$errors  = [];
$gallery = array_values((array) $cfg['gallery']);
$addForm = ['title' => '', 'type' => (string) array_key_first($buildTypes), 'spec' => '', 'desc' => '', 'alt' => ''];

$cleanEntry = static function (array $row) use ($buildTypes, $media): array {
    $image = p_line($row['image'] ?? '', 80);
    $type  = is_string($row['type'] ?? null) ? $row['type'] : '';
    return [
        'title'   => p_line($row['title'] ?? '', 80),
        'type'    => isset($buildTypes[$type]) ? $type : (string) array_key_first($buildTypes),
        'spec'    => p_line($row['spec'] ?? '', 260),
        'desc'    => str_replace("\n", ' ', p_text($row['desc'] ?? '', 500)),
        'variant' => p_int($row['variant'] ?? '', 0, 3, 0),
        'image'   => isset($media[$image]) ? $image : '',
        'alt'     => p_line($row['alt'] ?? '', 160),
    ];
};

if (a_is_post()) {
    a_require_post();
    $action = post_str('_action');

    if ($action === 'add') {
        $addForm = [
            'title' => p_line($_POST['title'] ?? '', 80),
            'type'  => isset($buildTypes[post_str('type')]) ? post_str('type') : (string) array_key_first($buildTypes),
            'spec'  => p_line($_POST['spec'] ?? '', 260),
            'desc'  => str_replace("\n", ' ', p_text($_POST['desc'] ?? '', 500)),
            'alt'   => p_line($_POST['alt'] ?? '', 160),
        ];
        if ($addForm['title'] === '') {
            $errors[] = 'Give the build a title.';
        }
        if (!isset($_FILES['image']) || (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Choose a photo to upload.';
        }
        if ($errors === []) {
            try {
                $record = media_process_upload($_FILES['image'], $addForm['title']);
                $entry  = $cleanEntry($addForm + ['image' => $record['id'], 'variant' => 0]);
                if ($entry['alt'] === '') {
                    $entry['alt'] = $entry['title'] . ' custom computer built by ' . $cfg['site']['name'];
                }
                array_unshift($gallery, $entry);
                if (!save_content_sections(['gallery' => $gallery])) {
                    throw new RuntimeException('The photo was uploaded but the gallery could not be saved. Check that the "storage" folder is writable.');
                }
                a_flash('ok', '"' . $entry['title'] . '" was added to the gallery.');
                a_redirect('gallery');
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
    } elseif ($action === 'save') {
        $rows = [];
        foreach ((array) ($_POST['entries'] ?? []) as $row) {
            if (is_array($row)) {
                $entry = $cleanEntry($row);
                if ($entry['title'] === '') {
                    $errors[] = 'Every gallery entry needs a title (use Remove to delete one).';
                    break;
                }
                $rows[] = $entry;
            }
            if (count($rows) >= 40) {
                break;
            }
        }
        if ($errors === []) {
            if (save_content_sections(['gallery' => $rows])) {
                a_flash('ok', 'Gallery saved.');
            } else {
                a_flash('error', 'Could not save. Check that the "storage" folder is writable.');
            }
            a_redirect('gallery');
        }
        $gallery = $rows;
    }
}

$mediaOptions = ['' => 'No photo (use illustration)'];
foreach ($media as $id => $m) {
    $mediaOptions[$id] = (string) ($m['name'] ?? $id);
}

a_head('Gallery', 'gallery');
echo a_errors_html($errors);
?>
<form method="post" action="<?= e(a_url('gallery')) ?>" enctype="multipart/form-data">
    <?= admin_csrf_field() ?>
    <input type="hidden" name="_action" value="add">
    <?= a_card_start('Add a new build', 'Upload a photo and describe the build. The photo is resized and converted automatically so the website stays fast. JPG, PNG or WebP up to 15 MB; PNGs with a transparent background work too.') ?>
    <div class="a-grid a-grid--2">
        <?= f_text('title', 'Title', $addForm['title'], ['max' => 80, 'required' => true, 'placeholder' => 'e.g. Crimson Workstation']) ?>
        <?= f_select('type', 'Type of build', $addForm['type'], $buildTypes) ?>
    </div>
    <?= f_text('spec', 'Key specs', $addForm['spec'], ['max' => 260, 'placeholder' => '16-core CPU, 64 GB DDR5, RTX 5090, 4 TB NVMe']) ?>
    <?= f_area('desc', 'Description', $addForm['desc'], ['rows' => 3, 'max' => 500, 'hint' => 'What makes this build special? Shown when someone opens the photo.']) ?>
    <?= f_text('alt', 'Photo description for Google and screen readers', $addForm['alt'], ['max' => 160, 'hint' => 'Describe what is in the photo, e.g. "Black custom gaming PC with RGB lighting built by Incrymnia". Leave blank to generate one.']) ?>
    <div class="a-field">
        <label for="f-image">Photo</label>
        <input class="a-input" type="file" id="f-image" name="image" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <button type="submit" class="a-btn a-btn--primary">Upload and add to gallery</button>
    <?= a_card_end() ?>
</form>

<form method="post" action="<?= e(a_url('gallery')) ?>">
    <?= admin_csrf_field() ?>
    <input type="hidden" name="_action" value="save">
    <?= a_card_start('Your builds (' . count($gallery) . ')', 'Use Up and Down to change the order. Remove takes a build off the website (the photo stays in the Image library). Remember to press Save.') ?>
    <?php if ($gallery === []): ?>
        <p class="a-hint">No builds yet. Add your first one above.</p>
    <?php endif; ?>
    <div class="repeater" data-repeater data-max="40" data-reorder-only>
        <div class="repeater__items" data-items>
            <?php foreach ($gallery as $i => $g):
                $imgId = (string) ($g['image'] ?? '');
                $has   = $imgId !== '' && isset($media[$imgId]);
                $p     = 'entries[' . $i . ']';
            ?>
                <div class="rep-item" data-item>
                    <div class="rep-item__head">
                        <span class="rep-item__label"><?= e((string) $g['title']) ?></span>
                        <span class="rep-item__tools">
                            <button type="button" class="a-mini" data-move="up" aria-label="Move up">Up</button>
                            <button type="button" class="a-mini" data-move="down" aria-label="Move down">Down</button>
                            <button type="button" class="a-mini a-mini--danger" data-remove data-confirm="Remove this build from the gallery?">Remove</button>
                        </span>
                    </div>
                    <div class="rep-item__body a-gal">
                        <div class="a-gal__thumb"><?= $has ? media_picture($imgId, (string) ($g['alt'] ?? ''), '140px') : '<span class="a-hint">Illustration</span>' ?></div>
                        <div class="a-gal__fields">
                            <input type="hidden" name="<?= e($p) ?>[variant]" value="<?= (int) ($g['variant'] ?? 0) ?>">
                            <div class="a-grid a-grid--2">
                                <?= f_text($p . '[title]', 'Title', (string) $g['title'], ['max' => 80, 'required' => true]) ?>
                                <?= f_select($p . '[type]', 'Type', (string) $g['type'], $buildTypes) ?>
                            </div>
                            <?= f_text($p . '[spec]', 'Key specs', (string) $g['spec'], ['max' => 260]) ?>
                            <?= f_area($p . '[desc]', 'Description', (string) $g['desc'], ['rows' => 2, 'max' => 500]) ?>
                            <?= f_text($p . '[alt]', 'Photo description (alt text)', (string) ($g['alt'] ?? ''), ['max' => 160]) ?>
                            <?= f_select($p . '[image]', 'Photo', $has ? $imgId : '', $mediaOptions) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?= a_card_end() ?>
    <?= a_save_bar('Save gallery') ?>
</form>
<?php
a_foot();
