<?php
require_once dirname(__DIR__,2).'/app/config/app.php';
require_once ROOT_PATH.'/app/core/db.php';
require_once ROOT_PATH.'/app/core/auth.php';
require_once ROOT_PATH.'/app/core/csrf.php';
require_once ROOT_PATH.'/app/core/helpers.php';
session_start_secure(); require_login();
$lang = current_lang();
$validStatuses = ['new', 'read', 'archived'];
$status = in_array($_GET['status'] ?? '', $validStatuses) ? $_GET['status'] : '';
$page = max(1,(int)($_GET['page']??1));
$per = ADMIN_ITEMS_PER_PAGE;
$where = "WHERE 1=1"; $p=[];
if($status){$where.=" AND status=:s";$p['s']=$status;}
$count = db()->prepare("SELECT COUNT(*) FROM contact_messages $where"); $count->execute($p); $total=(int)$count->fetchColumn(); $totalPages=max(1,ceil($total/$per));
$off = ($page-1)*$per;
$stmt = db()->prepare("SELECT * FROM contact_messages $where ORDER BY created_at DESC LIMIT :l OFFSET :o");
$stmt->bindValue(':l',$per,PDO::PARAM_INT); $stmt->bindValue(':o',$off,PDO::PARAM_INT);
foreach($p as $k=>$v)$stmt->bindValue(":$k",$v);
$stmt->execute(); $items = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="<?=e($lang)?>" dir="<?=dir_attribute($lang)?>" data-theme="light"><head><meta charset="UTF-8"><title><?=$lang==='ar'?'الرسائل':'Messages'?> - <?=e(APP_NAME)?></title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../public/assets/css/style.css"></head>
<body class="admin-theme-bg min-h-screen"><div class="blob blob-1"></div><div class="blob blob-2"></div>
<div class="relative z-10 flex h-screen"><?php include '../includes/sidebar.php';?><div class="flex-1 flex flex-col overflow-hidden"><?php include '../includes/header.php';?>
<main class="flex-1 overflow-y-auto p-6"><div class="flex items-center justify-between mb-6 flex-wrap gap-2"><h1 class="text-2xl font-bold text-white">✉️ <?=$lang==='ar'?'الرسائل':($lang==='fr'?'Messages':'Messages')?></h1>
<a href="mail-log.php" class="glass-btn text-sm">📨 <?=$lang==='ar'?'سجل إرسال البريد':($lang==='fr'?"Journal d'envoi":'Mail log')?></a></div>
<div class="flex gap-2 mb-4"><?php foreach([''=>($lang==='ar'?'الكل':($lang==='fr'?'Tous':'All')),'new'=>($lang==='ar'?'جديدة':($lang==='fr'?'Nouveau':'New')),'read'=>($lang==='ar'?'مقروءة':($lang==='fr'?'Lu':'Read')),'archived'=>($lang==='ar'?'مؤرشفة':($lang==='fr'?'Archivé':'Archived'))] as $k=>$l):?><a href="?<?=$k?'status='.$k:''?>" class="glass-btn text-sm <?=!$status&&!$k||$status===$k?'glass-btn-primary':''?>"><?=e($l)?></a><?php endforeach;?></div>
<div class="glass-card-static overflow-hidden"><?php if(empty($items)):?><div class="empty-state"><div class="empty-state-icon">✉️</div><p><?=$lang==='ar'?'لا توجد رسائل':($lang==='fr'?'Aucun message':'No messages')?></p></div>
<?php else:?><div class="space-y-2 p-4"><?php foreach($items as $msg):?><div class="flex items-center justify-between p-4 rounded-lg bg-white/5 hover:bg-white/10 transition-colors <?=$msg['status']==='new'?'border-l-2 border-emerald-500':''?>">
<div class="min-w-0 flex-1"><p class="text-white font-medium"><?=e($msg['name'])?><?php if($msg['status']==='new'):?><span class="ml-2 text-xs bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full"><?=$lang==='ar'?'جديدة':($lang==='fr'?'Nouveau':'New')?></span><?php endif;?></p>
<p class="text-sm text-emerald-200/60"><?=e($msg['subject']??'—')?></p><p class="text-xs text-emerald-300/40"><?=e($msg['email'])?> • <?=format_date($msg['created_at'],'d/m/Y H:i')?></p></div>
<div class="flex gap-2 shrink-0"><a href="view.php?id=<?=$msg['id']?>" class="glass-btn text-xs py-1.5">👁️</a>
<a href="update-status.php?id=<?=$msg['id']?>&status=read&csrf_token=<?=csrf_token()?>" class="glass-btn text-xs py-1.5 <?=$msg['status']==='read'?'bg-emerald-600/20':''?>">📖</a>
<a href="update-status.php?id=<?=$msg['id']?>&status=archived&csrf_token=<?=csrf_token()?>" class="glass-btn text-xs py-1.5">📦</a>
<a href="delete.php?id=<?=$msg['id']?>&csrf_token=<?=csrf_token()?>" class="glass-btn text-xs py-1.5 bg-red-500/20" onclick="return confirm('Delete?')">🗑️</a></div></div><?php endforeach;?></div><?php endif;?></div>
<?php if($totalPages>1):?><div class="flex justify-center gap-2 mt-4"><?php for($i=1;$i<=$totalPages;$i++):?><a href="?page=<?=$i?><?=$status?'&status='.$status:''?>" class="px-3 py-1 rounded <?=$i===$page?'bg-emerald-600/30':'bg-white/5'?> text-white"><?=$i?></a><?php endfor;?></div><?php endif;?>
</main><?php include '../includes/footer.php';?></div></div>
<script src="../../public/assets/js/admin.js"></script></body></html>