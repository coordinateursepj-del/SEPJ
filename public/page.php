<?php
/**
 * Public Single Content Page - SEPJ Gabès
 *
 * Data is fetched BEFORE the header is included so http_response_code(404)
 * can be set before any output is sent.
 */

// Bootstrap without outputting anything yet
require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/i18n.php';

session_start_secure();

$lang          = current_lang();
$slug          = trim($_GET['slug'] ?? '');
$item          = null;
$galleryImages = [];
$imageArray    = [];
$is404         = false;

if (!$slug) {
    $is404 = true;
} else {
    try {
        $stmt = db()->prepare("SELECT * FROM content_items WHERE slug = :slug AND status = 'published' LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $item = $stmt->fetch();
    } catch (PDOException $e) {
        $item = null;
    }

    if (!$item) {
        $is404 = true;
    } else {
        $title   = content_field($item, 'title', $lang);
        $summary = content_field($item, 'summary', $lang);
        $body    = content_field($item, 'body', $lang);

        try {
            $mediaStmt = db()->prepare("SELECT * FROM media WHERE content_item_id = :id ORDER BY sort_order ASC");
            $mediaStmt->execute(['id' => $item['id']]);
            $galleryImages = $mediaStmt->fetchAll();
        } catch (PDOException $e) {
            $galleryImages = [];
        }

        $imageArray = array_map(function ($m) { return upload_url($m['file_path']); }, $galleryImages);
    }
}

// Set 404 status BEFORE any output
if ($is404) {
    http_response_code(404);
}

// Now include the header (outputs DOCTYPE, head, body, nav)
require_once 'includes/header.php';
?>

<main id="main-content">
<?php if ($is404): ?>
    <div class="page-hero">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-6xl font-bold text-white mb-4">404</h1>
            <p class="text-xl text-emerald-200/80 mb-8"><?= __('page_not_found', $lang) ?></p>
            <a href="index.php" class="glass-btn glass-btn-primary"><?= __('back_to_home', $lang) ?></a>
        </div>
    </div>
<?php else: ?>
    <div class="page-hero">
        <div class="max-w-4xl mx-auto px-4">
            <?php if (($item['type'] ?? '') !== 'page'): ?>
            <div class="breadcrumbs justify-center mb-4">
                <a href="index.php"><?= __('nav_home', $lang) ?></a>
                <span class="separator">/</span>
                <span><?= e(mb_substr($title, 0, 50)) ?></span>
            </div>
            <?php endif; ?>
            <h1><?= e($title) ?></h1>
            <?php if (!empty($item['published_at'])): ?>
            <p class="text-emerald-300/60 text-sm mt-2"><?= __('published_on', $lang) ?> <?= format_date($item['published_at'], 'd/m/Y') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <section class="py-8 relative z-10">
        <div class="max-w-4xl mx-auto px-4">
            <?php if (!empty($item['featured_image'])): ?>
            <div class="mb-8 rounded-xl overflow-hidden">
                <img src="<?= e(upload_url($item['featured_image'])) ?>" alt="<?= e($title) ?>" class="w-full max-h-[500px] object-cover">
            </div>
            <?php endif; ?>

            <?php if ($summary): ?>
            <div class="glass-card-static p-6 mb-8">
                <p class="text-lg text-emerald-200/90 leading-relaxed"><?= e($summary) ?></p>
            </div>
            <?php endif; ?>

            <div class="prose prose-invert max-w-none text-emerald-200/80 leading-relaxed">
                <?= sanitize_body($body) ?>
            </div>

            <?php if (!empty($galleryImages)): ?>
            <div class="mt-12">
                <h2 class="section-title"><?= __('photo_gallery', $lang) ?></h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <?php foreach ($galleryImages as $idx => $img): ?>
                    <div class="img-card cursor-pointer"
                         role="button"
                         tabindex="0"
                         aria-label="<?= e(content_field($img, 'alt', $lang) ?: $title) ?>"
                         onclick="openLightbox(<?= $idx ?>, <?= e(json_encode($imageArray)) ?>, this)"
                         onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openLightbox(<?= $idx ?>, <?= e(json_encode($imageArray)) ?>, this);}">
                        <img src="<?= e(upload_url($img['file_path'])) ?>"
                             alt="<?= e(content_field($img, 'alt', $lang) ?: $title) ?>"
                             loading="lazy">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
