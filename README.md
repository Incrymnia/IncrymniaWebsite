# Emberforge – PC and server builder website

A responsive, single-page PHP site for a custom PC builder: gaming PCs, school/study PCs,
workstations and NAS / storage servers. Styled after the "Ember" dark vermilion dashboard reference.

Plain PHP 8.1+, CSS and vanilla JS. No framework, no database, no build step.

## Run it locally

    cd emberforge
    php -S localhost:8000

Then open http://localhost:8000

## What to edit first

Open `config.php`. Everything marked **REPLACE** is placeholder text:

- Business name, email, phone, address, opening hours
- `quote_to` (where quote requests are emailed) and `mail_from` (an address on your own domain)
- Warranty length, typical build time and hero stats (the numbers are examples, not claims)
- Build types, services, process, FAQ, "how we work" and quote-form options

## Gallery

The gallery ships with illustrated sample entries. To use real photos, add a 4:3 image to
`assets/img/` and set `'image' => 'assets/img/my-build.jpg'` on the entry in `config.php`.

## Quote form

- Validates on the client (JS) and again on the server (PHP)
- Saves every request to `storage/quotes.jsonl` (one JSON object per line)
- Emails the request through Gmail SMTP (see "Email setup" below); no Composer or extra libraries needed
- Spam protection: CSRF token, hidden honeypot field, minimum fill time and a per-session rate limit
- Post/Redirect/Get, so refreshing never re-submits

Make sure `storage/` is writable by the web server (`chmod 750 storage`) and **not** web-accessible.
The included `.htaccess` blocks it on Apache. On nginx add, for example:

    location ~ ^/(storage|includes|partials)/ { deny all; }
    location = /config.php { deny all; }

Quote files contain personal information, so review your local privacy obligations and add a
privacy policy page before going live.

## Structure

    index.php               entry point
    config.php              content and settings
    includes/               helpers and quote form handler
    partials/               one file per page section
    assets/css/style.css    theme (colours are CSS variables at the top)
    assets/js/main.js       menu, tabs, gallery lightbox, form enhancements
    storage/                saved quote requests (private)

## Theme

Colours live in `:root` at the top of `assets/css/style.css`
(`--bg`, `--crimson`, `--ember`, ...). Font is Space Grotesk via Google Fonts, with a system fallback.


## Email setup (Gmail)

Quote requests are sent from your Gmail account to `quote_to` in `config.php`. The customer's address is set as
Reply-To, so hitting Reply in Gmail answers them directly.

1. Sign in to the Gmail account that will send the emails and turn on 2-Step Verification.
2. Go to https://myaccount.google.com/apppasswords and create an App Password (16 letters).
3. Copy `config.local.example.php` to `config.local.php` and paste the App Password in.
   `config.local.php` is private: blocked in `.htaccess` and ignored by git.
4. Test it from a terminal in the project folder:

        php tools/test-mail.php

   It prints each step and tells you if something is wrong. Then submit the real form once.

Troubleshooting:

- **"Gmail rejected the login"**: you need an App Password, not your normal password.
- **Certificate or SSL error on Windows**: enable `extension=openssl` in `php.ini` and set
  `openssl.cafile` to a CA bundle (download `cacert.pem` from curl.se/docs/caextract.html).
- **Connection timeout**: your network blocks port 465. Set `'port' => 587` and `'encryption' => 'tls'`.
- **Nothing arrives**: check Spam, then `storage/mail.log`. Quotes are always saved to `storage/quotes.jsonl` too.
