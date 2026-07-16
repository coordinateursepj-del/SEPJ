# Production Audit Report — SEPJ Gabès
Date: 2026-06-17

## Architecture

PHP / MySQL (XAMPP) multilingual CMS — Arabic (RTL default), French, English.

- `public/` — public frontend (15 PHP pages + assets)
- `admin/` — admin panel (content, media, messages, settings, users)
- `app/core/` — auth, CSRF, DB, helpers, i18n, upload
- `app/config/` — app + database config
- `database/` — schema.sql + seed.sql

---

## Issues Found & Fixed

### CRITICAL

| # | File | Issue | Fix Applied |
|---|------|-------|-------------|
| 1 | `public/includes/header.php` | Premature `</body></html>` tags — entire page content rendered **outside** the HTML document | Removed the two closing tags; `footer.php` owns them |
| 2 | All 15 public `.php` pages | Duplicate `</body></html>` after `include footer.php` (footer already closes) | Removed the redundant closing tags from all 15 pages |
| 3 | `public/includes/header.php` | No `session_start()` called on public pages — language preference never persisted; contact form CSRF **always rejected** | Added `auth.php` + `csrf.php` requires and `session_start_secure()` call |
| 4 | `public/page.php` | `header('HTTP/1.0 404 Not Found')` called after HTML output — HTTP status never actually sent | Restructured: DB lookup now occurs before any HTML; uses `http_response_code(404)` |

### HIGH

| # | File | Issue | Fix Applied |
|---|------|-------|-------------|
| 5 | `public/index.php` | Gallery lightbox called `openLightbox(0, ['single-url'])` per image — no navigation between images | Built `$galleryUrls` array; each image now passes full array + correct index |
| 6 | `public/services.php` | Service cards had no `<a>` wrapper — users could not open any service detail | Wrapped each card in `<a href="page.php?slug=...">` |
| 7 | `admin/content/create.php` + `edit.php` | Summary textareas had no `id` attribute; `admin.js` character counter uses `textarea.id + '_counter'` — counters were silently broken | Added `id="summary_<?= $code ?>"` and counter HTML to `edit.php` |

### MEDIUM

| # | File | Issue | Fix Applied |
|---|------|-------|-------------|
| 8 | `public/projects.php`, `activities.php`, `index.php` | Missing or empty `alt` attributes on `<img>` elements | Added descriptive alt text from content title fields |
| 9 | `app/core/auth.php` — `require_role()` | Bare `die()` on unauthorized access — unstyled error string, no recovery path | Replaced with `set_flash('error', ...)` + redirect to dashboard |
| 10 | `app/core/helpers.php` — `sanitize_body()` | Allowed `href="javascript:..."` and event handlers via incomplete regex | Added `preg_replace_callback` stripping JS/VBScript/data URIs from href/src/action; improved event-handler regex |

---

## Items Flagged (Require Discussion — NOT Changed)

| # | File | Issue | Recommendation |
|---|------|-------|----------------|
| A | `admin/content/index.php` | Delete and toggle-status actions use GET with CSRF token in URL (tokens appear in server logs, referrer headers, history) | Convert to POST forms for destructive actions |
| B | `app/core/db.php` | Credentials hardcoded in `database.php` — no `.env` support | Move to environment variable / `.env` file before production deployment |
| C | Admin pages | No role check guards editor-only actions from viewing admin-only sections (e.g., user management) — only `require_login()` | Add `require_role('admin')` to users/ and settings/ pages as needed |

---

## Verification

- `grep -n "</body>|</html>"` on all 15 public pages: **0 matches** (only `footer.php` owns these tags)
- `public/includes/header.php`: opens `<html><head></head><body>` — no premature close
- Session started in public header before any `$_SESSION` access
- `page.php`: 404 path returns `http_response_code(404)` before any HTML
- Homepage gallery: `openLightbox(idx, fullArray)` — full navigation works
- Services: each card is an `<a>` link
- Character counters in admin forms: textareas have matching `id` attributes
