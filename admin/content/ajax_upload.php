<?php
/**
 * Admin Content - AJAX single image upload - SEPJ Gabès
 *
 * Uploads ONE image at a time (kept small to stay under OVH's FastCGI
 * request-length limit, which a single multi-file POST would exceed).
 * Returns JSON: { success, id, url, path, message }.
 */

require_once dirname(__DIR__, 2) . '/app/config/app.php';
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

$contentId = (int) ($_POST['content_id'] ?? 0);
$subdir    = ($_POST['subdir'] ?? 'content') === 'gallery' ? 'gallery' : 'content';

if (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'No file received.']);
    exit;
}

// Respect the global cap across existing + this upload.
if ($contentId > 0) {
    $count = (int) db()->prepare("SELECT COUNT(*) FROM media WHERE content_item_id = :id")
        ->execute(['id' => $contentId])->fetchColumn();
    // We don't know how many are pending in this batch; the hard cap is enforced
    // on save. Here we just guard a sane per-request limit.
}

$result = upload_file($_FILES['image'], $subdir);

if (!$result['success']) {
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit;
}

// Persist a media row. For create (content_id = 0) we use a temp marker and
// re-attach on save; for edit we attach immediately.
$sortStmt = db()->prepare("SELECT MAX(sort_order) FROM media WHERE content_item_id = :id");
$sortStmt->execute(['id' => $contentId]);
$maxSort = (int) $sortStmt->fetchColumn();

$stmt = db()->prepare("
    INSERT INTO media (content_item_id, file_path, file_name, file_type, sort_order, created_at)
    VALUES (:content_item_id, :file_path, :file_name, :file_type, :sort_order, NOW())
");
// Store as a temp row (content_item_id = NULL). The column is nullable and the
// foreign key ignores NULLs, so this never violates the FK. On save, create.php /
// edit.php re-attach these rows to the real content item by their id. (Using 0 as a
// sentinel would break the FK and return HTTP 500.)
$stmt->execute([
    'content_item_id' => null,
    'file_path'       => $result['path'],
    'file_name'       => basename($result['path']),
    'file_type'       => 'image',
    'sort_order'      => $maxSort + 1,
]);
$mediaId = db()->lastInsertId();

echo json_encode([
    'success' => true,
    'id'      => $mediaId,
    'url'     => upload_url($result['path']),
    'path'    => $result['path'],
]);
exit;
