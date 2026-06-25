<?php
require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/upload.php';
session_start_secure(); require_login();

$lang = current_lang();
$id   = (int)($_GET['id'] ?? 0);

// CSRF validation — required for all destructive GET operations
$token = $_GET['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    set_flash('error', $lang === 'ar' ? 'طلب غير صالح.' : ($lang === 'fr' ? 'Requête invalide.' : 'Invalid request.'));
    redirect('index.php');
}

$stmt = db()->prepare("SELECT * FROM media WHERE id = :id");
$stmt->execute(['id' => $id]);
$media = $stmt->fetch();
if ($media) {
    delete_uploaded_file($media['file_path']);
    db()->prepare("DELETE FROM media WHERE id = :id")->execute(['id' => $id]);
    log_audit($_SESSION['user_id'], 'delete', 'media', $id);
}

$return    = $_GET['return']     ?? '';
$contentId = (int)($_GET['content_id'] ?? 0);
if ($return === 'content' && $contentId) { redirect("../content/media.php?id={$contentId}"); }
redirect('index.php');