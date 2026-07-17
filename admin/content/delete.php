<?php
/**
 * Admin Content Delete - SEPJ Gabès
 */

require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/admin_helpers.php';
require_once ROOT_PATH . '/app/core/upload.php';

session_start_secure();
require_login();

$lang = current_lang();
$id = (int)($_GET['id'] ?? 0);
$reqType = validate_type($_GET['type'] ?? '') ? $_GET['type'] : 'post';

// CSRF protection for delete action (previously GET-only)
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    set_flash('error', $lang === 'ar' ? 'طلب غير صالح.' : ($lang === 'fr' ? 'Requête invalide.' : 'Invalid request.'));
    redirect('index.php?type=' . $reqType);
}

if (!$id) {
    set_flash('error', $lang === 'ar' ? 'معرف غير صالح.' : ($lang === 'fr' ? 'ID invalide.' : 'Invalid ID.'));
    redirect('index.php?type=' . $reqType);
}

try {
    // Fetch item
    $stmt = db()->prepare("SELECT * FROM content_items WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        set_flash('error', $lang === 'ar' ? 'العنصر غير موجود.' : ($lang === 'fr' ? 'Élément introuvable.' : 'Item not found.'));
        redirect('index.php?type=' . $reqType);
    }
    
    $type = $item['type'];
    $status = $item['status'] ?? 'draft';

    // Business rule (B-03): Published content should not be hard-deleted without unpublishing/notice.
    if ($status === 'published') {
        set_flash('error', $lang === 'ar'
            ? 'لا يمكن حذف المحتوى المنشور مباشرة. قم بإلغاء النشر أولاً.'
            : ($lang === 'fr'
                ? 'Impossible de supprimer un contenu publié directement. Veuillez le dépublier d’abord.'
                : 'Cannot delete published content directly. Please unpublish it first.')); 
        // Audit the blocked attempt
        log_audit($_SESSION['user_id'], 'delete_blocked_published', $type, $id);
        redirect('index.php?type=' . $type);
    }

    // Delete featured image if exists
    if (!empty($item['featured_image'])) {
        delete_uploaded_file($item['featured_image']);
    }

    // Delete the content item
    $stmt = db()->prepare("DELETE FROM content_items WHERE id = :id");
    $stmt->execute(['id' => $id]);

    
    // Log audit
    log_audit($_SESSION['user_id'], 'delete', $type, $id);
    
    set_flash('success', $lang === 'ar' ? 'تم حذف العنصر بنجاح.' : ($lang === 'fr' ? 'Élément supprimé avec succès.' : 'Item deleted successfully.'));
    
} catch (PDOException $e) {
    error_log("Delete content error: " . $e->getMessage());
    set_flash('error', $lang === 'ar' ? 'خطأ في الحذف.' : ($lang === 'fr' ? 'Erreur de suppression.' : 'Delete error.'));
}

$type = $type ?? 'post';
redirect('index.php?type=' . $type);