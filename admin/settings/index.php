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
$groupLabels=['company'=>($lang==='ar'?'الشركة':($lang==='fr'?'Société':'Company')),'contact'=>($lang==='ar'?'الاتصال':($lang==='fr'?'Contact':'Contact')),'social'=>($lang==='ar'?'التواصل الاجتماعي':($lang==='fr'?'Social':'Social')),'hero'=>($lang==='ar'?'الشريط الرئيسي':($lang==='fr'?'Héros':'Hero')),'stats'=>($lang==='ar'?'الإحصائيات':($lang==='fr'?'Statistiques':'Stats')),'seo'=>($lang==='ar'?'تحسين محركات البحث':($lang==='fr'?'SEO':'SEO')),'footer'=>($lang==='ar'?'التذييل':($lang==='fr'?'Pied de page':'Footer')),'mail'=>($lang==='ar'?'📧 إعدادات البريد الإلكتروني':($lang==='fr'?'📧 Configuration Email':'📧 Email Configuration')),'other'=>($lang==='ar'?'أخرى':($lang==='fr'?'Autres':'Other'))];

$settingLabels=[
    // Company
    'company_name'=>($lang==='ar'?'اسم الشركة':($lang==='fr'?'Nom de la société':'Company Name')),
    'company_short_name'=>($lang==='ar'?'الاسم المختصر':($lang==='fr'?'Nom abrégé':'Short Name')),
    'mission_statement'=>($lang==='ar'?'بيان المهمة':($lang==='fr'?'Mission':'Mission Statement')),
    'about_summary'=>($lang==='ar'?'نبذة عن الشركة':($lang==='fr'?'Résumé':'About Summary')),
    'director_name'=>($lang==='ar'?'اسم المدير العام':($lang==='fr'?'Nom du directeur général':'Director Name')),
    // Contact
    'phone'=>($lang==='ar'?'الهاتف':($lang==='fr'?'Téléphone':'Phone')),
    'fax'=>($lang==='ar'?'الفاكس':($lang==='fr'?'Fax':'Fax')),
    'email_primary'=>($lang==='ar'?'البريد الإلكتروني الأساسي':($lang==='fr'?'Email principal':'Primary Email')),
    'email_secondary'=>($lang==='ar'?'البريد الإلكتروني الثانوي':($lang==='fr'?'Email secondaire':'Secondary Email')),
    'po_box'=>($lang==='ar'?'صندوق البريد':($lang==='fr'?'Boîte postale':'P.O. Box')),
    'address'=>($lang==='ar'?'العنوان':($lang==='fr'?'Adresse':'Address')),
    // Social
    'facebook_url'=>($lang==='ar'?'رابط فيسبوك':($lang==='fr'?'URL Facebook':'Facebook URL')),
    // Hero
    'hero_title'=>($lang==='ar'?'عنوان الشريط الرئيسي':($lang==='fr'?'Titre du héros':'Hero Title')),
    'hero_subtitle'=>($lang==='ar'?'العنوان الفرعي للشريط الرئيسي':($lang==='fr'?'Sous-titre du héros':'Hero Subtitle')),
    // Stats
    'stat_founded_label'=>($lang==='ar'?'تسمية "تأسست في"':($lang==='fr'?'Étiquette "Fondée en"':'Label "Founded In"')),
    'stat_founded_value'=>($lang==='ar'?'قيمة "تأسست في"':($lang==='fr'?'Valeur "Fondée en"':'Value "Founded In"')),
    'stat_trees_label'=>($lang==='ar'?'تسمية "الأشجار المزروعة"':($lang==='fr'?'Étiquette "Arbres plantés"':'Label "Trees Planted"')),
    'stat_trees_value'=>($lang==='ar'?'قيمة "الأشجار المزروعة"':($lang==='fr'?'Valeur "Arbres plantés"':'Value "Trees Planted"')),
    'stat_hectares_label'=>($lang==='ar'?'تسمية "الهكتارات المحولة"':($lang==='fr'?'Étiquette "Hectares transformés"':'Label "Hectares Transformed"')),
    'stat_hectares_value'=>($lang==='ar'?'قيمة "الهكتارات المحولة"':($lang==='fr'?'Valeur "Hectares transformés"':'Value "Hectares Transformed"')),
    'stat_activation_label'=>($lang==='ar'?'تسمية "نسبة التفعيل"':($lang==='fr'?'Étiquette "Taux d\'activation"':'Label "Activation Rate"')),
    'stat_activation_value'=>($lang==='ar'?'قيمة "نسبة التفعيل"':($lang==='fr'?'Valeur "Taux d\'activation"':'Value "Activation Rate"')),
    'stat_activation_goal'=>($lang==='ar'?'تسمية "هدف التفعيل"':($lang==='fr'?'Étiquette "Objectif d\'activation"':'Label "Activation Goal"')),
    'stat_activation_goal_value'=>($lang==='ar'?'قيمة "هدف التفعيل"':($lang==='fr'?'Valeur "Objectif d\'activation"':'Value "Activation Goal"')),
    'stat_activation_year'=>($lang==='ar'?'سنة التفعيل':($lang==='fr'?'Année d\'activation':'Activation Year')),
    // SEO
    'seo_title'=>($lang==='ar'?'عنوان تحسين محركات البحث':($lang==='fr'?'Titre SEO':'SEO Title')),
    'seo_description'=>($lang==='ar'?'وصف تحسين محركات البحث':($lang==='fr'?'Description SEO':'SEO Description')),
    // Footer
    'footer_text'=>($lang==='ar'?'نص التذييل':($lang==='fr'?'Texte du pied de page':'Footer Text')),
    // Mail
    'mail_driver'=>($lang==='ar'?'برنامج البريد الإلكتروني':($lang==='fr'?'Pilote mail':'Mail Driver')),
    'smtp_host'=>($lang==='ar'?'مضيف SMTP':($lang==='fr'?'Hôte SMTP':'SMTP Host')),
    'smtp_port'=>($lang==='ar'?'منفذ SMTP':($lang==='fr'?'Port SMTP':'SMTP Port')),
    'smtp_secure'=>($lang==='ar'?'تشفير SMTP':($lang==='fr'?'Chiffrement SMTP':'SMTP Encryption')),
    'smtp_username'=>($lang==='ar'?'اسم مستخدم SMTP':($lang==='fr'?'Nom d\'utilisateur SMTP':'SMTP Username')),
    'smtp_password'=>($lang==='ar'?'كلمة مرور SMTP':($lang==='fr'?'Mot de passe SMTP':'SMTP Password')),
    'smtp_from_email'=>($lang==='ar'?'البريد الإلكتروني للمرسل':($lang==='fr'?'Email d\'envoi':'From Email')),
    'smtp_from_name'=>($lang==='ar'?'اسم المرسل':($lang==='fr'?'Nom d\'envoi':'From Name')),
];

$settingDescriptions=[
    'email_primary'=>($lang==='ar'?'البريد الإلكتروني الذي يظهر في منطقة الاتصال بالموقع':($lang==='fr'?'Email affiché dans la zone de contact du site':'The email displayed in the contact area of the website')),
    'email_secondary'=>($lang==='ar'?'بريد إلكتروني احتياطي إضافي':($lang==='fr'?'Email secondaire supplémentaire':'Additional backup email')),
    'phone'=>($lang==='ar'?'رقم الهاتف الذي يظهر في الموقع':($lang==='fr'?'Numéro de téléphone affiché sur le site':'Phone number displayed on the website')),
    'fax'=>($lang==='ar'?'رقم الفاكس':($lang==='fr'?'Numéro de fax':'Fax number')),
    'po_box'=>($lang==='ar'?'صندوق البريد البريدي للشركة':($lang==='fr'?'Boîte postale de la société':'Company P.O. Box')),
    'address'=>($lang==='ar'?'العنوان الكامل للشركة':($lang==='fr'?'Adresse complète de la société':'Full company address')),
    'company_name'=>($lang==='ar'?'الاسم الرسمي الكامل للشركة':($lang==='fr'?'Nom officiel complet de la société':'Full official company name')),
    'company_short_name'=>($lang==='ar'?'الاسم المختصر للشركة (للشعار والعناوين)':($lang==='fr'?'Nom abrégé de la société (logo, en-têtes)':'Short company name (logo, headers)')),
    'mission_statement'=>($lang==='ar'?'رسالة الشركة وأهدافها الأساسية':($lang==='fr'?'Mission et objectifs principaux de la société':'Company mission and core objectives')),
    'about_summary'=>($lang==='ar'?'فقرة موجزة عن الشركة تظهر في صفحة "من نحن" وفي التذييل':($lang==='fr'?'Paragraphe sur la société (page À propos, pied de page)':'Brief paragraph about the company (About page, footer)')),
    'director_name'=>($lang==='ar'?'اسم المدير العام للشركة':($lang==='fr'?'Nom du directeur général de la société':'Name of the company\'s general director')),
    'hero_title'=>($lang==='ar'?'العنوان الرئيسي الكبير في الصفحة الرئيسية':($lang==='fr'?'Grand titre sur la page d\'accueil':'Main title on the homepage hero section')),
    'hero_subtitle'=>($lang==='ar'?'النص الفرعي تحت العنوان في الصفحة الرئيسية':($lang==='fr'?'Sous-titre sur la page d\'accueil':'Subtitle below the hero title')),
    'facebook_url'=>($lang==='ar'?'الرابط الكامل لصفحة الفيسبوك الرسمية':($lang==='fr'?'URL complète de la page Facebook officielle':'Full URL of the official Facebook page')),
    'seo_title'=>($lang==='ar'?'عنوان الموقع الذي يظهر في نتائج محركات البحث':($lang==='fr'?'Titre du site dans les résultats de recherche':'Site title shown in search engine results')),
    'seo_description'=>($lang==='ar'?'الوصف الذي يظهر تحت الرابط في نتائج البحث':($lang==='fr'?'Description sous le lien dans les résultats de recherche':'Description shown under the link in search results')),
    'footer_text'=>($lang==='ar'?'نص حقوق النشر الذي يظهر في أسفل كل صفحة':($lang==='fr'?'Texte de copyright en bas de chaque page':'Copyright text at the bottom of every page')),
    'smtp_from_email'=>($lang==='ar'?'عنوان البريد الإلكتروني الذي يظهر كمرسل للرسائل':($lang==='fr'?'Adresse email apparaissant comme expéditeur':'Email address shown as the sender')),
    'smtp_from_name'=>($lang==='ar'?'الاسم الذي يظهر كمرسل للرسائل البريدية':($lang==='fr'?'Nom apparaissant comme expéditeur des emails':'Name shown as the email sender')),
];
?>
<!DOCTYPE html><html lang="<?=e($lang)?>" dir="<?=dir_attribute($lang)?>" data-theme="light"><head><meta charset="UTF-8"><title><?=$lang==='ar'?'الإعدادات':'Settings'?> - <?=e(APP_NAME)?></title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../public/assets/css/style.css"></head>
<body class="admin-theme-bg min-h-screen"><div class="blob blob-1"></div><div class="blob blob-2"></div>
<div class="relative z-10 flex h-screen"><?php include '../includes/sidebar.php';?><div class="flex-1 flex flex-col overflow-hidden pt-16"><?php include '../includes/header.php';?>
<main class="flex-1 overflow-y-auto p-6"><h1 class="text-2xl font-bold text-white mb-6">⚙️ <?=$lang==='ar'?'الإعدادات':($lang==='fr'?'Paramètres':'Settings')?></h1>
<?php $flash=get_flash();if($flash):?><div class="mb-4 p-4 rounded-lg bg-emerald-600/30 border border-emerald-500/30 text-emerald-300"><?=e($flash['message'])?></div><?php endif;?>
<form method="POST"><?=csrf_field()?>
<?php foreach($grouped as $g=>$items):?><div class="glass-card-static p-6 mb-6"><h2 class="text-lg font-semibold text-white mb-4 border-b border-white/10 pb-2"><?=e($groupLabels[$g]??$g)?></h2>
<div class="space-y-4"><?php foreach($items as $s):?><div><label class="block text-sm font-medium text-white"><?=e($settingLabels[$s['setting_key']]??$s['setting_key'])?></label>
<?php if(!empty($settingDescriptions[$s['setting_key']])):?><p class="text-xs text-emerald-300/50 mb-1"><?=e($settingDescriptions[$s['setting_key']])?></p><?php endif;?>
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