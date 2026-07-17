<?php
require_once dirname(__DIR__,2).'/app/config/app.php';
require_once ROOT_PATH.'/app/core/db.php';
require_once ROOT_PATH.'/app/core/auth.php';
require_once ROOT_PATH.'/app/core/csrf.php';
require_once ROOT_PATH.'/app/core/helpers.php';
require_once ROOT_PATH.'/app/core/admin_helpers.php';
session_start_secure(); require_role('admin');
$lang = current_lang();

// Handle create/edit
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_verify();
    $name=trim($_POST['name']??'');$email=trim($_POST['email']??'');
    $role=$_POST['role']??'editor';$status=$_POST['status']??'active';
    $password=$_POST['password']??'';$userId=(int)($_POST['user_id']??0);

    // Allowed values whitelists — prevent role/status injection
    if(!in_array($role,['admin','editor'],true)){$role='editor';}
    if(!in_array($status,['active','inactive'],true)){$status='active';}

    // Server-side validation
    $errors=[];
    if(empty($name)){$errors[]=$lang==='ar'?'الاسم مطلوب.':($lang==='fr'?'Nom requis.':'Name is required.');}
    if(empty($email)||!filter_var($email,FILTER_VALIDATE_EMAIL)){$errors[]=$lang==='ar'?'بريد إلكتروني صحيح مطلوب.':($lang==='fr'?'Email valide requis.':'Valid email is required.');}
    if(!$userId&&empty($password)){$errors[]=$lang==='ar'?'كلمة المرور مطلوبة للمستخدمين الجدد.':($lang==='fr'?'Mot de passe requis.':'Password is required for new users.');}
    if(!empty($password)&&strlen($password)<8){$errors[]=$lang==='ar'?'كلمة المرور يجب أن تكون 8 أحرف على الأقل.':($lang==='fr'?'Mot de passe: 8 caractères minimum.':'Password must be at least 8 characters.');}

    if(!empty($errors)){
        set_flash('error',implode(' | ',$errors));
        redirect($userId?'index.php?edit='.$userId:'index.php');
    }

    try{
        if($userId){
            if($userId===$_SESSION['user_id']){
                set_flash('error',$lang==='ar'?'لا يمكنك تعديل حسابك الخاص من هذه الصفحة.':($lang==='fr'?'Vous ne pouvez pas modifier votre propre compte.':'You cannot edit your own account here.'));
                redirect('index.php');
            }
            $sql="UPDATE users SET name=:n,email=:e,role=:r,status=:s".($password?",password_hash=:p":'')." WHERE id=:id";
            $p=['n'=>$name,'e'=>$email,'r'=>$role,'s'=>$status,'id'=>$userId];
            if($password){$p['p']=password_hash($password,PASSWORD_DEFAULT);}
            db()->prepare($sql)->execute($p);
            log_audit($_SESSION['user_id'],'update','user',$userId);
        } else {
            // Check email uniqueness
            $check=db()->prepare("SELECT id FROM users WHERE email=:e LIMIT 1");
            $check->execute(['e'=>$email]);
            if($check->fetch()){
                set_flash('error',$lang==='ar'?'هذا البريد الإلكتروني مستخدم بالفعل.':($lang==='fr'?'Email déjà utilisé.':'Email already in use.'));
                redirect('index.php');
            }
            $stmt=db()->prepare("INSERT INTO users (name,email,password_hash,role,status,created_at) VALUES (:n,:e,:p,:r,:s,NOW())");
            $stmt->execute(['n'=>$name,'e'=>$email,'p'=>password_hash($password,PASSWORD_DEFAULT),'r'=>$role,'s'=>$status]);
            log_audit($_SESSION['user_id'],'create','user',(int)db()->lastInsertId());
        }
        csrf_regenerate();
        set_flash('success',$lang==='ar'?'تم حفظ المستخدم.':($lang==='fr'?'Utilisateur sauvegardé.':'User saved.'));
    }catch(PDOException $e){
        error_log("User save error: ".$e->getMessage());
        set_flash('error',$lang==='ar'?'خطأ في قاعدة البيانات.':($lang==='fr'?'Erreur base de données.':'Database error.'));
    }
    redirect('index.php');
}

// Handle delete with CSRF check
if(isset($_GET['delete']) && isset($_GET['csrf_token'])){
    $token = $_GET['csrf_token'];
    if(isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)){
        $id=(int)$_GET['delete'];
        if($id!=$_SESSION['user_id']){
            db()->prepare("DELETE FROM users WHERE id=:id")->execute(['id'=>$id]);
            log_audit($_SESSION['user_id'], 'delete', 'user', $id);
            set_flash('success', $lang==='ar'?'تم حذف المستخدم.':($lang==='fr'?'Utilisateur supprimé.':'User deleted.'));
        }
    }
    redirect('index.php');
}

$users=db()->query("SELECT id,name,email,role,status,created_at FROM users ORDER BY created_at DESC")->fetchAll();
$editUser=null;if(isset($_GET['edit'])){$s=db()->prepare("SELECT * FROM users WHERE id=:id");$s->execute(['id'=>(int)$_GET['edit']]);$editUser=$s->fetch();}
?>
<!DOCTYPE html><html lang="<?=e($lang)?>" dir="<?=dir_attribute($lang)?>" data-theme="light"><head><meta charset="UTF-8"><title><?=$lang==='ar'?'المستخدمون':'Users'?> - <?=e(APP_NAME)?></title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../public/assets/css/style.css"></head>
<body class="admin-theme-bg min-h-screen"><div class="blob blob-1"></div><div class="blob blob-2"></div>
<div class="relative z-10 flex h-screen"><?php include '../includes/sidebar.php';?><div class="flex-1 flex flex-col overflow-hidden"><?php include '../includes/header.php';?>
<main class="flex-1 overflow-y-auto p-6"><h1 class="text-2xl font-bold text-white mb-6">👥 <?=$lang==='ar'?'المستخدمون':($lang==='fr'?'Utilisateurs':'Users')?></h1>
<?php $flash=get_flash();if($flash):?><div class="mb-4 p-4 rounded-lg bg-emerald-600/30 border border-emerald-500/30 text-emerald-300"><?=e($flash['message'])?></div><?php endif;?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<div class="lg:col-span-2">
<div class="glass-card-static overflow-hidden"><table class="w-full text-sm"><thead><tr class="border-b border-white/10 text-emerald-300/70"><th class="text-right p-3"><?=$lang==='ar'?'الاسم':($lang==='fr'?'Nom':'Name')?></th><th class="text-right p-3">Email</th><th class="text-center p-3"><?=$lang==='ar'?'الدور':($lang==='fr'?'Rôle':'Role')?></th><th class="text-center p-3"><?=$lang==='ar'?'الحالة':($lang==='fr'?'Statut':'Status')?></th><th class="text-center p-3"><?=$lang==='ar'?'الإجراءات':($lang==='fr'?'Actions':'Actions')?></th></tr></thead>
<tbody><?php foreach($users as $u):?><tr class="border-b border-white/5 hover:bg-white/5"><td class="p-3 text-white"><?=e($u['name'])?><?=$u['id']==$_SESSION['user_id']?(' <span class="text-xs text-emerald-400">('.($lang==='ar'?'أنت':($lang==='fr'?'vous':'you')).')</span>'):''?></td>
<td class="p-3 text-emerald-200/70"><?=e($u['email'])?></td><td class="p-3 text-center"><?=e($u['role'])?></td><td class="p-3 text-center"><?=status_badge($u['status'])?></td>
<td class="p-3 text-center"><div class="flex justify-center gap-2">
<a href="?edit=<?=$u['id']?>" class="text-emerald-400">✏️</a>
<?php if($u['id']!=$_SESSION['user_id']):?>
<a href="?delete=<?=$u['id']?>&csrf_token=<?=csrf_token()?>" class="text-red-400" onclick="return confirm('Delete user?')">🗑️</a>
<?php endif;?>
</div></td></tr><?php endforeach;?></tbody></table></div></div>
<div class="glass-card-static p-6"><h2 class="text-lg font-semibold text-white mb-4"><?=$editUser?($lang==='ar'?'تعديل مستخدم':($lang==='fr'?'Modifier':'Edit User')):($lang==='ar'?'إضافة مستخدم جديد':($lang==='fr'?'Nouvel utilisateur':'Add User'))?></h2>
<form method="POST"><?=csrf_field()?>
<?php if($editUser):?><input type="hidden" name="user_id" value="<?=$editUser['id']?>"><?php endif;?>
<div class="space-y-4"><div><input type="text" name="name" value="<?=e($editUser['name']??'')?>" required class="form-input" placeholder="<?=$lang==='ar'?'الاسم':($lang==='fr'?'Nom':'Name')?>"></div>
<div><input type="email" name="email" value="<?=e($editUser['email']??'')?>" required class="form-input" placeholder="Email"></div>
<div><input type="password" name="password" class="form-input" placeholder="<?=$editUser?($lang==='ar'?'كلمة مرور جديدة (اختياري)':($lang==='fr'?'Nouveau mot de passe (optionnel)':'New password (optional)')):($lang==='ar'?'كلمة المرور':($lang==='fr'?'Mot de passe':'Password'))?>"></div>
<div class="grid grid-cols-2 gap-2"><select name="role" class="form-input"><option value="editor"<?=$editUser&&$editUser['role']==='editor'?' selected':''?>>Editor</option><option value="admin"<?=$editUser&&$editUser['role']==='admin'?' selected':''?>>Admin</option></select>
<select name="status" class="form-input"><option value="active"<?=$editUser&&$editUser['status']==='active'?' selected':''?>><?=$lang==='ar'?'نشط':($lang==='fr'?'Actif':'Active')?></option><option value="inactive"<?=$editUser&&$editUser['status']==='inactive'?' selected':''?>><?=$lang==='ar'?'غير نشط':($lang==='fr'?'Inactif':'Inactive')?></option></select></div>
<button type="submit" class="glass-btn w-full justify-center"><?=$editUser?($lang==='ar'?'تحديث':($lang==='fr'?'Mettre à jour':'Update')):($lang==='ar'?'إضافة':($lang==='fr'?'Ajouter':'Add'))?></button>
<?php if($editUser):?><a href="index.php" class="block text-center text-sm text-emerald-400 hover:text-emerald-300"><?=$lang==='ar'?'إلغاء':($lang==='fr'?'Annuler':'Cancel')?></a><?php endif;?>
</div></form></div></div></main>
<?php include '../includes/footer.php';?></div></div>
<script src="../../public/assets/js/admin.js"></script></body></html>