<?php
/**
 * Application Configuration - SEPJ Gabès
 */

define('APP_NAME', 'SEPJ Gabès');
define('APP_NAME_AR', 'شركة البيئة والغراسة والبستنة بقابس');
define('APP_NAME_FR', "Société d'Environnement, Plantation et Jardinage de Gabès");

// On production the vhost docroot is the project root, so there is
// no base-path prefix. On localhost the project sits under /sepj-gabes.
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host === 'sepjgabes.tn' || $host === 'www.sepjgabes.tn') {
    define('BASE_URL',      'https://sepjgabes.tn');
    define('APP_BASE_PATH', '');
} else {
    define('BASE_URL',      'http://localhost/sepj-gabes');
    define('APP_BASE_PATH', '/sepj-gabes');
}

// Paths
define('ROOT_PATH', dirname(__DIR__, 2));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('UPLOAD_URL', APP_BASE_PATH . '/public/uploads');
define('PUBLIC_URL', APP_BASE_PATH . '/public');
define('ADMIN_URL', APP_BASE_PATH . '/admin');

// Default language: ar, fr, en
define('DEFAULT_LANGUAGE', 'ar');

// Supported languages
define('SUPPORTED_LANGUAGES', serialize(['ar', 'fr', 'en']));

// Session
define('SESSION_LIFETIME', 7200); // 2 hours

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// Upload limits
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,webp');
define('ALLOWED_MIME_TYPES', serialize(['image/jpeg', 'image/png', 'image/webp']));
define('MAX_GALLERY_IMAGES', 20); // max images attached to a single content item

// Gemini AI API key for auto-generating titles and summaries from article body.
// Get a free key at https://aistudio.google.com/apikey
define('GEMINI_API_KEY', '');

// Timezone
date_default_timezone_set('Africa/Tunis');