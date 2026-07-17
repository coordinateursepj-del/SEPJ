<?php
require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
session_start_secure(); require_login();

$lang = current_lang();
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM media WHERE id = :id");
$stmt->execute(['id' => $id]);
$media = $stmt->fetch();
if (!$media) { redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = db()->prepare("UPDATE media SET alt_ar=:alt_ar, alt_fr=:alt_fr, alt_en=:alt_en, caption_ar=:caption_ar, caption_fr=:caption_fr, caption_en=:caption_en, sort_order=:sort_order WHERE id=:id");
    $stmt->execute([
        'alt_ar'     => trim($_POST['alt_ar']     ?? ''),
        'alt_fr'     => trim($_POST['alt_fr']     ?? ''),
        'alt_en'     => trim($_POST['alt_en']     ?? ''),
        'caption_ar' => trim($_POST['caption_ar'] ?? ''),
        'caption_fr' => trim($_POST['caption_fr'] ?? ''),
        'caption_en' => trim($_POST['caption_en'] ?? ''),
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'id'         => $id,
    ]);
    csrf_regenerate();
    set_flash('success', $lang === 'ar' ? 'تم التحديث.' : ($lang === 'fr' ? 'Mis à jour.' : 'Updated.'));
    $return = $_GET['return'] ?? '';
    $contentId = $_GET['content_id'] ?? 0;
    if ($return === 'content' && $contentId) { redirect("../content/media.php?id={$contentId}"); }
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" data-theme="light"><head><meta charset="UTF-8"><title><?= $lang==='ar'?'تعديل الصورة':'Edit Image' ?> - <?= e(APP_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../public/assets/css/style.css"></head>
<body class="admin-theme-bg min-h-screen">
<div class="blob blob-1"></div><div class="blob blob-2"></div>
<div class="relative z-10 flex h-screen">
<?php include '../includes/sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include '../includes/header.php'; ?>
<main class="flex-1 overflow-y-auto p-6">
<h1 class="text-2xl font-bold text-white mb-6">✏️ <?= $lang==='ar'?'تعديل الصورة':($lang==='fr'?'Modifier':'Edit Image') ?></h1>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
<div class="glass-card-static p-4"><img src="<?= e(upload_url($media['file_path'])) ?>" class="w-full rounded-lg"></div>
<div class="glass-card-static p-4">
<form method="POST"><?= csrf_field() ?>
<div class="space-y-4">
<?php foreach (['ar'=>['alt_ar'=>'النص البديل (عربي)','caption_ar'=>'التعليق (عربي)'],'fr'=>['alt_fr'=>'Alt (Français)','caption_fr'=>'Légende (Français)'],'en'=>['alt_en'=>'Alt (English)','caption_en'=>'Caption (English)']] as $code => $fields): ?>
<?php foreach ($fields as $field => $label): ?>
<div><label class="block text-sm text-emerald-200 mb-1"><?= $label ?></label>
<input type="text" name="<?= $field ?>" value="<?= e($media[$field]??'') ?>" class="form-input text-sm"></div>
<?php endforeach; endforeach; ?>
<div><label class="block text-sm text-emerald-200 mb-1"><?= $lang==='ar'?'الترتيب':($lang==='fr'?'Ordre':'Sort Order') ?></label>
<input type="number" name="sort_order" value="<?= (int)($media['sort_order']??0) ?>" class="form-input text-sm w-24"></div>
<button type="submit" class="glass-btn"><?= $lang==='ar'?'حفظ':($lang==='fr'?'Enregistrer':'Save') ?></button>
</div></form></div></div>
</main>
<?php include '../includes/footer.php'; ?>
</div></div>
<script src="../../public/assets/js/admin.js"></script>
</body></html>