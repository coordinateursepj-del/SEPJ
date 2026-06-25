<?php
/**
 * Admin Content List - SEPJ Gabès
 */

require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/admin_helpers.php';
require_once ROOT_PATH . '/app/core/i18n.php';

session_start_secure();
require_login();

$lang = current_lang();
$type = $_GET['type'] ?? 'post';

if (!validate_type($type)) {
    redirect(ADMIN_URL . '/content/index.php?type=post');
}

$pageTitle = get_content_page_title($type, $lang, 'list');

// Search and filters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$featured = $_GET['featured'] ?? '';
$rseCategory = trim($_GET['rse_category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ADMIN_ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

try {
    $pdo = db();
    
    // Build query
    $where = "WHERE type = :type";
    $params = ['type' => $type];
    
    if ($search) {
        $where .= " AND (title_ar LIKE :search1 OR title_fr LIKE :search2 OR title_en LIKE :search3)";
        $params['search1'] = "%{$search}%";
        $params['search2'] = "%{$search}%";
        $params['search3'] = "%{$search}%";
    }
    
    if ($status) {
        $where .= " AND status = :status";
        $params['status'] = $status;
    }
    
    if ($featured !== '') {
        $where .= " AND is_featured = :featured";
        $params['featured'] = $featured ? 1 : 0;
    }
    
    if ($rseCategory !== '') {
        $where .= " AND rse_category = :rse_category";
        $params['rse_category'] = $rseCategory;
    }
    
    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM content_items {$where}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));
    
    // Fetch items
    $stmt = $pdo->prepare("
        SELECT id, type, slug, 
               COALESCE(NULLIF(title_ar, ''), NULLIF(title_fr, ''), NULLIF(title_en, ''), '---') AS title,
               rse_category, status, is_featured, featured_image, published_at, created_at, updated_at
        FROM content_items 
        {$where}
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    
    $stmt->execute();
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Content list error: " . $e->getMessage());
    $items = [];
    $total = 0;
    $totalPages = 1;
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attribute($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
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
                <?= admin_breadcrumb($type, 'list') ?>
                
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-bold text-white"><?= e($pageTitle) ?></h1>
                    <a href="create.php?type=<?= e($type) ?>" class="glass-btn">
                        + <?php if ($lang === 'ar'): ?>إضافة جديد
                        <?php elseif ($lang === 'fr'): ?>Ajouter
                        <?php else: ?>Add New
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Filters -->
                <div class="glass-card-static p-4 mb-6">
                    <form method="GET" class="flex flex-wrap gap-3 items-end">
                        <input type="hidden" name="type" value="<?= e($type) ?>">
                        
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs text-emerald-300/60 mb-1">
                                <?php if ($lang === 'ar'): ?>بحث
                                <?php elseif ($lang === 'fr'): ?>Recherche
                                <?php else: ?>Search
                                <?php endif; ?>
                            </label>
                            <input type="text" name="search" value="<?= e($search) ?>" 
                                   class="form-input text-sm" 
                                   placeholder="<?php if ($lang === 'ar'): ?>ابحث عن عنوان...
                                   <?php elseif ($lang === 'fr'): ?>Chercher un titre...
                                   <?php else: ?>Search title...
                                   <?php endif; ?>">
                        </div>
                        
                        <div>
                            <label class="block text-xs text-emerald-300/60 mb-1">
                                <?php if ($lang === 'ar'): ?>الحالة
                                <?php elseif ($lang === 'fr'): ?>Statut
                                <?php else: ?>Status
                                <?php endif; ?>
                            </label>
                            <select name="status" class="form-input text-sm">
                                <option value=""><?php if ($lang === 'ar'): ?>الكل
                                <?php elseif ($lang === 'fr'): ?>Tous
                                <?php else: ?>All
                                <?php endif; ?></option>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>><?php if ($lang === 'ar'): ?>منشور
                                <?php elseif ($lang === 'fr'): ?>Publié
                                <?php else: ?>Published
                                <?php endif; ?></option>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>><?php if ($lang === 'ar'): ?>مسودة
                                <?php elseif ($lang === 'fr'): ?>Brouillon
                                <?php else: ?>Draft
                                <?php endif; ?></option>
                            </select>
                        </div>
                        
                        <!-- RSE Category filter (only for RSE type) -->
                        <?php if ($type === 'rse'): ?>
                        <div>
                            <label class="block text-xs text-emerald-300/60 mb-1">
                                <?= $lang === 'ar' ? 'التصنيف' : ($lang === 'fr' ? 'Catégorie' : 'Category') ?>
                            </label>
                            <select name="rse_category" class="form-input text-sm">
                                <option value=""><?= $lang === 'ar' ? 'الكل' : ($lang === 'fr' ? 'Tous' : 'All') ?></option>
                                <?php foreach (array_keys(rse_category_labels()) as $cat): ?>
                                <option value="<?= $cat ?>" <?= $rseCategory === $cat ? 'selected' : '' ?>>
                                    <?= rse_category_label($cat, $lang) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-xs text-emerald-300/60 mb-1">&nbsp;</label>
                            <button type="submit" class="glass-btn text-sm py-2.5">
                                <?php if ($lang === 'ar'): ?>تصفية
                                <?php elseif ($lang === 'fr'): ?>Filtrer
                                <?php else: ?>Filter
                                <?php endif; ?>
                            </button>
                        </div>
                        
                        <?php if ($search || $status || $rseCategory): ?>
                        <div>
                            <label class="block text-xs text-emerald-300/60 mb-1">&nbsp;</label>
                            <a href="index.php?type=<?= e($type) ?>" class="glass-btn text-sm py-2.5 bg-red-500/20 border-red-500/30 hover:bg-red-500/40">
                                ✕ <?php if ($lang === 'ar'): ?>مسح
                                <?php elseif ($lang === 'fr'): ?>Effacer
                                <?php else: ?>Clear
                                <?php endif; ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Content Table -->
                <div class="glass-card-static overflow-hidden">
                    <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><?= get_type_icon($type) ?></div>
                        <p><?= __('no_results', $lang) ?></p>
                        <a href="create.php?type=<?= e($type) ?>" class="glass-btn mt-4 inline-block">
                            + <?php if ($lang === 'ar'): ?>إضافة أول عنصر
                            <?php elseif ($lang === 'fr'): ?>Ajouter le premier
                            <?php else: ?>Add first item
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/10 text-emerald-300/70">
                                    <th class="text-right p-3 font-medium">
                                        <?php if ($lang === 'ar'): ?>العنوان
                                        <?php elseif ($lang === 'fr'): ?>Titre
                                        <?php else: ?>Title
                                        <?php endif; ?>
                                    </th>
                                    <th class="text-right p-3 font-medium hidden md:table-cell">
                                        <?php if ($lang === 'ar'): ?>الرابط
                                        <?php elseif ($lang === 'fr'): ?>Slug
                                        <?php else: ?>Slug
                                        <?php endif; ?>
                                    </th>
                                    <?php if ($type === 'rse'): ?>
                                    <th class="text-center p-3 font-medium hidden md:table-cell">
                                        <?= $lang === 'ar' ? 'التصنيف' : ($lang === 'fr' ? 'Catégorie' : 'Category') ?>
                                    </th>
                                    <?php endif; ?>
                                    <th class="text-center p-3 font-medium">
                                        <?php if ($lang === 'ar'): ?>الحالة
                                        <?php elseif ($lang === 'fr'): ?>Statut
                                        <?php else: ?>Status
                                        <?php endif; ?>
                                    </th>
                                    <th class="text-center p-3 font-medium hidden lg:table-cell">
                                        <?php if ($lang === 'ar'): ?>مميز
                                        <?php elseif ($lang === 'fr'): ?>À la une
                                        <?php else: ?>Featured
                                        <?php endif; ?>
                                    </th>
                                    <th class="text-center p-3 font-medium hidden md:table-cell">
                                        <?php if ($lang === 'ar'): ?>التاريخ
                                        <?php elseif ($lang === 'fr'): ?>Date
                                        <?php else: ?>Date
                                        <?php endif; ?>
                                    </th>
                                    <th class="text-center p-3 font-medium">
                                        <?php if ($lang === 'ar'): ?>الإجراءات
                                        <?php elseif ($lang === 'fr'): ?>Actions
                                        <?php else: ?>Actions
                                        <?php endif; ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                    <td class="p-3">
                                        <div class="flex items-center gap-2">
                                            <?php if ($item['featured_image']): ?>
                                            <img src="<?= e(upload_url($item['featured_image'])) ?>" alt="" class="w-10 h-10 rounded object-cover shrink-0">
                                            <?php else: ?>
                                            <div class="w-10 h-10 rounded bg-white/5 flex items-center justify-center text-lg shrink-0">
                                                <?= get_type_icon($type) ?>
                                            </div>
                                            <?php endif; ?>
                                            <span class="text-white font-medium"><?= e(mb_substr($item['title'], 0, 60)) ?></span>
                                        </div>
                                    </td>
                                    <td class="p-3 text-emerald-300/50 text-xs hidden md:table-cell">
                                        <?= e($item['slug']) ?>
                                    </td>
                                    <?php if ($type === 'rse'): ?>
                                    <td class="p-3 text-center hidden md:table-cell">
                                        <span class="inline-block px-2 py-0.5 text-xs rounded-full border bg-emerald-500/20 text-emerald-300 border-emerald-500/30">
                                            <?= e(rse_category_label($item['rse_category'] ?? null, $lang)) ?>
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                    <td class="p-3 text-center">
                                        <?= status_badge($item['status']) ?>
                                    </td>
                                    <td class="p-3 text-center hidden lg:table-cell">
                                        <?php if ($item['is_featured']): ?>
                                        <span class="text-emerald-400">★</span>
                                        <?php else: ?>
                                        <span class="text-white/20">☆</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center text-emerald-300/50 text-xs hidden md:table-cell">
                                        <?= format_date($item['created_at'], 'd/m/Y') ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="edit.php?id=<?= $item['id'] ?>" class="text-emerald-400 hover:text-emerald-300 transition-colors" title="Edit">
                                                ✏️
                                            </a>
                                            <a href="toggle-status.php?id=<?= $item['id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                               class="transition-colors <?= $item['status'] === 'published' ? 'text-yellow-400 hover:text-yellow-300' : 'text-emerald-400 hover:text-emerald-300' ?>" 
                                               title="<?= $item['status'] === 'published' ? 'Unpublish' : 'Publish' ?>">
                                                <?= $item['status'] === 'published' ? '📪' : '📫' ?>
                                            </a>
<a href="delete.php?id=<?= $item['id'] ?>&type=<?= e($type) ?>&csrf_token=<?= urlencode(csrf_token()) ?>" 
                                               class="text-red-400 hover:text-red-300 transition-colors" 
                                               title="Delete"
                                               onclick="return confirm('<?php if ($lang === 'ar'): ?>هل أنت متأكد من حذف هذا العنصر؟\nلا يمكن التراجع عن هذا الإجراء.<?php elseif ($lang === 'fr'): ?>Êtes-vous sûr de vouloir supprimer cet élément ?\nCette action est irréversible.<?php else: ?>Are you sure you want to delete this item?\nThis action cannot be undone.<?php endif; ?>')">
                                                🗑️
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between p-4 border-t border-white/10">
                        <div class="text-xs text-emerald-300/50">
                            <?= sprintf('%s %d %s %d', 
                                $lang === 'ar' ? 'صفحة' : ($lang === 'fr' ? 'Page' : 'Page'),
                                $page, 
                                $lang === 'ar' ? 'من' : ($lang === 'fr' ? 'sur' : 'of'),
                                $totalPages) ?>
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                            <a href="?type=<?= e($type) ?>&page=<?= $page - 1 ?><?= $search ? '&search=' . e($search) : '' ?><?= $status ? '&status=' . e($status) : '' ?><?= $rseCategory ? '&rse_category=' . e($rseCategory) : '' ?>" 
                               class="px-3 py-1.5 rounded bg-white/5 text-white/70 hover:bg-white/10 transition-colors text-sm">
                                &laquo;
                            </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?type=<?= e($type) ?>&page=<?= $i ?><?= $search ? '&search=' . e($search) : '' ?><?= $status ? '&status=' . e($status) : '' ?><?= $rseCategory ? '&rse_category=' . e($rseCategory) : '' ?>" 
                               class="px-3 py-1.5 rounded text-sm transition-colors <?= $i === $page ? 'bg-emerald-600/30 text-emerald-300' : 'bg-white/5 text-white/70 hover:bg-white/10' ?>">
                                <?= $i ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="?type=<?= e($type) ?>&page=<?= $page + 1 ?><?= $search ? '&search=' . e($search) : '' ?><?= $status ? '&status=' . e($status) : '' ?><?= $rseCategory ? '&rse_category=' . e($rseCategory) : '' ?>" 
                               class="px-3 py-1.5 rounded bg-white/5 text-white/70 hover:bg-white/10 transition-colors text-sm">
                                &raquo;
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </main>
            
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>
    
    <script src="../../public/assets/js/admin.js"></script>
</body>
</html>