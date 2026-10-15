<?php
/**
 * Handles the quote form.
 *
 * Expects $cfg (config array) and a started session.
 * Produces $quote = [errors, old, sent, ref].
 *
 * Flow: validate -> save to storage/quotes.jsonl -> email -> redirect (PRG)
 * so a refresh never re-submits the form.
 */

declare(strict_types=1);

$quote = ['errors' => [], 'old' => [], 'sent' => false, 'ref' => ''];

/* Success flash after redirect */
if (!empty($_SESSION['quote_flash'])) {
    $quote['sent'] = true;
    $quote['ref']  = (string) $_SESSION['quote_flash'];
    unset($_SESSION['quote_flash']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'quote') {

    $errors   = [];
    $site     = $cfg['site'];
    $buildOpts = $cfg['builds'];
    $budgets  = $cfg['quote']['budgets'];
    $timelines = $cfg['quote']['timelines'];
    $extrasOpts = $cfg['quote']['extras'];

    $old = [
        'name'     => clean_line($_POST['name'] ?? ''),
        'email'    => clean_line($_POST['email'] ?? ''),
        'phone'    => clean_line($_POST['phone'] ?? ''),
        'build'    => clean_line($_POST['build'] ?? ''),
        'budget'   => clean_line($_POST['budget'] ?? ''),
        'timeline' => clean_line($_POST['timeline'] ?? ''),
        'message'  => clean_text($_POST['message'] ?? ''),
        'extras'   => [],
    ];

    if (isset($_POST['extras']) && is_array($_POST['extras'])) {
        foreach ($_POST['extras'] as $key) {
            if (is_string($key) && isset($extrasOpts[$key])) {
                $old['extras'][] = $key;
            }
        }
        $old['extras'] = array_values(array_unique($old['extras']));
    }

    /* Anti-spam: honeypot. Bots fill it, people never see it. Pretend it worked. */
    if (clean_line($_POST['website'] ?? '') !== '') {
        $_SESSION['quote_flash'] = 'Q-' . date('Ymd') . '-0000';
        header('Location: ' . preg_replace('/[^\x21-\x7e]/', '', (string) strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?')) . '#quote');
        exit;
    }

    /* Session / CSRF */
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session expired. Please check your details and send the form again.';
    }

    /* Anti-spam: too fast to be a person */
    $rendered = (int) ($_SESSION['form_rendered'] ?? 0);
    if ($rendered > 0 && (time() - $rendered) < 3) {
        $errors['form'] = 'That was very quick. Please check your details and send the form again.';
    }

    /* Rate limit: 5 requests per hour per session */
    $times = array_filter(
        (array) ($_SESSION['quote_times'] ?? []),
        static fn ($t) => is_int($t) && $t > time() - 3600
    );
    if (count($times) >= 5) {
        $errors['form'] = 'You have sent several requests recently. Please email us directly at ' . $site['email'] . '.';
    }

    /* Field validation */
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) {
        $errors['name'] = 'Enter your name (2 to 100 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 150) {
        $errors['email'] = 'Enter a valid email address so we can send your quote.';
    }
    if ($old['phone'] !== '' && !preg_match('/^[0-9+()\-.\s]{6,25}$/', $old['phone'])) {
        $errors['phone'] = 'Enter a phone number using digits, spaces, + or -, or leave it blank.';
    }
    if (!isset($buildOpts[$old['build']]) && $old['build'] !== 'other') {
        $errors['build'] = 'Choose the kind of computer you need.';
    }
    if (!isset($budgets[$old['budget']])) {
        $errors['budget'] = 'Choose a budget range. “Not sure yet” is fine.';
    }
    if (!isset($timelines[$old['timeline']])) {
        $errors['timeline'] = 'Choose when you need it.';
    }
    if (mb_strlen($old['message']) < 10) {
        $errors['message'] = 'Tell us a little more about what you will use it for (at least 10 characters).';
    } elseif (mb_strlen($old['message']) > 3000) {
        $errors['message'] = 'Please keep your message under 3,000 characters.';
    }
    if (empty($_POST['consent'])) {
        $errors['consent'] = 'Tick the box so we know we can contact you about this request.';
    }

    if ($errors === []) {
        $ref = 'Q-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

        $record = $old + [
            'ref'     => $ref,
            'created' => date('c'),
            'ip'      => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'agent'   => mb_substr(clean_line($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
        ];

        $buildLabel = $old['build'] === 'other' ? 'Something else' : $buildOpts[$old['build']]['label'];

        $saved  = save_quote($record);
        $mailed = send_quote_mail($cfg, $record, $buildLabel);

        if ($saved || $mailed) {
            $times[] = time();
            $_SESSION['quote_times'] = array_values($times);
            unset($_SESSION['form_rendered']);
            $_SESSION['csrf']        = bin2hex(random_bytes(32));
            $_SESSION['quote_flash'] = $ref;

            $self = preg_replace('/[^\x21-\x7e]/', '', (string) strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?'));
            header('Location: ' . $self . '#quote');
            exit;
        }

        $errors['form'] = 'We could not send your request just now. Please email us at ' . $site['email'] . ' and we will sort out your quote.';
    }

    $quote['errors'] = $errors;
    $quote['old']    = $old;
    $quote['sent']   = false;
    http_response_code($errors ? 422 : 200);
}
