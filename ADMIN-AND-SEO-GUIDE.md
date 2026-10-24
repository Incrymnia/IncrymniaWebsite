# Incrymnia: admin panel and SEO guide

## 1. First-time admin setup (2 minutes)

1. Upload/unzip the site, then open `https://your-domain/admin/` (locally: `http://localhost:8000/admin/`).
2. The page asks for a **setup code**. Open `storage/setup-code.txt` on your server (file manager, FTP, or your project folder locally) and type the code in. This proves you own the server, so nobody else can claim the admin account first.
3. Choose a username and a password of 12+ characters. Done. The setup code file is deleted automatically.

Forgot the password later? Run `php tools/admin-password.php` on the server, or delete `storage/admin.json` and repeat the setup.

### How the password is protected
- Only a hash is stored (`storage/admin.json`): **Argon2id** (bcrypt if the server lacks it) with a unique random salt per hash.
- Before hashing, the password is mixed with a secret server-side key (a "pepper") stored in `storage/secret.key`. **Back this file up with admin.json**: without it the password cannot be verified (use the reset tool if lost).
- 5 failed logins lock that IP address for 15 minutes; attempts are logged (Account page).
- Session cookie is HttpOnly + SameSite=Strict (+ Secure on https), a new session id is issued at login, logout happens after 30 minutes idle (8 hours max), and every form carries a CSRF token.
- The admin sends `noindex` and strict security headers, and robots.txt tells search engines to stay out.
- Uploaded images are re-encoded with GD (strips hidden data), stored in `uploads/` where PHP cannot execute.

Use HTTPS on the live site. Without it, passwords travel unencrypted.

## 2. What you can change from the admin panel

| Page | What it controls |
|---|---|
| Business details | Name, contact, opening hours, areas served, social links, warranty/build time |
| Search and SEO | Home page Google title/description (live preview), **titles/headlines for Gallery, Services, About, FAQ and Contact**, share image, Search Console / Bing / Analytics |
| Page text | Headlines and paragraphs across the home page |
| Build types and pages | Gaming / Office and Study / Workstation / Server cards **and each sub-page's content, FAQ and Google title** |
| Gallery | Upload builds with photos, edit, reorder, remove |
| Image library | Upload and delete images (auto-resized to fast WebP) |
| Services, FAQ and lists | Services, process steps, FAQ, headline numbers, brands |
| Quote requests | Everything sent from the quote form (CSV export) |
| Backups | Automatic backup before every save; one-click restore |

Edits are stored in `storage/content.json`; the defaults in `config.php` are untouched. SMTP/Gmail settings are deliberately **not** editable from the panel.

## 3. What was done for SEO

- **A real multi-page site** (each page has its own address, title, description and content):
  - Home `/`, Gallery `/gallery/`, Services `/services/`, About `/about/`, FAQ `/faq/`, Contact and quote `/contact/`
  - Four build sub-pages: **Gaming PCs** `/gaming-pcs/`, **Office and study PCs** `/office-study-pcs/`, **Workstations** `/workstations/`, **Servers** `/servers/`
  - The old `/study-pcs/` address redirects permanently to `/office-study-pcs/`.
  One page per topic is the biggest ranking factor available to a small site. The Builds menu in the header lists the four sub-pages, and every page links to the others.
- **Structured data (JSON-LD)**: LocalBusiness/ComputerStore with name variants ("Incrymnia Computers"), areas served, opening hours, logo, services catalogue, social profiles; plus WebSite, WebPage, BreadcrumbList, Service and FAQPage.
- **Meta**: unique title/description per page, canonical URLs, Open Graph + Twitter cards (1200x630 share image), `lang="en-AU"`, favicons/touch icons/web manifest.
- **`/sitemap.xml`** (auto-generated, includes gallery images) and **`/robots.txt`** (blocks admin/private folders, points to the sitemap).
- **Speed (a Google ranking factor)**: your 10 MB workstation PNG became a ~90 KB WebP. Images are responsive (`srcset`), lazy-loaded with width/height set; fonts load without blocking; Apache gzip + long-lived caching in `.htaccess`.
- Real 404 page, clean trailing-slash URLs, internal links between pages, descriptive alt text.

## 4. Your to-do list to actually appear on Google

Code can make the site eligible and easy to understand; **ranking also depends on things only you can do**:

1. **Set the real address**: Admin > Business details > Website address (e.g. `https://incrymnia.com`). Put the site live on HTTPS.
2. **Google Search Console** (free): add your domain, verify using the code in Admin > Search and SEO, then submit `https://your-domain/sitemap.xml`. Use "URL inspection > Request indexing" for the home page and the four service pages.
3. **Bing Webmaster Tools**: same steps (also feeds DuckDuckGo/Yahoo).
4. **Google Business Profile** (free): create it as a *service-area business* (hide your home address). This is what makes you show up in Maps and "near me" searches, and it is the strongest signal for local queries and for your brand name.
5. **Be consistent everywhere**: same name "Incrymnia", email and phone on Facebook, Instagram, YouTube, TikTok, LinkedIn, then add those links under Business details. Google uses them to confirm who you are.
6. **Get real reviews** on Google from happy customers, and a few links from local directories/forums/communities (PC building groups, local business directories, suppliers you buy from).
7. **Keep adding builds**: new photos with good descriptions give Google fresh content. Write alt text that says what the build is.
8. **Be patient**: new sites usually take days to a few weeks to be indexed and months to rank for competitive phrases. Searching your exact brand ("incrymnia") should work within weeks of indexing. Broad phrases like "computer building" are dominated by big retailers and guides; the pages target realistic ones like "custom gaming PC builder Melbourne".

## 5. Server notes

- PHP 8.1+, with the **GD** extension (WebP support recommended) for uploads.
- Uploads: set `upload_max_filesize = 16M` and `post_max_size = 32M` in php.ini (local dev: `php -d upload_max_filesize=16M -d post_max_size=32M -S localhost:8000 router.php`).
- Local development: run **`php -S localhost:8000 router.php`** (the router replaces `.htaccess` rewrites, so `/gaming-pcs/` and `/sitemap.xml` work locally).
- Apache: enable `mod_rewrite` (and optionally `mod_deflate`, `mod_expires`, `mod_headers`). On nginx, copy the rules from `.htaccess` (README) and deny `/storage/`, `/includes/`, `/partials/`, `/tools/`.
- Folders `storage/` and `uploads/` must be writable by the web server.
- After HTTPS works, uncomment the 3 redirect lines at the bottom of `.htaccess`.
- You can delete the large original images in `assets/img/` (`matrix-5090.png`, `crimson-workstation.png`, `the-incryminator-server-no-bg.png`): the site now uses the optimised copies in `uploads/`.
