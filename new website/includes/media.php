<?php
/**
 * Media library.
 *
 * Uploaded images are re-encoded with GD (which strips metadata and any hidden payload),
 * resized into responsive WebP variants plus one JPEG/PNG fallback, and recorded in
 * storage/media.json. Files live in uploads/<id>/ (PHP execution is disabled there).
 */

declare(strict_types=1);

const MEDIA_MAX_BYTES  = 15 * 1024 * 1024;   // 15 MB per upload
const MEDIA_MAX_PIXELS = 40000000;           // 40 megapixels
const MEDIA_WIDTHS     = [480, 800, 1200];
const MEDIA_MAX_HEIGHT = 1400;

function media_manifest_path(): string
{
    return STORAGE . '/media.json';
}

/** @return array<string, array> */
function media_all(): array
{
    $data = read_json(media_manifest_path());
    return isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
}

function media_get(string $id): ?array
{
    $all = media_all();
    return $all[$id] ?? null;
}

function media_save_all(array $items): bool
{
    return write_json(media_manifest_path(), ['items' => $items]);
}

function media_valid_id(string $id): bool
{
    return (bool) preg_match('/^[a-z0-9][a-z0-9-]{2,70}$/', $id);
}

function media_slug(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text === '' ? 'image' : substr($text, 0, 40);
}

function upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is larger than the server allows. Try a smaller image (under 15 MB).',
        UPLOAD_ERR_PARTIAL   => 'The upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE   => 'No file was selected.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'The server could not save the upload.',
        default              => 'The upload failed.',
    };
}

/* ----------------------------------------------------------------------
 * Rendering
 * -------------------------------------------------------------------- */

function media_srcset(array $variants): string
{
    usort($variants, static fn ($a, $b) => $a['w'] <=> $b['w']);
    $parts = [];
    foreach ($variants as $v) {
        $parts[] = url($v['path']) . ' ' . (int) $v['w'] . 'w';
    }
    return implode(', ', $parts);
}

/** Largest variant of a media item: [path, w, h, mime] */
function media_largest(array $m): ?array
{
    $best = null;
    foreach (($m['webp'] ?? []) as $v) {
        if ($best === null || $v['w'] > $best['w']) {
            $best = $v + ['mime' => 'image/webp'];
        }
    }
    foreach (($m['fallback']['variants'] ?? []) as $v) {
        if ($best === null || $v['w'] > $best['w']) {
            $best = $v + ['mime' => (string) ($m['fallback']['type'] ?? 'image/jpeg')];
        }
    }
    return $best;
}

/** <picture> with WebP sources, a JPEG/PNG fallback, width/height set to avoid layout shift. */
function media_picture(string $id, string $alt, string $sizes = '100vw', array $opts = []): string
{
    $m = media_get($id);
    if ($m === null) {
        return '';
    }

    $lazy     = $opts['lazy'] ?? true;
    $priority = $opts['priority'] ?? false;
    $fallback = $m['fallback']['variants'] ?? [];
    $largest  = media_largest($m);
    if ($largest === null || $fallback === []) {
        return '';
    }

    usort($fallback, static fn ($a, $b) => $a['w'] <=> $b['w']);
    $src = $fallback[count($fallback) - 1];

    $html = '<picture>';
    if (!empty($m['webp'])) {
        $html .= '<source type="image/webp" srcset="' . e(media_srcset($m['webp'])) . '" sizes="' . e($sizes) . '">';
    }
    $html .= '<img src="' . e(url($src['path'])) . '"';
    if (count($fallback) > 1) {
        $html .= ' srcset="' . e(media_srcset($fallback)) . '" sizes="' . e($sizes) . '"';
    }
    $html .= ' width="' . (int) $largest['w'] . '" height="' . (int) $largest['h'] . '"'
        . ' alt="' . e($alt) . '" decoding="async"'
        . ($priority ? ' fetchpriority="high"' : ($lazy ? ' loading="lazy"' : ''))
        . '></picture>';

    return $html;
}

/** Social-share details for a media item: [url, width, height, mime] or null. */
function media_share(string $id): ?array
{
    $m = media_get($id);
    if ($m === null) {
        return null;
    }
    $largest = media_largest($m);
    return $largest === null ? null : [url($largest['path']), (int) $largest['w'], (int) $largest['h'], (string) $largest['mime']];
}

/** Where a media item is used (for the delete guard). */
function media_usage(string $id, array $cfg): array
{
    $used = [];
    foreach (($cfg['gallery'] ?? []) as $g) {
        if (($g['image'] ?? '') === $id) {
            $used[] = 'Gallery: ' . ($g['title'] ?? 'build');
        }
    }
    if (($cfg['seo']['og_image'] ?? '') === $id) {
        $used[] = 'Social share image';
    }
    return $used;
}

/* ----------------------------------------------------------------------
 * Upload processing
 * -------------------------------------------------------------------- */

function media_resize(\GdImage $src, int $w, int $h, int $tw, int $th): \GdImage
{
    $dst = imagecreatetruecolor($tw, $th);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
    return $dst;
}

/** Does the picture contain transparent pixels? (sampled, not exhaustive) */
function media_has_alpha(\GdImage $img, int $w, int $h): bool
{
    $stepX = max(1, intdiv($w, 60));
    $stepY = max(1, intdiv($h, 60));
    for ($y = 0; $y < $h; $y += $stepY) {
        for ($x = 0; $x < $w; $x += $stepX) {
            if ((imagecolorat($img, $x, $y) >> 24) & 0x7F) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Validate and process one uploaded image ($_FILES entry). Returns the new media record
 * (already saved to the manifest). Throws RuntimeException with a message safe to show.
 */
function media_process_upload(array $file, string $label): array
{
    if (!function_exists('imagecreatetruecolor')) {
        throw new RuntimeException('Image uploads need the PHP GD extension, which is not enabled on this server. Enable "extension=gd" in php.ini (or ask your host).');
    }
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($error));
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('The upload could not be verified.');
    }
    if ((int) ($file['size'] ?? 0) > MEDIA_MAX_BYTES) {
        throw new RuntimeException('That image is larger than 15 MB. Please resize it first.');
    }

    $info = @getimagesize($tmp);
    if ($info === false) {
        throw new RuntimeException('That file is not a valid image. Use a JPG, PNG or WebP file.');
    }
    [$w, $h, $type] = $info;
    if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        throw new RuntimeException('Only JPG, PNG and WebP images are accepted.');
    }
    if ($w < 50 || $h < 50) {
        throw new RuntimeException('That image is too small to use.');
    }
    if ($w * $h > MEDIA_MAX_PIXELS) {
        throw new RuntimeException('That image has too many pixels (over 40 megapixels). Please resize it first.');
    }

    @ini_set('memory_limit', '768M');
    @set_time_limit(120);

    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
        IMAGETYPE_PNG  => @imagecreatefrompng($tmp),
        default        => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
    };
    if (!$src instanceof \GdImage) {
        throw new RuntimeException('The image could not be read. It may be damaged, or the server cannot open this format.');
    }

    if (!imageistruecolor($src)) {
        imagepalettetotruecolor($src);   // so transparency can be read from the pixels
    }

    // Respect camera orientation for JPEGs
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        if ($angle !== 0) {
            $rotated = imagerotate($src, $angle, 0);
            if ($rotated instanceof \GdImage) {
                $src = $rotated;
                $w = imagesx($src);
                $h = imagesy($src);
            }
        }
    }

    $alpha = $type !== IMAGETYPE_JPEG && media_has_alpha($src, $w, $h);

    // Unique id and folder
    $all  = media_all();
    $slug = media_slug($label);
    do {
        $id = $slug . '-' . bin2hex(random_bytes(3));
    } while (isset($all[$id]) || is_dir(ROOT . '/uploads/' . $id));

    $relDir = 'uploads/' . $id;
    $absDir = ROOT . '/' . $relDir;
    if (!@mkdir($absDir, 0755, true) && !is_dir($absDir)) {
        throw new RuntimeException('The uploads folder is not writable. Check the permissions on the "uploads" folder.');
    }

    // Work out the sizes (never upscale, cap the height)
    $sizes = [];
    foreach (MEDIA_WIDTHS as $target) {
        $tw = min($target, $w);
        $th = (int) round($h * $tw / $w);
        if ($th > MEDIA_MAX_HEIGHT) {
            $th = MEDIA_MAX_HEIGHT;
            $tw = (int) round($w * $th / $h);
        }
        $sizes[$tw . 'x' . $th] = [$tw, $th];
    }
    $sizes = array_values($sizes);
    usort($sizes, static fn ($a, $b) => $a[0] <=> $b[0]);

    $webp     = [];
    $fallback = [];
    $canWebp  = function_exists('imagewebp');
    $fbType   = $alpha ? 'image/png' : 'image/jpeg';
    $fbExt    = $alpha ? 'png' : 'jpg';
    // With WebP available, keep one mid-size fallback. Without it, keep every size as the fallback.
    $fbIndex  = count($sizes) > 1 ? 1 : 0;

    try {
        foreach ($sizes as $i => [$tw, $th]) {
            $dst = media_resize($src, $w, $h, $tw, $th);

            if ($canWebp) {
                $path = $relDir . '/' . $slug . '-' . $tw . '.webp';
                if (!@imagewebp($dst, ROOT . '/' . $path, 80)) {
                    throw new RuntimeException('The server could not save the image.');
                }
                $webp[] = ['w' => $tw, 'h' => $th, 'path' => $path];
            }

            if (!$canWebp || $i === $fbIndex) {
                $path = $relDir . '/' . $slug . '-' . $tw . '.' . $fbExt;
                if ($alpha) {
                    $ok = @imagepng($dst, ROOT . '/' . $path, 9);
                } else {
                    // JPEG has no alpha: flatten onto white (only matters if sampling missed a corner)
                    $flat = imagecreatetruecolor($tw, $th);
                    imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
                    imagecopy($flat, $dst, 0, 0, 0, 0, $tw, $th);
                    $ok = @imagejpeg($flat, ROOT . '/' . $path, 82);
                    unset($flat);
                }
                if (!$ok) {
                    throw new RuntimeException('The server could not save the image.');
                }
                $fallback[] = ['w' => $tw, 'h' => $th, 'path' => $path];
            }
            unset($dst);
        }
    } catch (Throwable $e) {
        media_remove_dir($absDir);
        unset($src);
        throw $e instanceof RuntimeException ? $e : new RuntimeException('The image could not be processed.');
    }
    unset($src);

    $record = [
        'id'       => $id,
        'name'     => mb_substr(clean_line($label), 0, 80),
        'created'  => date('c'),
        'width'    => $w,
        'height'   => $h,
        'alpha'    => $alpha,
        'webp'     => $webp,
        'fallback' => ['type' => $fbType, 'variants' => $fallback],
    ];

    $all[$id] = $record;
    if (!media_save_all($all)) {
        media_remove_dir($absDir);
        throw new RuntimeException('The image was processed but the library could not be saved. Check that the "storage" folder is writable.');
    }
    return $record;
}

function media_remove_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (glob($dir . '/*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    @rmdir($dir);
}

/** Delete a media item and its files. Caller checks that it is unused. */
function media_delete(string $id): bool
{
    if (!media_valid_id($id)) {
        return false;
    }
    $all = media_all();
    if (!isset($all[$id])) {
        return false;
    }
    unset($all[$id]);
    if (!media_save_all($all)) {
        return false;
    }
    media_remove_dir(ROOT . '/uploads/' . $id);
    return true;
}
