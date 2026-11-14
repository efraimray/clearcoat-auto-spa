# How Clearcoat Auto Spa Was Built

This is the plain-language record of how the Clearcoat Auto Spa booking website was made: which languages it uses, what each file does, how it was tested, and what went wrong along the way.

| | |
| --- | --- |
| **Project** | Clearcoat Auto Spa (repo `clearcoat-auto-spa`) |
| **Type** | Friday website showcase · Local Service / Booking website · car wash and detailing · PHP (no framework) |
| **Version** | 1.0.0 |
| **Built** | 2026-10-30, finished 2026-11-14 after a retry, by Claude (nightly showcase run) for Efraim Ray |
| **Difficulty** | Intermediate (PHP sessions, file locking, a slot algorithm, JavaScript UI) |
| **Time to install/run** | About 2 minutes locally (`php -S localhost:8000`); about 10 minutes on cPanel |

## 1. Languages Used

Line counts come from `find . -name "*.<ext>" | xargs cat | wc -l`, excluding `demo/` (generated), `storage/` and the publisher scripts.

| Language | Lines | Share of code | Files | What it does here |
| --- | ---: | ---: | --- | --- |
| PHP | 2,117 | 62.7% | 9 pages, `ics.php`, `api/slots.php`, 6 files in `inc/`, 3 in `tools/` | Renders every page, holds the slot engine, validates and saves bookings under a file lock, CSRF tokens, contact form, .ics files, static export, tests |
| JavaScript | 765 | 22.7% | `assets/js/main.js`, `engine.js`, `booking.js`, `tools/test-engine.js` | Booking wizard, price switcher, mobile menu, theme toggle, form validation, the JavaScript twin of the slot engine, parity test |
| CSS | 424 | 12.6% | `assets/css/style.css` | Design tokens, light and dark themes, layout for 360px to desktop, every component |
| JSON | 69 | 2.0% | `data/services.json`, `staff.json`, `settings.json` | Services and prices, staff schedules and skills, hours, holidays, business details |

**In one sentence:** PHP builds the pages and decides which times can be booked, JavaScript makes the booking wizard feel instant, CSS gives it the look in both themes, and JSON holds everything the owner may want to change.

HTML is written inside the PHP templates (and generated as plain `.html` files for the demo), so it is not counted separately.

### Frameworks and APIs

- No framework and no libraries. Plain PHP 7.4+ and vanilla JavaScript (ES5).
- PHP built-ins: sessions, `random_bytes`, `hash_equals`, `flock`, `json_encode/decode`, `mail`.
- Browser APIs: `fetch`, `localStorage` (demo only, wrapped in try/catch), `crypto.getRandomValues`, `Blob` + `URL.createObjectURL` (demo .ics download), `IntersectionObserver`.
- Standards: schema.org JSON-LD (`AutoWash`, `FAQPage`), iCalendar RFC 5545, Open Graph, sitemaps.org.
- Fonts: Archivo and Instrument Sans (SIL OFL), self-hosted `.woff2` from the `@fontsource` npm packages.

### Tools used to build and test

| Tool | What it was used for |
| --- | --- |
| `php -l` (PHP 8.3 CLI) | Syntax check of all 20 PHP files |
| `php -S` | Local web server for the real PHP site |
| `node --check` (Node 22) | Syntax check of the JavaScript files and the Blogger post script |
| `tools/test-engine.php` | 20 checks of the slot engine and booking rules |
| `tools/test-engine.js` | 13 checks that the JavaScript engine gives exactly the same results as PHP |
| Playwright + Chromium | 26 browser checks of both the PHP site and the static demo |
| Python `http.server` | Serving the static demo locally |
| Python + Pillow | Converting screenshots to JPEG, composing the phone sample image |
| PowerShell 7.4.6 (Linux) + a stand-in `gh` | Testing `publish-to-github.ps1` end to end |
| `zip` | Packaging the release |
| GitHub CLI + `publish-to-github.bat` | Publishing (run by Efraim on Windows) |

**Build step?** None. No npm, Composer or database. `php tools/export-static.php` regenerates the static demo when the PHP pages change.

## 2. How It Works (the 30-second version)

```
Page map
  index ── services ── book ──► confirmation ──► ics.php (calendar file)
    │          │          ▲
    about     faq     contact     privacy     404

Booking flow (PHP site)
  Customer ─► book.php wizard ─► api/slots.php ─► cc_slots()  ◄─ data/*.json
              (booking.js)                         (free times only)
           └─ Confirm (POST + CSRF) ─► cc_create_booking()
                                         └─ LOCK storage/bookings.php
                                            re-check the slot, save, unlock
                                         └─ 303 redirect ─► confirmation.php?ref=…&t=…
```

The key idea: the list of open times is only a suggestion. The real decision happens at the moment of saving, inside an exclusive file lock, where the server recomputes the open times with the very latest bookings. Two people can both see 9:00 AM as free, but only one of them can save it.

## 3. Step-by-Step: How We Built It

### Step 1: Checked the day and the rules

The run started on Friday 2026-10-30 at 19:57 Manila time. Day 30 means week 5 (`ceil(30/7)`), so the website type is whichever type was built least recently. Only a Professional practice site (Ngiti Dental Studio) had been built on a Friday, so Brochure, E-commerce and Booking had never been built. I chose **Local Service / Booking**, which by the rules uses **PHP without a framework**.

### Step 2: Checked GitHub and improvement requests

`gh api user` answered `efraimray`, but every repository endpoint was refused (the cloud session is not connected to his repositories), and the GitHub issues page refuses automated reading. So no `claude-improve` issues could be read tonight and that step was skipped.

### Step 3: Avoided repeats and picked the niche and name

I listed the artifact gallery (25 artifacts) and compared it with the list of earlier projects. Bakery (Bloomhill Bakeshop brand kit) and dental were taken. A **car wash and detailing shop** suits a booking site well: services have clear lengths, prices change by vehicle size, and staff have different skills.

Name: **Clearcoat Auto Spa** (plain English, says what it does; slug `clearcoat-auto-spa`). A web search for the name found no well-known business or product using it.

### Step 4: Planned the data (JSON)

- `data/services.json`: 8 services from Express Exterior Wash (30 min, ₱180 sedan) to Full Detail Package (4 hr, ₱3,800–₱5,200), plus a motorcycle-only service. Prices are per vehicle type: sedan, SUV/pickup, van, motorcycle.
- `data/staff.json`: 3 fictional detailers with initials avatars, working days and the services each can do (for example, Dexter does washes and engine bays but not interiors, and is off on Tuesdays).
- `data/settings.json`: business details for Sayre Highway, Valencia City; hours Mon–Sat 8–6 and Sun 8–2; 2026 holidays (All Saints' Day, Bonifacio Day, Christmas...); 30-minute steps, 60-minute lead time, 21 days ahead; `demo_busy` for realistic sample bookings.

### Step 5: Wrote the PHP core (`inc/`)

- `functions.php`: escaping (`e()`), links that work as `.php` or `.html`, CSRF tokens, input cleaning, Philippine mobile number check, and `cc_with_store()`, which opens the storage file, takes an exclusive lock, lets a function change the data and writes it back.
- `booking.php`: the slot engine `cc_slots()`, `cc_create_booking()` (validates every field, then re-checks the slot inside the lock), booking references like `CC-1107-K4TP`, private lookup by reference + random token, and the `.ics` builder (Manila time converted to UTC, CRLF line endings, 1-hour alarm).
- `cc_demo_busy()` adds 0–2 sample busy blocks per detailer per day using a 32-bit FNV-1a hash of the date and detailer, so the demo schedule looks real but is the same for everyone.
- `header.php` / `footer.php`: shared head (title, description, canonical, Open Graph, AutoWash JSON-LD, preloaded fonts, theme script), skip link, navigation, footer with hours table, address, map link, social placeholders, and the floating Call / Book buttons with the phone number shown as text.
- `icons.php`: the droplet "C" logo, 23 line icons and the hero car illustration, all inline SVG.

### Step 6: Wrote the pages (PHP)

`index.php` (hero, next free slot, services, how booking works, sample testimonials labeled as samples), `services.php` (price switcher and full table), `book.php` (wizard + POST handler with CSRF and honeypot), `confirmation.php`, `about.php` (story, facts, team), `faq.php` (9 questions + FAQPage JSON-LD), `contact.php` (form with CSRF, honeypot, validation, Post/Redirect/Get), `privacy.php` (Data Privacy Act RA 10173), `404.php`, plus `ics.php` and `api/slots.php`.

### Step 7: Wrote the JavaScript

- `engine.js`: a line-by-line JavaScript copy of the PHP slot rules (works in the browser and in Node).
- `booking.js`: the five-step wizard (vehicle filters services and prices, service disables detailers who can't do it, date strip with closed days crossed out, time buttons, details validation, review), the home page "next free slot" line, and the static demo's confirmation page and .ics download.
- `main.js`: mobile menu (Escape closes it), theme toggle saved in `localStorage`, "Open now / Closed now" badge in Manila time, price switcher with arrow-key support, contact form validation, hiding the floating buttons over the footer.

### Step 8: Styled it (CSS)

Concept: wash-bay signage. Heavy Archivo headings, Instrument Sans text, aqua for water and links, signal yellow only for "Book" actions. All colours are tokens with a full dark theme (both `prefers-color-scheme` and a manual toggle), visible focus rings, reduced-motion support, and layouts that stack down to 360px. Fonts are self-hosted with a `latin-ext` file so the ₱ sign renders in the brand font.

### Step 9: Security and privacy details

CSRF token on every form, honeypot fields, server-side validation, output escaping, security headers, `.htaccess` rules that block `data/`, `storage/`, `inc/` and `tools/`, private confirmation links, and an API that returns only free times.

### Step 10: Built the static export

`tools/export-static.php` renders every page with a `CC_STATIC` flag: links become `.html`, sessions are off, and the JavaScript simulates the server (slots from `engine.js`, bookings in `localStorage`, contact form success state). It also writes an "About this project" page with live language counts, a sitemap and robots.txt into `demo/`. Result: 10 pages.

### Step 11: Tested everything

**Engine tests** (`php tools/test-engine.php`, fixed "now" of Friday 2026-10-30 10:05 Manila):

| Case | Expected | Result |
| --- | --- | --- |
| Wash & Vacuum, today | first slot after 10:05 + 60 min lead | PASS (12 slots) |
| Full Detail (4 hr), Saturday | never ends after 6:00 PM | PASS (13 slots) |
| Interior Deep Clean with Dexter | refused: not his service | PASS |
| Motorcycle wash with Dexter on Tuesday | refused: day off | PASS |
| Express wash with Ana on Sunday | last start fits before 2:00 PM | PASS (4 slots) |
| Any service on All Saints' Day | "Closed: All Saints' Day." | PASS |
| Yesterday / 46 days ahead | refused | PASS / PASS |
| Same slot booked twice | second refused | PASS |
| Overlapping slot (+30 min of a 60-min job) | refused | PASS |
| Motorcycle service for a sedan | refused | PASS |
| Bad mobile number, no consent | both errors | PASS |
| Lookup with wrong token | nothing returned | PASS |
| .ics start time | Manila minus 8 hours, CRLF | PASS |
| Storage file | starts with `<?php exit; ?>` | PASS |

Total: **20 passed, 0 failed**. The parity test (`node tools/test-engine.js`) ran the same 10 slot cases plus 3 hash checks through `engine.js`: **13 passed, 0 failed**, so the demo and the PHP site agree exactly.

**Browser tests** (Playwright + Chromium, 26 checks, all passed in the final run):

| Check | Result |
| --- | --- |
| 10 static pages × 1280px and 390px × light and dark: no horizontal scroll | PASS (4 of 4) |
| Same 40 page loads: no page or console errors | PASS (4 of 4) |
| Internal links from the home page (14 static, 13 PHP) | PASS, none broken |
| Mobile menu opens and closes; theme toggle | PASS |
| Full booking flow, static demo, 1280px and 390px | PASS (validation, SUV price ₱1,500, detailer filter, slot, details, confirmation, `.ics` download) |
| Full booking flow on the PHP site | PASS, including `ics.php` with `text/calendar` |
| Booked slot disappears for that detailer (static and PHP) | PASS |
| Two simultaneous POSTs for the same slot | PASS: one 303 redirect, one refusal |
| Wrong CSRF token | PASS: refused |
| `storage/bookings.php` opened in a browser | PASS: 0 bytes shown |
| Contact form, static and PHP | PASS: errors first, then success state |

**Measurements:** 20 PHP files, `php -l` 0 errors; CSS 35.6 KB raw / **7.5 KB gzipped**; JavaScript 36.5 KB raw / **12.0 KB gzipped** (main 2.7 + engine 2.9 + booking 6.7); fonts 104 KB for 7 files; **0 external requests**. Lighthouse was not run.

**Problems found and fixed:**

1. **CSRF check always failed on the PHP site.** The session started only when the form printed its token, after the page had begun to output, so the session cookie was never sent. Fix: start the session in `inc/bootstrap.php` before any output.
2. **`storage/bookings.json` could be downloaded** from `php -S` (and from nginx), because those servers ignore `.htaccess`. Fix: storage moved to `.php` files that begin with `<?php exit; ?>`; a new test confirms the file shows nothing.
3. **The static export warned about a constant defined twice** when it rendered several pages in one run. Fix: `defined('CC_VERSION') || define(...)`.
4. **"Today" looked bookable after closing time** in the date strip. Fix: today is greyed out when no start time is left.
5. **The race test used a fixed date that had passed** when the run was retried on 2026-11-14. Fix: moved it to 2026-11-19 (a Thursday, when Dexter works).

### Step 12: Wrote the docs

README (features, folder map, quick start, configuration, rebranding for a client, testing, roadmap, `claude-improve`), `docs/TUTORIAL.md` (architecture, each main function, key concepts, 3 exercises), `docs/DEPLOYMENT.md` (double-click publisher, GitHub Desktop, git CLI, version bumps, cPanel, nginx, custom domain, connecting forms), CHANGELOG, MIT LICENSE, `.gitignore` (keeps customer data out of git) and `.gitattributes`.

### Step 13: Showcase artifact

Published "Showcase · Clearcoat Auto Spa Website" as a multi-file artifact: the static `index.html` as the page (with its own `<title>` set to that name) and the other 9 pages, CSS, 3 scripts, 7 fonts and 3 images as files, so every page is clickable. The "About this project" page is linked in the footer. The site itself is the demo in `demo/`.

### Step 14: The one-click GitHub publisher and its test

`publish-to-github.bat` and `.ps1` were copied from the proven Ambag Drive template (it contains the LAST STEP social-preview block) and only the settings block was changed: slug, name, version 1.0.0, description (265 characters, ASCII) and topics (`efraimray-showcase`, php, booking-system, appointment-booking, website-template, car-wash, small-business, philippines). Both files are ASCII with CRLF line endings.

Test with PowerShell 7.4.6 on Linux, a stand-in `gh` script and a local bare repository (via `url.insteadOf`):

| Check | Result |
| --- | --- |
| First run: git init, commit "Clearcoat Auto Spa v1.0.0", create repo, push `main` | PASS |
| Tag `v1.0.0` pushed | PASS |
| Release zip: 88 files, all under `clearcoat-auto-spa/`, no `.git`, `.github` or `publish-to-github` entries | PASS |
| LAST STEP finds `.github/social-preview.jpg` | PASS |
| Second run: "No new changes", repo and release reused, no errors | PASS |

The only noise was Linux's `xdg-open` having no browser when the script opens the Settings page, which is expected in the test sandbox. **The project is published by double-clicking `publish-to-github.bat`** on Efraim's Windows PC.

### Step 15: Blogger post

Built `2026-11-14-blogger-clearcoat-auto-spa.html` from the reference template: settings comment (title, merged Labels line of 172 characters starting with "Projects" and ending with "Efraim Ray, Web Developer in Bukidnon", links, a 147-character search description), one JSON-LD `@graph` with `TechArticle` + `FAQPage`, the template's CSS copied verbatim plus a `cca-` block, a working booking slot demo inside the browser frame, a blank screenshot slot, 4 blank sample-image slots, measured scorecard, real code excerpts, roadmap, slider, links, verdict and 6 FAQs. The FAQ text comes from one Python list for both the visible questions and the schema. Checks: `node --check` on the script, order of the comment lines, label length, JSON-LD parses, FAQ text identical, 4 slots with matching alt text, and Playwright at 900px and 390px in light and dark: no overflow, no errors, demo booking confirmed, FAQ opens, code tabs and slider work.

### Step 16: Featured image

Took full-length screenshots of 7 pages at 1280px (FAQ and contact in dark) and the home page at 390px, then laid them out as an isometric board (`rotateX(48deg) rotateZ(-30deg)`) with the complete home page in the centre column, centred by JavaScript after load. The first render used `scale(.62)` and showed only a zoomed-in slice of the home page, so it was reduced to `scale(.185)`. Text on the left: pill, name in two lines, subtitle, 3 stats (9 pages, 2 colour themes, 0 external requests), PHP chip and 2 feature chips, `efraimray.com` badge. Bounding boxes were measured and nothing overlaps. Saved at 2400×1260 as `clearcoat-auto-spa-featured.jpg`, copied to `.github/social-preview.jpg` and resized to `assets/img/og-image.jpg` for Open Graph.

### Step 17: Sample images

Four real screenshots at 1200×675, device scale 2 (2400×1350), floating buttons hidden:

1. Home page, light: hero, open status and next free slot.
2. Booking wizard step 3, light: SUV + Interior Deep Clean with Ana, 9:00 AM picked, ₱1,800 summary.
3. Confirmation, dark: reference, service, time, detailer and price after a real demo booking.
4. Services on a 390px phone, dark, SUV selected (₱1,500 Hand Wax & Polish), centred on a #0b1220 canvas.

The first phone shot cut the headline in half, so it was retaken scrolled to a full price card. A first capture script failed because the hidden radio inputs could not be clicked directly; it was changed to click the visible time buttons.

### Step 18: Packaging

Zipped the folder as `clearcoat-auto-spa.zip` with the project folder at the top level, keeping `.gitignore`, `.gitattributes`, `.github/`, `docs/screenshots/` and the publisher files, without `.git` and without any test bookings in `storage/`.

## 4. What You Received

| Deliverable | Where it lives |
| --- | --- |
| Full project (PHP site, static demo, docs, publisher) | `clearcoat-auto-spa.zip` |
| Showcase artifact (all pages clickable) | "Showcase · Clearcoat Auto Spa Website" in the Claude artifacts gallery |
| Live demo (after publishing) | https://efraimray.github.io/clearcoat-auto-spa/demo/ |
| Blogger post | `2026-11-14-blogger-clearcoat-auto-spa.html` |
| Featured image | `clearcoat-auto-spa-featured.jpg` and `.github/social-preview.jpg` |
| Sample image 1: home page | `clearcoat-auto-spa-sample-1.jpg` and `docs/screenshots/` |
| Sample image 2: booking step 3 | `clearcoat-auto-spa-sample-2.jpg` and `docs/screenshots/` |
| Sample image 3: confirmation, dark | `clearcoat-auto-spa-sample-3.jpg` and `docs/screenshots/` |
| Sample image 4: services on a phone | `clearcoat-auto-spa-sample-4.jpg` and `docs/screenshots/` |
| This build log, tutorial, deployment guide | `docs/BUILD-LOG.md`, `docs/TUTORIAL.md`, `docs/DEPLOYMENT.md` |
| One-click publisher | `publish-to-github.bat` + `publish-to-github.ps1` |

## 5. Known Limits and What's Next

- The **static demo** keeps bookings only in the visitor's browser and adds sample busy blocks. Real bookings need the PHP version on a PHP host.
- There is **no owner dashboard** yet: bookings are read from `storage/bookings.php` or from the e-mail notifications.
- E-mail uses PHP `mail()`; some hosts need SMTP instead (see DEPLOYMENT.md).
- JSON-file storage with a lock is fine for a small shop (a few hundred bookings a month). A very busy shop should move to SQLite.
- Tested with PHP 8.3 on the built-in server, not on a live cPanel host or Apache; Lighthouse was not run.
- Holidays for 2027 beyond New Year's Day must be added to `settings.json` each year.

**Roadmap:** owner dashboard with CSV export, SMS/Messenger reminders, self-cancel link, SQLite option.

**Want a change?** Open an issue on GitHub with the label `claude-improve` and the nightly run will pick it up, implement it, bump the version and prepare a new release.
