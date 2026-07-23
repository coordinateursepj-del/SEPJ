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
    
    // Read titles first so they're available for slug generation
    foreach (['title', 'summary', 'body'] as $field) {
        foreach (['ar', 'fr', 'en'] as $flang) {
            $key = "{$field}_{$flang}";
            if (isset($_POST[$key])) {
                $item[$key] = ($field === 'body') ? $_POST[$key] : trim($_POST[$key]);
            }
        }
    }
    
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
    $item['status'] = $_POST['status'] ?? 'draft';
    $item['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $item['published_at'] = $_POST['published_at'] ?? $item['published_at'];
    $item['rse_category'] = trim($_POST['rse_category'] ?? $item['rse_category'] ?? '');
    $item['video_url'] = trim($_POST['video_url'] ?? $item['video_url'] ?? '');
    $item['video_thumb'] = trim($_POST['video_thumb_path'] ?? $item['video_thumb'] ?? '');
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
    
    // Gallery images are uploaded individually via AJAX (ajax_upload.php). The
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

    // Collect images marked for deletion (deleted on save, not immediately)
    $deletedMediaIds = [];
    if (!empty($_POST['deleted_media_ids']) && is_array($_POST['deleted_media_ids'])) {
        foreach ($_POST['deleted_media_ids'] as $did) {
            $did = (int)$did;
            if ($did > 0) $deletedMediaIds[] = $did;
        }
    }

    try {
        $cntStmt = db()->prepare("SELECT COUNT(*) FROM media WHERE content_item_id = :id");
        $cntStmt->execute(['id' => $id]);
        $totalCount = (int) ($cntStmt->fetchColumn() ?: 0);
        // Marked-for-delete images don't count toward the limit
        $totalCount = max(0, $totalCount - count($deletedMediaIds));
    } catch (PDOException $e) {
        error_log("Edit content media count error: " . $e->getMessage());
        $totalCount = 0;
    }
    $typeMax = $type === 'video' ? 1 : MAX_GALLERY_IMAGES;
    if (($totalCount + count($uploadedPaths)) > $typeMax) {
        $errors[] = $lang === 'ar'
            ? 'يمكن رفع حتى ' . $typeMax . ' صورة كحد أقصى (لديك حالياً ' . $totalCount . ').'
            : ($lang === 'fr'
                ? 'Maximum ' . $typeMax . ' images (vous en avez déjà ' . $totalCount . ').'
                : 'Maximum ' . $typeMax . ' images (you already have ' . $totalCount . ').');
        $uploadedPaths = array_slice($uploadedPaths, 0, $typeMax - $totalCount);
    }

    // Determine cover: a newly uploaded path (cover_path) or an existing media id
    // (cover_media_id). If none chosen, the first existing gallery image wins.
    $coverPath = '';
    $coverPostedPath = trim((string) ($_POST['cover_path'] ?? ''));
    $coverMediaId   = isset($_POST['cover_media_id']) ? (int) $_POST['cover_media_id'] : 0;
    if ($coverPostedPath !== '' && in_array($coverPostedPath, $uploadedPaths, true)) {
        $coverPath = $coverPostedPath;
    } elseif ($coverMediaId > 0) {
        try {
            $cStmt = db()->prepare("SELECT file_path FROM media WHERE id = :id AND content_item_id = :cid");
            $cStmt->execute(['id' => $coverMediaId, 'cid' => $id]);
            $cp = $cStmt->fetchColumn();
            if ($cp) {
                $coverPath = $cp;
            }
        } catch (PDOException $e) {
            error_log("Edit content cover lookup error: " . $e->getMessage());
        }
    }
    if ($coverPath === '' && !empty($item['featured_image'])) {
        $coverPath = $item['featured_image'];
    }
    $item['featured_image'] = $coverPath;
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
            $hasThumbCol = table_column_exists('content_items', 'video_thumb');
            $set = [
                'rse_category = :rse_category',
                'slug = :slug',
                'title_ar = :title_ar', 'title_fr = :title_fr', 'title_en = :title_en',
                'summary_ar = :summary_ar', 'summary_fr = :summary_fr', 'summary_en = :summary_en',
                'body_ar = :body_ar', 'body_fr = :body_fr', 'body_en = :body_en',
                'featured_image = :featured_image',
                'video_url = :video_url',
            ];
            $params = [
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
                $set[] = 'video_thumb = :video_thumb';
                $params['video_thumb'] = $item['video_thumb'] !== '' ? $item['video_thumb'] : null;
            }
            $set[] = 'status = :status';
            $set[] = 'is_featured = :is_featured';
            $set[] = 'published_at = :published_at';
            $set[] = 'updated_by = :updated_by';
            $set[] = 'updated_at = NOW()';
            $params['status'] = $item['status'];
            $params['is_featured'] = $item['is_featured'];
            $params['published_at'] = $item['status'] === 'published' ? $item['published_at'] : null;
            $params['updated_by'] = $_SESSION['user_id'];
            $params['id'] = $id;

            $stmt = db()->prepare("UPDATE content_items SET " . implode(', ', $set) . " WHERE id = :id");
            $stmt->execute($params);
            
            // Delete marked media files and database rows
            if (!empty($deletedMediaIds)) {
                $placeholders = implode(',', array_fill(0, count($deletedMediaIds), '?'));
                $delStmt = db()->prepare("SELECT id, file_path FROM media WHERE id IN ($placeholders) AND content_item_id = ?");
                $delParams = array_merge($deletedMediaIds, [$id]);
                $delStmt->execute($delParams);
                while ($dm = $delStmt->fetch()) {
                    if (!empty($dm['file_path'])) {
                        delete_uploaded_file($dm['file_path']);
                    }
                }
                $delMediaStmt = db()->prepare("DELETE FROM media WHERE id IN ($placeholders) AND content_item_id = ?");
                $delMediaStmt->execute($delParams);
                
                // If the cover image was among the deleted ones, reset featured_image
                if (in_array($coverMediaId, $deletedMediaIds) || !empty($item['featured_image'])) {
                    // Check if the current featured image still exists in media
                    $checkCover = db()->prepare("SELECT COUNT(*) FROM media WHERE content_item_id = ? AND file_path = ?");
                    $checkCover->execute([$id, $item['featured_image']]);
                    if ((int)$checkCover->fetchColumn() === 0) {
                        // Pick the first remaining image as cover (if any)
                        $firstMedia = db()->prepare("SELECT file_path FROM media WHERE content_item_id = ? ORDER BY sort_order ASC, created_at ASC LIMIT 1");
                        $firstMedia->execute([$id]);
                        $firstPath = $firstMedia->fetchColumn();
                        $item['featured_image'] = $firstPath ?: '';
                        // Update the featured_image in the content item
                        $updCover = db()->prepare("UPDATE content_items SET featured_image = :fi WHERE id = :id");
                        $updCover->execute(['fi' => $item['featured_image'], 'id' => $id]);
                    }
                }
            }

            // Create media rows for each newly uploaded image, with a real content id.
            $insMedia = db()->prepare("INSERT INTO media (content_item_id, file_path, file_name, file_type, sort_order, created_at) VALUES (:cid, :path, :name, 'image', :so, NOW())");
            foreach ($uploadedPaths as $idx => $p) {
                $name = basename($p);
                $insMedia->execute(['cid' => $id, 'path' => $p, 'name' => $name, 'so' => $totalCount + $idx]);
            }

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
                                    <label class="block text-sm font-medium text-emerald-200 mb-1">
                                        المحتوى (<?= strtoupper($code) ?>)
                                        <button type="button" class="ai-generate-btn hidden" data-lang="<?= $code ?>"
                                                title="<?= $lang === 'ar' ? 'توليد العنوان والملخص تلقائياً' : ($lang === 'fr' ? 'Générer le titre et le résumé automatiquement' : 'Auto-generate title and summary') ?>">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="inline-block align-middle"><path d="M12 2l2.4 7.2L22 12l-7.6 2.8L12 22l-2.4-7.2L2 12l7.6-2.8z"/></svg>
                                            <span class="align-middle"><?= $lang === 'ar' ? 'توليد' : ($lang === 'fr' ? 'Générer' : 'Generate') ?></span>
                                        </button>
                                        <span class="ai-error hidden text-red-400 text-xs"></span>
                                    </label>
                                    <textarea id="body_<?= $code ?>" name="body_<?= $code ?>" rows="15" class="form-input font-mono text-sm" data-ai-source="true"><?= e($item['body_' . $code]) ?></textarea>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
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
                                   data-max="1" data-content-id="<?= $id ?>" data-mode="edit">
                        </div>

                        <?php if (!empty($existingMedia)): ?>
                        <div id="existingGallery" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-4">
                            <?php foreach ($existingMedia as $m):
                            $isCover = $m['id'] === $coverCheckedId;
                            $coverLabel = $lang === 'ar' ? 'غلاف' : ($lang === 'fr' ? 'Une' : 'Cover');
                            $deleteLabel = $lang === 'ar' ? 'سيتم الحذف' : ($lang === 'fr' ? 'Supprimé' : 'To delete');
                            ?>
                            <div class="gallery-item<?= $isCover ? ' is-cover' : '' ?>" data-media-id="<?= $m['id'] ?>">
                                <div class="gallery-item-img">
                                    <img src="<?= e(upload_url($m['file_path'])) ?>" alt="">
                                    <span class="gallery-item-badge"><?= $coverLabel ?></span>
                                    <span class="gallery-item-delete-badge"><?= $deleteLabel ?></span>
                                </div>
                                <div class="gallery-item-actions">
                                    <button type="button" class="gallery-btn gallery-btn-cover<?= $isCover ? ' is-active' : '' ?>" onclick="toggleCover(this, <?= $m['id'] ?>, 'media')">
                                        <svg viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        <?= $coverLabel ?>
                                    </button>
                                    <button type="button" class="gallery-btn gallery-btn-delete" onclick="markExistingForDelete(this, <?= $m['id'] ?>)">
                                        <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    </button>
                                </div>
                                <input type="radio" name="cover_media_id" value="<?= $m['id'] ?>" <?= $isCover ? 'checked' : '' ?> class="hidden">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <p class="text-xs text-emerald-300/40 mt-3 mb-3"><?= $lang === 'ar' ? 'لا توجد صورة مصغرة بعد.' : ($lang === 'fr' ? 'Aucune vignette.' : 'No thumbnail yet.') ?></p>
                        <?php endif; ?>

                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?= __('video_thumbnail_help', $lang) ?>
                        </p>
                        <div id="galleryPreview" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-3"></div>
                        <p id="galleryCount" class="text-xs text-emerald-400 mt-2"></p>
                        <!-- Holds AJAX-uploaded image paths; cover path posted as cover_path -->
                        <div id="galleryFields"></div>
                    </div>
                    <?php else: ?>
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?= __('article_images', $lang) ?> (<?= $lang === 'ar' ? 'حتى ' . MAX_GALLERY_IMAGES . ' صورة' : ($lang === 'fr' ? 'max ' . MAX_GALLERY_IMAGES : 'up to ' . MAX_GALLERY_IMAGES) ?>)
                        </label>

                        <?php
                        // Existing gallery with a cover radio per image.
                        $coverCheckedId = null;
                        if (!empty($item['featured_image'])) {
                            foreach ($existingMedia as $m) {
                                if ($m['file_path'] === $item['featured_image']) { $coverCheckedId = $m['id']; break; }
                            }
                        }
                        ?>
                        <?php if (!empty($existingMedia)): ?>
                        <div id="existingGallery" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-4">
                            <?php foreach ($existingMedia as $m):
                            $isCover = $m['id'] === $coverCheckedId;
                            $coverLabel = $lang === 'ar' ? 'غلاف' : ($lang === 'fr' ? 'Une' : 'Cover');
                            $deleteLabel = $lang === 'ar' ? 'سيتم الحذف' : ($lang === 'fr' ? 'Supprimé' : 'To delete');
                            ?>
                            <div class="gallery-item<?= $isCover ? ' is-cover' : '' ?>" data-media-id="<?= $m['id'] ?>">
                                <div class="gallery-item-img">
                                    <img src="<?= e(upload_url($m['file_path'])) ?>" alt="">
                                    <span class="gallery-item-badge"><?= $coverLabel ?></span>
                                    <span class="gallery-item-delete-badge"><?= $deleteLabel ?></span>
                                </div>
                                <div class="gallery-item-actions">
                                    <button type="button" class="gallery-btn gallery-btn-cover<?= $isCover ? ' is-active' : '' ?>" onclick="toggleCover(this, <?= $m['id'] ?>, 'media')">
                                        <svg viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        <?= $coverLabel ?>
                                    </button>
                                    <button type="button" class="gallery-btn gallery-btn-delete" onclick="markExistingForDelete(this, <?= $m['id'] ?>)">
                                        <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    </button>
                                </div>
                                <input type="radio" name="cover_media_id" value="<?= $m['id'] ?>" <?= $isCover ? 'checked' : '' ?> class="hidden">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <p class="text-xs text-emerald-300/40 mb-3"><?= $lang === 'ar' ? 'لا توجد صور بعد.' : ($lang === 'fr' ? 'Aucune image.' : 'No images yet.') ?></p>
                        <?php endif; ?>

                        <label class="block text-sm font-medium text-emerald-200 mb-1 mt-2">
                            <?= $lang === 'ar' ? 'إضافة صور جديدة' : ($lang === 'fr' ? 'Ajouter de nouvelles images' : 'Add new images') ?>
                        </label>
                        <div class="file-input-wrap">
                            <span class="file-input-btn">
                                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                <?= $lang === 'ar' ? 'اختر صور' : ($lang === 'fr' ? 'Choisir' : 'Browse') ?>
                            </span>
                            <span class="file-input-name" data-empty="<?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?>"><?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?></span>
                            <input type="file" id="galleryInput" multiple accept="image/jpeg,image/png,image/webp"
                                   data-max="<?= MAX_GALLERY_IMAGES ?>" data-content-id="<?= $id ?>" data-mode="edit">
                        </div>
                        <div id="galleryPreview" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-3"></div>
                        <p id="galleryCount" class="text-xs text-emerald-400 mt-2"></p>
                        <p class="text-xs text-emerald-300/40 mt-1">
                            <?= $lang === 'ar' ? 'تُرفع كل صورة على حدة. الصورة المحددة كـ "غلاف" تظهر في القوائم. يمكنك أيضاً إدارة الصور من زر "إدارة الصور".' : ($lang === 'fr' ? 'Chaque image est envoyée séparément. Celle marquée « couverture » apparaît dans les listes. Gérez les images via « Gérer les images ».' : 'Each image is uploaded separately. The one marked "cover" is shown in listings. Manage images via "Manage images".') ?>
                        </p>
                        <!-- Holds AJAX-uploaded image paths; cover path posted as cover_path -->
                        <div id="galleryFields"></div>
                    </div>
                    <?php endif; ?>
                    
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

                    <!-- Optional YouTube video (for Posts/News, shown in the article) -->
                    <?php if ($type === 'post'): ?>
                    <div class="glass-card-static p-4">
                        <label class="block text-sm font-medium text-emerald-200 mb-2">
                            <?= __('video_section_label', $lang) ?>
                        </label>
                        <input type="text" name="video_url" value="<?= e($item['video_url'] ?? '') ?>" dir="ltr"
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
                                   data-content-id="<?= $id ?>" data-mode="edit">
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
                        <input type="hidden" name="video_thumb_path" id="videoThumbPath" value="<?= e($item['video_thumb'] ?? '') ?>">
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
                                <span class="text-xs text-white/30">(<?= defined('TRANSLATION_PROVIDER') && strtolower(TRANSLATION_PROVIDER) === 'libretranslate' ? 'LibreTranslate' : 'Google Translate' ?>)</span>
                            </label>
                            <div class="flex items-center gap-3 mt-2">
                                <button type="button" id="translateNowBtn"
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all
                                               bg-emerald-600/20 text-emerald-300 border-emerald-500/30
                                               hover:bg-emerald-600/30">
                                    <span class="flex items-center gap-1.5">
                                        <svg viewBox="0 0 20 20" width="14" height="14" fill="currentColor"><path d="M7.41 2l-4.5 9h2.08l.8-1.79h4.03l.8 1.79h2.08L9.59 2H7.41zm-.73 5.21L9 4l2.32 3.21H6.68zM2 17h16v2H2v-2zm3-4h10l-1.5 2h-7L5 13z"/></svg>
                                        <?= $lang === 'ar' ? 'توليد الترجمة الآن' : ($lang === 'fr' ? 'Générer la traduction' : 'Generate translation now') ?>
                                    </span>
                                </button>
                                <span id="translateStatus" class="text-xs text-white/50"></span>
                            </div>
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

    <script>window.SEPJ_CSRF = <?= json_encode(csrf_token()) ?>;</script>
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
                    btn.classList.toggle('hidden', count < 5);
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

        // Watch language tab switches
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

                fetch('ajax/ai_generate.php', {
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
                            errSpan.textContent = data.error || '<?= $lang === 'ar' ? 'فشل التوليد' : ($lang === 'fr' ? 'Échec de génération' : 'Generation failed') ?>';
                            errSpan.classList.remove('hidden');
                            setTimeout(function() { errSpan.classList.add('hidden'); }, 8000);
                        }
                    }
                })
                .catch(function(err) {
                    var errSpan = btn.parentElement.querySelector('.ai-error');
                    if (errSpan) {
                        errSpan.textContent = '<?= $lang === 'ar' ? 'خطأ في الاتصال' : ($lang === 'fr' ? 'Erreur de connexion' : 'Connection error') ?>';
                        errSpan.classList.remove('hidden');
                        setTimeout(function() { errSpan.classList.add('hidden'); }, 8000);
                    }
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.classList.remove('loading');
                    btn.querySelector('span').textContent = '<?= $lang === 'ar' ? 'توليد' : ($lang === 'fr' ? 'Générer' : 'Generate') ?>';
                });
            });
        });

        updateButtons();
    });
    </script>
</body>
</html>