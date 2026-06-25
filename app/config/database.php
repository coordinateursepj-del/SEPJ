<?php
/**
 * Database Configuration - SEPJ Gabès
 * 
 * Local XAMPP settings. For production, update these values.
 * Keep this file outside public web root for security.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'sepj_gabes');
define('DB_USER', 'sepj_user');          // dedicated DB user — not root
define('DB_PASS', 'CHANGE_ME_STRONG_PASSWORD'); // set this before deployment
define('DB_CHARSET', 'utf8mb4');