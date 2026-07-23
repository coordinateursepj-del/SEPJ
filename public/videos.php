<?php
require_once 'includes/header.php';
$lang = current_lang();
$l = ['ar' => 'ar', 'fr' => 'fr', 'en' => 'en'][$lang] ?? 'ar';

$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

try {
    $total = (int)db()->query("
        SELECT COUNT(*) FROM content_items
        WHERE (type='video' OR (type!='video' AND video_url IS NOT NULL AND video_url != ''))
        AND status='published'
    ")->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));

    $items = db()->prepare("
        SELECT id, slug, type,
               COALESCE(NULLIF(title_{$l}, ''), title_ar) AS t,
               COALESCE(NULLIF(body_{$l}, ''), body_ar) AS b,
               featured_image, video_url, video_thumb
        FROM content_items
        WHERE (type='video' OR (type!='video' AND video_url IS NOT NULL AND video_url != ''))
        AND status='published'
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $items->execute([$perPage, $offset]);
    $items = $items->fetchAll();
} catch (Exception $e) { $items = []; $totalPages = 1; }
?>
<main id="main-content">
    <div class="page-hero">
        <div class="max-w-7xl mx-auto px-4">
            <h1><i class="fa-solid fa-video text-emerald-400" aria-hidden="true"></i><?= __('nav_videos', $lang) ?></h1>
        </div>
    </div>
    <section class="py-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <?php if (empty($items)): ?>
            <div class="empty-state">
                <div class="empty-state-icon" aria-hidden="true"><i class="fa-solid fa-video"></i></div>
                <p><?= __('no_results', $lang) ?></p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($items as $i):
                    $embed = youtube_embed_url($i['video_url'] ?? '') ?? youtube_embed_url($i['b'] ?? '');
                    $thumb = youtube_thumbnail_url($i['video_url'] ?? '', !empty($i['video_thumb']) ? upload_url($i['video_thumb']) : (!empty($i['featured_image']) ? upload_url($i['featured_image']) : null));
                ?>
                <div class="glass-card overflow-hidden flex flex-col">
                    <?php if ($embed): ?>
                    <div class="video-thumb rounded-none overflow-hidden" data-embed="<?= e($embed) ?>">
                        <?php if ($thumb): ?>
                        <img src="<?= e($thumb) ?>" alt="<?= e($i['t']) ?>" class="w-full h-full object-cover" loading="lazy">
                        <?php else: ?>
                        <div class="w-full h-full bg-emerald-900/30 flex items-center justify-center" aria-hidden="true"><i class="fa-solid fa-video text-emerald-400 text-3xl"></i></div>
                        <?php endif; ?>
                        <span class="yt-play" aria-hidden="true"><svg width="68" height="48" viewBox="0 0 68 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M66.5 7.7c-.8-2.9-2.5-5.4-5.4-6.2C55.8.1 34 0 34 0S12.2.1 6.9 1.5C4 2.3 2.3 4.8 1.5 7.7.1 13 0 24 0 24s.1 11 1.5 16.3c.8 2.9 2.5 5.4 5.4 6.2C12.2 47.9 34 48 34 48s21.8-.1 27.1-1.5c2.9-.8 4.6-3.3 5.4-6.2C67.9 35 68 24 68 24s-.1-11-1.5-16.3z" fill="#FF0000"/><path d="M45 24 27 14v20z" fill="#FFFFFF"/></svg></span>
                        <span class="sr-only"><?= __('watch_video', $lang) ?></span>
                    </div>
                    <?php else: ?>
                    <div class="aspect-video bg-emerald-900/30 flex items-center justify-center" aria-hidden="true"><i class="fa-solid fa-video text-emerald-400 text-3xl"></i></div>
                    <?php endif; ?>
                    <div class="px-4 pb-4 pt-3">
                        <h3 class="font-semibold text-white"><?= e($i['t']) ?></h3>
                    </div>
                    <div class="mt-auto">
                        <a href="page.php?slug=<?= e($i['slug']) ?>"
                           class="flex items-center gap-2 w-full px-4 py-2.5 bg-white/5 backdrop-blur-sm border-t border-white/10 text-xs text-emerald-300/80 hover:text-emerald-200 hover:bg-white/10 transition-all">
                            <i class="fa-solid fa-link text-[10px]" aria-hidden="true"></i>
                            <?= $lang === 'ar' ? 'من: ' : ($lang === 'fr' ? 'De: ' : 'From: ') ?><?= e(mb_substr($i['t'], 0, 60)) ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav aria-label="<?= $lang === 'ar' ? 'تنقل الصفحات' : ($lang === 'fr' ? 'Pagination' : 'Pagination') ?>" class="flex items-center justify-center gap-2 mt-10">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>"
                   class="flex items-center justify-center w-10 h-10 rounded-xl backdrop-blur-md border border-white/15 text-white/80 hover:text-white hover:border-emerald-400/40 hover:bg-emerald-600/20 transition-all duration-300">
                    <i class="fa-solid fa-chevron-left text-sm" aria-hidden="true"></i>
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
                    <i class="fa-solid fa-chevron-right text-sm" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>
