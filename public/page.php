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
        echo "<!-- DBG-A video_url=[" . ($item['video_url'] ?? 'NULL') . "] -->\n";
    } catch (PDOException $e) {
        $item = null;
    }

    if (!$item) {
        $is404 = true;
    } else {
        $title   = content_field($item, 'title', $lang);
        $summary = content_field($item, 'summary', $lang);
        $body    = content_field($item, 'body', $lang);
        echo "<!-- DBG-B video_url=[" . ($item['video_url'] ?? 'NULL') . "] -->\n";

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

            <?php
            // Use the same resolution order as the (working) Videos page:
            // explicit video_url first, then fall back to a YouTube link
            // embedded anywhere in the article body.
            ?>
            <!-- DEPLOYED:videoblock-v3 -->
            <?php
            $pageVideoEmbed = youtube_embed_url($item['video_url'] ?? '') ?? youtube_embed_url($body ?? '');
            // DIAGNOSTIC: render unconditionally to confirm output path
            $pageVideoThumb = youtube_thumbnail_url(
                $item['video_url'] ?? '',
                !empty($item['video_thumb'])
                    ? upload_url($item['video_thumb'])
                    : (!empty($item['featured_image']) ? upload_url($item['featured_image']) : null)
            );
            ?>
            <div class="mt-10">
                <h2 class="section-title"><?= __('video_section_label', $lang) ?></h2>
                <div class="video-thumb rounded-xl overflow-hidden" data-embed="<?= e($pageVideoEmbed) ?>" data-dbgvurl="<?= e($item['video_url'] ?? 'NULL') ?>" data-dbglang="<?= e($lang) ?>">
                    <?php if ($pageVideoThumb): ?><img src="<?= e($pageVideoThumb) ?>" alt="<?= e($title) ?>" class="w-full h-full object-cover" loading="lazy">
                    <?php else: ?><div class="w-full h-full bg-emerald-900/30 flex items-center justify-center" aria-hidden="true"><i class="fa-solid fa-video text-emerald-400 text-3xl"></i></div><?php endif; ?>
                    <span class="yt-play" aria-hidden="true"><svg viewBox="0 0 68 48"><path d="M66.5 7.7c-.8-2.9-2.5-5.4-5.4-6.2C55.8.1 34 0 34 0S12.2.1 6.9 1.5C4 2.3 2.3 4.8 1.5 7.7.1 13 0 24 0 24s.1 11 1.5 16.3c.8-2.9 2.5-5.4-5.4-6.2C12.2 47.9 34 48 34 48s21.8-.1 27.1-1.5c2.9-.8 4.6-3.3 5.4-6.2C67.9 35 68 24 68 24s-.1-11-1.5-16.3z" fill="#f00"/><path d="M45 24 27 14v20z" fill="#fff"/></svg></span>
                    <span class="sr-only"><?= __('watch_video', $lang) ?></span>
                </div>
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
