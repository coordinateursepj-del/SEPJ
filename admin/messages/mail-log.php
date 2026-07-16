<?php
require_once dirname(__DIR__,2).'/app/config/app.php';
require_once ROOT_PATH.'/app/core/db.php';
require_once ROOT_PATH.'/app/core/auth.php';
require_once ROOT_PATH.'/app/core/csrf.php';
require_once ROOT_PATH.'/app/core/helpers.php';
session_start_secure(); require_login();
$lang = current_lang();
$validStatuses = ['sent', 'failed'];
$status = in_array($_GET['status'] ?? '', $validStatuses) ? $_GET['status'] : '';
$page = max(1,(int)($_GET['page']??1));
$per = ADMIN_ITEMS_PER_PAGE;
$where = "WHERE 1=1"; $p=[];
if($status){$where.=" AND status=:s";$p['s']=$status;}
$count = db()->prepare("SELECT COUNT(*) FROM mail_log $where"); $count->execute($p); $total=(int)$count->fetchColumn(); $totalPages=max(1,ceil($total/$per));
$off = ($page-1)*$per;
$stmt = db()->prepare("SELECT * FROM mail_log $where ORDER BY sent_at DESC LIMIT :l OFFSET :o");
$stmt->bindValue(':l',$per,PDO::PARAM_INT); $stmt->bindValue(':o',$off,PDO::PARAM_INT);
foreach($p as $k=>$v)$stmt->bindValue(":$k",$v);
$stmt->execute(); $items = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="<?=e($lang)?>" dir="<?=dir_attribute($lang)?>"><head><meta charset="UTF-8"><title><?=$lang==='ar'?'سجل البريد':'Mail Log'?> - <?=e(APP_NAME)?></title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../public/assets/css/style.css"></head>
<body class="bg-gradient-to-br from-gray-900 via-emerald-950 to-gray-900 min-h-screen"><div class="blob blob-1"></div><div class="blob blob-2"></div>
<div class="relative z-10 flex h-screen"><?php include '../includes/sidebar.php';?><div class="flex-1 flex flex-col overflow-hidden"><?php include '../includes/header.php';?>
<main class="flex-1 overflow-y-auto p-6">
<a href="index.php" class="text-emerald-400 hover:text-emerald-300 mb-4 inline-block">&larr; <?=$lang==='ar'?'الرسائل':($lang==='fr'?'Messages':'Messages')?></a>
<h1 class="text-2xl font-bold text-white mb-6">📨 <?=$lang==='ar'?'سجل إرسال البريد':($lang==='fr'?"Journal d'envoi des emails":'Mail Send Log')?></h1>
<div class="flex gap-2 mb-4"><?php foreach([''=>($lang==='ar'?'الكل':($lang==='fr'?'Tous':'All')),'sent'=>($lang==='ar'?'ناجحة':($lang==='fr'?'Envoyés':'Sent')),'failed'=>($lang==='ar'?'فاشلة':($lang==='fr'?'Échoués':'Failed'))] as $k=>$l):?><a href="?<?=$k?'status='.$k:''?>" class="glass-btn text-sm <?=!$status&&!$k||$status===$k?'glass-btn-primary':''?>"><?=e($l)?></a><?php endforeach;?></div>
<div class="glass-card-static overflow-hidden"><?php if(empty($items)):?><div class="empty-state"><div class="empty-state-icon">📨</div><p><?=$lang==='ar'?'لا يوجد سجل':($lang==='fr'?"Aucun envoi":'No sends yet')?></p></div>
<?php else:?><div class="space-y-2 p-4"><?php foreach($items as $row):?><div class="p-4 rounded-lg bg-white/5 <?=$row['status']==='failed'?'border-l-2 border-red-500':'border-l-2 border-emerald-500'?>">
<div class="flex items-center justify-between gap-3 flex-wrap">
<div class="min-w-0">
<p class="text-white font-medium truncate"><?=e($row['to_address'])?>
<span class="ml-2 text-xs px-2 py-0.5 rounded-full <?=$row['status']==='failed'?'bg-red-500/20 text-red-300':'bg-emerald-500/20 text-emerald-300'?>"><?=e($row['status'])?></span>
</p>
<p class="text-sm text-emerald-200/60 truncate"><?=e($row['subject'])?></p>
<?php if($row['reply_to']):?><p class="text-xs text-emerald-300/40"><?=$lang==='fr'?'Répondre à':'Reply-To'?>: <?=e($row['reply_to'])?></p><?php endif;?>
</div>
<p class="text-xs text-emerald-300/40 shrink-0"><?=format_date($row['sent_at'],'d/m/Y H:i')?></p>
</div>
<?php if($row['status']==='failed' && $row['error_message']):?><p class="mt-2 text-xs text-red-300/80 bg-red-500/10 rounded p-2 font-mono break-all"><?=e($row['error_message'])?></p><?php endif;?>
</div><?php endforeach;?></div><?php endif;?></div>
<?php if($totalPages>1):?><div class="flex justify-center gap-2 mt-4"><?php for($i=1;$i<=$totalPages;$i++):?><a href="?page=<?=$i?><?=$status?'&status='.$status:''?>" class="px-3 py-1 rounded <?=$i===$page?'bg-emerald-600/30':'bg-white/5'?> text-white"><?=$i?></a><?php endfor;?></div><?php endif;?>
</main><?php include '../includes/footer.php';?></div></div>
<script src="../../public/assets/js/admin.js"></script></body></html>
