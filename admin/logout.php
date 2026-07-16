<?php
/**
 * Admin Logout - SEPJ Gabès
 */

require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';

session_start_secure();
require_login();

// CSRF protection: require valid token from POST only (GET leaks token in logs/history)
$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    error_log("SECURITY: Invalid CSRF token for logout attempt from {$_SERVER['REMOTE_ADDR']}");
    set_flash('error', 'Invalid request. Please try again.');
    redirect('dashboard.php');
}

// Log out and redirect to login
logout();
redirect('login.php');