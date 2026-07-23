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

$lang = current_lang();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = match ($lang) {
            'ar' => 'طلب غير صالح. الرجاء المحاولة مرة أخرى.',
            'fr' => 'Requête invalide. Veuillez réessayer.',
            default => 'Invalid request. Please try again.',
        };
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = match ($lang) {
                'ar' => 'الرجاء إدخال البريد الإلكتروني وكلمة المرور.',
                'fr' => 'Veuillez entrer l\'email et le mot de passe.',
                default => 'Please enter email and password.',
            };
        } else {
            $result = login($email, $password);
            
            if ($result['success']) {
                $redirect = $_SESSION['redirect_after_login'] ?? (ADMIN_URL . '/dashboard.php');
                unset($_SESSION['redirect_after_login']);
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

// Language metadata for the dropdown
$adminLangMeta = [
    'ar' => ['label' => 'العربية',    'flag' => PUBLIC_URL . '/assets/tn1.png', 'alt' => 'تونس'],
    'fr' => ['label' => 'Français',   'flag' => PUBLIC_URL . '/assets/fr1.png', 'alt' => 'France'],
    'en' => ['label' => 'English',    'flag' => PUBLIC_URL . '/assets/uk1.png', 'alt' => 'UK'],
];
$adminCurrent = $adminLangMeta[$lang];

// Catchy subtitle per language
$catchyLine = match ($lang) {
    'ar' => 'تسجيل الدخول إلى لوحة التحكم — إدارة المحتوى، الرسائل، والوسائط بكل سهولة',
    'fr' => 'Connectez-vous au tableau de bord — Gérez contenu, messages et médias en toute simplicité',
    default => 'Sign in to the admin dashboard — Manage content, messages, and media with ease',
};
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attribute($lang) ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php if ($lang === 'ar'): ?>تسجيل الدخول
        <?php elseif ($lang === 'fr'): ?>Connexion
        <?php else: ?>Login
        <?php endif; ?> - <?= e(APP_NAME) ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/assets/css/style.css">
    <script>
    (function() {
        var theme = localStorage.getItem('sepj-theme');
        if (theme === 'light' || theme === 'dark') {
            document.documentElement.setAttribute('data-theme', theme);
        }
    })();
    </script>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">
    <!-- Background blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- Top pill bar -->
    <div class="fixed top-4 left-2 right-2 z-50 flex justify-center pointer-events-none">
        <div class="w-full max-w-7xl px-2 pointer-events-auto">
            <div class="flex items-center justify-between px-5 py-2.5 rounded-full backdrop-blur-xl bg-white/5 border border-white/10 shadow-lg">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-emerald-400 hidden sm:inline"><?= e(APP_NAME) ?></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400/60 hidden sm:block"></span>
                    <span class="text-[11px] text-white/40 hidden sm:block"><?= e(APP_NAME_AR) ?></span>
                </div>
                <div class="flex items-center gap-3" dir="ltr">
                    <!-- Language dropdown -->
                    <div class="relative group">
                        <button class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-white/80 hover:text-white hover:bg-white/10 transition-colors"
                                aria-haspopup="true" aria-expanded="false">
                            <img src="<?= e($adminCurrent['flag']) ?>" alt="<?= e($adminCurrent['alt']) ?>" width="18" height="13" style="border-radius:2px;flex-shrink:0;">
                            <span class="text-xs hidden sm:inline"><?= e($adminCurrent['label']) ?></span>
                            <svg class="w-3 h-3 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="absolute top-full right-0 mt-2 w-36 bg-gray-900/95 backdrop-blur-md border border-white/10 rounded-xl shadow-xl p-1.5 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50" dir="ltr">
                            <?php foreach ($adminLangMeta as $code => $meta): ?>
                            <a href="<?= e(lang_url($code)) ?>"
                               lang="<?= $code ?>" hreflang="<?= $code ?>"
                               class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors <?= $lang === $code ? 'text-emerald-400 bg-white/5' : 'text-white/80 hover:text-white hover:bg-white/5' ?>">
                                <img src="<?= e($meta['flag']) ?>" alt="<?= e($meta['alt']) ?>" width="18" height="13" style="border-radius:2px;flex-shrink:0;">
                                <span><?= e($meta['label']) ?></span>
                                <?php if ($lang === $code): ?>
                                <svg class="w-3 h-3 ml-auto text-emerald-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Divider -->
                    <span class="w-px h-5 bg-white/10"></span>
                    <!-- Theme toggle -->
                    <button id="loginThemeToggle"
                            class="theme-toggle"
                            role="switch"
                            aria-checked="false"
                            aria-label="<?= $lang === 'ar' ? 'تبديل الوضع الفاتح/الداكن' : ($lang === 'fr' ? 'Basculer le mode clair/sombre' : 'Toggle light/dark mode') ?>">
                        <span class="theme-toggle-track" aria-hidden="true">
                            <span class="theme-toggle-thumb">
                                <svg class="theme-icon-moon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="10" height="10">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                </svg>
                                <svg class="theme-icon-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="10" height="10">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Card -->
    <div class="glass-card-static w-full max-w-md p-8 relative z-10 mt-16">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-500/15 border border-emerald-500/25 mb-4">
                <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white mb-1"><?= e(APP_NAME) ?></h1>
            <p class="text-emerald-300/80 text-sm font-medium mb-3"><?= e(APP_NAME_AR) ?></p>
            <div class="flex items-center gap-3 justify-center mb-2">
                <span class="h-px flex-1 max-w-12 bg-white/10"></span>
                <span class="text-xs text-white/40 font-medium tracking-wider uppercase">
                    <?php if ($lang === 'ar'): ?>لوحة التحكم
                    <?php elseif ($lang === 'fr'): ?>Administration
                    <?php else: ?>Admin Panel
                    <?php endif; ?>
                </span>
                <span class="h-px flex-1 max-w-12 bg-white/10"></span>
            </div>
            <p class="text-white/50 text-sm leading-relaxed max-w-xs mx-auto"><?= e($catchyLine) ?></p>
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
                    <?php if ($lang === 'ar'): ?>البريد الإلكتروني
                    <?php elseif ($lang === 'fr'): ?>Email
                    <?php else: ?>Email
                    <?php endif; ?>
                </label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       class="form-input w-full px-4 py-3 rounded-lg bg-white/5 border border-white/20 text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-all"
                       placeholder="admin@sepj.local">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-emerald-200 mb-2">
                    <?php if ($lang === 'ar'): ?>كلمة المرور
                    <?php elseif ($lang === 'fr'): ?>Mot de passe
                    <?php else: ?>Password
                    <?php endif; ?>
                </label>
                <div class="relative">
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="form-input w-full px-4 py-3 pr-12 rounded-lg bg-white/5 border border-white/20 text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-all"
                           placeholder="••••••••">
                    <button type="button" onclick="togglePassword(this)"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-white/40 hover:text-emerald-300 transition-colors"
                            tabindex="-1" aria-label="<?= $lang === 'ar' ? 'إظهار/إخفاء كلمة المرور' : ($lang === 'fr' ? 'Afficher/Masquer le mot de passe' : 'Toggle password visibility') ?>">
                        <svg class="pw-eye w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg class="pw-eye-off w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878l4.242 4.242"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.125-5.125M16.626 12.376A10.05 10.05 0 0112 7c-1.126 0-2.207.186-3.213.527"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="glass-btn w-full justify-center py-3 text-base font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                <?php if ($lang === 'ar'): ?>تسجيل الدخول
                <?php elseif ($lang === 'fr'): ?>Connexion
                <?php else: ?>Login
                <?php endif; ?>
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="../public/" class="inline-flex items-center gap-1.5 text-sm text-emerald-400/70 hover:text-emerald-300 transition-colors">
                <svg class="w-3.5 h-3.5 rtl-flip-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                </svg>
                <?php if ($lang === 'ar'): ?>العودة إلى الموقع
                <?php elseif ($lang === 'fr'): ?>Retour au site
                <?php else: ?>Back to site
                <?php endif; ?>
            </a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('loginThemeToggle');
        var html = document.documentElement;
        var STORAGE_KEY = 'sepj-theme';

        function syncToggle() {
            if (!btn) return;
            var isLight = html.getAttribute('data-theme') !== 'dark';
            btn.setAttribute('aria-checked', isLight ? 'false' : 'true');
        }

        syncToggle();

        if (btn) {
            btn.addEventListener('click', function() {
                var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                localStorage.setItem(STORAGE_KEY, next);
                syncToggle();
            });
        }
    });

    function togglePassword(btn) {
        var input = btn.parentElement.querySelector('input');
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.querySelector('.pw-eye').classList.toggle('hidden');
        btn.querySelector('.pw-eye-off').classList.toggle('hidden');
    }
    </script>
</body>
</html>
