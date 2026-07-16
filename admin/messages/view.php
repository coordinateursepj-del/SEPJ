<?php require_once dirname(__DIR__,2).'/app/config/app.php'; require_once ROOT_PATH.'/app/core/db.php'; require_once ROOT_PATH.'/app/core/auth.php'; require_once ROOT_PATH.'/app/core/csrf.php'; require_once ROOT_PATH.'/app/core/helpers.php'; session_start_secure(); require_login(); $id=(int)($_GET['id']??0); $lang=current_lang(); $stmt=db()->prepare("SELECT * FROM contact_messages WHERE id=:id"); $stmt->execute(['id'=>$id]); $msg=$stmt->fetch(); if(!$msg){redirect('index.php');}
// Mark as read automatically
if($msg['status']==='new'){db()->prepare("UPDATE contact_messages SET status='read' WHERE id=:id")->execute(['id'=>$id]);}
?>
<!DOCTYPE html><html lang="<?=e($lang)?>" dir="<?=dir_attribute($lang)?>"><head><meta charset="UTF-8"><title><?=$lang==='ar'?'الرسالة':'Message'?> - <?=e(APP_NAME)?></title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../public/assets/css/style.css"></head>
<body class="bg-gradient-to-br from-gray-900 via-emerald-950 to-gray-900 min-h-screen">
<div class="blob blob-1"></div><div class="blob blob-2"></div>
<div class="relative z-10 flex h-screen"><?php include '../includes/sidebar.php';?><div class="flex-1 flex flex-col overflow-hidden"><?php include '../includes/header.php';?>
<main class="flex-1 overflow-y-auto p-6">
<a href="index.php" class="text-emerald-400 hover:text-emerald-300 mb-4 inline-block">&larr; <?=$lang==='ar'?'العودة':($lang==='fr'?'Retour':'Back')?></a>
<div class="glass-card-static p-6 max-w-2xl"><div class="space-y-4">
<div><span class="text-xs text-emerald-300/60"><?=$lang==='ar'?'الاسم':($lang==='fr'?'Nom':'Name')?></span><p class="text-white font-medium"><?=e($msg['name'])?></p></div>
<div><span class="text-xs text-emerald-300/60">Email</span><p class="text-white"><?=e($msg['email'])?></p></div>
<?php if($msg['phone']):?><div><span class="text-xs text-emerald-300/60"><?=$lang==='ar'?'الهاتف':($lang==='fr'?'Téléphone':'Phone')?></span><p class="text-white"><?=e($msg['phone'])?></p></div><?php endif;?>
<?php if($msg['subject']):?><div><span class="text-xs text-emerald-300/60"><?=$lang==='ar'?'الموضوع':($lang==='fr'?'Sujet':'Subject')?></span><p class="text-white"><?=e($msg['subject'])?></p></div><?php endif;?>
<div><span class="text-xs text-emerald-300/60"><?=$lang==='ar'?'التاريخ':($lang==='fr'?'Date':'Date')?></span><p class="text-white"><?=format_date($msg['created_at'],'d/m/Y H:i')?></p></div>
<div><span class="text-xs text-emerald-300/60"><?=$lang==='ar'?'الرسالة':($lang==='fr'?'Message':'Message')?></span><p class="text-white/80 bg-white/5 rounded-lg p-4 mt-1 leading-relaxed"><?=e($msg['message'])?></p></div>
</div>
<div class="flex gap-2 mt-6 pt-4 border-t border-white/10">
<a href="update-status.php?id=<?=$msg['id']?>&status=archived&csrf_token=<?=csrf_token()?>" class="glass-btn text-sm">📦 <?=$lang==='ar'?'أرشفة':($lang==='fr'?'Archiver':'Archive')?></a>
<a href="delete.php?id=<?=$msg['id']?>&csrf_token=<?=csrf_token()?>" class="glass-btn text-sm bg-red-500/20" onclick="return confirm('Delete?')">🗑️ <?=$lang==='ar'?'حذف':($lang==='fr'?'Supprimer':'Delete')?></a>
</div></div></main>
<?php include '../includes/footer.php';?></div></div>
<script src="../../public/assets/js/admin.js"></script></body></html>