<?php
/**
 * Admin Content Edit - SEPJ Gabès
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
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirect('index.php');
}

// Fetch existing item
try {
    $stmt = db()->prepare("SELECT * FROM content_items WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();

    if (!$item) {
        set_flash('error', $lang === 'ar' ? 'العنصر غير موجود.' : ($lang === 'fr' ? 'Élément introuvable.' : 'Item not found.'));
        redirect(ADMIN_URL . '/content/index.php');
    }
} catch (PDOException $e) {
    error_log("Edit content fetch error: " . $e->getMessage());
    set_flash('error', $lang === 'ar' ? 'خطأ في قاعدة البيانات.' : ($lang === 'fr' ? 'Erreur base de données.' : 'Database error.'));
    redirect(ADMIN_URL . '/content/index.php');
}

$type = $item['type'];
$pageTitle = get_content_page_title($type, $lang, 'edit');

// Fetch attached gallery media for this item
$mediaStmt = db()->prepare("SELECT * FROM media WHERE content_item_id = :id ORDER BY sort_order ASC, created_at ASC");
$mediaStmt->execute(['id' => $id]);
$existingMedia = $mediaStmt->fetchAll();
$errors = [];
$translation_warnings = [];
$auto_translate = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $item['slug'] = slugify(trim($_POST['slug'] ?? $item['slug']));
    foreach (['title', 'summary', 'body'] as $field) {
        foreach (['ar', 'fr', 'en'] as $flang) {
            $key = "{$field}_{$flang}";
            if (isset($_POST[$key])) {
                $item[$key] = ($field === 'body') ? $_POST[$key] : trim($_POST[$key]);
            }
        }
    }
    $item['status'] = $_POST['status'] ?? 'draft';
    $item['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $item['published_at'] = $_POST['published_at'] ?? $item['published_at'];
    $item['rse_category'] = trim($_POST['rse_category'] ?? $item['rse_category'] ?? '');
    $item['video_url'] = trim($_POST['video_url'] ?? $item['video_url'] ?? '');
    $auto_translate = isset($_POST['auto_translate']);

    if (empty($item['title_ar']) && empty($item['title_fr']) && empty($item['title_en'])) {
        $errors[] = $lang === 'ar' ? 'العنوان مطلوب.' : ($lang === 'fr' ? 'Titre requis.' : 'Title required.');
    }
    
    if (empty($item['slug'])) {
        $errors[] = $lang === 'ar' ? 'الرابط القصير مطلوب.' : ($lang === 'fr' ? 'Slug requis.' : 'Slug required.');
    }

    // Video items require a valid YouTube URL
    if ($type === 'video') {
        if ($item['video_url'] === '') {
            $errors[] = $lang === 'ar' ? 'رابط الفيديو (YouTube) مطلوب.' : ($lang === 'fr' ? 'L\'URL de la vidéo YouTube est requise.' : 'The YouTube video URL is required.');
        } elseif (youtube_embed_url($item['video_url']) === null) {
            $errors[] = $lang === 'ar' ? 'رابط YouTube غير صالح. استخدم رابطاً مثل youtube.com/watch?v=... أو youtu.be/...' : ($lang === 'fr' ? 'URL YouTube invalide. Utilisez un lien comme youtube.com/watch?v=... ou youtu.be/...' : 'Invalid YouTube URL. Use a link like youtube.com/watch?v=... or youtu.be/...');
        }
    }

    // Check duplicate slug within same type (excluding current item)
    if (empty($errors)) {
        $stmt = db()->prepare("SELECT id FROM content_items WHERE slug = :slug AND type = :type AND id != :id");
        $stmt->execute(['slug' => $item['slug'], 'type' => $type, 'id' => $id]);
        if ($stmt->fetch()) {
            $errors[] = $lang === 'ar' ? 'الرابط القصير مستخدم بالفعل.' : ($lang === 'fr' ? 'Slug déjà utilisé.' : 'Slug already in use.');
        }
    }
    
    // Handle new gallery image uploads (up to MAX_GALLERY_IMAGES total).
    // Use !== UPLOAD_ERR_NO_FILE so a provided-but-rejected file surfaces a clear error.
    $newPaths = [];
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        $currentCount = (int) db()->prepare("SELECT COUNT(*) FROM media WHERE content_item_id = :id")
            ->execute(['id' => $id])->fetchColumn();

        $uploaded = upload_multiple_files($_FILES['gallery_images'], 'content');
        foreach ($uploaded as $result) {
            if ($result['success']) {
                $newPaths[] = $result['path'];
            } elseif (!empty($result['message'])) {
                $errors[] = $result['message'];
            }
        }

        if (($currentCount + count($newPaths)) > MAX_GALLERY_IMAGES) {
            $errors[] = $lang === 'ar'
                ? 'يمكن رفع حتى ' . MAX_GALLERY_IMAGES . ' صورة كحد أقصى (لديك حالياً ' . $currentCount . ').'
                : ($lang === 'fr'
                    ? 'Maximum ' . MAX_GALLERY_IMAGES . ' images (vous en avez déjà ' . $currentCount . ').'
                    : 'Maximum ' . MAX_GALLERY_IMAGES . ' images (you already have ' . $currentCount . ').');
            foreach ($newPaths as $ex) {
                delete_uploaded_file($ex);
            }
            $newPaths = [];
        }

        if (empty($errors) && !empty($newPaths)) {
            foreach ($newPaths as $path) {
                $sortStmt = db()->prepare("SELECT MAX(sort_order) FROM media WHERE content_item_id = :id");
                $sortStmt->execute(['id' => $id]);
                $maxSort = (int) $sortStmt->fetchColumn();
                $stmt = db()->prepare("
                    INSERT INTO media (content_item_id, file_path, file_name, file_type, sort_order, created_at)
                    VALUES (:content_item_id, :file_path, :file_name, :file_type, :sort_order, NOW())
                ");
                $stmt->execute([
                    'content_item_id' => $id,
                    'file_path'       => $path,
                    'file_name'       => basename($path),
                    'file_type'       => 'image',
                    'sort_order'      => $maxSort + 1,
                ]);
            }
        }
    }

    // Determine cover.
    // cover_source: 'existing:<media_id>' or 'new:<index>' or 'none'.
    $coverSource = $_POST['cover_source'] ?? '';
    if ($coverSource !== '') {
        if (strpos($coverSource, 'existing:') === 0) {
            $coverId = (int) substr($coverSource, strlen('existing:'));
            $cStmt = db()->prepare("SELECT file_path FROM media WHERE id = :id AND content_item_id = :cid");
            $cStmt->execute(['id' => $coverId, 'cid' => $id]);
            $cp = $cStmt->fetchColumn();
            if ($cp) {
                $item['featured_image'] = $cp;
            }
        } elseif (strpos($coverSource, 'new:') === 0) {
            $idx = (int) substr($coverSource, strlen('new:'));
            if (isset($newPaths[$idx])) {
                $item['featured_image'] = $newPaths[$idx];
            }
        } elseif ($coverSource === 'none') {
            $item['featured_image'] = '';
        }
    }

    // Remove featured image entirely (legacy single-image remove)
    if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
        if (!empty($item['featured_image'])) {
            delete_uploaded_file($item['featured_image']);
        }
        $item['featured_image'] = '';
    }
    
    if (empty($errors)) {
        // Auto-translate missing fields before updating
        $tr = fill_missing_translations($item, $auto_translate);
        $item = $tr['item'];
        $translation_warnings = $tr['warnings'];

        try {
            $stmt = db()->prepare("
                UPDATE content_items SET
                    rse_category = :rse_category,
                    slug = :slug,
                    title_ar = :title_ar, title_fr = :title_fr, title_en = :title_en,
                    summary_ar = :summary_ar, summary_fr = :summary_fr, summary_en = :summary_en,
                    body_ar = :body_ar, body_fr = :body_fr, body_en = :body_en,
                    featured_image = :featured_image,
                    video_url = :video_url,
                    status = :status,
                    is_featured = :is_featured,
                    published_at = :published_at,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
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
                'updated_by' => $_SESSION['user_id'],
                'id' => $id,
            ]);
            
            log_audit($_SESSION['user_id'], 'update', $type, $id);
            csrf_regenerate();
            $flashMsg = $lang === 'ar' ? 'تم التحديث بنجاح.' : ($lang === 'fr' ? 'Mis à jour avec succès.' : 'Updated successfully.');
            if (!empty($translation_warnings)) {
                $flashMsg .= ' ' . ($lang === 'ar' ? '(تحذير: فشلت بعض الترجمات التلقائية، راجع سجل الأخطاء.)' : ($lang === 'fr' ? '(Avertissement : certaines traductions automatiques ont échoué, vérifiez le journal d\'erreurs.)' : '(Warning: some auto-translations failed — check error log.)'));
            }
            set_flash('success', $flashMsg);
            redirect('edit.php?id=' . $id);
            
        } catch (PDOException $e) {
            error_log("Update content error: " . $e->getMessage());
            $errors[] = $lang === 'ar' ? 'خطأ في التحديث.' : ($lang === 'fr' ? 'Erreur de mise à jour.' : 'Update error.');
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
                <?= admin_breadcrumb($type, 'edit') ?>
                
                <div class="flex items-center justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-bold text-white"><?= e($pageTitle) ?></h1>
                    <div class="flex gap-2">
                        <a href="index.php?type=<?= e($type) ?>" class="text-sm text-emerald-400 hover:text-emerald-300 transition-colors">
                            &larr; <?= $lang === 'ar' ? 'العودة' : ($lang === 'fr' ? 'Retour' : 'Back') ?>
                        </a>
                        <a href="media.php?id=<?= $id ?>" class="glass-btn text-sm">
                            🖼️ <?= $lang === 'ar' ? 'إدارة الصور' : ($lang === 'fr' ? 'Gérer les images' : 'Manage images') ?>
                        </a>
                    </div>
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
                
                <?php $flash = get_flash(); if ($flash): ?>
                <div class="mb-6 p-4 rounded-lg border text-sm
                    <?= $flash['type'] === 'error'
                        ? 'bg-red-500/20 border-red-500/30 text-red-300'
                        : ($flash['type'] === 'warning'
                            ? 'bg-yellow-500/20 border-yellow-500/30 text-yellow-300'
                            : 'bg-emerald-600/30 border-emerald-500/30 text-emerald-300') ?>">
                    <?= e($flash['message']) ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?= csrf_field() ?>
                    
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?= $lang === 'ar' ? 'الرابط القصير' : ($lang === 'fr' ? 'Slug' : 'Slug') ?>
                        </label>
                        <input type="text" name="slug" value="<?= e($item['slug']) ?>" 
                               class="form-input font-mono text-sm" pattern="[a-z0-9-]+">
                    </div>
                    
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
                                    <label class="block text-sm font-medium text-emerald-200 mb-1">العنوان (<?= strtoupper($code) ?>)</label>
                                    <input type="text" name="title_<?= $code ?>" value="<?= e($item['title_' . $code]) ?>" class="form-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-emerald-200 mb-1">ملخص (<?= strtoupper($code) ?>)</label>
                                    <textarea id="summary_<?= $code ?>" name="summary_<?= $code ?>" rows="3" class="form-input" data-maxlength="300"><?= e($item['summary_' . $code]) ?></textarea>
                                    <div class="text-xs text-emerald-400 mt-1 text-left">
                                        <span id="summary_<?= $code ?>_counter">300</span> <?= $lang === 'ar' ? 'حرف متبقي' : ($lang === 'fr' ? 'caractères restants' : 'characters remaining') ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-emerald-200 mb-1">المحتوى (<?= strtoupper($code) ?>)</label>
                                    <textarea name="body_<?= $code ?>" rows="15" class="form-input font-mono text-sm"><?= e($item['body_' . $code]) ?></textarea>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?= $lang === 'ar' ? 'صور المقال (حتى ' . MAX_GALLERY_IMAGES . ' صورة)' : ($lang === 'fr' ? 'Images de l\'article (max ' . MAX_GALLERY_IMAGES . ')' : 'Article Images (up to ' . MAX_GALLERY_IMAGES . ')') ?>
                        </label>

                        <?php
                        // Merge existing + newly uploaded (preview only) into one cover-selector grid.
                        $coverCheckedId = null;
                        if (!empty($item['featured_image'])) {
                            foreach ($existingMedia as $m) {
                                if ($m['file_path'] === $item['featured_image']) { $coverCheckedId = $m['id']; break; }
                            }
                        }
                        ?>
                        <?php if (!empty($existingMedia)): ?>
                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-4">
                            <?php foreach ($existingMedia as $m): ?>
                            <div class="relative glass-card overflow-hidden group">
                                <div class="aspect-square overflow-hidden">
                                    <img src="<?= e(upload_url($m['file_path'])) ?>" alt="" class="w-full h-full object-cover">
                                </div>
                                <label class="flex items-center gap-1 p-1 text-xs text-emerald-200 cursor-pointer">
                                    <input type="radio" name="cover_source" value="existing:<?= $m['id'] ?>" <?= $m['id'] === $coverCheckedId ? 'checked' : '' ?>>
                                    <?= $lang === 'ar' ? 'غلاف' : ($lang === 'fr' ? 'Couverture' : 'Cover') ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <p class="text-xs text-emerald-300/40 mb-3"><?= $lang === 'ar' ? 'لا توجد صور بعد.' : ($lang === 'fr' ? 'Aucune image.' : 'No images yet.') ?></p>
                        <?php endif; ?>

                        <label class="block text-sm font-medium text-emerald-200 mb-1 mt-2">
                            <?= $lang === 'ar' ? 'إضافة صور جديدة' : ($lang === 'fr' ? 'Ajouter de nouvelles images' : 'Add new images') ?>
                        </label>
                        <input type="file" name="gallery_images[]" multiple accept="image/jpeg,image/png,image/webp" id="galleryInput"
                               data-max="<?= MAX_GALLERY_IMAGES ?>"
                               data-cover-name="cover_source" data-cover-prefix="new:"
                               class="block w-full text-sm text-white/70 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-600/30 file:text-emerald-300 hover:file:bg-emerald-600/40">
                        <div id="galleryPreview" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-3"></div>
                        <p id="galleryCount" class="text-xs text-emerald-400 mt-2"></p>
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?= $lang === 'ar' ? 'الصورة المحددة كـ "غلاف" تظهر في القوائم. يمكنك أيضاً إدارة الصور من زر "إدارة الصور".' : ($lang === 'fr' ? 'L\'image marquée « couverture » apparaît dans les listes. Gérez les images via « Gérer les images ».' : 'The image marked "cover" is shown in listings. Manage images via "Manage images".') ?>
                        </p>
                    </div>
                    
                    <!-- Video URL (only for Video type) -->
                    <?php if ($type === 'video'): ?>
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?= $lang === 'ar' ? 'رابط الفيديو (YouTube)' : ($lang === 'fr' ? 'URL de la vidéo (YouTube)' : 'Video URL (YouTube)') ?>
                        </label>
                        <input type="text" name="video_url" value="<?= e($item['video_url'] ?? '') ?>" dir="ltr"
                               class="form-input font-mono text-sm"
                               placeholder="https://www.youtube.com/watch?v=...">
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?= $lang === 'ar' ? 'يقبل جميع صيغ روابط YouTube: youtube.com/watch?v=… أو youtu.be/… أو embed/…' : ($lang === 'fr' ? 'Accepte tous les formats de lien YouTube : youtube.com/watch?v=…, youtu.be/… ou embed/…' : 'Accepts any YouTube link format: youtube.com/watch?v=…, youtu.be/…, or embed/…') ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- RSE Category (only for RSE type) -->
                    <?php if ($type === 'rse'): ?>
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?= $lang === 'ar' ? 'تصنيف المسؤولية المجتمعية' : ($lang === 'fr' ? 'Catégorie RSE' : 'RSE Category') ?>
                        </label>
                        <select name="rse_category" class="form-input">
                            <?php foreach (rse_category_labels($lang) as $catKey => $catLabels): ?>
                            <option value="<?= e($catKey) ?>" <?= ($item['rse_category'] ?? 'engagement_social') === $catKey ? 'selected' : '' ?>>
                                <?= e($catLabels[$lang] ?? $catLabels['fr']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?= $lang === 'ar' ? 'اختر تصنيفاً لعنصر المسؤولية المجتمعية' : ($lang === 'fr' ? 'Choisissez une catégorie pour cet élément RSE' : 'Choose a category for this RSE item') ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="glass-card-static p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-emerald-200 mb-2"><?= $lang === 'ar' ? 'الحالة' : ($lang === 'fr' ? 'Statut' : 'Status') ?></label>
                                <select name="status" class="form-input">
                                    <option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>><?= $lang === 'ar' ? 'مسودة' : ($lang === 'fr' ? 'Brouillon' : 'Draft') ?></option>
                                    <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>><?= $lang === 'ar' ? 'منشور' : ($lang === 'fr' ? 'Publié' : 'Published') ?></option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-emerald-200 mb-2"><?= $lang === 'ar' ? 'تاريخ النشر' : ($lang === 'fr' ? 'Date de publication' : 'Publish Date') ?></label>
                                <input type="datetime-local" name="published_at" value="<?= e(str_replace(' ', 'T', $item['published_at'] ?? date('Y-m-d H:i:s'))) ?>" class="form-input">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="flex items-center gap-2 text-sm text-emerald-200 cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" <?= $item['is_featured'] ? 'checked' : '' ?> class="rounded bg-white/5 border-white/20 text-emerald-500">
                                <?= $lang === 'ar' ? 'محتوًى مميز' : ($lang === 'fr' ? 'Contenu à la une' : 'Featured content') ?>
                            </label>
                        </div>
                        <?php if (defined('ENABLE_TRANSLATION') && ENABLE_TRANSLATION): ?>
                        <div class="mt-3 pt-3 border-t border-white/10">
                            <label class="flex items-center gap-2 text-sm text-emerald-200 cursor-pointer">
                                <input type="checkbox" name="auto_translate" value="1" <?= $auto_translate ? 'checked' : '' ?>
                                       class="rounded bg-white/5 border-white/20 text-emerald-500">
                                <?= $lang === 'ar' ? 'ترجمة تلقائية للحقول الفارغة' : ($lang === 'fr' ? 'Traduire automatiquement les champs vides' : 'Auto-translate empty fields') ?>
                                <span class="text-xs text-white/30">(LibreTranslate)</span>
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex gap-3 pt-4 border-t border-white/10">
                        <button type="submit" class="glass-btn glass-btn-primary"><?= $lang === 'ar' ? 'حفظ التغييرات' : ($lang === 'fr' ? 'Enregistrer' : 'Save Changes') ?></button>
                        <a href="index.php?type=<?= e($type) ?>" class="glass-btn bg-white/5"><?= $lang === 'ar' ? 'إلغاء' : ($lang === 'fr' ? 'Annuler' : 'Cancel') ?></a>
                    </div>
                </form>
            </main>
            
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>
    
    <script src="../../public/assets/js/admin.js"></script>
</body>
</html>