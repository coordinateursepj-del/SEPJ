<?php
/**
 * Route Check Script - SEPJ Gabès
 * Run from project root: php scripts/check_routes.php
 * Checks if all expected files exist.
 */

$baseDir = dirname(__DIR__);

$files = [
    // PUBLIC FILES
    ['area' => 'Public', 'path' => 'public/index.php', 'expected' => 'Public homepage'],
    ['area' => 'Public', 'path' => 'public/news.php', 'expected' => 'News listing page (was missing, created)'],
    ['area' => 'Public', 'path' => 'public/page.php', 'expected' => 'Content detail page'],
    ['area' => 'Public', 'path' => 'public/projects.php', 'expected' => 'Projects listing'],
    ['area' => 'Public', 'path' => 'public/services.php', 'expected' => 'Services listing'],
    ['area' => 'Public', 'path' => 'public/activities.php', 'expected' => 'Activities listing'],
    ['area' => 'Public', 'path' => 'public/prizes.php', 'expected' => 'Prizes listing'],
    ['area' => 'Public', 'path' => 'public/rse.php', 'expected' => 'RSE page'],
    ['area' => 'Public', 'path' => 'public/resources.php', 'expected' => 'Resources listing'],
    ['area' => 'Public', 'path' => 'public/sports.php', 'expected' => 'Sports page'],
    ['area' => 'Public', 'path' => 'public/gallery.php', 'expected' => 'Gallery page'],
    ['area' => 'Public', 'path' => 'public/videos.php', 'expected' => 'Videos page'],
    ['area' => 'Public', 'path' => 'public/contact.php', 'expected' => 'Contact page'],
    ['area' => 'Public', 'path' => 'public/search.php', 'expected' => 'Search page'],
    ['area' => 'Public', 'path' => 'public/404.php', 'expected' => 'Public 404 page (created)'],

    // ADMIN FILES
    ['area' => 'Admin', 'path' => 'admin/index.php', 'expected' => 'Admin entry (redirects)'],
    ['area' => 'Admin', 'path' => 'admin/login.php', 'expected' => 'Admin login'],
    ['area' => 'Admin', 'path' => 'admin/logout.php', 'expected' => 'Admin logout'],
    ['area' => 'Admin', 'path' => 'admin/dashboard.php', 'expected' => 'Admin dashboard'],
    ['area' => 'Admin', 'path' => 'admin/404.php', 'expected' => 'Admin 404 page (created)'],

    // ADMIN INCLUDES
    ['area' => 'Admin', 'path' => 'admin/includes/sidebar.php', 'expected' => 'Sidebar (fixed with admin_url)'],
    ['area' => 'Admin', 'path' => 'admin/includes/header.php', 'expected' => 'Header (fixed logout path)'],
    ['area' => 'Admin', 'path' => 'admin/includes/footer.php', 'expected' => 'Admin footer'],

    // ADMIN CONTENT
    ['area' => 'Admin', 'path' => 'admin/content/index.php', 'expected' => 'Content list (works with type= param)'],
    ['area' => 'Admin', 'path' => 'admin/content/create.php', 'expected' => 'Content create'],
    ['area' => 'Admin', 'path' => 'admin/content/edit.php', 'expected' => 'Content edit'],
    ['area' => 'Admin', 'path' => 'admin/content/delete.php', 'expected' => 'Content delete'],
    ['area' => 'Admin', 'path' => 'admin/content/toggle-status.php', 'expected' => 'Content toggle status'],
    ['area' => 'Admin', 'path' => 'admin/content/media.php', 'expected' => 'Content media manager'],

    // ADMIN MEDIA
    ['area' => 'Admin', 'path' => 'admin/media/index.php', 'expected' => 'Media library'],
    ['area' => 'Admin', 'path' => 'admin/media/upload.php', 'expected' => 'Media upload'],
    ['area' => 'Admin', 'path' => 'admin/media/edit.php', 'expected' => 'Media edit'],
    ['area' => 'Admin', 'path' => 'admin/media/delete.php', 'expected' => 'Media delete'],

    // ADMIN MESSAGES
    ['area' => 'Admin', 'path' => 'admin/messages/index.php', 'expected' => 'Messages list'],
    ['area' => 'Admin', 'path' => 'admin/messages/view.php', 'expected' => 'Message view'],
    ['area' => 'Admin', 'path' => 'admin/messages/update-status.php', 'expected' => 'Message status update'],
    ['area' => 'Admin', 'path' => 'admin/messages/delete.php', 'expected' => 'Message delete'],

    // ADMIN SETTINGS
    ['area' => 'Admin', 'path' => 'admin/settings/index.php', 'expected' => 'Settings (inline CRUD)'],
    ['area' => 'Admin', 'path' => 'admin/settings/edit.php', 'expected' => 'Settings edit redirect (created)'],

    // ADMIN USERS
    ['area' => 'Admin', 'path' => 'admin/users/index.php', 'expected' => 'Users (inline CRUD)'],
    ['area' => 'Admin', 'path' => 'admin/users/create.php', 'expected' => 'Create user redirect (created)'],
    ['area' => 'Admin', 'path' => 'admin/users/edit.php', 'expected' => 'Edit user redirect (created)'],
    ['area' => 'Admin', 'path' => 'admin/users/delete.php', 'expected' => 'Delete user (created)'],
    ['area' => 'Admin', 'path' => 'admin/users/change-password.php', 'expected' => 'Change password redirect (created)'],

    // APP CORE
    ['area' => 'Core', 'path' => 'app/config/app.php', 'expected' => 'App config (updated with path constants)'],
    ['area' => 'Core', 'path' => 'app/core/helpers.php', 'expected' => 'Helpers (added public_url/admin_url helpers)'],
    ['area' => 'Core', 'path' => 'app/core/auth.php', 'expected' => 'Auth'],
    ['area' => 'Core', 'path' => 'app/core/csrf.php', 'expected' => 'CSRF'],
    ['area' => 'Core', 'path' => 'app/core/db.php', 'expected' => 'DB'],
    ['area' => 'Core', 'path' => 'app/core/i18n.php', 'expected' => 'i18n'],
    ['area' => 'Core', 'path' => 'app/core/upload.php', 'expected' => 'Upload handler'],
    ['area' => 'Core', 'path' => 'app/core/admin_helpers.php', 'expected' => 'Admin helpers'],
];

$pass = 0;
$fail = 0;

echo "========================================\n";
echo "  SEPJ Gabès - Route/File Check\n";
echo "========================================\n\n";

$currentArea = '';
foreach ($files as $f) {
    if ($f['area'] !== $currentArea) {
        $currentArea = $f['area'];
        echo "─── {$currentArea} Files ───\n";
    }
    
    $fullPath = $baseDir . '/' . $f['path'];
    $exists = file_exists($fullPath);
    
    $status = $exists ? "  ✅ OK" : "  ❌ MISSING";
    $note = $exists ? '' : " *** {$f['expected']}";
    
    echo "{$status}  {$f['path']}{$note}\n";
    
    if ($exists) $pass++; else $fail++;
}

echo "\n─── Summary ───\n";
echo "  ✅ {$pass} files exist\n";
if ($fail > 0) echo "  ❌ {$fail} files MISSING\n";
echo "  🟢 Total: " . ($pass + $fail) . " checked\n";

echo "\n─── Fixed Issues Summary ───\n";
echo "  1. Created public/news.php (was missing, all public nav links pointed here)\n";
echo "  2. Updated public/includes/nav.php: news link changed to news.php\n";
echo "  3. Updated public/includes/footer.php: news link changed to news.php\n";
echo "  4. Updated public/index.php: hero + view all news links changed to news.php\n";
echo "  5. Added path constants to app/config/app.php (APP_BASE_PATH, PUBLIC_URL, ADMIN_URL)\n";
echo "  6. Added public_url(), admin_url(), asset_url() helpers to app/core/helpers.php\n";
echo "  7. Fixed admin/includes/sidebar.php: all 17 links use admin_url() absolute paths\n";
echo "  8. Fixed admin/includes/header.php: logout link uses admin_url() absolute path\n";
echo "  9. Created admin/settings/edit.php (redirect to index.php)\n";
echo "  10. Created admin/users/create.php (redirect to index.php)\n";
echo "  11. Created admin/users/edit.php (redirect to index.php?edit=id)\n";
echo "  12. Created admin/users/delete.php (POST handler)\n";
echo "  13. Created admin/users/change-password.php (redirect to index.php?edit=id)\n";
echo "  14. Created public/404.php\n";
echo "  15. Created admin/404.php\n";

echo "\n─── URLs to Test in Browser ───\n";
echo "  Public:\n";
echo "    http://localhost/sepj-gabes/public/\n";
echo "    http://localhost/sepj-gabes/public/news.php\n";
echo "    http://localhost/sepj-gabes/public/projects.php\n";
echo "    http://localhost/sepj-gabes/public/services.php\n";
echo "    http://localhost/sepj-gabes/public/404.php\n";
echo "  Admin:\n";
echo "    http://localhost/sepj-gabes/admin/login.php\n";
echo "    (login: admin@sepj.local / Admin12345!)\n";
echo "    http://localhost/sepj-gabes/admin/dashboard.php\n";
echo "    http://localhost/sepj-gabes/admin/content/index.php?type=post\n";
echo "    http://localhost/sepj-gabes/admin/content/index.php?type=page\n";
echo "    http://localhost/sepj-gabes/admin/content/index.php?type=project\n";
echo "    http://localhost/sepj-gabes/admin/messages/index.php\n";
echo "    http://localhost/sepj-gabes/admin/media/index.php\n";
echo "    http://localhost/sepj-gabes/admin/settings/index.php\n";
echo "    http://localhost/sepj-gabes/admin/users/index.php\n";

echo "\n";
if ($fail > 0) {
    echo "⚠️  Some files are MISSING. Review the list above.\n";
} else {
    echo "🎉 All files present! Run browser tests to confirm functionality.\n";
}
echo "========================================\n";