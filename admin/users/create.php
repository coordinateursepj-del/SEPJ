<?php
/**
 * Create User - Redirect to main users page (inline form)
 */
require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/auth.php';
session_start_secure();
require_role('admin');
redirect('index.php');