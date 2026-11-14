# Clearcoat Auto Spa: Tutorial

This tutorial walks through how the site works, file by file, so you can change it with confidence. You need only basic PHP and JavaScript.

## 1. Architecture

```
Browser                                   Server (PHP)
-------                                   ------------
book.php page  ── wizard (booking.js) ──► api/slots.php ──► cc_slots()  ─┐
     │                                                                   │ reads data/*.json
     └── final POST ──────────────────►  book.php ──► cc_create_booking()│ and storage/bookings.php
                                              │        (re-check in lock)│
                                              ▼                          │
                               confirmation.php?ref=…&t=…  ──► ics.php ◄─┘
```

- **Pages** are plain PHP files. Each one fills a `$page` array (title, description), then includes `inc/header.php`, prints its content and includes `inc/footer.php`.
- **Data** lives in three JSON files in `data/`. Nothing is hard-coded in the pages except the copy.
- **Bookings** are an array saved in `storage/bookings.php`.
- **The same rules run in two places**: `inc/booking.php` (the authority) and `assets/js/engine.js` (for the static demo). `tools/test-engine.js` proves they agree.

## 2. Key files and functions

### inc/functions.php

| Function | What it does |
| --- | --- |
| `cc_data($name)` | Reads `data/<name>.json` once per request. |
| `e($value)` | Escapes text for HTML. Every printed value goes through it. |
| `cc_url($page, $query)` | Link to a page: `about.php` on the server, `about.html` in the static export. |
| `cc_csrf_token()` / `cc_csrf_check()` | A random token kept in the session; forms must send it back. |
| `cc_post()` / `cc_post_text()` | Read a POST field as a trimmed, length-capped string with control characters removed. |
| `cc_valid_mobile()` | Accepts `09XXXXXXXXX` or `+639XXXXXXXXX` with spaces or dashes. |
| `cc_with_store($name, $fn)` | Opens the storage file, takes an **exclusive lock**, lets `$fn` change the array, writes it back. |

### inc/booking.php: the slot engine

`cc_slots($service, $staff, $date, $bookings, $now)` answers "when can this service start on this day?":

1. `cc_day_hours()` refuses past dates, dates more than `days_ahead` away, holidays in `blocked_dates` and closed weekdays. Otherwise it returns opening and closing minutes (480 = 8:00 AM).
2. `cc_candidates()` keeps detailers who do this service **and** work this weekday (or just the one chosen).
3. For every 30-minute step from opening, while `start + duration <= closing`, each candidate is free if no busy interval overlaps: `start < busyEnd && start + duration > busyStart`.
4. Today's slots must start at least `lead_minutes` after now (Manila time).

`cc_create_booking($input)` validates every field, then calls `cc_with_store('bookings', …)`. **Inside the lock** it runs `cc_slots()` again with the freshest bookings. If the chosen start is gone, the booking is refused. That is the whole double-booking defence: two requests cannot both be inside the lock, so the second one always sees the first one's booking.

`cc_demo_busy()` adds a few fake busy blocks per day (from a hash of date + detailer) so the demo calendar looks realistic. Set `"demo_busy": false` for a real shop.

`cc_ics($booking)` writes an iCalendar file. Times are converted from Manila time to UTC (minus 8 hours) and lines end with CRLF, as the standard requires.

### book.php

On GET it prints the wizard. On POST it reads the fields with `cc_post()`, checks the CSRF token and the honeypot, calls `cc_create_booking()` and, on success, redirects (HTTP 303) to `confirmation.php?ref=…&t=…`. The token `t` is random, so nobody can guess someone else's confirmation link.

### assets/js/booking.js

- `getSlots()` calls `api/slots.php` on the PHP site, or `engine.slots()` in the static demo.
- `applyVehicle()` hides services that do not fit the vehicle and updates the prices.
- `applyService()` disables detailers who do not do the chosen service.
- `buildStrip()` draws the date pills; `pickDate()` loads and draws the time buttons.
- `validate(step)` checks each step before moving on; `summary()` feeds the dark summary card.
- In the static demo, the submit handler repeats the double-booking check against `localStorage` and then opens `confirmation.html#<ref>`.

### assets/css/style.css

All colours are tokens on `:root` (light) and repeated for dark mode in two blocks: `@media (prefers-color-scheme: dark)` and `[data-theme="dark"]`. Components only use tokens, so a rebrand is a few lines.

## 3. Key concepts

- **Post/Redirect/Get**: after a successful POST the server redirects, so refreshing the confirmation page never books twice.
- **CSRF token**: a secret per session that other websites cannot read, so they cannot submit your forms for a visitor.
- **Honeypot**: a hidden field people never fill in; bots often do.
- **File locking**: `flock(LOCK_EX)` makes other requests wait until the current write is finished.
- **Escaping on output**: data is stored as typed and escaped with `e()` when printed.

## 4. Extension exercises

1. **Admin list.** Create `admin.php` protected with a password from `settings.json` (`password_hash` / `password_verify`) that reads `cc_read_store('bookings')` and shows today's bookings in a table. Add a "Cancel" button that sets `status` to `cancelled` inside `cc_with_store()`; the slot becomes free again automatically.
2. **Lunch break.** Add `"breaks": [["12:00","13:00"]]` to settings and treat it as a busy interval for every detailer in `cc_busy()` (and in `engine.js`). Run `node tools/test-engine.js` to confirm both still agree.
3. **Different durations by size.** Change `minutes` in `services.json` to an object like prices (`{"sedan":60,"suv":75}`) and update `cc_slots()`, `cc_create_booking()` and the wizard summary.
