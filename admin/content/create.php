<?php
/**
 * Admin Content Create - SEPJ Gabès
 */

require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/admin_helpers.php';
require_once ROOT_PATH . '/app/core/upload.php';
require_once ROOT_PATH . '/app/core/i18n.php';
require_once ROOT_PATH . '/app/config/translation.php';
require_once ROOT_PATH . '/app/core/translation_service.php';

session_start_secure();
require_login();

$lang = current_lang();
$type = $_GET['type'] ?? 'post';

if (!validate_type($type)) {
    set_flash('error', 'Invalid content type.');
    redirect(ADMIN_URL . '/content/index.php?type=post');
}

$pageTitle = get_content_page_title($type, $lang, 'create');
$errors = [];
$success = false;
$translation_warnings = [];
$auto_translate = true;

// Default values
$item = [
    'slug' => '',
    'title_ar' => '', 'title_fr' => '', 'title_en' => '',
    'summary_ar' => '', 'summary_fr' => '', 'summary_en' => '',
    'body_ar' => '', 'body_fr' => '', 'body_en' => '',
    'featured_image' => '',
    'video_url' => '',
    'status' => 'draft',
    'is_featured' => 0,
    'published_at' => date('Y-m-d H:i:s'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $item['title_ar'] = trim($_POST['title_ar'] ?? '');
    $item['title_fr'] = trim($_POST['title_fr'] ?? '');
    $item['title_en'] = trim($_POST['title_en'] ?? '');
    
    // Auto-generate slug from date + title if empty
    $slugInput = trim($_POST['slug'] ?? '');
    if ($slugInput === '') {
        $datePrefix = '';
        if (!empty($_POST['published_at'])) {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['published_at']);
            if ($dt) {
                $datePrefix = $dt->format('Y-m-d-');
            }
        }
        $titleForSlug = $item['title_ar'] ?: $item['title_fr'] ?: $item['title_en'] ?: '';
        if ($titleForSlug) {
            $slugInput = $datePrefix . $titleForSlug;
        }
    }
    $item['slug'] = slugify($slugInput);
    $item['summary_ar'] = trim($_POST['summary_ar'] ?? '');
    $item['summary_fr'] = trim($_POST['summary_fr'] ?? '');
    $item['summary_en'] = trim($_POST['summary_en'] ?? '');
    $item['body_ar'] = $_POST['body_ar'] ?? '';
    $item['body_fr'] = $_POST['body_fr'] ?? '';
    $item['body_en'] = $_POST['body_en'] ?? '';
    $item['status'] = $_POST['status'] ?? 'draft';
    $item['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $item['published_at'] = $_POST['published_at'] ?? date('Y-m-d H:i:s');
    $item['rse_category'] = trim($_POST['rse_category'] ?? '');
    $item['video_url'] = trim($_POST['video_url'] ?? '');
    $item['video_thumb'] = trim($_POST['video_thumb_path'] ?? '');
    $auto_translate = isset($_POST['auto_translate']);

    // Validate
    if (empty($item['title_ar']) && empty($item['title_fr']) && empty($item['title_en'])) {
        $errors[] = $lang === 'ar' ? 'العنوان مطلوب في لغة واحدة على الأقل.' : ($lang === 'fr' ? 'Le titre est requis dans au moins une langue.' : 'Title is required in at least one language.');
    }
    
    if (empty($item['slug'])) {
        $errors[] = $lang === 'ar' ? 'الرابط القصير (slug) مطلوب.' : ($lang === 'fr' ? 'Le slug est requis.' : 'Slug is required.');
    }
    
    if (!preg_match('/^[a-z0-9-]+$/', $item['slug'])) {
        $errors[] = $lang === 'ar' ? 'الرابط القصير يجب أن يحتوي فقط على أحرف وأرقام وشرطات.' : ($lang === 'fr' ? 'Le slug ne peut contenir que des lettres, chiffres et tirets.' : 'Slug can only contain letters, numbers, and hyphens.');
    }

    // Video items require a valid YouTube URL
    if ($type === 'video') {
        if ($item['video_url'] === '') {
            $errors[] = $lang === 'ar' ? 'رابط الفيديو (YouTube) مطلوب.' : ($lang === 'fr' ? 'L\'URL de la vidéo YouTube est requise.' : 'The YouTube video URL is required.');
        } elseif (youtube_embed_url($item['video_url']) === null) {
            $errors[] = $lang === 'ar' ? 'رابط YouTube غير صالح. استخدم رابطاً مثل youtube.com/watch?v=... أو youtu.be/...' : ($lang === 'fr' ? 'URL YouTube invalide. Utilisez un lien comme youtube.com/watch?v=... ou youtu.be/...' : 'Invalid YouTube URL. Use a link like youtube.com/watch?v=... or youtu.be/...');
        }
    }

    // Check duplicate slug
    if (empty($errors)) {
        $stmt = db()->prepare("SELECT id FROM content_items WHERE slug = :slug AND type = :type");
        $stmt->execute(['slug' => $item['slug'], 'type' => $type]);
        if ($stmt->fetch()) {
            $errors[] = $lang === 'ar' ? 'هذا الرابط القصير مستخدم بالفعل.' : ($lang === 'fr' ? 'Ce slug est déjà utilisé.' : 'This slug is already in use.');
        }
    }
    
    // Gallery images are uploaded individually via AJAX (see ajax_upload.php). The
    // endpoint only saves the file and returns its path; we create the media rows
    // here on Save, where we already have a valid content_item_id (no FK issues).
    $uploadedPaths = [];
    if (!empty($_POST['uploaded_images']) && is_array($_POST['uploaded_images'])) {
        foreach ($_POST['uploaded_images'] as $p) {
            $p = trim($p);
            if ($p !== '') {
                $uploadedPaths[] = $p;
            }
        }
    }

    $typeMax = $type === 'video' ? 1 : MAX_GALLERY_IMAGES;
    if (count($uploadedPaths) > $typeMax) {
        $errors[] = $lang === 'ar'
            ? 'يمكن رفع حتى ' . $typeMax . ' صورة كحد أقصى.'
            : ($lang === 'fr'
                ? 'Vous pouvez télécharger un maximum de ' . $typeMax . ' images.'
                : 'You can upload a maximum of ' . $typeMax . ' images.');
        $uploadedPaths = array_slice($uploadedPaths, 0, $typeMax);
    }

    // Cover: either an existing media id (cover_media_id) or a newly uploaded path
    // (cover_path). The first uploaded image is the default cover.
    $coverPath = '';
    $coverPostedPath = trim((string) ($_POST['cover_path'] ?? ''));
    $coverMediaId   = isset($_POST['cover_media_id']) ? (int) $_POST['cover_media_id'] : 0;
    if ($coverPostedPath !== '' && in_array($coverPostedPath, $uploadedPaths, true)) {
        $coverPath = $coverPostedPath;
    } elseif ($coverMediaId > 0) {
        $cStmt = db()->prepare("SELECT file_path FROM media WHERE id = :id AND content_item_id = :cid");
        $cStmt->execute(['id' => $coverMediaId, 'cid' => 0]);
        $cp = $cStmt->fetchColumn();
        if ($cp) {
            $coverPath = $cp;
        }
    }
    if ($coverPath === '' && !empty($uploadedPaths)) {
        $coverPath = $uploadedPaths[0];
    }
    $item['featured_image'] = $coverPath;

    // Save to database
    if (empty($errors)) {
        // Auto-translate missing fields before inserting
        $tr = fill_missing_translations($item, $auto_translate);
        $item = $tr['item'];
        $translation_warnings = $tr['warnings'];

        try {
            $hasThumbCol = table_column_exists('content_items', 'video_thumb');
            $cols = ['type', 'rse_category', 'slug', 'title_ar', 'title_fr', 'title_en', 'summary_ar', 'summary_fr', 'summary_en', 'body_ar', 'body_fr', 'body_en', 'featured_image', 'video_url'];
            $params = [
                'type' => $type,
                'rse_category' => $item['rse_category'] ?: null,
                'slug' => $item['slug'],
                'title_ar' => $item['title_ar'],
                'title_fr' => $item['title_fr'],
                'title_en' => $item['title_en'],
                'summary_ar' => $item['summary_ar'],
                'summary_fr' => $item['summary_fr'],
                'summary_en' => $item['summary_en'],
                'body_ar' => $item['body_ar'],
                'body_fr' => $item['body_fr'],
                'body_en' => $item['body_en'],
                'featured_image' => $item['featured_image'],
                'video_url' => $item['video_url'] ?: null,
            ];
            if ($hasThumbCol) {
                $cols[] = 'video_thumb';
                $params['video_thumb'] = $item['video_thumb'] !== '' ? $item['video_thumb'] : null;
            }
            $cols[] = 'status'; $params['status'] = $item['status'];
            $cols[] = 'is_featured'; $params['is_featured'] = $item['is_featured'];
            $cols[] = 'published_at'; $params['published_at'] = $item['status'] === 'published' ? $item['published_at'] : null;
            $cols[] = 'created_by'; $params['created_by'] = $_SESSION['user_id'];

            $colSql = implode(', ', $cols);
            $valSql = implode(', ', array_map(fn($c) => ":$c", $cols));
            $stmt = db()->prepare("INSERT INTO content_items ($colSql, created_at, updated_at) VALUES ($valSql, NOW(), NOW())");
            $stmt->execute($params);
            
            $newId = db()->lastInsertId();

            // Create media rows for each uploaded image, now that we have a valid
            // content_item_id. We only insert — no FK risk because the id is real.
            $insMedia = db()->prepare("INSERT INTO media (content_item_id, file_path, file_name, file_type, sort_order, created_at) VALUES (:cid, :path, :name, 'image', :so, NOW())");
            foreach ($uploadedPaths as $idx => $p) {
                $name = basename($p);
                $insMedia->execute(['cid' => $newId, 'path' => $p, 'name' => $name, 'so' => $idx]);
            }

            if (empty($item['featured_image']) && !empty($uploadedPaths)) {
                $item['featured_image'] = $uploadedPaths[0];
                $updCov = db()->prepare("UPDATE content_items SET featured_image = :fi WHERE id = :id");
                $updCov->execute(['fi' => $uploadedPaths[0], 'id' => $newId]);
            }

            log_audit($_SESSION['user_id'], 'create', $type, (int)$newId);
            
            csrf_regenerate();
            $flashMsg = $lang === 'ar' ? 'تم إنشاء العنصر بنجاح.' : ($lang === 'fr' ? 'Élément créé avec succès.' : 'Item created successfully.');
            if (!empty($translation_warnings)) {
                $flashMsg .= ' ' . ($lang === 'ar' ? '(تحذير: فشلت بعض الترجمات التلقائية، راجع سجل الأخطاء.)' : ($lang === 'fr' ? '(Avertissement : certaines traductions automatiques ont échoué, vérifiez le journal d\'erreurs.)' : '(Warning: some auto-translations failed — check error log.)'));
            }
            set_flash('success', $flashMsg);
            redirect('edit.php?id=' . $newId);
            
        } catch (PDOException $e) {
            error_log("Create content error: " . $e->getMessage());
            $errors[] = $lang === 'ar' ? 'حدث خطأ في قاعدة البيانات.' : ($lang === 'fr' ? 'Erreur de base de données.' : 'Database error.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attribute($lang) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
</head>
<body class="admin-theme-bg min-h-screen">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    
    <div class="relative z-10 flex h-screen">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden pt-16">
            <?php include '../includes/header.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-6">
                <?= admin_breadcrumb($type, 'create') ?>
                
                <div class="flex items-center justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-bold text-white"><?= e($pageTitle) ?></h1>
                    <a href="index.php?type=<?= e($type) ?>" class="text-sm text-emerald-400 hover:text-emerald-300 transition-colors">
                        &larr; <?php if ($lang === 'ar'): ?>العودة
                        <?php elseif ($lang === 'fr'): ?>Retour
                        <?php else: ?>Back
                        <?php endif; ?>
                    </a>
                </div>
                
                <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-500/20 border border-red-500/30">
                    <ul class="list-disc list-inside text-red-300 text-sm space-y-1">
                        <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="<?= e($type) ?>">
                    
                    <!-- Slug -->
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?php if ($lang === 'ar'): ?>الرابط القصير (Slug)
                            <?php elseif ($lang === 'fr'): ?>Slug
                            <?php else: ?>Slug
                            <?php endif; ?>
                        </label>
                        <input type="text" id="slug" name="slug" value="<?= e($item['slug']) ?>" data-auto="false"
                               class="form-input font-mono text-sm" 
                               placeholder="ex: mon-article" 
                               pattern="[a-z0-9-]+"
                               title="Only lowercase letters, numbers, and hyphens">
                        <p class="text-xs text-emerald-300/40 mt-1"><?php if ($lang === 'ar'): ?>يتم إنشاؤه تلقائياً من العنوان
                        <?php elseif ($lang === 'fr'): ?>Généré automatiquement à partir du titre
                        <?php else: ?>Auto-generated from title
                        <?php endif; ?></p>
                    </div>
                    
                    <!-- Language Tabs -->
                    <div class="glass-card-static p-4">
                        <div class="lang-tabs">
                            <div class="flex gap-2 mb-4 border-b border-white/10 pb-3">
                                <?php foreach (['ar' => 'العربية', 'fr' => 'Français', 'en' => 'English'] as $code => $label): ?>
                                <button type="button" data-lang="<?= $code ?>" 
                                        class="lang-tab px-4 py-2 rounded-lg text-sm border transition-all
                                        <?= $code === 'ar' ? 'bg-emerald-600/30 text-emerald-300 border-emerald-500' : 'text-white/50 border-transparent hover:text-white' ?>">
                                    <?= $label ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php foreach (['ar' => 'rtl', 'fr' => 'ltr', 'en' => 'ltr'] as $code => $dir): ?>
                            <div data-lang="<?= $code ?>" class="lang-content space-y-4 <?= $code !== 'ar' ? 'hidden' : '' ?>" dir="<?= $dir ?>">
                                <div>
                                    <label class="block text-sm font-medium text-emerald-200 mb-1">
                                        <?php if ($lang === 'ar'): ?>العنوان
                                        <?php elseif ($lang === 'fr'): ?>Titre
                                        <?php else: ?>Title
                                        <?php endif; ?> (<?= strtoupper($code) ?>)
                                    </label>
                                    <input type="text" name="title_<?= $code ?>" value="<?= e($item['title_' . $code]) ?>" 
                                           class="form-input" data-slug-source
                                           placeholder="<?php if ($lang === 'ar'): ?>أدخل العنوان بالعربية
                                           <?php elseif ($lang === 'fr'): ?>Entrez le titre
                                           <?php else: ?>Enter title
                                           <?php endif; ?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-emerald-200 mb-1">
                                        <?php if ($lang === 'ar'): ?>ملخص
                                        <?php elseif ($lang === 'fr'): ?>Résumé
                                        <?php else: ?>Summary
                                        <?php endif; ?> (<?= strtoupper($code) ?>)
                                    </label>
                                    <textarea id="summary_<?= $code ?>" name="summary_<?= $code ?>" rows="3" class="form-input"
                                              data-maxlength="300"><?= e($item['summary_' . $code]) ?></textarea>
                                    <div class="text-xs text-emerald-400 mt-1 text-left">
                                        <span id="summary_<?= $code ?>_counter">300</span> <?php if ($lang === 'ar'): ?>حرف متبقي
                                        <?php elseif ($lang === 'fr'): ?>caractères restants
                                        <?php else: ?>characters remaining
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-emerald-200 mb-1">
                                        <?php if ($lang === 'ar'): ?>المحتوى
                                        <?php elseif ($lang === 'fr'): ?>Contenu
                                        <?php else: ?>Body
                                        <?php endif; ?> (<?= strtoupper($code) ?>)
                                        <button type="button" class="ai-generate-btn hidden" data-lang="<?= $code ?>"
                                                title="<?php if ($lang === 'ar'): ?>توليد العنوان والملخص تلقائياً<?php elseif ($lang === 'fr'): ?>Générer le titre et le résumé automatiquement<?php else: ?>Auto-generate title and summary<?php endif; ?>">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="inline-block align-middle"><path d="M12 2l2.4 7.2L22 12l-7.6 2.8L12 22l-2.4-7.2L2 12l7.6-2.8z"/></svg>
                                            <span class="align-middle"><?php if ($lang === 'ar'): ?>توليد<?php elseif ($lang === 'fr'): ?>Générer<?php else: ?>Generate<?php endif; ?></span>
                                        </button>
                                        <span class="ai-error hidden text-red-400 text-xs"></span>
                                    </label>
                                    <textarea id="body_<?= $code ?>" name="body_<?= $code ?>" rows="12" class="form-input font-mono text-sm" data-ai-source="true"><?= e($item['body_' . $code]) ?></textarea>
                                    <p class="text-xs text-emerald-300/40 mt-1">HTML <?php if ($lang === 'ar'): ?>مسموح به
                                    <?php elseif ($lang === 'fr'): ?>autorisé
                                    <?php else: ?>allowed
                                    <?php endif; ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Gallery Images + Cover (uploaded individually via AJAX) -->
                    <?php if ($type === 'video'): ?>
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?= __('video_thumbnail', $lang) ?>
                        </label>
                        <div class="file-input-wrap">
                            <span class="file-input-btn">
                                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                <?= $lang === 'ar' ? 'اختر صور' : ($lang === 'fr' ? 'Choisir' : 'Browse') ?>
                            </span>
                            <span class="file-input-name" data-empty="<?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?>"><?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?></span>
                            <input type="file" id="galleryInput" accept="image/jpeg,image/png,image/webp"
                                   data-max="1" data-content-id="0" data-mode="create">
                        </div>
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?= __('video_thumbnail_help', $lang) ?>
                        </p>
                        <div id="galleryPreview" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-3"></div>
                        <p id="galleryCount" class="text-xs text-emerald-400 mt-2"></p>
                        <!-- Holds AJAX-uploaded media ids + cover, posted on save -->
                        <div id="galleryFields"></div>
                    </div>
                    <?php else: ?>
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?= __('article_images', $lang) ?> (<?= $lang === 'ar' ? 'حتى ' . MAX_GALLERY_IMAGES . ' صورة' : ($lang === 'fr' ? 'max ' . MAX_GALLERY_IMAGES : 'up to ' . MAX_GALLERY_IMAGES) ?>)
                        </label>
                        <div class="file-input-wrap">
                            <span class="file-input-btn">
                                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                <?= $lang === 'ar' ? 'اختر صور' : ($lang === 'fr' ? 'Choisir' : 'Browse') ?>
                            </span>
                            <span class="file-input-name" data-empty="<?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?>"><?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?></span>
                            <input type="file" id="galleryInput" multiple accept="image/jpeg,image/png,image/webp"
                                   data-max="<?= MAX_GALLERY_IMAGES ?>" data-content-id="0" data-mode="create">
                        </div>
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?php if ($lang === 'ar'): ?>تُرفع كل صورة على حدة. الصورة المحددة كـ "غلاف" تظهر في القوائم.
                            <?php elseif ($lang === 'fr'): ?>Chaque image est envoyée séparément. Celle marquée « couverture » apparaît dans les listes.
                            <?php else: ?>Each image is uploaded separately. The one marked "cover" is shown in listings.
                            <?php endif; ?>
                        </p>
                        <div id="galleryPreview" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-3"></div>
                        <p id="galleryCount" class="text-xs text-emerald-400 mt-2"></p>
                        <!-- Holds AJAX-uploaded media ids + cover, posted on save -->
                        <div id="galleryFields"></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Video URL (only for Video type) -->
                    <?php if ($type === 'video'): ?>
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?php if ($lang === 'ar'): ?>رابط الفيديو (YouTube)
                            <?php elseif ($lang === 'fr'): ?>URL de la vidéo (YouTube)
                            <?php else: ?>Video URL (YouTube)
                            <?php endif; ?>
                        </label>
                        <input type="text" name="video_url" value="<?= e($item['video_url']) ?>" dir="ltr"
                               class="form-input font-mono text-sm"
                               placeholder="https://www.youtube.com/watch?v=...">
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?php if ($lang === 'ar'): ?>يقبل جميع صيغ روابط YouTube: youtube.com/watch?v=… أو youtu.be/… أو embed/…
                            <?php elseif ($lang === 'fr'): ?>Accepte tous les formats de lien YouTube : youtube.com/watch?v=…, youtu.be/… ou embed/…
                            <?php else: ?>Accepts any YouTube link format: youtube.com/watch?v=…, youtu.be/…, or embed/…
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Optional YouTube video (for Posts/News, shown in the article) -->
                    <?php if ($type === 'post'): ?>
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?= __('video_section_label', $lang) ?>
                        </label>
                        <input type="text" name="video_url" value="<?= e($item['video_url']) ?>" dir="ltr"
                               class="form-input font-mono text-sm"
                               placeholder="https://www.youtube.com/watch?v=...">
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?= __('attach_video_help', $lang) ?>
                        </p>

                        <label class="block text-sm font-medium text-emerald-200 mb-2 mt-4">
                            <?= __('video_thumbnail', $lang) ?>
                        </label>
                        <div class="file-input-wrap">
                            <span class="file-input-btn">
                                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                <?= $lang === 'ar' ? 'اختر صور' : ($lang === 'fr' ? 'Choisir' : 'Browse') ?>
                            </span>
                            <span class="file-input-name" data-empty="<?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?>"><?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?></span>
                            <input type="file" id="videoThumbInput" accept="image/jpeg,image/png,image/webp"
                                   data-content-id="0" data-mode="create">
                        </div>
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?= __('video_thumbnail_help', $lang) ?>
                        </p>
                        <div id="videoThumbPreview" class="mt-3 <?= !empty($item['video_thumb']) ? '' : 'hidden' ?>">
                            <?php if (!empty($item['video_thumb'])): ?>
                            <div class="relative inline-block">
                                <img src="<?= e(upload_url($item['video_thumb'])) ?>" alt="" class="w-40 rounded-lg object-cover">
                                <button type="button" id="videoThumbRemove" class="absolute -top-2 -right-2 text-xs text-red-400 bg-black/50 rounded-full px-2 py-1">✕</button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="video_thumb_path" id="videoThumbPath" value="<?= e($item['video_thumb']) ?>">
                    </div>
                    <?php endif; ?>

                    <!-- RSE Category (only for RSE type) -->
                    <?php if ($type === 'rse'): ?>
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?php if ($lang === 'ar'): ?>تصنيف المسؤولية المجتمعية
                            <?php elseif ($lang === 'fr'): ?>Catégorie RSE
                            <?php else: ?>RSE Category
                            <?php endif; ?>
                        </label>
                        <select name="rse_category" class="form-input">
                            <?php foreach (rse_category_labels($lang) as $catKey => $catLabels): ?>
                            <option value="<?= e($catKey) ?>" <?= ($item['rse_category'] ?? 'engagement_social') === $catKey ? 'selected' : '' ?>>
                                <?= e($catLabels[$lang] ?? $catLabels['fr']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?php if ($lang === 'ar'): ?>اختر تصنيفاً لعنصر المسؤولية المجتمعية
                            <?php elseif ($lang === 'fr'): ?>Choisissez une catégorie pour cet élément RSE
                            <?php else: ?>Choose a category for this RSE item
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Status & Options -->
                    <div class="glass-card-static p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-emerald-200 mb-2">
                                    <?php if ($lang === 'ar'): ?>الحالة
                                    <?php elseif ($lang === 'fr'): ?>Statut
                                    <?php else: ?>Status
                                    <?php endif; ?>
                                </label>
                                <select name="status" class="form-input">
                                    <option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>>
                                        <?= $lang === 'ar' ? 'مسودة' : ($lang === 'fr' ? 'Brouillon' : 'Draft') ?>
                                    </option>
                                    <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>>
                                        <?= $lang === 'ar' ? 'منشور' : ($lang === 'fr' ? 'Publié' : 'Published') ?>
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-emerald-200 mb-2">
                                    <?php if ($lang === 'ar'): ?>تاريخ النشر
                                    <?php elseif ($lang === 'fr'): ?>Date de publication
                                    <?php else: ?>Publish Date
                                    <?php endif; ?>
                                </label>
                                <input type="datetime-local" name="published_at" value="<?= e(str_replace(' ', 'T', $item['published_at'])) ?>" class="form-input">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="flex items-center gap-2 text-sm text-emerald-200 cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" <?= $item['is_featured'] ? 'checked' : '' ?> class="rounded bg-white/5 border-white/20 text-emerald-500 focus:ring-emerald-500">
                                <?php if ($lang === 'ar'): ?>محتوًى مميز
                                <?php elseif ($lang === 'fr'): ?>Contenu à la une
                                <?php else: ?>Featured content
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php if (defined('ENABLE_TRANSLATION') && ENABLE_TRANSLATION): ?>
                        <div class="mt-3 pt-3 border-t border-white/10">
                            <label class="flex items-center gap-2 text-sm text-emerald-200 cursor-pointer">
                                <input type="checkbox" name="auto_translate" value="1" <?= $auto_translate ? 'checked' : '' ?>
                                       class="rounded bg-white/5 border-white/20 text-emerald-500 focus:ring-emerald-500">
                                <?php if ($lang === 'ar'): ?>ترجمة تلقائية للحقول الفارغة
                                <?php elseif ($lang === 'fr'): ?>Traduire automatiquement les champs vides
                                <?php else: ?>Auto-translate empty fields
                                <?php endif; ?>
                                <span class="text-xs text-white/30">(<?= defined('TRANSLATION_PROVIDER') && strtolower(TRANSLATION_PROVIDER) === 'libretranslate' ? 'LibreTranslate' : 'Google Translate' ?>)</span>
                            </label>
                            <div class="flex items-center gap-3 mt-2">
                                <button type="button" id="translateNowBtn"
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all
                                               bg-emerald-600/20 text-emerald-300 border-emerald-500/30
                                               hover:bg-emerald-600/30">
                                    <span class="flex items-center gap-1.5">
                                        <svg viewBox="0 0 20 20" width="14" height="14" fill="currentColor"><path d="M7.41 2l-4.5 9h2.08l.8-1.79h4.03l.8 1.79h2.08L9.59 2H7.41zm-.73 5.21L9 4l2.32 3.21H6.68zM2 17h16v2H2v-2zm3-4h10l-1.5 2h-7L5 13z"/></svg>
                                        <?php if ($lang === 'ar'): ?>توليد الترجمة الآن
                                        <?php elseif ($lang === 'fr'): ?>Générer la traduction
                                        <?php else: ?>Generate translation now
                                        <?php endif; ?>
                                    </span>
                                </button>
                                <span id="translateStatus" class="text-xs text-white/50"></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Submit -->
                    <div class="flex gap-3 pt-4 border-t border-white/10">
                        <button type="submit" class="glass-btn glass-btn-primary">
                            <?php if ($lang === 'ar'): ?>حفظ
                            <?php elseif ($lang === 'fr'): ?>Enregistrer
                            <?php else: ?>Save
                            <?php endif; ?>
                        </button>
                        <a href="index.php?type=<?= e($type) ?>" class="glass-btn bg-white/5 hover:bg-white/10">
                            <?php if ($lang === 'ar'): ?>إلغاء
                            <?php elseif ($lang === 'fr'): ?>Annuler
                            <?php else: ?>Cancel
                            <?php endif; ?>
                        </a>
                    </div>
                </form>
            </main>
            
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>
    
    <style>
    .ai-generate-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.6rem;
        font-size: 0.7rem;
        font-weight: 500;
        border-radius: 999px;
        border: 1px solid rgba(251, 191, 36, 0.3);
        background: rgba(251, 191, 36, 0.1);
        color: #fbbf24;
        cursor: pointer;
        transition: all 0.2s;
        vertical-align: middle;
        margin-left: 0.5rem;
    }
    .ai-generate-btn:hover {
        background: rgba(251, 191, 36, 0.2);
        border-color: rgba(251, 191, 36, 0.5);
    }
    .ai-generate-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .ai-generate-btn .spinner {
        display: none;
        width: 12px;
        height: 12px;
        border: 2px solid rgba(251, 191, 36, 0.3);
        border-top-color: #fbbf24;
        border-radius: 50%;
        animation: ai-spin 0.6s linear infinite;
    }
    .ai-generate-btn.loading .spinner { display: inline-block; }
    .ai-generate-btn.loading svg { display: none; }
    @keyframes ai-spin { to { transform: rotate(360deg); } }
    </style>

    <script>window.SEPJ_CSRF = <?= json_encode(csrf_token()) ?>; window.SEPJ_AJAX_PATH = '../ajax';</script>
    <script src="../../public/assets/js/admin.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sources = document.querySelectorAll('textarea[data-ai-source]');
        var btns = document.querySelectorAll('.ai-generate-btn');

        function wordCount(text) {
            return text.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length;
        }

        function updateButtons() {
            var lang = getActiveLang();
            var textarea = document.getElementById('body_' + lang);
            if (!textarea) return;
            var count = wordCount(textarea.value);
            btns.forEach(function(btn) {
                if (btn.dataset.lang === lang) {
                    if (count >= 5) {
                        btn.classList.remove('hidden');
                        btn.style.opacity = '0';
                        btn.style.transform = 'translateY(4px)';
                        requestAnimationFrame(function() {
                            btn.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            btn.style.opacity = '1';
                            btn.style.transform = 'translateY(0)';
                        });
                    } else {
                        btn.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                        btn.style.opacity = '0';
                        btn.style.transform = 'translateY(4px)';
                        setTimeout(function() { btn.classList.add('hidden'); }, 200);
                    }
                } else {
                    btn.classList.add('hidden');
                }
            });
        }

        function getActiveLang() {
            var active = document.querySelector('.lang-content:not(.hidden)');
            return active ? active.dataset.lang : 'ar';
        }

        sources.forEach(function(ta) {
            ta.addEventListener('input', updateButtons);
        });

        var tabs = document.querySelectorAll('.lang-tab');
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                setTimeout(updateButtons, 50);
            });
        });

        btns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var lang = btn.dataset.lang;
                var body = document.getElementById('body_' + lang);
                var titleInput = document.querySelector('input[name="title_' + lang + '"]');
                var summaryTA = document.getElementById('summary_' + lang);
                if (!body || !titleInput || !summaryTA) return;
                var text = body.value.trim();
                if (wordCount(text) < 5) return;

                btn.disabled = true;
                btn.classList.add('loading');
                btn.querySelector('span').textContent = '...';

                var formData = new FormData();
                formData.append('body', text);
                formData.append('lang', lang);
                formData.append('csrf_token', window.SEPJ_CSRF || '');

                fetch('../ajax/ai_generate.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var errSpan = btn.parentElement.querySelector('.ai-error');
                    if (data.success) {
                        if (data.title && !titleInput.value.trim()) {
                            titleInput.value = data.title;
                        }
                        if (data.summary && !summaryTA.value.trim()) {
                            summaryTA.value = data.summary;
                            summaryTA.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        if (errSpan) { errSpan.classList.add('hidden'); errSpan.textContent = ''; }
                    } else {
                        if (errSpan) {
                            errSpan.textContent = data.error || '<?php if ($lang === 'ar'): ?>فشل التوليد<?php elseif ($lang === 'fr'): ?>Échec de génération<?php else: ?>Generation failed<?php endif; ?>';
                            errSpan.classList.remove('hidden');
                            setTimeout(function() { errSpan.classList.add('hidden'); }, 8000);
                        }
                    }
                })
                .catch(function(err) {
                    var errSpan = btn.parentElement.querySelector('.ai-error');
                    if (errSpan) {
                        errSpan.textContent = '<?php if ($lang === 'ar'): ?>خطأ في الاتصال<?php elseif ($lang === 'fr'): ?>Erreur de connexion<?php else: ?>Connection error<?php endif; ?>';
                        errSpan.classList.remove('hidden');
                        setTimeout(function() { errSpan.classList.add('hidden'); }, 8000);
                    }
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.classList.remove('loading');
                    btn.querySelector('span').textContent = '<?php if ($lang === 'ar'): ?>توليد<?php elseif ($lang === 'fr'): ?>Générer<?php else: ?>Generate<?php endif; ?>';
                });
            });
        });

        updateButtons();
    });
    </script>
</body>
</html>