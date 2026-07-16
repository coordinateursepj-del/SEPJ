<?php
/**
 * About + Director's Message - SEPJ Gabès
 * Merged page: company overview + director's message
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

$about    = null;
$director = null;

try {
    $pdo = db();

    $stmt = $pdo->prepare("SELECT * FROM content_items WHERE slug = :slug AND status = 'published' LIMIT 1");

    $stmt->execute(['slug' => 'about-company']);
    $about = $stmt->fetch() ?: null;
    $stmt->closeCursor();

    $stmt->execute(['slug' => 'director-message']);
    $director = $stmt->fetch() ?: null;

} catch (PDOException $e) {}

require_once 'includes/header.php';
?>

<main id="main-content">
    <div class="page-hero">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1><i class="fa-solid fa-building-columns text-emerald-400" aria-hidden="true"></i><?= $lang === 'ar' ? 'تقديم الشركة' : ($lang === 'fr' ? 'À propos' : 'About us') ?></h1>
        </div>
    </div>

    <section class="py-8 relative z-10">
        <div class="max-w-4xl mx-auto px-4">

            <?php if ($about): ?>
                <?php
                    $aboutTitle   = content_field($about, 'title',   $lang);
                    $aboutSummary = content_field($about, 'summary', $lang);
                    $aboutBody    = content_field($about, 'body',    $lang);
                ?>

                <?php if (!empty($about['featured_image'])): ?>
                <div class="mb-8 rounded-xl overflow-hidden">
                    <img src="<?= e(upload_url($about['featured_image'])) ?>"
                         alt="<?= e($aboutTitle) ?>"
                         class="w-full max-h-[500px] object-cover">
                </div>
                <?php endif; ?>

                <?php if ($aboutSummary): ?>
                <div class="glass-card-static p-6 mb-8">
                    <p class="text-lg text-emerald-200/90 leading-relaxed"><?= e($aboutSummary) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($aboutBody): ?>
                <div class="prose prose-invert max-w-none text-emerald-200/80 leading-relaxed mb-12">
                    <?= sanitize_body($aboutBody) ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($director): ?>
                <?php
                    $dirTitle   = content_field($director, 'title',   $lang);
                    $dirSummary = content_field($director, 'summary', $lang);
                    $dirBody    = content_field($director, 'body',    $lang);
                    $dirName    = get_setting('director_name', $lang) ?: ($lang === 'ar' ? 'عبد السلام بسيسة' : 'Abdel Salam Besisse');
                    $dirRole    = $lang === 'ar' ? 'المدير العام' : ($lang === 'fr' ? 'Directeur Général' : 'General Director');
                    $eyebrow    = $lang === 'ar' ? 'قيادتنا' : ($lang === 'fr' ? 'DIRECTION' : 'LEADERSHIP');
                ?>

                <div class="dg-section">

                    <!-- Section header -->
                    <div class="dg-header" <?= $lang === 'ar' ? 'dir="rtl"' : '' ?>>
                        <span class="dg-eyebrow"><?= e($eyebrow) ?></span>
                        <h2 class="dg-title"><?= e($dirTitle) ?></h2>
                    </div>

                    <!-- Premium card — always LTR layout: text left, photo right -->
                    <div class="dg-card" dir="ltr">

                        <!-- Message column -->
                        <div class="dg-message-col" <?= $lang === 'ar' ? 'dir="rtl"' : '' ?>>
                            <span class="dg-quote-mark" aria-hidden="true">❝</span>

                            <?php if ($dirSummary): ?>
                            <p class="dg-summary"><?= e($dirSummary) ?></p>
                            <?php endif; ?>

                            <?php if ($dirBody): ?>
                            <div class="dg-body">
                                <?= sanitize_body($dirBody) ?>
                            </div>
                            <?php endif; ?>

                            <!-- Signature -->
                            <div class="dg-signature">
                                <span class="dg-sig-name"><?= e($dirName) ?></span>
                                <span class="dg-sig-role"><?= e($dirRole) ?></span>
                            </div>
                        </div>

                        <!-- Photo column -->
                        <div class="dg-photo-col">
                            <div class="dg-photo-ring">
                                <img src="assets/DG.png" alt="<?= e($dirName) ?>">
                            </div>
                            <div class="dg-identity">
                                <strong class="dg-identity-name"><?= e($dirName) ?></strong>
                                <span class="dg-identity-role"><?= e($dirRole) ?></span>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
