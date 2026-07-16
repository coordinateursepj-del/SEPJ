# SEPJ Gabès CMS - CRITICAL FIXES GUIDE

**Quick reference for fixing all 4 CRITICAL BREAKING issues in 2-3 hours**

---

## 🔴 CRITICAL FIX #1: Edit Form Data Loss

**File:** `admin/content/edit.php`  
**Lines:** 56-65  
**Time:** 15 minutes

### Current (BROKEN):
```php
$item['slug'] = slugify(trim($_POST['slug'] ?? $item['slug']));
$item['title_ar'] = trim($_POST['title_ar'] ?? '');
$item['title_fr'] = trim($_POST['title_fr'] ?? '');
$item['title_en'] = trim($_POST['title_en'] ?? '');
$item['summary_ar'] = trim($_POST['summary_ar'] ?? '');
$item['summary_fr'] = trim($_POST['summary_fr'] ?? '');
$item['summary_en'] = trim($_POST['summary_en'] ?? '');
$item['body_ar'] = $_POST['body_ar'] ?? '';
$item['body_fr'] = $_POST['body_fr'] ?? '';
$item['body_en'] = $_POST['body_en'] ?? '';
```

### Problem:
- Fields not in POST are set to empty string
- This overwrites database values with empty data
- Results in data loss

### Fixed Code:
```php
$item['slug'] = slugify(trim($_POST['slug'] ?? $item['slug']));

// Only update fields that were actually submitted
foreach (['title', 'summary', 'body'] as $field) {
    foreach (['ar', 'fr', 'en'] as $lang) {
        $key = "{$field}_{$lang}";
        if (isset($_POST[$key])) {
            $item[$key] = ($field === 'body') 
                ? $_POST[$key]  // Don't trim HTML content
                : trim($_POST[$key]);  // Trim text fields
        }
        // Otherwise: preserve existing database value (don't overwrite!)
    }
}
```

### Explanation:
- `isset($_POST[$key])` - Only processes fields that were actually submitted
- Doesn't modify fields that weren't submitted (preserves database values)
- Prevents accidental data loss when only editing one or two languages

---

## 🔴 CRITICAL FIX #2: Honeypot False Success

**File:** `public/contact.php`  
**Lines:** 15-44  
**Time:** 10 minutes

### Current (BROKEN):
```php
if (!empty($_POST['website'])) {
    // Bot detected, silently reject
    csrf_regenerate();
    $success = true;  // ← BUG: Sets success but doesn't save!
} else {
    $name = trim($_POST['name'] ?? '');
    // ... validation and save ...
    if (empty($errors)) {
        // ... INSERT into database ...
        $success = true;
    }
}
```

### Problem:
- When honeypot is filled, `$success = true` but message never saves
- User sees "Message sent successfully" - they don't know it was rejected
- Bad UX and defeats bot protection verification

### Fixed Code:
```php
if (!empty($_POST['website'])) {
    // Bot detected, silently reject
    csrf_regenerate();
    // Don't set success = true here; treat as failed submission
    $errors[] = '';  // Add empty error so success message won't show
} else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name)) $errors[] = $lang === 'ar' ? 'الاسم مطلوب.' : ($lang === 'fr' ? 'Nom requis.' : 'Name is required.');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $lang === 'ar' ? 'بريد إلكتروني صحيح مطلوب.' : ($lang === 'fr' ? 'Email valide requis.' : 'Valid email is required.');
    if (empty($message)) $errors[] = $lang === 'ar' ? 'الرسالة مطلوبة.' : ($lang === 'fr' ? 'Message requis.' : 'Message is required.');
    
    if (empty($errors)) {
        try {
            $stmt = db()->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at) VALUES (:name, :email, :phone, :subject, :message, 'new', NOW())");
            $stmt->execute(['name' => $name, 'email' => $email, 'phone' => $phone, 'subject' => $subject, 'message' => $message]);
            csrf_regenerate();
            $success = true;  // ← Only set success if message was actually saved
        } catch (PDOException $e) {
            $errors[] = $lang === 'ar' ? 'خطأ في إرسال الرسالة.' : ($lang === 'fr' ? 'Erreur d\'envoi.' : 'Error sending message.');
        }
    }
}
```

### Explanation:
- Honeypot detection: `if (!empty($_POST['website']))` - bot caught
- Don't set `$success = true` for bot submissions
- Only set `$success = true` after actual database INSERT
- Maintains bot protection while fixing UX

---

## 🔴 CRITICAL FIX #3: SQL Injection Anti-Pattern

**File:** `public/gallery.php`, `public/index.php`, and other public pages  
**Lines:** Multiple  
**Time:** 20-30 minutes

### Current (VULNERABLE):
```php
$images = db()->query("
    SELECT m.*, COALESCE(NULLIF(m.caption_{$lang}, ''), m.caption_ar) AS caption
    FROM media m
    LEFT JOIN content_items ci ON m.content_item_id = ci.id
    WHERE m.content_item_id IS NULL OR ci.status = 'published'
    ORDER BY m.created_at DESC
")->fetchAll();
```

### Problem:
- `m.caption_{$lang}` concatenates language code directly into SQL
- While `$lang` is validated, this violates SQL injection prevention principles
- If validation ever fails/is removed, becomes exploit vector

### Fixed Code (Option A - CASE Statement):
```php
$langCases = [
    'ar' => 'm.caption_ar',
    'fr' => 'm.caption_fr',
    'en' => 'm.caption_en'
];
$captionCol = isset($langCases[$lang]) ? $langCases[$lang] : 'm.caption_ar';

$images = db()->query("
    SELECT m.*, COALESCE(NULLIF({$captionCol}, ''), m.caption_ar) AS caption
    FROM media m
    LEFT JOIN content_items ci ON m.content_item_id = ci.id
    WHERE m.content_item_id IS NULL OR ci.status = 'published'
    ORDER BY m.created_at DESC
")->fetchAll();
```

### Fixed Code (Option B - Post-Processing, RECOMMENDED):
```php
// Fetch all fields, process in PHP
$allImages = db()->query("
    SELECT m.*, COALESCE(NULLIF(m.caption_ar, ''), '') AS caption_ar,
                 COALESCE(NULLIF(m.caption_fr, ''), '') AS caption_fr,
                 COALESCE(NULLIF(m.caption_en, ''), '') AS caption_en
    FROM media m
    LEFT JOIN content_items ci ON m.content_item_id = ci.id
    WHERE m.content_item_id IS NULL OR ci.status = 'published'
    ORDER BY m.created_at DESC
")->fetchAll();

// Apply language logic in PHP (100% safe)
foreach ($allImages as &$img) {
    $img['caption'] = $img["caption_{$lang}"] ?? $img['caption_ar'] ?? '';
}
$images = $allImages;
```

### Explanation:
- Option A: Whitelists column names before using in SQL
- Option B: Fetch all columns, apply language logic in PHP (safer, clearer)
- Both prevent SQL injection while maintaining functionality

---

## 🔴 CRITICAL FIX #4: Missing Function Definitions

**File:** `app/core/helpers.php` or `app/core/admin_helpers.php`  
**Add to end of file**  
**Time:** 30 minutes

### Add These Functions:

```php
/**
 * Get a multilingual field with language fallback
 */
function content_field(array $item, string $field, ?string $lang = null): string {
    $lang = $lang ?? current_lang();
    $key = "{$field}_{$lang}";
    
    // Return requested language, fall back to Arabic if empty
    if (!empty($item[$key] ?? null)) {
        return (string) $item[$key];
    }
    
    // Fallback to Arabic
    if (!empty($item["{$field}_ar"] ?? null)) {
        return (string) $item["{$field}_ar"];
    }
    
    return '';
}

/**
 * Sanitize HTML content - allows safe tags
 */
function sanitize_body(string $body): string {
    // List of allowed HTML tags
    $allowed = '<p><br><strong><em><u><a><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><code><pre>';
    
    // Strip dangerous tags but keep safe ones
    $sanitized = strip_tags($body, $allowed);
    
    // Remove on* event handlers
    $sanitized = preg_replace('/\s*on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $sanitized);
    
    return $sanitized;
}

/**
 * Format a date string to desired format
 */
function format_date(string $date, string $format = 'Y-m-d H:i:s'): string {
    if (empty($date)) {
        return '';
    }
    
    try {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;  // Return original if parsing fails
        }
        return date($format, $timestamp);
    } catch (Exception $e) {
        error_log("format_date error: " . $e->getMessage());
        return $date;  // Return original on error
    }
}

/**
 * Create a styled status badge for display
 */
function status_badge(string $status, ?string $lang = null): string {
    $lang = $lang ?? current_lang();
    
    $badges = [
        'new' => ['color' => 'bg-emerald-500', 'label' => ['ar' => 'جديد', 'fr' => 'Nouveau', 'en' => 'New']],
        'read' => ['color' => 'bg-blue-500', 'label' => ['ar' => 'مقروء', 'fr' => 'Lu', 'en' => 'Read']],
        'archived' => ['color' => 'bg-gray-500', 'label' => ['ar' => 'مؤرشف', 'fr' => 'Archivé', 'en' => 'Archived']],
        'draft' => ['color' => 'bg-yellow-500', 'label' => ['ar' => 'مسودة', 'fr' => 'Brouillon', 'en' => 'Draft']],
        'published' => ['color' => 'bg-emerald-600', 'label' => ['ar' => 'منشور', 'fr' => 'Publié', 'en' => 'Published']],
        'active' => ['color' => 'bg-green-500', 'label' => ['ar' => 'نشط', 'fr' => 'Actif', 'en' => 'Active']],
        'inactive' => ['color' => 'bg-red-500', 'label' => ['ar' => 'غير نشط', 'fr' => 'Inactif', 'en' => 'Inactive']],
    ];
    
    if (!isset($badges[$status])) {
        $status = 'draft';  // Default to draft if unknown
    }
    
    $badge = $badges[$status];
    $label = $badge['label'][$lang] ?? $badge['label']['en'];
    $color = $badge['color'];
    
    return "<span class='px-2 py-1 rounded text-white text-xs font-medium {$color}'>{$label}</span>";
}
```

### Usage in Code:
```php
// In page.php:
$title = content_field($item, 'title', $lang);
$body = sanitize_body($item['body_' . $lang]);

// In admin/messages/index.php:
<?= format_date($msg['created_at'], 'd/m/Y H:i') ?>
<?= status_badge($msg['status']) ?>
```

---

## ✅ VERIFICATION CHECKLIST

After applying fixes, test these scenarios:

### Fix #1 - Edit Form Data Loss:
- [ ] Create content with: title_fr="Mon Article", auto-translate fills other languages
- [ ] Edit: change title_ar only
- [ ] Save
- [ ] Re-open edit: All 3 languages still present ✓

### Fix #2 - Honeypot:
- [ ] Submit contact form with "website" field filled
- [ ] Check database: message NOT saved ✓
- [ ] Submit normal contact form
- [ ] Check database: message IS saved ✓

### Fix #3 - SQL Injection:
- [ ] Gallery page loads without errors
- [ ] All captions display in correct language
- [ ] Try ?lang=xx (invalid) - should fall back to Arabic ✓

### Fix #4 - Missing Functions:
- [ ] Single content page (/page.php?slug=...) loads without 500 error ✓
- [ ] Admin messages page displays dates correctly ✓
- [ ] Admin user page displays status badges ✓

---

## 📋 OTHER HIGH-PRIORITY FIXES

### Fix #5 - Message Status Validation (5 minutes)
**File:** `admin/messages/index.php` line 11

```php
// Current:
$status = $_GET['status'] ?? '';

// Fixed:
$validStatuses = ['new', 'read', 'archived'];
$status = in_array($_GET['status'] ?? '', $validStatuses) ? $_GET['status'] : '';
```

### Fix #6 - Tailwind CSS from CDN (45 minutes)
See PRODUCTION_READINESS_AUDIT.md section "Tailwind CSS CDN Not For Production"

---

## 🚀 DEPLOYMENT CHECKLIST

1. [ ] Apply all 4 CRITICAL fixes
2. [ ] Apply Fix #5 (message status validation)
3. [ ] Test checklist above passes
4. [ ] Run on local XAMPP - no 500 errors
5. [ ] Test all contact form scenarios
6. [ ] Test content creation with auto-translate
7. [ ] Test content editing (all languages)
8. [ ] Test public pages in all 3 languages
9. [ ] Verify no JavaScript console errors
10. [ ] Code review by senior developer
11. [ ] Deploy to production

---

**Estimated Total Fix Time: 1-2 hours (excluding Tailwind CSS)**
