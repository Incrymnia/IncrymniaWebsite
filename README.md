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
- Emails the request using PHP `mail()`; your host needs mail configured. If you prefer SMTP,
  swap `send_quote_mail()` in `includes/functions.php` for PHPMailer or your provider's API
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
