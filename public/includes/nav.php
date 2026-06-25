<?php
/**
 * Public Navigation - SEPJ Gabès
 */

$lang = current_lang();
$currentFile = basename($_SERVER['PHP_SELF']);

function nav_is_active(string $file): string {
    return basename($_SERVER['PHP_SELF']) === $file ? 'active' : '';
}

$navItems = [
    ['url' => 'index.php',   'label' => __('nav_home')],
    ['url' => 'about.php',  'label' => __('nav_about')],
    ['url' => 'projects.php',                'label' => __('nav_projects')],
    ['url' => 'services.php',                'label' => __('nav_services')],
    ['url' => 'rse.php',                     'label' => __('nav_rse')],
    ['url' => 'resources.php',               'label' => __('nav_resources')],
    ['url' => 'sports.php',                  'label' => __('nav_sports')],
    ['url' => 'news.php',                    'label' => __('nav_news')],
    ['url' => 'activities.php',              'label' => __('nav_activities')],
    ['url' => 'prizes.php',                  'label' => __('nav_prizes')],
    ['url' => 'gallery.php',                 'label' => __('nav_gallery')],
    ['url' => 'videos.php',                  'label' => __('nav_videos')],
    ['url' => 'contact.php',                 'label' => __('nav_contact')],
];

$moreLabel   = $lang === 'ar' ? 'المزيد' : ($lang === 'fr' ? 'Plus' : 'More');
$navAriaLabel = $lang === 'ar' ? 'التنقل الرئيسي' : ($lang === 'fr' ? 'Navigation principale' : 'Main navigation');
$menuToggleLabel = $lang === 'ar' ? 'فتح القائمة' : ($lang === 'fr' ? 'Ouvrir le menu' : 'Toggle navigation menu');
$searchAriaLabel = $lang === 'ar' ? 'بحث' : ($lang === 'fr' ? 'Recherche' : 'Search');
?>
<!-- Navbar — always LTR layout regardless of page direction -->
<nav class="navbar" dir="ltr" aria-label="<?= e($navAriaLabel) ?>">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo (always left) -->
            <a href="index.php" class="flex items-center gap-2 text-white font-bold text-lg" aria-label="<?= e(APP_NAME) ?> — <?= $lang === 'ar' ? 'الصفحة الرئيسية' : ($lang === 'fr' ? 'Accueil' : 'Home') ?>">
                <img src="assets/logo-sepj.png" alt="SEPJ Gabès logo" class="h-24 w-auto" aria-hidden="true">
                <span class="hidden sm:inline"><?= e(APP_NAME) ?></span>
                <span class="inline sm:hidden" aria-hidden="true">SEPJ</span>
            </a>

            <!-- Desktop Nav -->
            <div class="desktop-nav flex items-center gap-1" role="list">
                <?php foreach (array_slice($navItems, 0, 6) as $item):
                    $active = nav_is_active(basename($item['url']));
                ?>
                <a href="<?= e($item['url']) ?>"
                   class="nav-link px-3 py-2 text-sm <?= $active ?>"
                   <?= $active ? 'aria-current="page"' : '' ?>
                   role="listitem">
                    <?= e($item['label']) ?>
                </a>
                <?php endforeach; ?>

                <!-- "More" dropdown -->
                <div class="relative group ml-2">
                    <button id="moreDropdownBtn"
                            class="nav-link px-3 py-2 text-sm flex items-center gap-1"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="moreDropdownMenu">
                        <?= e($moreLabel) ?>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="moreDropdownMenu"
                         role="menu"
                         class="more-dropdown-menu absolute top-full right-0 mt-1 w-56 glass-card-static p-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">
                        <?php foreach (array_slice($navItems, 6) as $item):
                            $active = nav_is_active(basename($item['url']));
                        ?>
                        <a href="<?= e($item['url']) ?>"
                           role="menuitem"
                           class="block px-3 py-2 text-sm text-white/80 hover:text-white hover:bg-white/5 rounded-lg transition-colors"
                           <?= $active ? 'aria-current="page"' : '' ?>>
                            <?= e($item['label']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Language Switcher Dropdown -->
                <?php
                $langMeta = [
                    'ar' => ['label' => 'العربية',    'flag' => 'assets/tn1.png', 'alt' => 'تونس'],
                    'fr' => ['label' => 'الفرنسية',   'flag' => 'assets/fr1.png', 'alt' => 'France'],
                    'en' => ['label' => 'الإنجليزية', 'flag' => 'assets/uk1.png', 'alt' => 'UK'],
                ];
                $currentMeta = $langMeta[$lang];
                ?>
                <div class="relative group ml-1">
                    <button id="langDropdownBtn"
                            class="nav-link px-3 py-2 text-sm flex items-center gap-2"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="langDropdownMenu"
                            aria-label="اللغة">
                        <img src="<?= e($currentMeta['flag']) ?>" alt="<?= e($currentMeta['alt']) ?>" width="20" height="14" style="border-radius:2px;vertical-align:middle;">
                        <span><?= e($currentMeta['label']) ?></span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="langDropdownMenu"
                         role="menu"
                         class="lang-dropdown-menu more-dropdown-menu absolute top-full right-0 mt-1 w-44 glass-card-static p-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">
                        <?php foreach ($langMeta as $code => $meta): ?>
                        <a href="<?= e(lang_url($code)) ?>"
                           role="menuitem"
                           lang="<?= $code ?>"
                           hreflang="<?= $code ?>"
                           class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors <?= $lang === $code ? 'text-emerald-400 bg-white/5' : 'text-white/80 hover:text-white hover:bg-white/5' ?>"
                           <?= $lang === $code ? 'aria-current="true"' : '' ?>>
                            <img src="<?= e($meta['flag']) ?>" alt="<?= e($meta['alt']) ?>" width="22" height="16" style="border-radius:2px;flex-shrink:0;">
                            <span><?= e($meta['label']) ?></span>
                            <?php if ($lang === $code): ?>
                            <svg class="w-3 h-3 mr-auto text-emerald-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Search -->
                <a href="search.php"
                   class="text-white/60 hover:text-white transition-colors ml-2"
                   aria-label="<?= e($searchAriaLabel) ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </a>

                <!-- Theme Toggle Switch -->
                <button id="themeToggle"
                        class="theme-toggle ml-2"
                        role="switch"
                        aria-checked="false"
                        aria-label="<?= $lang === 'ar' ? 'تفعيل الوضع الفاتح' : ($lang === 'fr' ? 'Passer en mode clair' : 'Switch to light mode') ?>">
                    <span class="theme-toggle-track" aria-hidden="true">
                        <span class="theme-toggle-thumb">
                            <!-- Moon: shown in dark mode -->
                            <svg class="theme-icon-moon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="10" height="10">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <!-- Sun: shown in light mode -->
                            <svg class="theme-icon-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="10" height="10">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </span>
                    </span>
                </button>
            </div>

            <!-- Mobile menu toggle button -->
            <button id="mobileMenuBtn"
                    class="mobile-menu-btn text-white p-2"
                    aria-label="<?= e($menuToggleLabel) ?>"
                    aria-expanded="false"
                    aria-controls="mobileMenu">
                <!-- Bars icon (shown when closed) -->
                <svg class="w-6 h-6 menu-icon-bars" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <!-- X icon (shown when open) -->
                <svg class="w-6 h-6 menu-icon-close" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu"
         class="mobile-menu"
         role="dialog"
         aria-modal="true"
         aria-label="<?= $lang === 'ar' ? 'القائمة الرئيسية' : ($lang === 'fr' ? 'Navigation principale' : 'Main navigation') ?>">
        <?php foreach ($navItems as $item):
            $active = nav_is_active(basename($item['url']));
        ?>
        <a href="<?= e($item['url']) ?>"
           class="nav-link text-lg"
           <?= $active ? 'aria-current="page"' : '' ?>>
            <?= e($item['label']) ?>
        </a>
        <?php endforeach; ?>
        <div class="flex gap-2 mt-4" aria-label="<?= $lang === 'ar' ? 'تبديل اللغة' : 'Language' ?>">
            <a href="<?= e(lang_url('ar')) ?>"
               class="glass-btn text-sm <?= $lang === 'ar' ? 'glass-btn-primary' : '' ?>"
               lang="ar" hreflang="ar"
               aria-label="<?= $lang === 'ar' ? 'اللغة الحالية: العربية' : 'العربية' ?>">العربية</a>
            <a href="<?= e(lang_url('fr')) ?>"
               class="glass-btn text-sm <?= $lang === 'fr' ? 'glass-btn-primary' : '' ?>"
               lang="fr" hreflang="fr"
               aria-label="<?= $lang === 'fr' ? 'Langue actuelle : Français' : 'Français' ?>">Français</a>
            <a href="<?= e(lang_url('en')) ?>"
               class="glass-btn text-sm <?= $lang === 'en' ? 'glass-btn-primary' : '' ?>"
               lang="en" hreflang="en"
               aria-label="<?= $lang === 'en' ? 'Current language: English' : 'English' ?>">English</a>
        </div>
    </div>
</nav>
