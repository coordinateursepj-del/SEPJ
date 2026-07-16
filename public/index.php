<?php
/**
 * Public Homepage - SEPJ Gabès
 */

require_once 'includes/header.php';

$lang = current_lang();
$l = ['ar' => 'ar', 'fr' => 'fr', 'en' => 'en'][$lang] ?? 'ar';

// Fetch data
try {
    $pdo = db();

    // Featured posts
    $posts = $pdo->query("SELECT id, slug, COALESCE(NULLIF(title_{$l}, ''), title_ar) AS title, COALESCE(NULLIF(summary_{$l}, ''), summary_ar) AS summary, featured_image, published_at FROM content_items WHERE type='post' AND status='published' ORDER BY published_at DESC LIMIT 3")->fetchAll();

    // Featured projects — single query: featured first, then by date
    $projects = $pdo->query("SELECT id, slug, COALESCE(NULLIF(title_{$l}, ''), title_ar) AS title, COALESCE(NULLIF(summary_{$l}, ''), summary_ar) AS summary, featured_image FROM content_items WHERE type='project' AND status='published' ORDER BY is_featured DESC, created_at DESC LIMIT 3")->fetchAll();

    // Recent activities
    $activities = $pdo->query("SELECT id, slug, COALESCE(NULLIF(title_{$l}, ''), title_ar) AS title, COALESCE(NULLIF(summary_{$l}, ''), summary_ar) AS summary, featured_image, published_at FROM content_items WHERE type='activity' AND status='published' ORDER BY published_at DESC LIMIT 4")->fetchAll();

    // Gallery images
    $galleryImages = $pdo->query("SELECT m.file_path, COALESCE(NULLIF(m.caption_{$l}, ''), m.caption_ar) AS caption FROM media m ORDER BY m.created_at DESC LIMIT 8")->fetchAll();
    
} catch (PDOException $e) {
    $posts = $projects = $activities = $galleryImages = [];
}

$heroTitle = get_setting('hero_title', $lang) ?: __('company_name', $lang);
$heroSubtitle = get_setting('hero_subtitle', $lang) ?: __('hero_subtitle', $lang);
$companyName = get_setting('company_name', $lang) ?: __('company_name', $lang);
$phone = get_setting('phone');
$email = get_setting('email_primary');
$address = get_setting('address', $lang);
?>

<main id="main-content">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="max-w-5xl mx-auto px-4 text-center relative z-10">
            <p class="hero-eyebrow"><?= $lang === 'ar' ? 'من أجل ڨابس أكثر اخضراراً' : ($lang === 'fr' ? 'Pour un Gabès plus vert' : 'For a greener Gabès') ?></p>
            <h1 class="hero-title mb-6"><?= e($heroTitle) ?></h1>
            <p class="hero-subtitle mb-8 max-w-2xl mx-auto"><?= e($heroSubtitle) ?></p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="news.php" class="glass-btn glass-btn-primary">
                    <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                    <?= __('nav_news', $lang) ?>
                    <i class="fa-solid fa-arrow-right text-xs opacity-70" aria-hidden="true"></i>
                </a>
                <a href="projects.php" class="glass-btn">
                    <i class="fa-solid fa-diagram-project" aria-hidden="true"></i>
                    <?= __('nav_projects', $lang) ?>
                </a>
                <a href="contact.php" class="glass-btn">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    <?= __('nav_contact', $lang) ?>
                </a>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-16 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="section-title section-title-center text-center"><?= __('our_stats', $lang) ?></h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass-card stat-card"><div class="stat-number" data-count="<?= e(get_setting('stat_founded_value')) ?>"><?= e(get_setting('stat_founded_value')) ?></div><div class="stat-label"><?= e(get_setting('stat_founded_label', $lang) ?: __('founded', $lang)) ?></div></div>
                <div class="glass-card stat-card"><div class="stat-number" data-count="<?= e(get_setting('stat_trees_value')) ?>" data-suffix="+"><?= e(get_setting('stat_trees_value')) ?>+</div><div class="stat-label"><?= __('trees_planted', $lang) ?></div></div>
                <div class="glass-card stat-card"><div class="stat-number" data-count="<?= e(get_setting('stat_hectares_value')) ?>" data-suffix="+"><?= e(get_setting('stat_hectares_value')) ?>+</div><div class="stat-label"><?= __('hectares_transformed', $lang) ?></div></div>
                <div class="glass-card stat-card"><div class="stat-number" data-count="<?= e(get_setting('stat_activation_value')) ?>"><?= e(get_setting('stat_activation_value')) ?></div><div class="stat-label"><?= __('activation_rate', $lang) ?> <?= e(get_setting('stat_activation_year')) ?></div></div>
            </div>
        </div>
    </section>
    
    <!-- Featured News -->
    <?php if (!empty($posts)): ?>
    <section class="py-16 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between mb-8">
                <h2 class="section-title mb-0">
                    <i class="fa-solid fa-newspaper text-emerald-400 me-2 text-xl" aria-hidden="true"></i><?= __('latest_news', $lang) ?>
                </h2>
                <a href="news.php" class="glass-btn text-sm"><?= __('view_all', $lang) ?></a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($posts as $post): ?>
                <a href="page.php?slug=<?= e($post['slug']) ?>" class="glass-card overflow-hidden group reveal">
                    <?php if ($post['featured_image']): ?>
                    <div class="img-card"><img src="<?= e(upload_url($post['featured_image'])) ?>" alt="<?= e(mb_substr($post['title'], 0, 100)) ?>" loading="lazy"><div class="img-card-overlay"><span class="text-white text-sm"><?= __('read_more', $lang) ?></span></div></div>
                    <?php else: ?>
                    <div class="img-card bg-emerald-800/20 flex items-center justify-center" aria-hidden="true"><i class="fa-solid fa-newspaper text-emerald-400 text-3xl"></i></div>
                    <?php endif; ?>
                    <div class="p-4"><h3 class="font-semibold text-white mb-2"><?= e(mb_substr($post['title'], 0, 80)) ?></h3><p class="text-sm text-emerald-200/70"><?= e(excerpt($post['summary'] ?? '', 120)) ?></p></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Projects -->
    <?php if (!empty($projects)): ?>
    <section class="py-16 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between mb-8">
                <h2 class="section-title mb-0">
                    <i class="fa-solid fa-diagram-project text-emerald-400 me-2 text-xl" aria-hidden="true"></i><?= __('our_projects', $lang) ?>
                </h2>
                <a href="projects.php" class="glass-btn text-sm"><?= __('view_all', $lang) ?></a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($projects as $project): ?>
                <a href="page.php?slug=<?= e($project['slug']) ?>" class="glass-card p-6 reveal">
                    <h3 class="font-semibold text-white mb-2"><?= e($project['title']) ?></h3>
                    <p class="text-sm text-emerald-200/70"><?= e(excerpt($project['summary'] ?? '', 120)) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Activities -->
    <?php if (!empty($activities)): ?>
    <section class="py-16 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between mb-8">
                <h2 class="section-title mb-0">
                    <i class="fa-solid fa-calendar-check text-emerald-400 me-2 text-xl" aria-hidden="true"></i><?= __('our_activities', $lang) ?>
                </h2>
                <a href="activities.php" class="glass-btn text-sm"><?= __('view_all', $lang) ?></a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($activities as $activity): ?>
                <a href="page.php?slug=<?= e($activity['slug']) ?>" class="glass-card p-4 flex items-center gap-4 reveal">
                    <?php if ($activity['featured_image']): ?>
                    <img src="<?= e(upload_url($activity['featured_image'])) ?>"
                         alt="<?= e($activity['title']) ?>"
                         loading="lazy"
                         class="w-16 h-16 rounded-lg object-cover shrink-0">
                    <?php endif; ?>
                    <div><h4 class="text-white font-medium"><?= e($activity['title']) ?></h4><p class="text-xs text-emerald-300/60"><?= format_date($activity['published_at'], 'd/m/Y') ?></p></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Gallery Preview -->
    <?php if (!empty($galleryImages)): ?>
    <?php $galleryUrls = array_map(function($img) { return upload_url($img['file_path']); }, $galleryImages); ?>
    <section class="py-16 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between mb-8">
                <h2 class="section-title mb-0"><?= __('photo_gallery', $lang) ?></h2>
                <a href="gallery.php" class="glass-btn text-sm"><?= __('view_all', $lang) ?></a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <?php foreach ($galleryImages as $idx => $img):
                    $glabel = $img['caption'] ?: ($lang === 'ar' ? 'فتح الصورة ' . ($idx+1) : ($lang === 'fr' ? 'Ouvrir l\'image ' . ($idx+1) : 'Open image ' . ($idx+1)));
                ?>
                <div class="img-card cursor-pointer"
                     role="button"
                     tabindex="0"
                     aria-label="<?= e($glabel) ?>"
                     onclick="openLightbox(<?= $idx ?>, <?= e(json_encode($galleryUrls)) ?>, this)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openLightbox(<?= $idx ?>, <?= e(json_encode($galleryUrls)) ?>, this);}">
                    <img src="<?= e(upload_url($img['file_path'])) ?>"
                         alt="<?= e($img['caption'] ?? '') ?>"
                         loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- About Preview -->
    <section class="py-16 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="section-title section-title-center text-center"><?= __('about_company', $lang) ?></h2>
            <div class="max-w-3xl mx-auto text-center">
                <p class="text-emerald-200/80 leading-relaxed mb-6"><?= e(get_setting('about_summary', $lang)) ?></p>
                <p class="text-emerald-300"><strong><?= $lang === 'ar' ? 'المدير العام' : ($lang === 'fr' ? 'Directeur Général' : 'General Director') ?>:</strong> <?= e(get_setting('director_name', $lang)) ?></p>
            </div>
        </div>
    </section>
    
    <!-- Contact CTA -->
    <section class="cta-section py-20 relative z-10">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <div class="cta-card glass-card-static p-10 md:p-14 relative overflow-hidden">
                <h2 class="text-2xl font-bold text-white mb-4 relative"><?= __('contact_us', $lang) ?></h2>
                <div class="flex flex-wrap justify-center gap-6 text-emerald-200/80 mb-6 relative">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-phone text-emerald-400" aria-hidden="true"></i><?= e($phone) ?></span>
                    <span class="flex items-center gap-2"><i class="fa-solid fa-envelope text-emerald-400" aria-hidden="true"></i><?= e($email) ?></span>
                    <span class="flex items-center gap-2"><i class="fa-solid fa-location-dot text-emerald-400" aria-hidden="true"></i><?= e($address) ?></span>
                </div>
                <a href="contact.php" class="glass-btn glass-btn-accent relative">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    <?= __('send_message', $lang) ?>
                </a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>