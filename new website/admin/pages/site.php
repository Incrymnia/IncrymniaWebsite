<?php
/** Business details: name, contact, hours, areas, social profiles. */

declare(strict_types=1);

const SOCIAL_NETWORKS = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'x' => 'X (Twitter)', 'linkedin' => 'LinkedIn'];

$site   = $cfg['site'];
$errors = [];

$values = [
    'name'            => (string) $site['name'],
    'tagline'         => (string) $site['tagline'],
    'description'     => (string) $site['description'],
    'url'             => (string) ($site['url'] ?? ''),
    'email'           => (string) $site['email'],
    'quote_to'        => (string) $site['quote_to'],
    'phone'           => (string) $site['phone'],
    'phone_href'      => (string) ($site['phone_href'] ?? ''),
    'address'         => (string) $site['address'],
    'service_areas'   => implode("\n", (array) ($site['service_areas'] ?? [])),
    'alternate_names' => implode("\n", (array) ($site['alternate_names'] ?? [])),
    'warranty_months' => (string) $site['warranty_months'],
    'build_days'      => (string) $site['build_days'],
    'reply_time'      => (string) $site['reply_time'],
];
$social   = array_replace(array_fill_keys(array_keys(SOCIAL_NETWORKS), ''), (array) ($site['social'] ?? []));
$schedule = (array) ($site['schedule'] ?? []);

if (a_is_post()) {
    a_require_post();

    $values = [
        'name'            => p_line($_POST['name'] ?? '', 80),
        'tagline'         => p_line($_POST['tagline'] ?? '', 120),
        'description'     => p_text($_POST['description'] ?? '', 400),
        'url'             => rtrim(p_line($_POST['url'] ?? '', 120), '/'),
        'email'           => p_line($_POST['email'] ?? '', 120),
        'quote_to'        => p_line($_POST['quote_to'] ?? '', 120),
        'phone'           => p_line($_POST['phone'] ?? '', 40),
        'phone_href'      => str_replace([' ', '-', '(', ')'], '', p_line($_POST['phone_href'] ?? '', 25)),
        'address'         => p_line($_POST['address'] ?? '', 160),
        'service_areas'   => implode("\n", p_lines($_POST['service_areas'] ?? '', 20, 80)),
        'alternate_names' => implode("\n", p_lines($_POST['alternate_names'] ?? '', 8, 80)),
        'warranty_months' => (string) p_int($_POST['warranty_months'] ?? '', 0, 120, 12),
        'build_days'      => p_line($_POST['build_days'] ?? '', 60),
        'reply_time'      => p_line($_POST['reply_time'] ?? '', 60),
    ];

    if ($values['name'] === '') {
        $errors[] = 'Business name is required.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid public email address.';
    }
    if (!filter_var($values['quote_to'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address for receiving quote requests.';
    }
    if ($values['url'] !== '' && !preg_match('#^https?://[a-z0-9.\-]+(:\d+)?$#i', $values['url'])) {
        $errors[] = 'Website address must look like https://incrymnia.com (no page path, no trailing slash).';
    }
    if ($values['phone_href'] !== '' && !preg_match('/^\+[1-9][0-9]{7,14}$/', $values['phone_href'])) {
        $errors[] = 'Phone (international format) must look like +61485673647: a + sign, country code, then the number without the leading 0.';
    }

    $socialPost = is_array($_POST['social'] ?? null) ? $_POST['social'] : [];
    $openPost   = is_array($_POST['open'] ?? null) ? $_POST['open'] : [];
    $closePost  = is_array($_POST['close'] ?? null) ? $_POST['close'] : [];
    $closedPost = is_array($_POST['closed'] ?? null) ? $_POST['closed'] : [];

    $social = [];
    foreach (SOCIAL_NETWORKS as $key => $label) {
        $link = p_line($socialPost[$key] ?? '', 200);
        if ($link !== '' && !preg_match('#^https://[^\s]+$#i', $link)) {
            $errors[] = $label . ' link must start with https://';
        }
        $social[$key] = $link;
    }

    $schedule = [];
    foreach (DAY_KEYS as $day) {
        $closed = !empty($closedPost[$day]);
        $open   = p_line($openPost[$day] ?? '', 5);
        $close  = p_line($closePost[$day] ?? '', 5);
        if ($closed) {
            $schedule[$day] = null;
            continue;
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $open) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $close) || $close <= $open) {
            $errors[] = DAY_NAMES[$day] . ': enter an opening time before the closing time, or tick Closed.';
            $schedule[$day] = ['09:00', '17:00'];
            continue;
        }
        $schedule[$day] = [$open, $close];
    }

    if ($errors === []) {
        $save = [
            'name'            => $values['name'],
            'tagline'         => $values['tagline'],
            'description'     => $values['description'],
            'url'             => $values['url'],
            'email'           => $values['email'],
            'quote_to'        => $values['quote_to'],
            'phone'           => $values['phone'],
            'phone_href'      => $values['phone_href'],
            'address'         => $values['address'],
            'service_areas'   => p_lines($values['service_areas'], 20, 80),
            'alternate_names' => p_lines($values['alternate_names'], 8, 80),
            'warranty_months' => (int) $values['warranty_months'],
            'build_days'      => $values['build_days'],
            'reply_time'      => $values['reply_time'],
            'social'          => $social,
            'schedule'        => $schedule,
        ];
        if (save_content_sections(['site' => $save])) {
            a_flash('ok', 'Business details saved.');
        } else {
            a_flash('error', 'Could not save. Check that the "storage" folder is writable.');
        }
        a_redirect('site');
    }
}

a_head('Business details', 'site');
echo a_errors_html($errors);
?>
<form method="post" action="<?= e(a_url('site')) ?>">
    <?= admin_csrf_field() ?>

    <?= a_card_start('Your business') ?>
    <?= f_text('name', 'Business name', $values['name'], ['max' => 80, 'required' => true]) ?>
    <?= f_text('tagline', 'Tagline', $values['tagline'], ['max' => 120, 'hint' => 'Shown in the footer.']) ?>
    <?= f_area('description', 'Short description of the business', $values['description'], ['rows' => 3, 'max' => 400, 'hint' => 'Used in structured data that helps Google understand what you do.']) ?>
    <?= f_area('alternate_names', 'Other names people use for you (one per line)', $values['alternate_names'], ['rows' => 3, 'hint' => 'For example "Incrymnia Computers". Helps Google connect these searches to your business.']) ?>
    <?= f_text('url', 'Website address (Site URL)', $values['url'], ['placeholder' => 'https://incrymnia.com', 'hint' => 'Your real domain. Used for canonical links, the sitemap and social previews.']) ?>
    <?= a_card_end() ?>

    <?= a_card_start('Contact') ?>
    <div class="a-grid a-grid--2">
        <?= f_text('email', 'Public email', $values['email'], ['type' => 'email', 'required' => true, 'hint' => 'Shown on the website.']) ?>
        <?= f_text('quote_to', 'Email that receives quote requests', $values['quote_to'], ['type' => 'email', 'required' => true]) ?>
        <?= f_text('phone', 'Phone (as shown)', $values['phone'], ['hint' => 'How it appears on the page.']) ?>
        <?= f_text('phone_href', 'Phone (international format)', $values['phone_href'], ['placeholder' => '+61485673647', 'hint' => 'No spaces, no leading 0 after +61. Used for tap-to-call and Google.']) ?>
    </div>
    <?= f_text('address', 'Where you operate', $values['address'], ['max' => 160, 'hint' => 'Shown on the website. A suburb list or "Central and South East Melbourne" is fine.']) ?>
    <?= f_area('service_areas', 'Areas you serve (one per line)', $values['service_areas'], ['rows' => 4, 'hint' => 'Used for local search, e.g. Melbourne, South East Melbourne.']) ?>
    <?= a_card_end() ?>

    <?= a_card_start('Opening hours') ?>
    <table class="a-hours">
        <thead><tr><th>Day</th><th>Opens</th><th>Closes</th><th>Closed</th></tr></thead>
        <tbody>
        <?php foreach (DAY_KEYS as $day):
            $entry  = $schedule[$day] ?? null;
            $isOpen = is_array($entry) && count($entry) === 2;
        ?>
            <tr>
                <th scope="row"><?= e(DAY_NAMES[$day]) ?></th>
                <td><input class="a-input" type="time" name="open[<?= e($day) ?>]" value="<?= e($isOpen ? (string) $entry[0] : '09:00') ?>" aria-label="<?= e(DAY_NAMES[$day]) ?> opens"></td>
                <td><input class="a-input" type="time" name="close[<?= e($day) ?>]" value="<?= e($isOpen ? (string) $entry[1] : '17:00') ?>" aria-label="<?= e(DAY_NAMES[$day]) ?> closes"></td>
                <td><input type="checkbox" name="closed[<?= e($day) ?>]" value="1" <?= $isOpen ? '' : 'checked' ?> aria-label="<?= e(DAY_NAMES[$day]) ?> closed"></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?= a_card_end() ?>

    <?= a_card_start('Service details', 'These values are slotted into text across the site wherever it says {warranty}, {days} or {reply}.') ?>
    <div class="a-grid a-grid--3">
        <?= f_text('warranty_months', 'Warranty (months)', $values['warranty_months'], ['type' => 'number']) ?>
        <?= f_text('build_days', 'Typical build time', $values['build_days'], ['max' => 60, 'hint' => 'e.g. 5 to 7 business days']) ?>
        <?= f_text('reply_time', 'We reply to quotes within', $values['reply_time'], ['max' => 60, 'hint' => 'e.g. one to two business days']) ?>
    </div>
    <?= a_card_end() ?>

    <?= a_card_start('Social media', 'Add the full address of each profile you have. These are shown in the footer and help Google link your profiles to your business.') ?>
    <div class="a-grid a-grid--2">
        <?php foreach (SOCIAL_NETWORKS as $key => $label): ?>
            <?= f_text('social[' . $key . ']', $label, (string) ($social[$key] ?? ''), ['placeholder' => 'https://...', 'max' => 200]) ?>
        <?php endforeach; ?>
    </div>
    <?= a_card_end() ?>

    <?= a_save_bar() ?>
</form>
<?php
a_foot();
