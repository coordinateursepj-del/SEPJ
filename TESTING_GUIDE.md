# Testing Guide - Bug Fixes for Image Display and Language Switching

## Prerequisites
- XAMPP running with PHP 7.4+
- MariaDB/MySQL running
- Website accessible at `http://localhost/sepj-gabes`
- Browser developer tools (F12)

---

## ISSUE 1: Image Display Testing

### Test Setup
1. Clear all uploaded files (optional for clean test):
   ```
   rm -rf c:\xampp\htdocs\sepj-gabes\public\uploads\*
   ```

2. Create test images (you can use simple 1x1 pixel PNGs):
   - For gallery: test_gallery.jpg
   - For featured: test_featured.png

### Test Case 1.1: Upload to Gallery

**Steps:**
1. Go to: `http://localhost/sepj-gabes/admin/`
2. Log in with admin credentials
3. Navigate: **Media → Upload Images**
4. Select 2-3 test images
5. Click **Upload** button

**Expected Results:**
- ✓ Upload succeeds (green message)
- ✓ Image appears in media library with thumbnail
- ✓ Thumbnail displays image preview correctly
- ✓ File size shown correctly

**Verification in Admin:**
```
Expected URL format: /sepj-gabes/public/uploads/gallery/image_abc123.jpg
Check in browser DevTools (F12 → Network):
- All images load with 200 status
- Content-Type is image/* (jpeg/png/webp)
```

### Test Case 1.2: Upload Featured Image for Content

**Steps:**
1. Navigate: **Admin → Content → Create New Post**
2. Fill in title (required): "Test Article"
3. Fill in slug: "test-article"
4. Add body content
5. Click **Choose Featured Image** button
6. Upload a test image
7. Image preview should appear in the form
8. Click **Publish**

**Expected Results:**
- ✓ Featured image uploads successfully
- ✓ Image preview displays in edit form
- ✓ Post publishes successfully
- ✓ Featured image appears on public page

**Verification:**
```
Go to public page and inspect image element:
<img src="/sepj-gabes/public/uploads/content/image_xyz.jpg">
```

### Test Case 1.3: View Gallery on Public Site

**Steps:**
1. Go to: `http://localhost/sepj-gabes/public/gallery.php`
2. Wait for page to load completely
3. Inspect each image

**Expected Results:**
- ✓ All gallery images display correctly
- ✓ Images don't show broken image icons
- ✓ Image hover effects work (scale animation)
- ✓ Lightbox opens when clicking image
- ✓ Lightbox images display correctly
- ✓ Navigation arrows work in lightbox

**Browser Console Check:**
```
F12 → Console
Look for any errors related to images
Should see NO 404 errors for images
```

### Test Case 1.4: Check Homepage Images

**Steps:**
1. Go to: `http://localhost/sepj-gabes/public/`
2. Check all sections with images:
   - Featured projects section
   - Latest news section
   - Gallery carousel

**Expected Results:**
- ✓ All featured images display
- ✓ No broken image icons anywhere
- ✓ All images are the same language as page content
- ✓ Carousel animations work smoothly

### Test Case 1.5: Verify Image URLs in HTML

**Steps:**
1. Open any page with images
2. Right-click on image → Inspect (or F12)
3. Check the `<img>` tag's `src` attribute

**Expected Results:**
```
Image src should look like:
/sepj-gabes/public/uploads/gallery/image_name.jpg
NOT:
http://localhost/sepj-gabes/public/uploads/gallery/image_name.jpg
(relative path, not absolute)

Testing to confirm:
- Open DevTools (F12)
- Go to Network tab
- Click on image request
- Response should be 200 OK
- Preview should show the actual image
```

### Test Case 1.6: Test Image Access via Direct URL

**Steps:**
1. Open browser console (F12)
2. In the console, type:
   ```javascript
   fetch('/sepj-gabes/public/uploads/gallery/image_name.jpg')
     .then(r => console.log('Status:', r.status))
     .catch(e => console.error('Error:', e))
   ```
3. Check the response

**Expected Results:**
- ✓ Status should be 200
- ✓ No CORS errors
- ✓ No 404 errors

---

## ISSUE 2: Language Switcher Testing

### Test Setup
- Test on multiple pages (not just homepage)
- Use incognito/private window for clean session

### Test Case 2.1: Basic Language Switch on Homepage

**Steps:**
1. Go to: `http://localhost/sepj-gabes/public/index.php`
2. Note the content language (Arabic by default)
3. Click **F** (French) button in navbar
4. Page reloads

**Expected Results:**
- ✓ Page reloads with French content
- ✓ Navigation menu in French
- ✓ Section headers in French
- ✓ URL still shows: `index.php?lang=fr`
- ✓ Clicking **E** (English) shows English content

### Test Case 2.2: Language Switch on Inner Page

**Steps:**
1. Navigate to: `http://localhost/sepj-gabes/public/page.php?slug=about-company`
2. Note: Arabic about page displayed
3. Click **F** (French) button
4. Page reloads

**Expected Results:**
- ✓ SAME PAGE loads in French (not homepage)
- ✓ URL is: `page.php?slug=about-company&lang=fr`
- ✓ NOT just `?lang=fr` (which would lose context)
- ✓ About page content in French
- ✓ Parameter `slug=about-company` is preserved

**Verification:**
```
Before: http://localhost/sepj-gabes/public/page.php?slug=about-company
Click French button
After: http://localhost/sepj-gabes/public/page.php?slug=about-company&lang=fr
NOT:   http://localhost/sepj-gabes/public/?lang=fr ✗
```

### Test Case 2.3: Language Switch on News Page with Pagination

**Steps:**
1. Go to: `http://localhost/sepj-gabes/public/news.php?page=2`
2. Note: You're on page 2 of news
3. Click **E** (English) button
4. Page reloads

**Expected Results:**
- ✓ Still on page 2 (not page 1)
- ✓ URL is: `news.php?page=2&lang=en`
- ✓ Content is in English
- ✓ Pagination shows page 2 is still selected

### Test Case 2.4: Mobile Menu Language Switcher

**Steps:**
1. Resize browser to mobile size (< 768px width)
2. Click hamburger menu icon
3. At bottom, see language buttons
4. Currently on any page with parameters
5. Click different language

**Expected Results:**
- ✓ Same behavior as desktop
- ✓ Page parameters preserved
- ✓ Only language changes
- ✓ Menu closes after language selection

### Test Case 2.5: Admin Panel Language Switching

**Steps:**
1. Log into: `http://localhost/sepj-gabes/admin/`
2. Navigate to: **Admin → Content → List Posts**
3. Click page 2 if available, or add search parameters
4. Click language button (e.g., Français)

**Expected Results:**
- ✓ Admin interface in French
- ✓ Same page/filters maintained
- ✓ URL preserves search/pagination parameters
- ✓ No 404 errors

### Test Case 2.6: Admin Login Page Language Switching

**Steps:**
1. Go to: `http://localhost/sepj-gabes/admin/login.php`
2. Click **Français** button
3. Page reloads

**Expected Results:**
- ✓ Login form in French
- ✓ Error messages (if any) in French
- ✓ URL shows: `login.php?lang=fr`

### Test Case 2.7: No 404 Errors on Language Switch

**Steps:**
1. Open DevTools (F12) → Network tab
2. Go to any page with content and parameters
3. Switch languages several times
4. Monitor network requests

**Expected Results:**
- ✓ All requests return 200 status
- ✓ NO 404 responses
- ✓ NO 500 errors
- ✓ Page loads correctly each time

### Test Case 2.8: Multiple Query Parameters Preserved

**Steps:**
1. Create a URL with multiple parameters:
   `http://localhost/sepj-gabes/admin/content/index.php?type=post&status=published&page=2&search=test`
2. Click language button
3. Check URL

**Expected Results:**
- ✓ New URL: `index.php?type=post&status=published&page=2&search=test&lang=fr`
- ✓ ALL parameters preserved
- ✓ Only `lang` parameter changed
- ✓ Page displays with all filters applied in new language

---

## Error Log Monitoring

### Check for Image Upload Errors

**Location:** PHP Error Log
```
Windows (XAMPP): c:\xampp\apache\logs\error.log
or
c:\xampp\logs\php_error.log
```

**What to look for after uploads:**
```
INFO: File uploaded - Path: gallery/image_abc.jpg, Size: 124567 bytes
INFO: Created upload directory - path/to/uploads/gallery
```

**Issues that might appear:**
```
WARNING: Image file not found - DB Path: content/image.jpg, Physical: path/to/file, URL: /path/to/url
ERROR: File uploaded but verification failed
ERROR: move_uploaded_file failed - Source: ..., Destination: ...
ERROR: Failed to create upload directory
```

### Real-time Log Monitoring

**Command (if using command line):**
```bash
# Watch PHP error log in real-time
tail -f c:\xampp\logs\php_error.log

# Or with grep to filter image-related logs
tail -f c:\xampp\logs\php_error.log | grep -i "upload\|image"
```

---

## Cross-Browser Testing

Test on these browsers for image display:
- [ ] Chrome/Chromium (Latest)
- [ ] Firefox (Latest)
- [ ] Safari (if available)
- [ ] Edge (Latest)

Test on these for language switching:
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Mobile browser (test on real device if possible)

---

## Performance Check

### Image Loading Performance

**Steps:**
1. Open DevTools (F12) → Performance tab
2. Go to gallery page
3. Start recording
4. Reload page
5. Stop recording
6. Check metrics

**Expected Results:**
- ✓ Images load within 2-3 seconds
- ✓ No layout shifts due to missing images
- ✓ Cumulative Layout Shift (CLS) is low

### Language Switch Performance

**Steps:**
1. Measure time to switch language
2. Click language button and note time to new content

**Expected Results:**
- ✓ Language switch < 1 second
- ✓ No lag or delay

---

## Database Verification

### Check Image Paths in Database

```sql
-- Connect to database as admin
-- Run these queries:

-- Check media table
SELECT id, file_path, file_name FROM media LIMIT 5;

-- Check content_items table
SELECT id, type, slug, featured_image FROM content_items WHERE featured_image IS NOT NULL LIMIT 5;

-- Expected file_path format:
-- gallery/image_abc123.jpg
-- content/image_def456.png
-- (relative paths, not absolute URLs)
```

---

## Rollback Procedure (If Issues Occur)

If any issues arise, follow these steps:

1. **Restore app/config/app.php**
   ```php
   // Revert UPLOAD_URL to:
   define('UPLOAD_URL', BASE_URL . '/public/uploads');
   ```

2. **Restore language switchers**
   ```php
   // Revert all language switcher links to:
   <a href="?lang=ar">ع</a>
   ```

3. **Clear browser cache**
   - F12 → Network tab → Right-click → "Disable cache"
   - Or use Ctrl+Shift+Delete

4. **Restart browser**
   - Close all tabs
   - Open fresh browser session

---

## Success Criteria

### Issue 1 Fixed:
- [ ] All uploaded images display correctly
- [ ] No broken image icons anywhere
- [ ] Image URLs use relative paths
- [ ] Gallery pages work properly
- [ ] Featured images on content pages display
- [ ] Lightbox functionality works
- [ ] No 404 errors in console for images
- [ ] Error log shows successful uploads

### Issue 2 Fixed:
- [ ] Language switching preserves current page
- [ ] All query parameters preserved when switching language
- [ ] No unexpected redirects or 404 errors
- [ ] Works on desktop and mobile
- [ ] Admin pages switch language correctly
- [ ] No "Page Not Found" errors occur

---

## Common Issues & Solutions

### Issue: Images still not showing

**Solution:**
1. Check if `/public/uploads/` directory exists
   ```bash
   ls -la c:\xampp\htdocs\sepj-gabes\public\uploads\
   ```

2. Check directory permissions (should be 0755):
   ```bash
   # Fix permissions if needed
   chmod -R 0755 c:\xampp\htdocs\sepj-gabes\public/uploads
   ```

3. Check PHP error log for upload errors

4. Try uploading a new image and watch the logs

5. Clear browser cache (Ctrl+Shift+Delete)

### Issue: Language switch still redirects to homepage

**Solution:**
1. Clear browser cache
2. Verify `lang_url()` function exists in `app/core/helpers.php`
3. Verify all language links use `lang_url()` function
4. Check browser console for any JavaScript errors
5. Check if session is working (should have `$_SESSION['lang']`)

### Issue: Images show in admin but not on public site

**Solution:**
1. Check UPLOAD_URL configuration - should be relative path
2. Verify file permissions on uploaded files (should be 0644)
3. Check if .htaccess exists in uploads directory
4. Verify Apache is serving static files correctly
5. Check if files actually exist in filesystem

---

## Detailed Verification Steps

### Verify Image Upload Fix

**Step 1: Check configuration**
```php
// File: app/config/app.php
// Look for this line:
define('UPLOAD_URL', APP_BASE_PATH . '/public/uploads');
// Should NOT be:
// define('UPLOAD_URL', BASE_URL . '/public/uploads');
```

**Step 2: Check upload function**
```php
// File: app/core/upload.php
// Should have enhanced error logging
// Should set file permissions to 0644
// Should verify file exists after upload
```

**Step 3: Test upload**
- Upload image via admin
- Check error log for "SUCCESS" message
- Navigate to public gallery
- Verify image displays

### Verify Language Switching Fix

**Step 1: Check helper function**
```php
// File: app/core/helpers.php
// Should have lang_url() function
// Should preserve query string parameters
```

**Step 2: Check nav usage**
```php
// Files: public/includes/nav.php, admin/includes/header.php, admin/login.php
// Should use: <a href="<?= e(lang_url('ar')) ?>">
// NOT: <a href="?lang=ar">
```

**Step 3: Test switching**
- Go to page with parameters
- Switch language
- Verify URL preserves parameters
- Verify page content in new language

---

## Final Sign-Off

Once all tests pass, confirm:

- [ ] All images display correctly
- [ ] Language switching works on all pages
- [ ] No console errors
- [ ] No 404 errors
- [ ] Performance is acceptable
- [ ] Database records look correct
- [ ] Both fixes work in combination
- [ ] Browser cache clearing resolved any issues

**Status: ✓ READY FOR PRODUCTION**

---

**Test Completed By:** _________________  
**Date:** _________________  
**Notes:** _________________

