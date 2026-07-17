<?php
/**
 * Admin Content Media Manager - SEPJ Gabès
 * Manage gallery images for a specific content item
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
$contentId = (int)($_GET['id'] ?? 0);

if (!$contentId) {
    redirect('index.php');
}

// Fetch the content item
$stmt = db()->prepare("SELECT id, type, slug, COALESCE(NULLIF(title_ar, ''), NULLIF(title_fr, ''), NULLIF(title_en, ''), '---') AS title FROM content_items WHERE id = :id");
$stmt->execute(['id' => $contentId]);
$content = $stmt->fetch();

if (!$content) {
    set_flash('error', $lang === 'ar' ? 'المحتوى غير موجود.' : ($lang === 'fr' ? 'Contenu introuvable.' : 'Content not found.'));
    redirect(ADMIN_URL . '/content/index.php');
}

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    csrf_verify();
    
    $files = $_FILES['images'];
    $results = upload_multiple_files($files, 'gallery');
    
    foreach ($results as $result) {
        if ($result['success']) {
            // Find max sort order
            $sortStmt = db()->prepare("SELECT MAX(sort_order) FROM media WHERE content_item_id = :id");
            $sortStmt->execute(['id' => $contentId]);
            $maxSort = (int) $sortStmt->fetchColumn();
            
            $stmt = db()->prepare("
                INSERT INTO media (content_item_id, file_path, file_name, file_type, sort_order, created_at)
                VALUES (:content_item_id, :file_path, :file_name, :file_type, :sort_order, NOW())
            ");
            $stmt->execute([
                'content_item_id' => $contentId,
                'file_path' => $result['path'],
                'file_name' => basename($result['path']),
                'file_type' => 'image',
                'sort_order' => $maxSort + 1,
            ]);
        }
    }
    
    csrf_regenerate();
    set_flash('success', $lang === 'ar' ? 'تم رفع الصور بنجاح.' : ($lang === 'fr' ? 'Images téléchargées.' : 'Images uploaded.'));
    redirect('media.php?id=' . $contentId);
}

// Handle reorder — validate CSRF token even on GET to prevent CSRF attacks on state changes
if (isset($_GET['move']) && isset($_GET['media_id'])) {
    $reorderToken = $_GET['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $reorderToken)) {
        set_flash('error', 'Invalid request.');
        redirect('media.php?id=' . $contentId);
    }
    $mediaId = (int)$_GET['media_id'];
    $direction = $_GET['move'];
    
    $mediaStmt = db()->prepare("SELECT id, sort_order FROM media WHERE id = :id AND content_item_id = :content_id");
    $mediaStmt->execute(['id' => $mediaId, 'content_id' => $contentId]);
    $media = $mediaStmt->fetch();
    
    if ($media) {
        $newSort = $direction === 'up' ? $media['sort_order'] - 1 : $media['sort_order'] + 1;
        
        // Swap with the item that has the target sort order
        $swapStmt = db()->prepare("UPDATE media SET sort_order = :new_sort WHERE content_item_id = :content_id AND sort_order = :target_sort");
        $swapStmt->execute(['new_sort' => $media['sort_order'], 'content_id' => $contentId, 'target_sort' => $newSort]);
        
        $updateStmt = db()->prepare("UPDATE media SET sort_order = :new_sort WHERE id = :id");
        $updateStmt->execute(['new_sort' => $newSort, 'id' => $media['id']]);
    }
    
    redirect('media.php?id=' . $contentId);
}

// Fetch attached media
$mediaStmt = db()->prepare("SELECT * FROM media WHERE content_item_id = :id ORDER BY sort_order ASC, created_at ASC");
$mediaStmt->execute(['id' => $contentId]);
$mediaItems = $mediaStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attribute($lang) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Images: <?= e(mb_substr($content['title'], 0, 30)) ?> - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
</head>
<body class="admin-theme-bg min-h-screen">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    
    <div class="relative z-10 flex h-screen">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include '../includes/header.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-6">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <div>
                        <div class="breadcrumbs">
                            <a href="<?= ADMIN_URL ?>/dashboard.php"><?= $lang === 'ar' ? 'الرئيسية' : ($lang === 'fr' ? 'Accueil' : 'Home') ?></a>
                            <span class="separator">/</span>
                            <a href="<?= ADMIN_URL ?>/content/index.php?type=<?= e($content['type']) ?>"><?= get_content_type_label($content['type'], $lang) ?></a>
                            <span class="separator">/</span>
                            <span><?= e(mb_substr($content['title'], 0, 50)) ?></span>
                        </div>
                        <h1 class="text-2xl font-bold text-white mt-2">🖼️ <?= $lang === 'ar' ? 'إدارة الصور' : ($lang === 'fr' ? 'Gestion des images' : 'Image Management') ?></h1>
                    </div>
                    <a href="edit.php?id=<?= $contentId ?>" class="glass-btn text-sm">&larr; <?= $lang === 'ar' ? 'العودة للتعديل' : ($lang === 'fr' ? 'Retour' : 'Back to edit') ?></a>
                </div>
                
                <?php $flash = get_flash(); if ($flash): ?>
                <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-emerald-600/30 border-emerald-500/30 text-emerald-300' : 'bg-red-600/30 border-red-500/30 text-red-300' ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>
                
                <!-- Upload Form -->
                <div class="glass-card-static p-6 mb-6">
                    <h2 class="text-lg font-semibold text-white mb-4"><?= $lang === 'ar' ? 'رفع صور جديدة' : ($lang === 'fr' ? 'Télécharger des images' : 'Upload New Images') ?></h2>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" 
                               class="block w-full text-sm text-white/70 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-600/30 file:text-emerald-300 hover:file:bg-emerald-600/40 mb-4">
                        <button type="submit" class="glass-btn"><?= $lang === 'ar' ? 'رفع' : ($lang === 'fr' ? 'Télécharger' : 'Upload') ?></button>
                    </form>
                </div>
                
                <!-- Gallery Grid -->
                <div class="glass-card-static p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">
                        <?= $lang === 'ar' ? 'الصور المرفقة' : ($lang === 'fr' ? 'Images attachées' : 'Attached Images') ?>
                        (<?= count($mediaItems) ?>)
                    </h2>
                    
                    <?php if (empty($mediaItems)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🖼️</div>
                        <p><?= $lang === 'ar' ? 'لا توجد صور بعد' : ($lang === 'fr' ? 'Aucune image' : 'No images yet') ?></p>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <?php foreach ($mediaItems as $media): ?>
                        <div class="glass-card overflow-hidden group">
                            <div class="aspect-square overflow-hidden">
                                <img src="<?= e(upload_url($media['file_path'])) ?>" alt="<?= e($media['alt_ar'] ?? '') ?>" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            </div>
                            <div class="p-2 flex items-center justify-between gap-1">
                                <div class="flex gap-1">
                                    <a href="?id=<?= $contentId ?>&move=up&media_id=<?= $media['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                       class="text-xs text-emerald-400 hover:text-emerald-300 p-1 <?= $media === reset($mediaItems) ? 'opacity-30 pointer-events-none' : '' ?>">▲</a>
                                    <a href="?id=<?= $contentId ?>&move=down&media_id=<?= $media['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                       class="text-xs text-emerald-400 hover:text-emerald-300 p-1 <?= $media === end($mediaItems) ? 'opacity-30 pointer-events-none' : '' ?>">▼</a>
                                </div>
                                <a href="../media/edit.php?id=<?= $media['id'] ?>&return=content&content_id=<?= $contentId ?>" 
                                   class="text-xs text-blue-400 hover:text-blue-300 p-1">✏️</a>
                                <a href="../media/delete.php?id=<?= $media['id'] ?>&return=content&content_id=<?= $contentId ?>&csrf_token=<?= csrf_token() ?>"
                                   class="text-xs text-red-400 hover:text-red-300 p-1"
                                   onclick="return confirm('<?= $lang === 'ar' ? 'حذف هذه الصورة؟' : ($lang === 'fr' ? 'Supprimer cette image ?' : 'Delete this image?') ?>')">🗑️</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
            
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>
    
    <script src="../../public/assets/js/admin.js"></script>
</body>
</html>