<?php
require_once 'includes/header.php';
$lang = current_lang();

try {
    $captionCols = ['ar' => 'm.caption_ar', 'fr' => 'm.caption_fr', 'en' => 'm.caption_en'];
    $captionCol = $captionCols[$lang] ?? 'm.caption_ar';
    // Use LEFT JOIN instead of a correlated subquery — faster with an index on content_items.status
    $images = db()->query("
        SELECT m.*, COALESCE(NULLIF({$captionCol}, ''), m.caption_ar) AS caption
        FROM media m
        LEFT JOIN content_items ci ON m.content_item_id = ci.id
        WHERE m.content_item_id IS NULL OR ci.status = 'published'
        ORDER BY m.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) { $images = []; }

$imageUrls = array_map(function($img) { return upload_url($img['file_path']); }, $images);
?>
<main id="main-content">
    <div class="page-hero"><div class="max-w-7xl mx-auto px-4"><h1><i class="fa-solid fa-images text-emerald-400" aria-hidden="true"></i><?= __('photo_gallery', $lang) ?></h1></div></div>
    <section class="py-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <?php if (empty($images)): ?><div class="empty-state"><div class="empty-state-icon" aria-hidden="true"><i class="fa-solid fa-images"></i></div><p><?= __('no_results', $lang) ?></p></div>
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
                <div class="glass-card overflow-hidden group cursor-pointer"
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
                    <?php if ($img['caption']): ?><div class="p-2"><p class="text-xs text-white/70 truncate"><?= e($img['caption']) ?></p></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>