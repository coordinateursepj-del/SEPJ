<?php
/**
 * Admin Media Library - SEPJ Gabès
 */

require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/admin_helpers.php';
require_once ROOT_PATH . '/app/core/upload.php';
require_once ROOT_PATH . '/app/core/i18n.php';

session_start_secure();
require_login();

$lang = current_lang();
$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ADMIN_ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

try {
    $where = "WHERE 1=1";
    $params = [];

    if ($search) {
        $where .= " AND (file_name LIKE :search1 OR caption_ar LIKE :search2 OR caption_fr LIKE :search3 OR caption_en LIKE :search4)";
        $params['search1'] = "%{$search}%";
        $params['search2'] = "%{$search}%";
        $params['search3'] = "%{$search}%";
        $params['search4'] = "%{$search}%";
    }
    if ($type) {
        $where .= " AND file_type = :type";
        $params['type'] = $type;
    }
    
    $countStmt = db()->prepare("SELECT COUNT(*) FROM media {$where}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));
    
    $stmt = db()->prepare("SELECT * FROM media {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) $stmt->bindValue(":{$key}", $value);
    $stmt->execute();
    $mediaItems = $stmt->fetchAll();
} catch (PDOException $e) {
    $mediaItems = []; $total = 0; $totalPages = 1;
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attribute($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'ar' ? 'الوسائط' : ($lang === 'fr' ? 'Médias' : 'Media') ?> - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
</head>
<body class="bg-gradient-to-br from-gray-900 via-emerald-950 to-gray-900 min-h-screen">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="relative z-10 flex h-screen">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include '../includes/header.php'; ?>
            <main class="flex-1 overflow-y-auto p-6">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-bold text-white">🖼️ <?= $lang === 'ar' ? 'مكتبة الوسائط' : ($lang === 'fr' ? 'Médiathèque' : 'Media Library') ?></h1>
                    <a href="upload.php" class="glass-btn">+ <?= $lang === 'ar' ? 'رفع' : ($lang === 'fr' ? 'Télécharger' : 'Upload') ?></a>
                </div>
                
                <!-- Search -->
                <div class="glass-card-static p-4 mb-6">
                    <form method="GET" class="flex gap-3 items-end">
                        <div class="flex-1">
                            <input type="text" name="search" value="<?= e($search) ?>" class="form-input text-sm" placeholder="<?= $lang === 'ar' ? 'بحث...' : ($lang === 'fr' ? 'Recherche...' : 'Search...') ?>">
                        </div>
                        <button type="submit" class="glass-btn text-sm py-2.5"><?= $lang === 'ar' ? 'بحث' : ($lang === 'fr' ? 'Chercher' : 'Search') ?></button>
                        <?php if ($search): ?><a href="index.php" class="glass-btn text-sm py-2.5 bg-red-500/20">✕</a><?php endif; ?>
                    </form>
                </div>
                
                <?php if (empty($mediaItems)): ?>
                <div class="empty-state"><div class="empty-state-icon">🖼️</div><p><?= __('no_results', $lang) ?></p></div>
                <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php foreach ($mediaItems as $media): ?>
                    <div class="glass-card overflow-hidden group cursor-pointer" onclick="openMediaPreview('<?= e(upload_url($media['file_path'])) ?>', '<?= e($media['file_name']) ?>')">
                        <div class="aspect-square overflow-hidden">
                            <img src="<?= e(upload_url($media['file_path'])) ?>" alt="" class="w-full h-full object-cover group-hover:scale-110 transition-transform">
                        </div>
                        <div class="p-2">
                            <p class="text-xs text-white/70 truncate"><?= e($media['file_name']) ?></p>
                            <div class="flex justify-between mt-1">
                                <a href="edit.php?id=<?= $media['id'] ?>" class="text-xs text-blue-400">✏️</a>
                                <a href="delete.php?id=<?= $media['id'] ?>&csrf_token=<?= csrf_token() ?>" class="text-xs text-red-400" onclick="return confirm('<?= $lang === 'ar' ? 'حذف هذه الصورة؟' : ($lang === 'fr' ? 'Supprimer cette image ?' : 'Delete this image?') ?>')">🗑️</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center gap-2 mt-6">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?><?= $search ? '&search='.e($search) : '' ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-emerald-600/30' : 'bg-white/5' ?> text-white"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </main>
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Preview Modal — uses style.display toggle instead of Tailwind 'hidden' so flex centering works -->
    <div id="mediaPreviewModal"
         class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center"
         role="dialog" aria-modal="true"
         aria-label="<?= $lang === 'ar' ? 'معاينة الصورة' : ($lang === 'fr' ? 'Aperçu de l\'image' : 'Image preview') ?>"
         aria-hidden="true"
         style="display:none"
         onclick="closeMediaPreview()">
        <div class="max-w-4xl max-h-[90vh] p-4 relative" onclick="event.stopPropagation()">
            <img id="mediaPreviewImage" src="" alt="" class="max-w-full max-h-[80vh] rounded-lg">
            <p id="mediaPreviewCaption" class="text-white text-center mt-2"></p>
            <button data-close-modal onclick="closeMediaPreview()"
                    class="absolute top-0 right-0 text-white text-2xl w-10 h-10 flex items-center justify-center hover:text-red-400"
                    aria-label="Close">&times;</button>
        </div>
    </div>
    
    <script src="../../public/assets/js/admin.js"></script>
</body>
</html>