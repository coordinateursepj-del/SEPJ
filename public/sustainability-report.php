<?php
/**
 * Sustainability Report Page - SEPJ Gabès
 * Displays: rapport_rse + catalogue_rse (children of rapport_durabilite)
 */

require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/i18n.php';

session_start_secure();

$lang = current_lang();
$l    = in_array($lang, ['ar','fr','en']) ? $lang : 'ar';

// Fetch both sub-categories
$grouped = ['rapport_rse' => [], 'catalogue_rse' => []];

try {
    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT id, slug, rse_category,
               COALESCE(NULLIF(title_{$l},''), title_ar) AS t,
               COALESCE(NULLIF(summary_{$l},''), summary_ar) AS s,
               featured_image,
               published_at
        FROM content_items
        WHERE type = 'rse'
          AND status = 'published'
          AND rse_category IN ('rapport_rse', 'catalogue_rse', 'rapport_durabilite')
        ORDER BY published_at DESC, created_at DESC
    ");
    $stmt->execute();

    foreach ($stmt->fetchAll() as $item) {
        $cat = $item['rse_category'];
        // rapport_durabilite items without a sub-type go to rapport_rse by default
        $key = ($cat === 'catalogue_rse') ? 'catalogue_rse' : 'rapport_rse';
        $grouped[$key][] = $item;
    }

} catch (Exception $e) {
    error_log("Sustainability report query error: " . $e->getMessage());
}

// Labels
$pageTitle   = $l === 'ar' ? 'تقرير الاستدامة'
             : ($l === 'fr' ? 'Rapport de durabilité'
             : 'Sustainability Report');

$pageSubtitle = $l === 'ar' ? 'تقارير وكتالوجات المسؤولية الاجتماعية للشركة'
              : ($l === 'fr' ? 'Rapports et catalogues de responsabilité sociétale'
              : 'Corporate social responsibility reports and catalogues');

$sections = [
    'rapport_rse' => [
        'icon'     => '📄',
        'title'    => $l === 'ar' ? 'تقرير المسؤولية المجتمعية'
                    : ($l === 'fr' ? 'Rapport RSE' : 'CSR Report'),
        'subtitle' => $l === 'ar' ? 'تقارير المسؤولية الاجتماعية للشركة'
                    : ($l === 'fr' ? 'Rapports de responsabilité sociale des entreprises'
                    : 'Corporate social responsibility reports'),
    ],
    'catalogue_rse' => [
        'icon'     => '📚',
        'title'    => $l === 'ar' ? 'كتالوج المسؤولية المجتمعية'
                    : ($l === 'fr' ? 'Catalogue RSE' : 'CSR Catalogue'),
        'subtitle' => $l === 'ar' ? 'كتالوجات وأدلة المسؤولية المجتمعية'
                    : ($l === 'fr' ? 'Catalogues et guides de responsabilité sociétale'
                    : 'CSR catalogues and guides'),
    ],
];

$noResults = $l === 'ar' ? 'لا توجد نتائج بعد' : ($l === 'fr' ? 'Aucun résultat pour le moment' : 'No results yet');
$itemWord  = $l === 'ar' ? 'عنصر' : ($l === 'fr' ? 'élément(s)' : 'item(s)');
$backLabel = $l === 'ar' ? 'العودة إلى RSE' : ($l === 'fr' ? 'Retour à la RSE' : 'Back to CSR');

require_once 'includes/header.php';
?>

<main id="main-content">

    <!-- Hero -->
    <div class="page-hero">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <p class="sr-report-eyebrow">
                <a href="rse.php" class="sr-back-link">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <?= e($backLabel) ?>
                </a>
            </p>
            <h1 class="mt-2"><span aria-hidden="true">📊</span> <?= e($pageTitle) ?></h1>
            <p class="text-emerald-200/70 mt-3 text-lg"><?= e($pageSubtitle) ?></p>
        </div>
    </div>

    <!-- Sections -->
    <section class="py-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4 space-y-10">

            <?php foreach ($sections as $catKey => $meta):
                $items = $grouped[$catKey];
                $count = count($items);
            ?>
            <div class="sr-section" id="sr-<?= $catKey ?>">

                <!-- Section header -->
                <div class="sr-section-header">
                    <div class="sr-section-icon" aria-hidden="true"><?= $meta['icon'] ?></div>
                    <div>
                        <h2 class="sr-section-title"><?= e($meta['title']) ?></h2>
                        <p class="sr-section-subtitle">
                            <?= e($meta['subtitle']) ?>
                            <span class="sr-count"><?= $count ?> <?= $itemWord ?></span>
                        </p>
                    </div>
                </div>

                <!-- Items grid -->
                <?php if (empty($items)): ?>
                <div class="sr-empty">
                    <div class="sr-empty-icon" aria-hidden="true"><?= $meta['icon'] ?></div>
                    <p><?= $noResults ?></p>
                </div>

                <?php else: ?>
                <div class="sr-grid">
                    <?php foreach ($items as $i): ?>
                    <a href="page.php?slug=<?= e($i['slug']) ?>" class="sr-card">
                        <!-- Thumbnail -->
                        <?php if (!empty($i['featured_image'])): ?>
                        <div class="sr-card-thumb">
                            <img src="<?= e(upload_url($i['featured_image'])) ?>"
                                 alt=""
                                 loading="lazy">
                        </div>
                        <?php else: ?>
                        <div class="sr-card-thumb sr-card-thumb-placeholder" aria-hidden="true">
                            <?= $meta['icon'] ?>
                        </div>
                        <?php endif; ?>

                        <!-- Body -->
                        <div class="sr-card-body">
                            <h3 class="sr-card-title"><?= e($i['t']) ?></h3>
                            <?php if (!empty($i['s'])): ?>
                            <p class="sr-card-excerpt"><?= e(excerpt($i['s'], 150)) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($i['published_at'])): ?>
                            <time class="sr-card-date" datetime="<?= e($i['published_at']) ?>">
                                <?= format_date($i['published_at'], 'd/m/Y') ?>
                            </time>
                            <?php endif; ?>
                        </div>

                        <!-- Arrow -->
                        <div class="sr-card-arrow" aria-hidden="true">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>

        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
