<?php require_once 'includes/header.php'; $lang=current_lang(); $l=['ar'=>'ar','fr'=>'fr','en'=>'en'][$lang]??'ar'; try{$stmt=db()->prepare("SELECT id,slug,COALESCE(NULLIF(title_{$l},''),title_ar) AS t,COALESCE(NULLIF(summary_{$l},''),summary_ar) AS s,featured_image,published_at FROM content_items WHERE type='project' AND status='published' ORDER BY published_at DESC");$stmt->execute();$items=$stmt->fetchAll();}catch(Exception$e){$items=[];} ?>
<main id="main-content"><div class="page-hero"><div class="max-w-7xl mx-auto px-4"><h1><i class="fa-solid fa-diagram-project text-emerald-400" aria-hidden="true"></i><?= __('nav_projects',$lang) ?></h1></div></div>
<section class="py-8 relative z-10"><div class="max-w-7xl mx-auto px-4">
<?php if(empty($items)):?><div class="empty-state"><div class="empty-state-icon" aria-hidden="true"><i class="fa-solid fa-diagram-project"></i></div><p><?= __('no_results',$lang) ?></p></div>
<?php else:?><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
<?php foreach($items as $i):?><a href="page.php?slug=<?= e($i['slug']) ?>" class="glass-card overflow-hidden group reveal">
<?php if($i['featured_image']):?><div class="img-card"><img src="<?= e(upload_url($i['featured_image'])) ?>" alt="<?= e($i['t']) ?>" loading="lazy"></div><?php endif;?>
<div class="p-4"><h3 class="font-semibold text-white mb-2"><?= e($i['t']) ?></h3><p class="text-sm text-emerald-200/70"><?= e(excerpt($i['s']??'',120)) ?></p></div></a>
<?php endforeach;?></div><?php endif;?>
</div></section></main>
<?php include 'includes/footer.php';?>