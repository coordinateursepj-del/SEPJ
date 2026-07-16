<?php
/**
 * Admin Content - AJAX single image upload - SEPJ Gabès
 *
 * Uploads ONE image at a time (kept small to stay under OVH's FastCGI
 * request-length limit, which a single multi-file POST would exceed).
 *
 * This endpoint ONLY saves the file to disk and returns its path. It does NOT
 * touch the database — the media row is created later, on Save, when we already
 * have a valid content_item_id. This avoids any foreign-key constraint issues
 * during the upload step.
 *
 * Returns JSON: { success, path, url, message }
 */

require_once dirname(__DIR__, 2) . '/app/config/app.php';

// Surface ANY PHP error as JSON so the browser shows the real message instead of
// a blank "HTTP 500". This is essential to diagnose environment-specific failures.
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'PHP error: ' . $errstr . ' in ' . $errfile . ':' . $errline]);
    exit;
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR], true)) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Fatal: ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line']]);
        exit;
    }
});

require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/upload.php';

session_start_secure();
require_login();

header('Content-Type: application/json; charset=utf-8');

// Validate CSRF token from header or POST
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$subdir = ($_POST['subdir'] ?? 'content') === 'gallery' ? 'gallery' : 'content';

if (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'No file received.']);
    exit;
}

$result = upload_file($_FILES['image'], $subdir);

if (!$result['success']) {
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit;
}

echo json_encode([
    'success' => true,
    'path'    => $result['path'],
    'url'     => upload_url($result['path']),
]);
exit;
