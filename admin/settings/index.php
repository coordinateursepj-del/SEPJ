<?php
require_once dirname(__DIR__,2).'/app/config/app.php';
require_once ROOT_PATH.'/app/core/db.php';
require_once ROOT_PATH.'/app/core/auth.php';
require_once ROOT_PATH.'/app/core/csrf.php';
require_once ROOT_PATH.'/app/core/helpers.php';
session_start_secure(); require_login(); require_role('admin');
$lang = current_lang();

// Handle bulk update
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_verify();
    // Whitelist allowed column names — prevents SQL injection via the field_ POST parameter
    $allowedFields = ['value_ar', 'value_fr', 'value_en', 'value_raw'];
    foreach($_POST as $key=>$value){if(strpos($key,'setting_')===0){$k=substr($key,8);
        $f=$_POST['field_'.$k]??'value_raw';
        if(!in_array($f,$allowedFields,true)){$f='value_raw';} // Reject unknown fields
        $stmt=db()->prepare("UPDATE site_settings SET {$f}=:v WHERE setting_key=:k");$stmt->execute(['v'=>$value,'k'=>$k]);}}
    csrf_regenerate();set_flash('success',$lang==='ar'?'تم حفظ الإعدادات.':($lang==='fr'?'Paramètres sauvegardés.':'Settings saved.'));
    redirect('index.php');
}
$settings=db()->query("SELECT * FROM site_settings ORDER BY setting_key")->fetchAll();
$grouped=[];$groups=['company'=>['company_name','company_short_name','mission_statement','about_summary','director_name'],'contact'=>['phone','fax','email_primary','email_secondary','po_box','address'],'social'=>['facebook_url'],'hero'=>['hero_title','hero_subtitle'],'stats'=>['stat_founded_label','stat_founded_value','stat_trees_label','stat_trees_value','stat_hectares_label','stat_hectares_value','stat_activation_label','stat_activation_value','stat_activation_goal','stat_activation_goal_value','stat_activation_year'],'seo'=>['seo_title','seo_description'],'footer'=>['footer_text'],'mail'=>['mail_driver','smtp_host','smtp_port','smtp_secure','smtp_username','smtp_password','smtp_from_email','smtp_from_name']];
foreach($settings as $s){$g='other';foreach($groups as $gk=>$gks){if(in_array($s['setting_key'],$gks)){$g=$gk;break;}}$grouped[$g][]=$s;}
$groupLabels=['company'=>($lang==='ar'?'الشركة':($lang==='fr'?'Société':'Company')),'contact'=>($lang==='ar'?'الاتصال':($lang==='fr'?'Contact':'Contact')),'social'=>($lang==='ar'?'تواصل اجتماعي':($lang==='fr'?'Social':'Social')),'hero'=>($lang==='ar'?'الشريط الرئيسي':($lang==='fr'?'Héros':'Hero')),'stats'=>($lang==='ar'?'الإحصائيات':($lang==='fr'?'Statistiques':'Stats')),'seo'=>($lang==='ar'?'تحسين محركات البحث':($lang==='fr'?'SEO':'SEO')),'footer'=>($lang==='ar'?'التذييل':($lang==='fr'?'Pied de page':'Footer')),'mail'=>($lang==='ar'?'📧 إعدادات البريد الإلكتروني':($lang==='fr'?'📧 Configuration Email':'📧 Email Configuration')),'other'=>($lang==='ar'?'أخرى':($lang==='fr'?'Autres':'Other'))];
?>
<!DOCTYPE html><html lang="<?=e($lang)?>" dir="<?=dir_attribute($lang)?>"><head><meta charset="UTF-8"><title><?=$lang==='ar'?'الإعدادات':'Settings'?> - <?=e(APP_NAME)?></title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../public/assets/css/style.css"></head>
<body class="bg-gradient-to-br from-gray-900 via-emerald-950 to-gray-900 min-h-screen"><div class="blob blob-1"></div><div class="blob blob-2"></div>
<div class="relative z-10 flex h-screen"><?php include '../includes/sidebar.php';?><div class="flex-1 flex flex-col overflow-hidden"><?php include '../includes/header.php';?>
<main class="flex-1 overflow-y-auto p-6"><h1 class="text-2xl font-bold text-white mb-6">⚙️ <?=$lang==='ar'?'الإعدادات':($lang==='fr'?'Paramètres':'Settings')?></h1>
<?php $flash=get_flash();if($flash):?><div class="mb-4 p-4 rounded-lg bg-emerald-600/30 border border-emerald-500/30 text-emerald-300"><?=e($flash['message'])?></div><?php endif;?>
<form method="POST"><?=csrf_field()?>
<?php foreach($grouped as $g=>$items):?><div class="glass-card-static p-6 mb-6"><h2 class="text-lg font-semibold text-white mb-4 border-b border-white/10 pb-2"><?=e($groupLabels[$g]??$g)?></h2>
<div class="space-y-4"><?php foreach($items as $s):?><div><label class="block text-xs text-emerald-300/60 mb-1 font-mono"><?=e($s['setting_key'])?></label>
<?php if(in_array($s['setting_key'],['about_summary','mission_statement','footer_text'])):?><textarea name="setting_<?=e($s['setting_key'])?>" rows="3" class="form-input text-sm"><?=e($s['value_'.$lang]?:$s['value_raw']?:'')?></textarea>
<?php elseif($s['setting_key']==='smtp_password'):?><input type="password" name="setting_<?=e($s['setting_key'])?>" value="<?=e($s['value_raw']?:'')?>" class="form-input text-sm" placeholder="••••••••" autocomplete="new-password">
<?php elseif($s['setting_key']==='mail_driver'):?><select name="setting_<?=e($s['setting_key'])?>" class="form-input text-sm"><option value="php" <?=($s['value_raw']??'php')==='php'?'selected':''?>>PHP mail() — local / testing</option><option value="smtp" <?=($s['value_raw']??'')==='smtp'?'selected':''?>>SMTP — production</option></select><p class="text-xs text-emerald-300/40 mt-1"><?=$lang==='fr'?'Choisir SMTP pour la production avec Gmail / OVH / etc.':'Choose SMTP for production with Gmail / OVH / etc.'?></p>
<?php elseif($s['setting_key']==='smtp_secure'):?><select name="setting_<?=e($s['setting_key'])?>" class="form-input text-sm"><option value="tls" <?=($s['value_raw']??'tls')==='tls'?'selected':''?>>TLS — port 587 (recommended)</option><option value="ssl" <?=($s['value_raw']??'')==='ssl'?'selected':''?>>SSL — port 465</option><option value="" <?=($s['value_raw']??'')===''?'selected':''?>>None — port 25</option></select>
<?php else:?><input type="text" name="setting_<?=e($s['setting_key'])?>" value="<?=e($s['value_raw']?:'')?>" class="form-input text-sm"><?php endif;?>
<input type="hidden" name="field_<?=e($s['setting_key'])?>" value="value_raw"></div><?php endforeach;?></div></div>
<?php endforeach;?>
<button type="submit" class="glass-btn glass-btn-primary"><?=$lang==='ar'?'حفظ جميع الإعدادات':($lang==='fr'?'Sauvegarder':'Save All Settings')?></button>
</form></main><?php include '../includes/footer.php';?></div></div>
<script src="../../public/assets/js/admin.js"></script></body></html>