<?php
/**
 * Admin Header - SEPJ Gabès
 * 
 * Variables expected: $lang, $user
 */

$lang = $lang ?? current_lang();
$user = current_user();

$adminLangMeta = [
    'ar' => ['label' => 'العربية',    'flag' => PUBLIC_URL . '/assets/tn1.png', 'alt' => 'تونس'],
    'fr' => ['label' => 'Français',   'flag' => PUBLIC_URL . '/assets/fr1.png', 'alt' => 'France'],
    'en' => ['label' => 'English',    'flag' => PUBLIC_URL . '/assets/uk1.png', 'alt' => 'UK'],
];
$adminCurrent = $adminLangMeta[$lang];
?>
<!-- Fixed pill bar -->
<header class="fixed top-4 left-2 right-2 z-50 flex justify-center pointer-events-none">
    <div class="w-full max-w-7xl px-2 pointer-events-auto">
        <div class="flex items-center justify-between px-4 sm:px-5 py-2 sm:py-2.5 rounded-full backdrop-blur-xl bg-white/5 border border-white/10 shadow-lg">
            <!-- Left: sidebar toggle + brand -->
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <button id="sidebarToggle"
                        class="text-white/60 hover:text-white transition-colors lg:hidden shrink-0"
                        aria-label="<?= $lang === 'ar' ? 'فتح القائمة' : ($lang === 'fr' ? 'Ouvrir le menu' : 'Open menu') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="text-sm font-bold text-emerald-400 truncate"><?= e(APP_NAME) ?></span>
                <span class="w-1 h-1 rounded-full bg-emerald-400/50 hidden sm:block shrink-0"></span>
                <span class="text-[11px] text-white/40 truncate hidden sm:block"><?= e(APP_NAME_AR) ?></span>
            </div>

            <!-- Right: lang + theme + user -->
            <div class="flex items-center gap-2 sm:gap-3" dir="ltr">
                <!-- Language dropdown -->
                <div class="relative group">
                    <button class="flex items-center gap-1.5 sm:gap-2 px-2 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm text-white/70 hover:text-white hover:bg-white/10 transition-colors"
                            aria-haspopup="true" aria-expanded="false">
                        <img src="<?= e($adminCurrent['flag']) ?>" alt="<?= e($adminCurrent['alt']) ?>" width="16" height="11" style="border-radius:2px;flex-shrink:0;" class="sm:w-[18px] sm:h-[13px]">
                        <span class="hidden xs:inline"><?= e($adminCurrent['label']) ?></span>
                        <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                <span class="w-px h-5 bg-white/10 shrink-0"></span>

                <!-- Theme toggle -->
                <button id="adminThemeToggle"
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

                <!-- User info -->
                <div class="flex items-center gap-2 pl-1 border-l border-white/10">
                    <div class="hidden sm:block text-right">
                        <p class="text-xs font-medium text-white/80 leading-tight"><?= e($user['name'] ?? '') ?></p>
                        <p class="text-[10px] text-emerald-300/50 leading-tight"><?= e($user['role'] ?? '') ?></p>
                    </div>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-emerald-500/25 border border-emerald-400/25 flex items-center justify-center text-emerald-300 font-bold text-xs sm:text-sm shrink-0">
                        <?= mb_substr(e($user['name'] ?? 'U'), 0, 1) ?>
                    </div>
                    <form method="POST" action="<?= e(admin_url('logout.php')) ?>" class="inline-flex">
                        <?= csrf_field() ?>
                        <button type="submit" class="logout-icon-btn p-1" title="<?= $lang === 'ar' ? 'تسجيل الخروج' : ($lang === 'fr' ? 'Déconnexion' : 'Logout') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

