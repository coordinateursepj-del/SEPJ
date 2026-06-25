<?php
/**
 * Admin Content Toggle Status - SEPJ Gabès
 */

require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';

session_start_secure();
require_login();

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

// CSRF check via GET parameter
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    set_flash('error', 'Invalid request.');
    redirect('index.php');
}

if (!$id) {
    redirect('index.php');
}

try {
    $stmt = db()->prepare("SELECT id, type, status FROM content_items WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        set_flash('error', 'Item not found.');
        redirect('index.php');
    }
    
    $newStatus = $item['status'] === 'published' ? 'draft' : 'published';
    
    $stmt = db()->prepare("UPDATE content_items SET status = :status, published_at = :published_at, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        'status' => $newStatus,
        'published_at' => $newStatus === 'published' ? date('Y-m-d H:i:s') : null,
        'id' => $id,
    ]);
    
    log_audit($_SESSION['user_id'], $newStatus === 'published' ? 'publish' : 'unpublish', $item['type'], $id);
    
    set_flash('success', $newStatus === 'published' ? 'تم النشر بنجاح.' : 'تم إلغاء النشر.');
    
} catch (PDOException $e) {
    error_log("Toggle status error: " . $e->getMessage());
    set_flash('error', 'Error updating status.');
}

redirect('index.php?type=' . ($item['type'] ?? 'post'));