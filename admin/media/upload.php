<?php
require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/upload.php';

session_start_secure();
require_login();
$lang = current_lang();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $uploadedCount = 0;
    if (isset($_FILES['images'])) {
        $results = upload_multiple_files($_FILES['images'], 'gallery');
        foreach ($results as $result) {
            if ($result['success']) {
                $stmt = db()->prepare("INSERT INTO media (file_path, file_name, file_type, sort_order, created_at) VALUES (:path, :name, :type, 0, NOW())");
                $stmt->execute(['path' => $result['path'], 'name' => basename($result['path']), 'type' => 'image']);
                $uploadedCount++;
            }
        }
    }
    csrf_regenerate();
    if ($uploadedCount > 0) {
        set_flash('success', $lang === 'ar' ? "تم رفع {$uploadedCount} صورة." : ($lang === 'fr' ? "{$uploadedCount} image(s) téléchargée(s)." : "{$uploadedCount} image(s) uploaded."));
    } else {
        set_flash('error', $lang === 'ar' ? 'لم يتم رفع أي صورة. تحقق من نوع الملف وحجمه.' : ($lang === 'fr' ? 'Aucune image téléchargée.' : 'No images uploaded. Check file type and size.'));
    }
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" data-theme="light"><head><meta charset="UTF-8"><title>Upload - <?= e(APP_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../public/assets/css/style.css"></head>
<body class="admin-theme-bg min-h-screen">
<div class="blob blob-1"></div><div class="blob blob-2"></div>
<div class="relative z-10 flex h-screen">
<?php include '../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden pt-16">
        <?php include '../includes/header.php'; ?>
<main class="flex-1 overflow-y-auto p-6">
<h1 class="text-2xl font-bold text-white mb-6">🖼️ <?= $lang === 'ar' ? 'رفع الصور' : ($lang === 'fr' ? 'Télécharger' : 'Upload Images') ?></h1>
<div class="glass-card-static p-6 max-w-2xl">
<form method="POST" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="file-input-wrap">
    <span class="file-input-btn">
        <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
        <?= $lang === 'ar' ? 'اختر صور' : ($lang === 'fr' ? 'Choisir' : 'Browse') ?>
    </span>
    <span class="file-input-name" data-empty="<?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?>"><?= $lang === 'ar' ? 'لم يتم اختيار ملف' : ($lang === 'fr' ? 'Aucun fichier' : 'No file chosen') ?></span>
    <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
</div>
<button type="submit" class="glass-btn"><?= $lang === 'ar' ? 'رفع' : ($lang === 'fr' ? 'Télécharger' : 'Upload') ?></button>
</form>
</div>
</main>
<?php include '../includes/footer.php'; ?>
</div></div>
</body></html>