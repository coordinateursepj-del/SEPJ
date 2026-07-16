<?php
/**
 * Admin Login Page - SEPJ Gabès
 */

require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';

session_start_secure();

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'طلب غير صالح. الرجاء المحاولة مرة أخرى.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور.';
        } else {
            $result = login($email, $password);
            
            if ($result['success']) {
                $redirect = $_SESSION['redirect_after_login'] ?? (ADMIN_URL . '/dashboard.php');
                unset($_SESSION['redirect_after_login']);
                // Validate redirect stays inside the admin area (prevent open redirect)
                if (!is_string($redirect)
                    || !str_starts_with($redirect, ADMIN_URL)
                    || str_contains($redirect, '..')) {
                    $redirect = ADMIN_URL . '/dashboard.php';
                }
                csrf_regenerate();
                redirect($redirect);
            } else {
                $error = $result['message'];
                csrf_regenerate();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <!-- Background blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    
    <!-- Login Card -->
    <div class="glass-card-static w-full max-w-md p-8 relative z-10">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white mb-2"><?= e(APP_NAME) ?></h1>
            <p class="text-emerald-300 text-sm">شركة البيئة والغراسة والبستنة بقابس</p>
            <div class="mt-4 flex justify-center gap-2 text-xs">
                <a href="<?= e(lang_url('ar')) ?>" class="lang-switcher px-2 py-1 rounded <?= current_lang() === 'ar' ? 'bg-emerald-600/30 text-emerald-300' : 'text-gray-400 hover:text-white' ?>">العربية</a>
                <a href="<?= e(lang_url('fr')) ?>" class="lang-switcher px-2 py-1 rounded <?= current_lang() === 'fr' ? 'bg-emerald-600/30 text-emerald-300' : 'text-gray-400 hover:text-white' ?>">Français</a>
                <a href="<?= e(lang_url('en')) ?>" class="lang-switcher px-2 py-1 rounded <?= current_lang() === 'en' ? 'bg-emerald-600/30 text-emerald-300' : 'text-gray-400 hover:text-white' ?>">English</a>
            </div>
        </div>
        
        <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-500/20 border border-red-500/30 text-red-300 text-sm">
            <?= e($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-5">
            <?= csrf_field() ?>
            
            <div>
                <label for="email" class="block text-sm font-medium text-emerald-200 mb-2">
                    <?php if (current_lang() === 'ar'): ?>البريد الإلكتروني
                    <?php elseif (current_lang() === 'fr'): ?>Email
                    <?php else: ?>Email
                    <?php endif; ?>
                </label>
                <input type="email" id="email" name="email" required
                       class="form-input w-full px-4 py-3 rounded-lg bg-white/5 border border-white/20 text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-all"
                       placeholder="admin@sepj.local">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-emerald-200 mb-2">
                    <?php if (current_lang() === 'ar'): ?>كلمة المرور
                    <?php elseif (current_lang() === 'fr'): ?>Mot de passe
                    <?php else: ?>Password
                    <?php endif; ?>
                </label>
                <input type="password" id="password" name="password" required
                       class="form-input w-full px-4 py-3 rounded-lg bg-white/5 border border-white/20 text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-all"
                       placeholder="••••••••">
            </div>
            
            <button type="submit" class="glass-btn w-full justify-center py-3 text-base font-semibold">
                <?php if (current_lang() === 'ar'): ?>تسجيل الدخول
                <?php elseif (current_lang() === 'fr'): ?>Connexion
                <?php else: ?>Login
                <?php endif; ?>
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="../public/" class="text-sm text-emerald-400 hover:text-emerald-300 transition-colors">
                &larr; <?php if (current_lang() === 'ar'): ?>العودة إلى الموقع
                <?php elseif (current_lang() === 'fr'): ?>Retour au site
                <?php else: ?>Back to site
                <?php endif; ?>
            </a>
        </div>
    </div>
</body>
</html>