<?php
require_once 'includes/header.php';
$lang = current_lang();

$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

try {
    $captionCols = ['ar' => 'm.caption_ar', 'fr' => 'm.caption_fr', 'en' => 'm.caption_en'];
    $captionCol = $captionCols[$lang] ?? 'm.caption_ar';
    $titleCol = "COALESCE(NULLIF(ci.title_{$lang}, ''), NULLIF(ci.title_ar, ''), NULLIF(ci.title_fr, ''), NULLIF(ci.title_en, ''))";

    $total = (int)db()->query("
        SELECT COUNT(*)
        FROM media m
        LEFT JOIN content_items ci ON m.content_item_id = ci.id
        WHERE (m.content_item_id IS NULL OR ci.status = 'published')
          AND m.file_path IS NOT NULL AND m.file_path != ''
    ")->fetchColumn();

    $totalPages = max(1, ceil($total / $perPage));

    $images = db()->prepare("
        SELECT m.*,
               COALESCE(NULLIF({$captionCol}, ''), m.caption_ar) AS caption,
               ci.id AS article_id,
               ci.slug AS article_slug,
               {$titleCol} AS article_title
        FROM media m
        LEFT JOIN content_items ci ON m.content_item_id = ci.id
        WHERE (m.content_item_id IS NULL OR ci.status = 'published')
          AND m.file_path IS NOT NULL AND m.file_path != ''
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $images->execute([$perPage, $offset]);
    $images = $images->fetchAll();
} catch (PDOException $e) { $images = []; $totalPages = 1; }

$imageUrls = array_map(function($img) { return upload_url($img['file_path']); }, $images);
?>
<main id="main-content">
    <div class="page-hero">
        <div class="max-w-7xl mx-auto px-4">
            <h1><i class="fa-solid fa-images text-emerald-400" aria-hidden="true"></i><?= __('photo_gallery', $lang) ?></h1>
        </div>
    </div>
    <section class="py-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <?php if (empty($images)): ?>
            <div class="empty-state">
                <div class="empty-state-icon" aria-hidden="true"><i class="fa-solid fa-images"></i></div>
                <p><?= __('no_results', $lang) ?></p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 gallery-grid">
                <?php
                $totalImages = count($images);
                foreach ($images as $idx => $img):
                    $ariaLabel = $img['caption']
                        ? e($img['caption'])
                        : ($lang === 'ar'
                            ? 'فتح الصورة ' . ($idx + 1) . ' من ' . $totalImages
                            : ($lang === 'fr'
                                ? 'Ouvrir l\'image ' . ($idx + 1) . ' sur ' . $totalImages
                                : 'Open image ' . ($idx + 1) . ' of ' . $totalImages));
                ?>
                <div class="glass-card overflow-hidden group cursor-pointer flex flex-col"
                     role="button"
                     tabindex="0"
                     aria-label="<?= $ariaLabel ?>"
                     onclick="openLightbox(<?= $idx ?>, <?= e(json_encode($imageUrls)) ?>, this)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openLightbox(<?= $idx ?>, <?= e(json_encode($imageUrls)) ?>, this);}">
                    <div class="aspect-square overflow-hidden">
                        <img src="<?= e(upload_url($img['file_path'])) ?>"
                             alt="<?= e($img['caption'] ?? '') ?>"
                             loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                    </div>
                    <?php if ($img['caption']): ?>
                    <div class="p-2">
                        <p class="text-xs text-white/70 truncate"><?= e($img['caption']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($img['article_id'] && $img['article_title']): ?>
                    <div class="mt-auto">
                        <a href="page.php?slug=<?= e($img['article_slug']) ?>"
                           class="flex items-center gap-2 w-full px-3 py-2 bg-white/5 backdrop-blur-sm border-t border-white/10 text-xs text-emerald-300/80 hover:text-emerald-200 hover:bg-white/10 transition-all"
                           title="<?= e($img['article_title']) ?>"
                           onclick="event.stopPropagation()">
                            <i class="fa-solid fa-link text-[10px]" aria-hidden="true"></i>
                            <?= $lang === 'ar' ? 'من: ' : ($lang === 'fr' ? 'De: ' : 'From: ') ?><?= e(mb_substr($img['article_title'], 0, 60)) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav aria-label="<?= $lang === 'ar' ? 'تنقل الصفحات' : ($lang === 'fr' ? 'Pagination' : 'Pagination') ?>" class="flex items-center justify-center gap-2 mt-10">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>"
                   class="flex items-center justify-center w-10 h-10 rounded-xl backdrop-blur-md border border-white/15 text-white/80 hover:text-white hover:border-emerald-400/40 hover:bg-emerald-600/20 transition-all duration-300">
                    <i class="fa-solid fa-chevron-left text-sm rtl-flip-arrow" aria-hidden="true"></i>
                </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($startPage > 1): ?>
                <a href="?page=1"
                   class="flex items-center justify-center w-10 h-10 rounded-xl backdrop-blur-md border border-white/10 text-white/60 hover:text-white hover:border-emerald-400/40 hover:bg-emerald-600/20 transition-all duration-300 text-sm">1</a>
                <?php if ($startPage > 2): ?>
                <span class="text-white/30 text-sm px-1">...</span>
                <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="?page=<?= $i ?>"
                   class="flex items-center justify-center w-10 h-10 rounded-xl backdrop-blur-md border text-sm font-medium transition-all duration-300
                   <?= $i === $page
                       ? 'bg-emerald-600/30 border-emerald-500/50 text-emerald-300 shadow-lg shadow-emerald-900/30 scale-110'
                       : 'border-white/10 text-white/70 hover:text-white hover:border-emerald-400/40 hover:bg-emerald-600/20' ?>"
                   <?= $i === $page ? 'aria-current="page"' : '' ?>>
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                <span class="text-white/30 text-sm px-1">...</span>
                <?php endif; ?>
                <a href="?page=<?= $totalPages ?>"
                   class="flex items-center justify-center w-10 h-10 rounded-xl backdrop-blur-md border border-white/10 text-white/60 hover:text-white hover:border-emerald-400/40 hover:bg-emerald-600/20 transition-all duration-300 text-sm"><?= $totalPages ?></a>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>"
                   class="flex items-center justify-center w-10 h-10 rounded-xl backdrop-blur-md border border-white/15 text-white/80 hover:text-white hover:border-emerald-400/40 hover:bg-emerald-600/20 transition-all duration-300">
                    <i class="fa-solid fa-chevron-right text-sm rtl-flip-arrow" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>
