<?php
/**
 * Edit User - Redirect to main users page with edit parameter
 */
require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/auth.php';
session_start_secure();
require_role('admin');
$id = (int)($_GET['id'] ?? 0);
redirect('index.php?edit=' . $id);