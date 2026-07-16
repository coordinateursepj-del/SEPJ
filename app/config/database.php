<?php
/**
 * Database Configuration - SEPJ Gabès
 *
 * Real credentials live in app/config/database.local.php (git-ignored), so
 * they are NEVER committed to the repo and NEVER overwritten by a git deploy.
 *
 * - On the production server: create app/config/database.local.php with the
 *   OVH database credentials (see database.local.php.example).
 * - Locally (XAMPP): if you don't create that file, the safe defaults below
 *   are used.
 */

// Load server-specific credentials if present (defines the DB_* constants).
$__localConfig = __DIR__ . '/database.local.php';
if (is_file($__localConfig)) {
    require $__localConfig;
}

// Fallback defaults (local XAMPP) — only applied for any constant the local
// file did not already define.
defined('DB_HOST')    || define('DB_HOST', '127.0.0.1');
defined('DB_PORT')    || define('DB_PORT', '3306');
defined('DB_NAME')    || define('DB_NAME', 'sepj_gabes');
defined('DB_USER')    || define('DB_USER', 'sepj_user');
defined('DB_PASS')    || define('DB_PASS', '');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');
