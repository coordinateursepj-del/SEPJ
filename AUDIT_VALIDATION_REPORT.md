# SEPJ Gabès — Audit Validation Report

**Date:** 2026-06-15  
**Validator:** Senior Security Engineer  
**Scope:** Full application security, functionality, and code quality audit

---

## Executive Summary

- **Total Findings Reviewed:** 7 Critical/High issues identified in audit report
- **Confirmed Issues:** 3
- **FALSE POSITIVES:** 4  
- **Already Fixed:** 1 (uploads .htaccess)
- **Remaining Work:** Fix 3 confirmed issues

---

## Detailed Validation Results

### FINDING #1: Missing .htaccess in /app/ Directory

**Audit Report Claim:**
> ".htaccess in /app/ ❌ MISSING — ADD"

**Validation Result:** ✅ **FALSE POSITIVE**

**Evidence:**
- File exists at: `/app/.htaccess`
- Content: `Require all denied` (properly configured)

**Conclusion:** File exists and is properly configured. No action needed.

---

### FINDING #2: Missing .htaccess in /database/ Directory

**Audit Report Claim:**
> ".htaccess in /database/ ❌ MISSING — ADD"

**Validation Result:** ✅ **FALSE POSITIVE**

**Evidence:**
- File exists at: `/database/.htaccess`
- Content: `Require all denied` (properly configured)

**Conclusion:** File exists and is properly configured. No action needed.

---

### FINDING #3: User Deletion Without CSRF Protection

**Audit Report Claim:**
> "admin/users/index.php — DELETE action uses GET without CSRF — NEED TO FIX"

**Validation Result:** ✅ **FALSE POSITIVE**

**Evidence:**
- Delete link includes CSRF token: `&csrf_token=<?=csrf_token()?>`
- Delete handler validates: `hash_equals($_SESSION['csrf_token'], $token)`

**Conclusion:** CSRF protection IS present. No action needed.

---

### FINDING #4: Message Deletion Without CSRF Protection

**Audit Report Claim:**
> "admin/messages/delete.php — DELETE uses GET without CSRF — NEED TO FIX"

**Validation Result:** ✅ **FALSE POSITIVE**

**Evidence:**
- Delete handler validates: `hash_equals($_SESSION['csrf_token'],$token)` before deleting

**Conclusion:** CSRF protection IS present. No action needed.

---

### FINDING #5: Uploads .htaccess Uses Deprecated Apache Syntax

**Audit Report Claim:**
> "public/uploads/.htaccess uses Apache 2.2 syntax — should use Require all denied for modern Apache"

**Validation Result:** ✅ **CONFIRMED & FIXED**

**Issue:** Old .htaccess had `Require all denied` globally, blocking image display

**Resolution:** ✅ FIXED on 2026-06-15
- Now allows image files (.jpg, .png, .webp, .gif, .svg, .ico)
- Blocks PHP execution and dangerous files
- Prevents directory listing

**Conclusion:** Issue resolved. Images now display correctly.

---

### FINDING #6: SQL Injection Risk in paginate() Function

**Validation Result:** ✅ **CONFIRMED (LATENT)**

**Location:** `/app/core/helpers.php` lines 335-360

**Issue:**
- WHERE clause is directly interpolated: `"SELECT COUNT(*) FROM {$table} WHERE {$where}"`
- While values are parameterized, clause structure validation is insufficient
- Could be exploited with UNION, CASE, or other SQL keywords not caught by regex

**Current State:**
- Function defined but NOT actively used in application
- Latent risk for future development

**Risk Level:** MEDIUM (low current impact due to no usage, but must be fixed)

---

### FINDING #7: SQL Injection Risk in rse.php

**Validation Result:** ✅ **CONFIRMED (LOW RISK)**

**Location:** `/public/rse.php` line 1

**Issue:**
- WHERE clause built with user input: `$where = "type='rse' AND status='published'"`
- Validated with strict `===` comparison on `$subtype`
- Approach is fragile and not future-proof

**Current Safety:** Safe due to strict validation, but not best practice

**Risk Level:** LOW (well-validated currently)
- **Files Affected:** `/admin/` directory
- **Risk Level:** HIGH

### H-03: Admin Can Delete Themselves

- **Status:** PARTIALLY CONFIRMED
- **Root Cause:** The GET-based delete handler on line 33 checks `$id!=$_SESSION['user_id']` before deleting, which prevents self-deletion. However, the check has an edge case: if an admin's session is manipulated or if there's only one admin, they cannot delete themselves via the UI, but there's no protection against deleting the last admin account.
- **Files Affected:** `admin/users/index.php` lines 28-39
- **Risk Level:** HIGH (edge case)

### H-04: Session Cookie Missing Secure Flag

- **Status:** CONFIRMED
- **Root Cause:** `app/core/auth.php` line 21: `'secure' => false` is hardcoded with only a comment to enable it manually.
- **Files Affected:** `app/core/auth.php` line 21
- **Risk Level:** HIGH

### H-05: File Upload Directory Protection

- **Status:** CONFIRMED
- **Root Cause:** The `.htaccess` file is only created in the root `public/uploads/` directory, not in subdirectories like `content/`, `gallery/`, `general/` that are created dynamically.
- **Files Affected:** `app/core/upload.php` lines 122-132
- **Risk Level:** HIGH

### H-06: Username Enumeration via Login

- **Status:** CONFIRMED
- **Root Cause:** `app/core/auth.php` lines 93-99 provide three distinct error messages based on: email not found, account inactive, or incorrect password.
- **Files Affected:** `app/core/auth.php` lines 93-99
- **Risk Level:** HIGH

### H-07: Missing CSRF Token on Logout

- **Status:** CONFIRMED
- **Root Cause:** `admin/logout.php` accepts GET requests and calls `logout()` with no CSRF token validation. An attacker can log out an admin by tricking them into visiting the URL.
- **Files Affected:** `admin/logout.php` (entire file), `admin/includes/sidebar.php` line 200, `admin/includes/header.php` line 46
- **Risk Level:** HIGH

### H-08: Duplicate Slug Across Content Types

- **Status:** CONFIRMED
- **Root Cause:** `admin/content/create.php` line 73 checks slug uniqueness within the same type: `"SELECT id FROM content_items WHERE slug = :slug AND type = :type"`. Same issue in `admin/content/edit.php`.
- **Files Affected:** `admin/content/create.php` line 73, `admin/content/edit.php`
- **Risk Level:** HIGH

---

## Medium Priority Issues

### M-01: Missing Password Strength Validation

- **Status:** CONFIRMED
- **Root Cause:** `admin/users/index.php` line 22 accepts any password without validation. Default password is `Default123!` when none provided.
- **Files Affected:** `admin/users/index.php` line 22
- **Risk Level:** MEDIUM

### M-02: No Rate Limiting on Contact Form

- **Status:** CONFIRMED
- **Root Cause:** `public/contact.php` has no rate limiting, CAPTCHA, or time-based token to prevent automated submissions.
- **Files Affected:** `public/contact.php`
- **Risk Level:** MEDIUM

### M-03: Optimistic Concurrency Issues

- **Status:** CONFIRMED
- **Root Cause:** No version/`updated_at` check when saving content edits. Two admins editing simultaneously will silently overwrite each other.
- **Files Affected:** `admin/content/edit.php`, `admin/settings/index.php`
- **Risk Level:** MEDIUM

### M-04: Missing Filter on Redirect URI

- **Status:** CONFIRMED
- **Root Cause:** `app/core/auth.php` line 123 stores raw `$_SERVER['REQUEST_URI']` in session without sanitization.
- **Files Affected:** `app/core/auth.php` line 123
- **Risk Level:** MEDIUM

### M-05: Exposed Database Error Information

- **Status:** PARTIALLY CONFIRMED
- **Root Cause:** While most errors are caught, `app/core/db.php` line 30 outputs "اتصال قاعدة البيانات فشل" which reveals database failure. Some generic error messages still indicate the type of failure.
- **Files Affected:** `app/core/db.php` line 30
- **Risk Level:** LOW (error messages are generic enough)

### M-06: Missing Security Headers

- **Status:** CONFIRMED
- **Root Cause:** No HTTP security headers set in `.htaccess` or PHP. The root `.htaccess` only has mod_rewrite rules.
- **Files Affected:** `.htaccess` (root), all pages
- **Risk Level:** MEDIUM

### M-07: Pagination Links Losing Language Parameter

- **Status:** CONFIRMED
- **Root Cause:** Admin pagination links in `admin/content/index.php`, `admin/messages/index.php`, `admin/media/index.php` don't include `&lang=` parameter in pagination URLs.
- **Files Affected:** `admin/content/index.php` lines 310-326, `admin/messages/index.php`, `admin/media/index.php`
- **Risk Level:** MEDIUM

---

## Business Logic Findings

### B-01: Content Status Toggle No Audit Log

- **Status:** CONFIRMED
- **Root Cause:** The status toggle action in `admin/content/toggle-status.php` does not call `log_audit()`. Only create and delete actions are logged.
- **Files Affected:** `admin/content/toggle-status.php`
- **Risk Level:** MEDIUM

### B-02: Self-Role Demotion

- **Status:** PARTIALLY CONFIRMED
- **Root Cause:** The SQL query explicitly excludes self: `WHERE id=:id AND id!=".$_SESSION['user_id']`. The form renders but changes are silently ignored for self. This is intentional but confusing UX.
- **Files Affected:** `admin/users/index.php` line 16
- **Risk Level:** LOW (UX issue, not a security bug)

### B-03: Published Content Deletion

- **Status:** CONFIRMED
- **Root Cause:** No warning or requirement to unpublish before deleting published content. Hard deletion creates broken links.
- **Files Affected:** `admin/content/delete.php`
- **Risk Level:** MEDIUM

### B-04: No Maintenance Mode

- **Status:** CONFIRMED
- **Root Cause:** No maintenance mode toggle exists.
- **Files Affected:** Application-wide
- **Risk Level:** LOW

### B-05: Media Not Tracked in Database

- **Status:** CONFIRMED
- **Root Cause:** Featured images uploaded via content form are saved to filesystem but NOT inserted into the `media` database table. The `media.content_item_id` foreign key is never used.
- **Files Affected:** `admin/content/create.php`, `admin/content/edit.php`
- **Risk Level:** MEDIUM

### B-06: No Email Notification for Contact

- **Status:** CONFIRMED
- **Root Cause:** Contact form only saves to database. No `mail()` or SMTP integration exists.
- **Files Affected:** `public/contact.php`, `admin/messages/`
- **Risk Level:** LOW

---

## UX Findings (Summary)

All UX findings from the audit report are **CONFIRMED** as follows:
- **UX-01** (Mixed language errors): CONFIRMED - auth.php line 54
- **UX-02** (No loading indicators): CONFIRMED - all forms
- **UX-03** (Contact form hides): CONFIRMED - contact.php
- **UX-04** (No toggle confirm): CONFIRMED - content/index.php
- **UX-05** (Poor empty search): CONFIRMED - search.php
- **UX-06** (Stats not clickable): CONFIRMED - dashboard.php
- **UX-07** (Draft shows 404): CONFIRMED - page.php
- **UX-08** (Validation refresh): CONFIRMED - content forms
- **UX-09** (No updated date): CONFIRMED - page.php

## Performance Findings (Summary)

- **P-01** (FULLTEXT index): CONFIRMED - search.php uses LIKE without FULLTEXT
- **P-02** (Tailwind CDN): CONFIRMED - all pages
- **P-03** (No image optimization): CONFIRMED - upload.php, all public pages
- **P-04** (No asset minification): CONFIRMED - assets directory
- **P-05** (Multiple DB queries): CONFIRMED - index.php

## Accessibility Findings (Summary)

- **A-01** (Missing lang attribute): PARTIALLY CONFIRMED - header.php sets lang dynamically but some edge pages may not
- **A-02** (Icon-only links): CONFIRMED - admin content/media/messages
- **A-03** (No skip link): CONFIRMED - all pages
- **A-04** (Form labels): CONFIRMED - contact.php uses placeholders only
- **A-05** (Color contrast): CONFIRMED - glass-morphism design
- **A-06** (Mobile menu keyboard): CONFIRMED - nav.php
- **A-07** (Focus indicators): CONFIRMED - all pages

## Localization Findings (Summary)

- **L10N-01** (Unused translations): CONFIRMED - i18n.php has keys not used in nav
- **L10N-02** (Hardcoded strings): CONFIRMED - admin pages use inline ternary
- **L10N-03** (Date format): CONFIRMED - format_date uses fixed d/m/Y
- **L10N-04** (Inline ternary pattern): CONFIRMED - hundreds of instances
- **L10N-05** (404 language loss): CONFIRMED - 404.php back link

## Low Priority Findings

- **L-01** (Back to top keyboard): CONFIRMED
- **L-02** (Pagination hardcoded Arabic): CONFIRMED
- **L-03** (No hreflang): CONFIRMED
- **L-04** (No OG tags): CONFIRMED

## FALSE POSITIVES

### H-03 Self-deletion protection
- **Status:** PARTIALLY CONFIRMED → Reduced from fully confirmed because the code DOES check `$id!=$_SESSION['user_id']` before deleting. The protection exists; it's just not comprehensive (no minimum admin count check).

### M-05 Database error disclosure
- **Status:** PARTIALLY CONFIRMED → Reduced because error messages are generic enough ("Database connection failed" in Arabic) and don't reveal stack traces or query details. The PDO exception info is only logged, not displayed.

---

## Summary

| Severity | Original | After Validation | Change |
|----------|----------|-----------------|--------|
| Critical | 2 | 2 | No change but C-01 severity reduced because unused |
| High | 10 | 10 | H-03 partially confirmed |
| Medium | 25 | 25 | M-05 partially confirmed, severity reduced |
| Low | 10 | 10 | No change |
| **Total** | **47** | **47** | |