<?php
/**
 * Admin Header - SEPJ Gabès
 * 
 * Variables expected: $lang
 */

$lang = $lang ?? current_lang();
$user = current_user();
?>
<!-- Admin Header -->
<header class="bg-white/5 backdrop-blur-md border-b border-white/10 px-6 py-3 flex items-center justify-between shrink-0 relative z-50" dir="ltr">
    <!-- Mobile menu toggle -->
    <button id="sidebarToggle" class="text-white/70 hover:text-white transition-colors lg:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    
    <!-- Page title area (dynamic, set in individual pages) -->
    <div class="hidden md:block">
        <!-- Breadcrumb can go here -->
    </div>
    
    <!-- Right section -->
    <div class="flex items-center gap-4">
        <!-- Theme switcher (sits beside the language dropdown) -->
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

        <!-- Language switcher -->
        <?php
        $adminLangMeta = [
            'ar' => ['label' => 'العربية',    'flag' => PUBLIC_URL . '/assets/tn1.png', 'alt' => 'تونس'],
            'fr' => ['label' => 'Français',   'flag' => PUBLIC_URL . '/assets/fr1.png', 'alt' => 'France'],
            'en' => ['label' => 'English',    'flag' => PUBLIC_URL . '/assets/uk1.png', 'alt' => 'UK'],
        ];
        $adminCurrent = $adminLangMeta[$lang];
        ?>
        <div class="relative group" dir="ltr">
            <button class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-white/80 hover:text-white hover:bg-white/10 transition-colors"
                    aria-haspopup="true" aria-expanded="false">
                <img src="<?= e($adminCurrent['flag']) ?>" alt="<?= e($adminCurrent['alt']) ?>" width="20" height="14" style="border-radius:2px;flex-shrink:0;">
                <span><?= e($adminCurrent['label']) ?></span>
                <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="absolute top-full right-0 mt-1 w-40 bg-gray-900/95 backdrop-blur-md border border-white/10 rounded-xl shadow-xl p-1.5 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50" dir="ltr">
                <?php foreach ($adminLangMeta as $code => $meta): ?>
                <a href="<?= e(lang_url($code)) ?>"
                   lang="<?= $code ?>" hreflang="<?= $code ?>"
                   class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors <?= $lang === $code ? 'text-emerald-400 bg-white/5' : 'text-white/80 hover:text-white hover:bg-white/5' ?>">
                    <img src="<?= e($meta['flag']) ?>" alt="<?= e($meta['alt']) ?>" width="20" height="14" style="border-radius:2px;flex-shrink:0;">
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
        
        <!-- User info -->
        <div class="flex items-center gap-3 text-sm">
            <div class="text-right">
                <p class="text-white font-medium"><?= e($user['name'] ?? '') ?></p>
                <p class="text-emerald-300/60 text-xs"><?= e($user['role'] ?? '') ?></p>
            </div>
            <div class="w-8 h-8 rounded-full bg-emerald-500/30 border border-emerald-400/30 flex items-center justify-center text-emerald-300 font-bold text-sm">
                <?= mb_substr(e($user['name'] ?? 'U'), 0, 1) ?>
            </div>
            
            <a href="<?= e(admin_url('logout.php')) ?>" class="text-white/50 hover:text-red-400 transition-colors px-2 py-1 text-xs" title="Logout">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>
    </div>
</header>