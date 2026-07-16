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
    
    $item['slug'] = slugify(trim($_POST['slug'] ?? ''));
    $item['title_ar'] = trim($_POST['title_ar'] ?? '');
    $item['title_fr'] = trim($_POST['title_fr'] ?? '');
    $item['title_en'] = trim($_POST['title_en'] ?? '');
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
    
    // Gallery images are uploaded individually via AJAX (see ajax_upload.php) so we
    // never send one huge multipart POST (OVH FastCGI request-length limit). Each
    // uploaded image already has a media row with content_item_id = 0 (temp). On save
    // we re-attach those rows to the new item, enforce the cap, and set the cover.
    $uploadedIds = [];
    if (!empty($_POST['uploaded_media_ids']) && is_array($_POST['uploaded_media_ids'])) {
        foreach ($_POST['uploaded_media_ids'] as $mid) {
            $uploadedIds[] = (int) $mid;
        }
    }

    if (count($uploadedIds) > MAX_GALLERY_IMAGES) {
        $errors[] = $lang === 'ar'
            ? 'يمكن رفع حتى ' . MAX_GALLERY_IMAGES . ' صورة كحد أقصى.'
            : ($lang === 'fr'
                ? 'Vous pouvez télécharger un maximum de ' . MAX_GALLERY_IMAGES . ' images.'
                : 'You can upload a maximum of ' . MAX_GALLERY_IMAGES . ' images.');
        // Drop the excess media rows.
        $dropStmt = db()->prepare("DELETE FROM media WHERE id = :id AND content_item_id = 0");
        foreach (array_slice($uploadedIds, MAX_GALLERY_IMAGES) as $dropId) {
            $dropStmt->execute(['id' => $dropId]);
        }
        $uploadedIds = array_slice($uploadedIds, 0, MAX_GALLERY_IMAGES);
    }

    // Determine cover from the chosen media id (AJAX-uploaded or selected).
    $coverMediaId = isset($_POST['cover_media_id']) ? (int) $_POST['cover_media_id'] : 0;
    if ($coverMediaId > 0 && in_array($coverMediaId, $uploadedIds, true)) {
        $cStmt = db()->prepare("SELECT file_path FROM media WHERE id = :id AND content_item_id = 0");
        $cStmt->execute(['id' => $coverMediaId]);
        $cp = $cStmt->fetchColumn();
        if ($cp) {
            $item['featured_image'] = $cp;
        }
    }
    
    // Save to database
    if (empty($errors)) {
        // Auto-translate missing fields before inserting
        $tr = fill_missing_translations($item, $auto_translate);
        $item = $tr['item'];
        $translation_warnings = $tr['warnings'];

        try {
            $stmt = db()->prepare("
                INSERT INTO content_items (type, rse_category, slug, title_ar, title_fr, title_en, summary_ar, summary_fr, summary_en, body_ar, body_fr, body_en, featured_image, video_url, status, is_featured, published_at, created_by, created_at, updated_at)
                VALUES (:type, :rse_category, :slug, :title_ar, :title_fr, :title_en, :summary_ar, :summary_fr, :summary_en, :body_ar, :body_fr, :body_en, :featured_image, :video_url, :status, :is_featured, :published_at, :created_by, NOW(), NOW())
            ");
            $stmt->execute([
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
                'status' => $item['status'],
                'is_featured' => $item['is_featured'],
                'published_at' => $item['status'] === 'published' ? $item['published_at'] : null,
                'created_by' => $_SESSION['user_id'],
            ]);
            
            $newId = db()->lastInsertId();

            // Attach any gallery images uploaded above (stored temporarily with content_item_id = 0)
            // Attach the temp media rows (content_item_id = NULL) uploaded via AJAX to
            // this new content item, and set the cover if it wasn't already chosen.
            $attachStmt = db()->prepare("UPDATE media SET content_item_id = :cid WHERE id = :mid AND content_item_id IS NULL");
            foreach ($uploadedIds as $mid) {
                $attachStmt->execute(['cid' => $newId, 'mid' => $mid]);
            }

            $coverStmt = db()->prepare("SELECT file_path FROM media WHERE content_item_id = :cid ORDER BY sort_order ASC, id ASC LIMIT 1");
            $coverStmt->execute(['cid' => $newId]);
            $firstPath = $coverStmt->fetchColumn();
            if (empty($item['featured_image']) && $firstPath) {
                $item['featured_image'] = $firstPath;
                $updCov = db()->prepare("UPDATE content_items SET featured_image = :fi WHERE id = :id");
                $updCov->execute(['fi' => $firstPath, 'id' => $newId]);
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
<html lang="<?= e($lang) ?>" dir="<?= dir_attribute($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
</head>
<body class="bg-gradient-to-br from-gray-900 via-emerald-950 to-gray-900 min-h-screen">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    
    <div class="relative z-10 flex h-screen">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
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
                                    </label>
                                    <textarea name="body_<?= $code ?>" rows="12" class="form-input font-mono text-sm"><?= e($item['body_' . $code]) ?></textarea>
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
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?php if ($lang === 'ar'): ?>صور المقال (حتى <?= MAX_GALLERY_IMAGES ?> صورة)
                            <?php elseif ($lang === 'fr'): ?>Images de l'article (max <?= MAX_GALLERY_IMAGES ?>)
                            <?php else: ?>Article Images (up to <?= MAX_GALLERY_IMAGES ?>)
                            <?php endif; ?>
                        </label>
                        <input type="file" id="galleryInput" multiple accept="image/jpeg,image/png,image/webp"
                               data-max="<?= MAX_GALLERY_IMAGES ?>" data-content-id="0" data-mode="create"
                               class="block w-full text-sm text-white/70 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-600/30 file:text-emerald-300 hover:file:bg-emerald-600/40">
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
                                <span class="text-xs text-white/30">(LibreTranslate)</span>
                            </label>
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
    
    <script>window.SEPJ_CSRF = <?= json_encode(csrf_token()) ?>;</script>
    <script src="../../public/assets/js/admin.js"></script>
</body>
</html>