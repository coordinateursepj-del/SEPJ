<?php require_once 'includes/header.php'; $lang=current_lang(); $l=['ar'=>'ar','fr'=>'fr','en'=>'en'][$lang]??'ar'; try{$items=db()->query("SELECT id,slug,COALESCE(NULLIF(title_{$l},''),title_ar) AS t,COALESCE(NULLIF(body_{$l},''),body_ar) AS b,featured_image,video_url FROM content_items WHERE type='video' AND status='published' ORDER BY created_at DESC")->fetchAll();}catch(Exception$e){$items=[];} ?>
<main id="main-content"><div class="page-hero"><div class="max-w-4xl mx-auto px-4"><h1><i class="fa-solid fa-video text-emerald-400" aria-hidden="true"></i><?= __('nav_videos',$lang) ?></h1></div></div>
<section class="py-8 relative z-10"><div class="max-w-7xl mx-auto px-4"><?php if(empty($items)):?><div class="empty-state"><div class="empty-state-icon" aria-hidden="true"><i class="fa-solid fa-video"></i></div><p><?= __('no_results',$lang)?></p></div><?php else:?><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"><?php foreach($items as $i): $embed = youtube_embed_url($i['video_url'] ?? '') ?? youtube_embed_url($i['b'] ?? ''); $thumb = youtube_thumbnail_url($i['video_url'] ?? '', !empty($i['video_thumb']) ? upload_url($i['video_thumb']) : (!empty($i['featured_image']) ? upload_url($i['featured_image']) : null));?><div class="glass-card p-4">
<?php if($embed):?>
<div class="video-thumb rounded-lg overflow-hidden mb-3" data-embed="<?= e($embed) ?>">
    <?php if($thumb):?><img src="<?= e($thumb) ?>" alt="<?= e($i['t']) ?>" class="w-full h-full object-cover" loading="lazy"><?php else:?><div class="w-full h-full bg-emerald-900/30 flex items-center justify-center" aria-hidden="true"><i class="fa-solid fa-video text-emerald-400 text-3xl"></i></div><?php endif;?>
    <span class="yt-play" aria-hidden="true"><svg viewBox="0 0 68 48"><path d="M66.5 7.7c-.8-2.9-2.5-5.4-5.4-6.2C55.8.1 34 0 34 0S12.2.1 6.9 1.5C4 2.3 2.3 4.8 1.5 7.7.1 13 0 24 0 24s.1 11 1.5 16.3c.8-2.9 2.5-5.4-5.4-6.2C12.2 47.9 34 48 34 48s21.8-.1 27.1-1.5c2.9-.8 4.6-3.3 5.4-6.2C67.9 35 68 24 68 24s-.1-11-1.5-16.3z" fill="#f00"/><path d="M45 24 27 14v20z" fill="#fff"/></svg></span>
    <span class="sr-only"><?= __('watch_video',$lang) ?></span>
</div>
<?php else:?>
<div class="aspect-video rounded-lg mb-3 bg-emerald-900/30 flex items-center justify-center" aria-hidden="true"><i class="fa-solid fa-video text-emerald-400 text-3xl"></i></div>
<?php endif;?>
<h3 class="font-semibold text-white"><?= e($i['t']) ?></h3></div><?php endforeach;?></div><?php endif;?></div></section></main>
<?php include 'includes/footer.php';?>
