<?php
/**
 * Public News Listing - SEPJ Gabès
 */
require_once 'includes/header.php';
$lang = current_lang();
$l = ['ar' => 'ar', 'fr' => 'fr', 'en' => 'en'][$lang] ?? 'ar';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

try {
    $pdo = db();
    
    // Count total published posts
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM content_items WHERE type = 'post' AND status = 'published'");
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));
    
    // Fetch posts
    $stmt = $pdo->prepare("
        SELECT id, slug, 
               COALESCE(NULLIF(title_{$l}, ''), title_ar) AS t,
               COALESCE(NULLIF(summary_{$l}, ''), summary_ar) AS s,
               featured_image, published_at 
        FROM content_items 
        WHERE type = 'post' AND status = 'published' 
        ORDER BY published_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll();
} catch (Exception $e) {
    $items = [];
    $total = 0;
    $totalPages = 1;
}
?>
<main id="main-content">
    <div class="page-hero">
        <div class="max-w-7xl mx-auto px-4">
            <h1><span aria-hidden="true">📰</span> <?= __('nav_news', $lang) ?></h1>
        </div>
    </div>

    <section class="py-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <?php if (empty($items)): ?>
            <div class="empty-state">
                <div class="empty-state-icon" aria-hidden="true">📰</div>
                <p><?= __('no_results', $lang) ?></p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($items as $i): ?>
                <a href="page.php?slug=<?= e($i['slug']) ?>" class="glass-card overflow-hidden group">
                    <?php if ($i['featured_image']): ?>
                    <div class="img-card">
                        <img src="<?= e(upload_url($i['featured_image'])) ?>"
                             alt="<?= e($i['t']) ?>"
                             loading="lazy">
                        <div class="img-card-overlay"><span class="text-white text-sm"><?= __('read_more', $lang) ?></span></div>
                    </div>
                    <?php else: ?>
                    <div class="img-card bg-emerald-800/20 flex items-center justify-center text-4xl" aria-hidden="true">📰</div>
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-semibold text-white mb-2"><?= e(mb_substr($i['t'], 0, 80)) ?></h3>
                        <?php if ($i['published_at']): ?>
                        <p class="text-xs text-emerald-300/50 mb-2"><?= format_date($i['published_at'], 'd/m/Y') ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-emerald-200/70"><?= e(excerpt($i['s'] ?? '', 120)) ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="<?= $lang === 'ar' ? 'تنقل الصفحات' : ($lang === 'fr' ? 'Pagination' : 'Pagination') ?>"
                 class="flex justify-center gap-2 mt-8">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>"
                   class="px-4 py-2 rounded-lg backdrop-blur-sm border border-white/20 text-white transition-all pagination-btn <?= $i === $page ? 'bg-emerald-600/40 border-emerald-500' : 'bg-white/10 hover:bg-emerald-600/40' ?>"
                   <?= $i === $page ? 'aria-current="page"' : '' ?>>
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>