<?php
require_once 'includes/header.php';
$lang = current_lang();
$l = ['ar' => 'ar', 'fr' => 'fr', 'en' => 'en'][$lang] ?? 'ar';

// Fetch all published RSE items
try {
    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT id, slug, rse_category,
               COALESCE(NULLIF(title_{$l},''), title_ar) AS t,
               COALESCE(NULLIF(summary_{$l},''), summary_ar) AS s,
               featured_image
        FROM content_items
        WHERE type='rse' AND status='published'
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $allItems = $stmt->fetchAll();

    $grouped = [
        'engagement_social'       => [],
        'engagement_environmental'=> [],
        'rapport_rse'             => [],
        'catalogue_rse'           => [],
        'rapport_durabilite'      => [],
    ];

    foreach ($allItems as $item) {
        $cat = $item['rse_category'] ?? 'engagement_social';
        if (isset($grouped[$cat])) {
            $grouped[$cat][] = $item;
        } else {
            $grouped['engagement_social'][] = $item;
        }
    }

} catch (Exception $e) {
    error_log("RSE query error: " . $e->getMessage());
    $grouped = [
        'engagement_social'       => [],
        'engagement_environmental'=> [],
        'rapport_rse'             => [],
        'catalogue_rse'           => [],
        'rapport_durabilite'      => [],
    ];
}

$pageTitle = $l === 'ar' ? 'التزامنا المجتمعي والبيئي'
           : ($l === 'fr' ? 'Notre engagement sociétal et environnemental'
           : 'Our Societal and Environmental Commitment');

$noResults = $l === 'ar' ? 'لا توجد نتائج' : ($l === 'fr' ? 'Aucun résultat' : 'No results');
$itemWord  = $l === 'ar' ? 'عنصر' : ($l === 'fr' ? 'élément(s)' : 'item(s)');
?>
<main id="main-content">

    <!-- Hero -->
    <div class="page-hero">
        <div class="max-w-4xl mx-auto px-4">
            <h1><span aria-hidden="true">🌱</span> <?= e($pageTitle) ?></h1>
            <p class="text-emerald-200/70 mt-2 text-lg">
                <?= $l === 'ar' ? 'نعمل من أجل بيئة أفضل ومستقبل مستدام'
                  : ($l === 'fr' ? 'Nous agissons pour un meilleur environnement et un avenir durable'
                  : 'We act for a better environment and a sustainable future') ?>
            </p>
        </div>
    </div>

    <section class="py-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4 space-y-8">

            <!-- ══════════════════════════════════════════ -->
            <!-- SECTION 1 — Notre engagement social        -->
            <!-- ══════════════════════════════════════════ -->
            <?php
            $items    = $grouped['engagement_social'];
            $count    = count($items);
            $title    = $l === 'ar' ? 'التزامنا الاجتماعي'
                      : ($l === 'fr' ? 'Notre engagement social'
                      : 'Our Social Engagement');
            $subtitle = $l === 'ar' ? 'مبادراتنا وبرامجنا الاجتماعية'
                      : ($l === 'fr' ? 'Nos initiatives et programmes sociaux'
                      : 'Our social initiatives and programs');
            ?>
            <div class="glass-card-static p-6" id="rse-engagement-social">
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-3xl" aria-hidden="true">🤝</span>
                    <div>
                        <h2 class="text-xl font-bold text-white"><?= e($title) ?></h2>
                        <p class="text-sm text-emerald-200/50"><?= e($subtitle) ?> — <strong><?= $count ?></strong> <?= $itemWord ?></p>
                    </div>
                </div>
                <?php if (empty($items)): ?>
                    <div class="empty-state py-8"><p><?= $noResults ?></p></div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($items as $i): ?>
                        <a href="page.php?slug=<?= e($i['slug']) ?>" class="glass-card p-5 flex gap-4 items-start hover:bg-white/5 transition-all">
                            <?php if (!empty($i['featured_image'])): ?>
                            <img src="<?= e(upload_url($i['featured_image'])) ?>" alt="" class="w-16 h-16 object-cover rounded-lg shrink-0" loading="lazy">
                            <?php else: ?>
                            <span class="text-2xl shrink-0 mt-1" aria-hidden="true">🤝</span>
                            <?php endif; ?>
                            <div>
                                <h3 class="font-semibold text-white mb-2"><?= e($i['t']) ?></h3>
                                <?php if (!empty($i['s'])): ?><p class="text-sm text-emerald-200/70"><?= e(excerpt($i['s'], 200)) ?></p><?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ══════════════════════════════════════════════════════════ -->
            <!-- SECTION 2 — Notre engagement sociétale et environnemental  -->
            <!-- ══════════════════════════════════════════════════════════ -->
            <?php
            $items    = $grouped['engagement_environmental'];
            $count    = count($items);
            $title    = $l === 'ar' ? 'التزامنا المجتمعي والبيئي'
                      : ($l === 'fr' ? 'Notre engagement sociétale et environnemental'
                      : 'Our Societal and Environmental Engagement');
            $subtitle = $l === 'ar' ? 'مبادراتنا البيئية والمجتمعية'
                      : ($l === 'fr' ? 'Nos initiatives environnementales et sociétales'
                      : 'Our environmental and societal initiatives');
            ?>
            <div class="glass-card-static p-6" id="rse-engagement-environmental">
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-3xl" aria-hidden="true">🌿</span>
                    <div>
                        <h2 class="text-xl font-bold text-white"><?= e($title) ?></h2>
                        <p class="text-sm text-emerald-200/50"><?= e($subtitle) ?> — <strong><?= $count ?></strong> <?= $itemWord ?></p>
                    </div>
                </div>
                <?php if (empty($items)): ?>
                    <div class="empty-state py-8"><p><?= $noResults ?></p></div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($items as $i): ?>
                        <a href="page.php?slug=<?= e($i['slug']) ?>" class="glass-card p-5 flex gap-4 items-start hover:bg-white/5 transition-all">
                            <?php if (!empty($i['featured_image'])): ?>
                            <img src="<?= e(upload_url($i['featured_image'])) ?>" alt="" class="w-16 h-16 object-cover rounded-lg shrink-0" loading="lazy">
                            <?php else: ?>
                            <span class="text-2xl shrink-0 mt-1" aria-hidden="true">🌿</span>
                            <?php endif; ?>
                            <div>
                                <h3 class="font-semibold text-white mb-2"><?= e($i['t']) ?></h3>
                                <?php if (!empty($i['s'])): ?><p class="text-sm text-emerald-200/70"><?= e(excerpt($i['s'], 200)) ?></p><?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ══════════════════════════════════════════════════════ -->
            <!-- SECTION 3 — Rapport de durabilité                      -->
            <!--              (contains: CSR Report + CSR Catalogue)    -->
            <!-- ══════════════════════════════════════════════════════ -->
            <?php
            $totalDurabilite = count($grouped['rapport_durabilite'])
                             + count($grouped['rapport_rse'])
                             + count($grouped['catalogue_rse']);
            $durTitle    = $l === 'ar' ? 'تقرير الاستدامة'
                         : ($l === 'fr' ? 'Rapport de durabilité'
                         : 'Sustainability Report');
            $durSubtitle = $l === 'ar' ? 'تقارير وكتالوجات المسؤولية المجتمعية'
                         : ($l === 'fr' ? 'Rapports et catalogues RSE'
                         : 'CSR reports and catalogues');
            ?>
            <div class="glass-card-static p-6" id="rse-rapport-durabilite">
                <div class="flex items-center justify-between gap-3 mb-6">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl" aria-hidden="true">📊</span>
                        <div>
                            <h2 class="text-xl font-bold text-white"><?= e($durTitle) ?></h2>
                            <p class="text-sm text-emerald-200/50"><?= e($durSubtitle) ?> — <strong><?= $totalDurabilite ?></strong> <?= $itemWord ?></p>
                        </div>
                    </div>
                    <a href="sustainability-report.php" class="glass-btn text-sm shrink-0">
                        <?= $l === 'ar' ? 'عرض الكل' : ($l === 'fr' ? 'Voir tout' : 'View all') ?>
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <?php if ($totalDurabilite === 0): ?>
                    <div class="empty-state py-8"><p><?= $noResults ?></p></div>
                <?php else: ?>

                    <!-- Sub-section: CSR Report -->
                    <?php if (!empty($grouped['rapport_rse'])): ?>
                    <div class="mb-6">
                        <h3 class="text-base font-semibold text-emerald-300 mb-3 flex items-center gap-2">
                            <span aria-hidden="true">📄</span>
                            <?= $l === 'ar' ? 'تقرير المسؤولية المجتمعية'
                              : ($l === 'fr' ? 'Rapport RSE'
                              : 'CSR Report') ?>
                            <span class="text-xs text-white/40 font-normal">(<?= count($grouped['rapport_rse']) ?>)</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($grouped['rapport_rse'] as $i): ?>
                            <a href="page.php?slug=<?= e($i['slug']) ?>" class="glass-card p-5 flex gap-4 items-start hover:bg-white/5 transition-all">
                                <?php if (!empty($i['featured_image'])): ?>
                                <img src="<?= e(upload_url($i['featured_image'])) ?>" alt="" class="w-16 h-16 object-cover rounded-lg shrink-0" loading="lazy">
                                <?php else: ?>
                                <span class="text-2xl shrink-0 mt-1" aria-hidden="true">📄</span>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-semibold text-white mb-2"><?= e($i['t']) ?></h4>
                                    <?php if (!empty($i['s'])): ?><p class="text-sm text-emerald-200/70"><?= e(excerpt($i['s'], 200)) ?></p><?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Sub-section: CSR Catalogue -->
                    <?php if (!empty($grouped['catalogue_rse'])): ?>
                    <div class="mb-6">
                        <h3 class="text-base font-semibold text-emerald-300 mb-3 flex items-center gap-2">
                            <span aria-hidden="true">📚</span>
                            <?= $l === 'ar' ? 'كتالوج المسؤولية المجتمعية'
                              : ($l === 'fr' ? 'Catalogue RSE'
                              : 'CSR Catalogue') ?>
                            <span class="text-xs text-white/40 font-normal">(<?= count($grouped['catalogue_rse']) ?>)</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($grouped['catalogue_rse'] as $i): ?>
                            <a href="page.php?slug=<?= e($i['slug']) ?>" class="glass-card p-5 flex gap-4 items-start hover:bg-white/5 transition-all">
                                <?php if (!empty($i['featured_image'])): ?>
                                <img src="<?= e(upload_url($i['featured_image'])) ?>" alt="" class="w-16 h-16 object-cover rounded-lg shrink-0" loading="lazy">
                                <?php else: ?>
                                <span class="text-2xl shrink-0 mt-1" aria-hidden="true">📚</span>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-semibold text-white mb-2"><?= e($i['t']) ?></h4>
                                    <?php if (!empty($i['s'])): ?><p class="text-sm text-emerald-200/70"><?= e(excerpt($i['s'], 200)) ?></p><?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Sub-section: Durability Report (standalone) -->
                    <?php if (!empty($grouped['rapport_durabilite'])): ?>
                    <div>
                        <h3 class="text-base font-semibold text-emerald-300 mb-3 flex items-center gap-2">
                            <span aria-hidden="true">📊</span>
                            <?= e($durTitle) ?>
                            <span class="text-xs text-white/40 font-normal">(<?= count($grouped['rapport_durabilite']) ?>)</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($grouped['rapport_durabilite'] as $i): ?>
                            <a href="page.php?slug=<?= e($i['slug']) ?>" class="glass-card p-5 flex gap-4 items-start hover:bg-white/5 transition-all">
                                <?php if (!empty($i['featured_image'])): ?>
                                <img src="<?= e(upload_url($i['featured_image'])) ?>" alt="" class="w-16 h-16 object-cover rounded-lg shrink-0" loading="lazy">
                                <?php else: ?>
                                <span class="text-2xl shrink-0 mt-1" aria-hidden="true">📊</span>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-semibold text-white mb-2"><?= e($i['t']) ?></h4>
                                    <?php if (!empty($i['s'])): ?><p class="text-sm text-emerald-200/70"><?= e(excerpt($i['s'], 200)) ?></p><?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
