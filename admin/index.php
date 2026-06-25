<?php
/**
 * Admin Index - Redirect to login or dashboard
 * SEPJ Gabès
 */

require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/helpers.php';

session_start_secure();

if (is_logged_in()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}