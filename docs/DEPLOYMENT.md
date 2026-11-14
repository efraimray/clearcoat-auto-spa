# Deploying Clearcoat Auto Spa

## Easiest: double-click (Windows)

`publish-to-github.bat` publishes the project to GitHub in one go: it creates the public repository `clearcoat-auto-spa`, pushes the code, adds topics and the `claude-improve` label, tags `v1.0.0`, creates a release with `clearcoat-auto-spa.zip`, and turns on GitHub Pages for the live demo.

**One-time setup** (PowerShell):

```powershell
winget install Git.Git
winget install GitHub.cli
```

Close and reopen PowerShell after installing.

**Every time:**

1. Extract the zip into `C:\Users\YodnoX\Documents\_Codes` (or any folder).
2. Open the `clearcoat-auto-spa` folder and double-click `publish-to-github.bat`.
3. The first time, a browser opens to sign in to GitHub. Approve it.
4. When it says **ALL DONE**, the repository Settings page and File Explorer open. Scroll to **Social preview → Edit → Upload an image…**, press **Ctrl+V** in File name (the path of `.github\social-preview.jpg` is already copied) and press Enter.

It is safe to run again: it only pushes new commits and makes a release for a new version.

Live demo after a minute or two: https://efraimray.github.io/clearcoat-auto-spa/demo/

## With GitHub Desktop

1. File → Add local repository → choose the folder → "create a repository".
2. Commit all files, then **Publish repository** (untick "Keep this code private").
3. On github.com: Settings → Pages → Source "Deploy from a branch", Branch `main`, folder `/ (root)`.

## With the git command line

```bash
cd clearcoat-auto-spa
git init -b main
git add -A
git commit -m "Clearcoat Auto Spa v1.0.0"
gh repo create EfraimRay/clearcoat-auto-spa --public --source . --push \
  --description "PHP booking website template for a car wash" \
  --homepage https://efraimray.github.io/clearcoat-auto-spa/demo/
gh api -X POST repos/EfraimRay/clearcoat-auto-spa/pages -f "source[branch]=main" -f "source[path]=/"
```

## Bumping the version and releasing

1. Change the version in **all** of these: `inc/bootstrap.php` (`CC_VERSION`), `CHANGELOG.md` (new section), the README badge, and `$Version` in `publish-to-github.ps1`.
2. Rebuild the demo: `php tools/export-static.php`.
3. Run the tests: `php tools/test-engine.php` and `node tools/test-engine.js`.
4. Double-click `publish-to-github.bat`, or by hand:

```bash
git commit -am "Clearcoat Auto Spa v1.1.0"
git tag v1.1.0
git push origin main --tags
gh release create v1.1.0 --notes-file CHANGELOG.md
```

## Shared hosting (cPanel)

The real site (with server-side booking) needs PHP. GitHub Pages only serves the static demo.

1. Check the host runs **PHP 7.4 or newer** (cPanel → Select PHP Version).
2. In **File Manager**, open `public_html` (or a subfolder) and upload `clearcoat-auto-spa.zip`, then **Extract**. Move the contents of the `clearcoat-auto-spa` folder up into `public_html` if you want it at the domain root.
3. You can delete `demo/`, `docs/`, `tools/` and the `publish-to-github.*` files on the live server.
4. Make `storage/` writable by PHP: permissions **755** (or 775 if your host needs it). Never 777.
5. Edit `data/settings.json`: real phone, e-mail, address, `base_url` (e.g. `https://clearcoatautospa.ph/`), `notify_email`, and set `"demo_busy": false`.
6. Edit `sitemap.xml` and `robots.txt` with the real domain.
7. Visit the site, make a test booking, check `storage/bookings.php` was created, then delete the test booking from that file.

`.htaccess` blocks `data/`, `storage/`, `inc/` and `tools/`, sets the 404 page and caching. On nginx, add:

```nginx
location ~ ^/(data|storage|inc|tools)/ { deny all; }
error_page 404 /404.php;
```

Storage files also start with `<?php exit; ?>`, so even without these rules nobody can read them.

## Custom domain

- **Shared hosting:** point the domain's nameservers to the host, or add an **A record** to the host's IP, then add the domain in cPanel → Domains. Turn on free SSL (AutoSSL / Let's Encrypt).
- **GitHub Pages demo:** Settings → Pages → Custom domain, and a **CNAME** record `www → efraimray.github.io`. Remember the demo is static only.

## Connecting the forms

The PHP site already saves bookings and messages. To get them by e-mail:

1. **PHP mail (simplest):** set `"notify_email": "owner@yourdomain.ph"` in `data/settings.json`. Most cPanel hosts send PHP mail out of the box. Create the mailbox in cPanel and add SPF/DKIM (cPanel → Email Deliverability) so messages do not land in spam.
2. **SMTP:** if your host blocks `mail()`, replace `cc_notify()` in `inc/functions.php` with an SMTP library such as PHPMailer (one file include, no Composer needed).
3. **Formspree-style service (static hosting only):** for a static copy of the site, point the contact form `action` to the service's endpoint and remove `data-static-form`. Bookings still need PHP to prevent double booking.
4. **Google Sheets via Apps Script:** create a Sheet, Extensions → Apps Script, write a `doPost(e)` that appends `e.parameter` values to a row, deploy as a Web App (anyone), then add a `file_get_contents()` POST (or cURL) to that URL inside `cc_notify()`. Never put private keys in the repository.

## After going live

- Remove "Sample testimonials" and the fictional team, or replace them with real ones.
- Add the business to Google Business Profile with the same name, address and phone as the site.
- Back up `storage/` regularly (cPanel → Backup, or download the files).
