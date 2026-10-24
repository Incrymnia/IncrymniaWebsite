<?php
declare(strict_types=1);

$site    = $cfg['site'];
$errors  = $quote['errors'];
$old     = $quote['old'];
$builds  = $cfg['builds'];
$opts    = $cfg['quote'];

/* Which build is pre-selected: previous submission, then ?build=, then none */
$selected = old($old, 'build');
if ($selected === '' && isset($quotePreselect) && isset($builds[$quotePreselect])) {
    $selected = (string) $quotePreselect;
}
if ($selected === '' && isset($_GET['build']) && is_string($_GET['build']) && isset($builds[$_GET['build']])) {
    $selected = $_GET['build'];
}
$checkedExtras = isset($old['extras']) && is_array($old['extras']) ? $old['extras'] : [];

/* Remember when the form was first shown (used as a simple "too fast" check) */
if (empty($_SESSION['form_rendered'])) {
    $_SESSION['form_rendered'] = time();
}

/** Attributes that link an input to its error message. */
$aria = static function (string $field) use ($errors): string {
    return isset($errors[$field])
        ? ' aria-invalid="true" aria-describedby="err-' . $field . '"'
        : '';
};
$err = static function (string $field) use ($errors): string {
    return '<p class="field-error" id="err-' . $field . '" data-error-for="' . $field . '"'
        . (isset($errors[$field]) ? '' : ' hidden') . '>'
        . (isset($errors[$field]) ? e($errors[$field]) : '') . '</p>';
};
$fieldErrors = array_diff_key($errors, ['form' => true]);
?>
<section class="section" id="quote" aria-labelledby="quote-title">
    <div class="container quote">

        <aside class="card card--ember quote__info">
            <h2 id="quote-title"><?= e(txt($cfg, 'quote_title')) ?></h2>
            <p><?= e(txt($cfg, 'quote_intro')) ?></p>

            <ul class="contact">
                <li>
                    <?= icon('mail') ?>
                    <span><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></span>
                </li>
                <li>
                    <?= icon('phone') ?>
                    <span>
                        <?php if ($site['phone_href'] !== ''): ?>
                            <a href="tel:<?= e($site['phone_href']) ?>"><?= e($site['phone']) ?></a>
                        <?php else: ?>
                            <?= e($site['phone']) ?>
                        <?php endif; ?>
                    </span>
                </li>
                <li>
                    <?= icon('pin') ?>
                    <span><?= e($site['address']) ?></span>
                </li>
            </ul>

            <div class="hours">
                <h3><?= icon('clock') ?> Workshop hours</h3>
                <dl>
                    <?php foreach (hours_display((array) ($site['schedule'] ?? [])) as [$day, $time]): ?>
                        <div><dt><?= e($day) ?></dt><dd><?= e($time) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </aside>

        <div class="card quote__form">
            <?php if ($quote['sent']): ?>

                <div class="success" role="status" tabindex="-1" id="quote-success">
                    <span class="success__icon"><?= icon('check') ?></span>
                    <h3>Quote request sent</h3>
                    <p>Thanks. We will reply within <?= e($site['reply_time']) ?>. Your reference is <strong><?= e($quote['ref']) ?></strong>.</p>
                    <a class="btn btn-ghost" href="./#quote">Send another request</a>
                </div>

            <?php else: ?>

                <form method="post" action="#quote" id="quote-form" novalidate>
                    <input type="hidden" name="action" value="quote">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <div class="hp" aria-hidden="true">
                        <label>Leave this field empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <?php if ($errors): ?>
                        <div class="alert alert--error" role="alert" tabindex="-1" id="quote-errors">
                            <?php if (isset($errors['form'])): ?>
                                <p><?= e($errors['form']) ?></p>
                            <?php else: ?>
                                <p>Please fix the highlighted fields and send the form again.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <fieldset class="field-group">
                        <legend>What do you need?</legend>
                        <div class="choices">
                            <?php foreach ($builds as $key => $b): ?>
                                <label class="choice">
                                    <input type="radio" name="build" value="<?= e($key) ?>" <?= $selected === $key ? 'checked' : '' ?> required>
                                    <span class="choice__body"><?= icon($b['icon']) ?><span><?= e($b['label']) ?></span></span>
                                </label>
                            <?php endforeach; ?>
                            <label class="choice">
                                <input type="radio" name="build" value="other" <?= $selected === 'other' ? 'checked' : '' ?> required>
                                <span class="choice__body"><?= icon('help') ?><span>Not sure yet</span></span>
                            </label>
                        </div>
                        <?= $err('build') ?>
                    </fieldset>

                    <div class="grid-2">
                        <div class="field">
                            <label for="q-name">Your name</label>
                            <input class="input" type="text" id="q-name" name="name" autocomplete="name" maxlength="100" required value="<?= e(old($old, 'name')) ?>"<?= $aria('name') ?>>
                            <?= $err('name') ?>
                        </div>
                        <div class="field">
                            <label for="q-email">Email</label>
                            <input class="input" type="email" id="q-email" name="email" autocomplete="email" maxlength="150" required value="<?= e(old($old, 'email')) ?>"<?= $aria('email') ?>>
                            <?= $err('email') ?>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="q-phone">Phone <span class="optional">(optional)</span></label>
                            <input class="input" type="tel" id="q-phone" name="phone" autocomplete="tel" maxlength="25" value="<?= e(old($old, 'phone')) ?>"<?= $aria('phone') ?>>
                            <?= $err('phone') ?>
                        </div>
                        <div class="field">
                            <label for="q-budget">Budget</label>
                            <select class="input" id="q-budget" name="budget" required<?= $aria('budget') ?>>
                                <option value="">Choose a range</option>
                                <?php foreach ($opts['budgets'] as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= old($old, 'budget') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('budget') ?>
                        </div>
                    </div>

                    <div class="field">
                        <label for="q-timeline">When do you need it?</label>
                        <select class="input" id="q-timeline" name="timeline" required<?= $aria('timeline') ?>>
                            <option value="">Choose a timeframe</option>
                            <?php foreach ($opts['timelines'] as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= old($old, 'timeline') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $err('timeline') ?>
                    </div>

                    <fieldset class="field-group">
                        <legend>Anything extra? <span class="optional">(optional)</span></legend>
                        <div class="checks-grid">
                            <?php foreach ($opts['extras'] as $key => $label): ?>
                                <label class="check">
                                    <input type="checkbox" name="extras[]" value="<?= e($key) ?>" <?= in_array($key, $checkedExtras, true) ? 'checked' : '' ?>>
                                    <span><?= e($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div class="field">
                        <label for="q-message">What will you use it for?</label>
                        <textarea class="input" id="q-message" name="message" rows="5" maxlength="3000" required
                                  placeholder="Games or software you run, screen size, storage you need, anything you already own..."<?= $aria('message') ?>><?= e(old($old, 'message')) ?></textarea>
                        <div class="field-foot">
                            <?= $err('message') ?>
                            <span class="counter" id="message-counter" aria-hidden="true">0 / 3000</span>
                        </div>
                    </div>

                    <div class="field">
                        <label class="check">
                            <input type="checkbox" name="consent" value="1" required <?= !empty($_POST['consent']) ? 'checked' : '' ?><?= $aria('consent') ?>>
                            <span>I am happy to be contacted about this request.</span>
                        </label>
                        <?= $err('consent') ?>
                    </div>

                    <button class="btn btn-primary btn-lg btn-block" type="submit">Send quote request</button>
                    <p class="form-note">We only use your details to reply to this request.</p>
                </form>

            <?php endif; ?>
        </div>
    </div>
</section>
