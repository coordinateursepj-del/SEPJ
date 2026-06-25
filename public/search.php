<?php
require_once 'includes/header.php';
$lang = current_lang();
$l = ['ar' => 'ar', 'fr' => 'fr', 'en' => 'en'][$lang] ?? 'ar';
$q = trim($_GET['q'] ?? '');
$results = [];

if ($q) {
    try {
        $stmt = db()->prepare("
            SELECT id, type, slug,
                   COALESCE(NULLIF(title_{$l}, ''), title_ar)   AS title,
                   COALESCE(NULLIF(summary_{$l}, ''), summary_ar) AS summary,
                   featured_image, published_at
            FROM   content_items
            WHERE  status = 'published'
              AND  (
                       title_ar   LIKE :q1 OR title_fr   LIKE :q2 OR title_en   LIKE :q3
                    OR summary_ar LIKE :q4 OR summary_fr LIKE :q5 OR summary_en LIKE :q6
                    OR body_ar    LIKE :q7 OR body_fr    LIKE :q8 OR body_en    LIKE :q9
                   )
            ORDER  BY published_at DESC
            LIMIT  30
        ");
        $qp = "%{$q}%";
        $stmt->execute([
            'q1' => $qp, 'q2' => $qp, 'q3' => $qp,
            'q4' => $qp, 'q5' => $qp, 'q6' => $qp,
            'q7' => $qp, 'q8' => $qp, 'q9' => $qp,
        ]);
        $results = $stmt->fetchAll();
    } catch (PDOException $e) { $results = []; }
}
?>
<main id="main-content">
    <div class="page-hero"><div class="max-w-4xl mx-auto px-4">
        <h1><?= __('search', $lang) ?></h1>
        <form method="GET" role="search" class="mt-6 max-w-xl mx-auto">
            <div class="flex gap-2">
                <label for="searchInput" class="sr-only"><?= __('search', $lang) ?></label>
                <input type="search" id="searchInput" name="q" value="<?= e($q) ?>"
                       class="form-input flex-1"
                       placeholder="<?= __('search', $lang) ?>..."
                       autocomplete="off"
                       aria-label="<?= __('search', $lang) ?>">
                <button type="submit" class="glass-btn"><span aria-hidden="true">🔍</span> <?= __('search', $lang) ?></button>
            </div>
        </form>
    </div></div>
    <section class="py-8 relative z-10">
        <div class="max-w-4xl mx-auto px-4">
            <?php if ($q): ?>
            <p class="text-emerald-300/60 mb-6" aria-live="polite"><?= count($results) ?> <?= $lang==='ar'?'نتيجة':($lang==='fr'?'résultat(s)':'result(s)') ?> "<?= e($q) ?>"</p>
            <?php if (empty($results)): ?><div class="empty-state"><div class="empty-state-icon">🔍</div><p><?= __('no_results', $lang) ?></p></div>
            <?php else: ?>
            <div class="space-y-4"><?php foreach ($results as $r): ?>
                <a href="page.php?slug=<?= e($r['slug']) ?>" class="glass-card p-4 flex items-center gap-4 hover:translate-x-2 transition-transform">
                    <?php if ($r['featured_image']): ?><img src="<?= e(upload_url($r['featured_image'])) ?>" alt="<?= e($r['title']) ?>" loading="lazy" class="w-16 h-16 rounded-lg object-cover shrink-0"><?php endif; ?>
                    <div><h3 class="text-white font-medium"><?= e($r['title']) ?></h3><p class="text-xs text-emerald-300/60 mt-1"><?= e(excerpt($r['summary'] ?? '', 100)) ?></p></div>
                </a>
            <?php endforeach; ?></div>
            <?php endif; endif; ?>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>