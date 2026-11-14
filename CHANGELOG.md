# Changelog

All notable changes to Clearcoat Auto Spa are listed here. This project uses [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-10-30

First release.

### Added
- Nine-page PHP website for a fictional car wash and detailing shop in Valencia City, Bukidnon: Home, Services & Prices, Book, Confirmation, About, FAQ, Contact, Privacy notice (RA 10173) and 404.
- Five-step booking wizard: vehicle and service, detailer, date and time, details, review.
- Slot engine (`inc/booking.php`) with business hours, holidays, staff working days and skills, service length, 30-minute steps and a 60-minute lead time.
- Double-booking prevention: the slot is re-checked inside an exclusive file lock before saving.
- Booking confirmation with a private link (reference + token) and an `.ics` calendar download.
- JSON API `api/slots.php` that returns only free start times.
- Prices in pesos by vehicle size (sedan, SUV/pickup, van, motorcycle) with a live price switcher.
- Contact form with CSRF token, honeypot field, server-side validation and optional e-mail notification.
- Storage in `.php` files guarded with `<?php exit; ?>` so no server can show the data.
- Light and dark themes, mobile menu, sticky call/book buttons, AutoWash + FAQPage JSON-LD, Open Graph tags, sitemap.xml, robots.txt, SVG favicon, self-hosted fonts (no external requests).
- `engine.js`, a JavaScript twin of the slot engine, and a static HTML export in `demo/` for GitHub Pages.
- Tests: `tools/test-engine.php` (20 checks) and `tools/test-engine.js` (PHP/JS parity, 13 checks).
- One-click GitHub publisher (`publish-to-github.bat` + `.ps1`).
