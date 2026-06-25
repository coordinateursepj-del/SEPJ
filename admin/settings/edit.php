<?php
/**
 * Settings - Redirect to main settings page (all editing is inline)
 */
require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/auth.php';
session_start_secure();
require_login();
redirect('index.php');