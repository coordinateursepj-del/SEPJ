<?php
/**
 * Admin Sidebar - SEPJ Gabès
 * 
 * Variables expected: $lang
 */

$lang = $lang ?? current_lang();
$currentFile = basename($_SERVER['PHP_SELF']);
$currentType = $_GET['type'] ?? '';
$currentQuery = $_SERVER['QUERY_STRING'] ?? '';

function isSidebarActive(string $file, string $type = ''): bool
{
    $currentFile = basename($_SERVER['PHP_SELF']);
    $currentType = $_GET['type'] ?? '';
    
    if ($currentFile === $file) {
        if ($type && $currentType !== $type) {
            return false;
        }
        return true;
    }
    return false;
}

$menuItems = [
    'dashboard' => [
        'label_ar' => 'لوحة القيادة',
        'label_fr' => 'Tableau de bord',
        'label_en' => 'Dashboard',
        'url' => 'dashboard.php',
        'icon' => '📊',
    ],
    'content_post' => [
        'label_ar' => 'الأخبار',
        'label_fr' => 'Actualités',
        'label_en' => 'News',
        'url' => 'content/?type=post',
        'icon' => '📰',
    ],
    'content_page' => [
        'label_ar' => 'الصفحات',
        'label_fr' => 'Pages',
        'label_en' => 'Pages',
        'url' => 'content/?type=page',
        'icon' => '📄',
    ],
    'content_project' => [
        'label_ar' => 'المشاريع',
        'label_fr' => 'Projets',
        'label_en' => 'Projects',
        'url' => 'content/?type=project',
        'icon' => '🏗️',
    ],
    'content_service' => [
        'label_ar' => 'الخدمات',
        'label_fr' => 'Services',
        'label_en' => 'Services',
        'url' => 'content/?type=service',
        'icon' => '🔧',
    ],
    'content_activity' => [
        'label_ar' => 'الأنشطة',
        'label_fr' => 'Activités',
        'label_en' => 'Activities',
        'url' => 'content/?type=activity',
        'icon' => '📋',
    ],
    'content_prize' => [
        'label_ar' => 'التتويجات',
        'label_fr' => 'Distinctions',
        'label_en' => 'Awards',
        'url' => 'content/?type=prize',
        'icon' => '🏆',
    ],
    'content_rse' => [
        'label_ar' => 'المسؤولية المجتمعية',
        'label_fr' => 'RSE',
        'label_en' => 'CSR',
        'url' => 'content/?type=rse',
        'icon' => '🌱',
    ],
    'content_resource' => [
        'label_ar' => 'الموارد والتنمية',
        'label_fr' => 'Ressources',
        'label_en' => 'Resources',
        'url' => 'content/?type=resource',
        'icon' => '📚',
    ],
    'content_sport' => [
        'label_ar' => 'الرياضة والعمل',
        'label_fr' => 'Sports',
        'label_en' => 'Sports',
        'url' => 'content/?type=sport',
        'icon' => '⚽',
    ],
    'content_video' => [
        'label_ar' => 'الفيديو',
        'label_fr' => 'Vidéos',
        'label_en' => 'Videos',
        'url' => 'content/?type=video',
        'icon' => '🎥',
    ],
    'media' => [
        'label_ar' => 'الوسائط',
        'label_fr' => 'Médias',
        'label_en' => 'Media',
        'url' => 'media/',
        'icon' => '🖼️',
    ],
    'messages' => [
        'label_ar' => 'الرسائل',
        'label_fr' => 'Messages',
        'label_en' => 'Messages',
        'url' => 'messages/',
        'icon' => '✉️',
    ],
    'settings' => [
        'label_ar' => 'الإعدادات',
        'label_fr' => 'Paramètres',
        'label_en' => 'Settings',
        'url' => 'settings/',
        'icon' => '⚙️',
    ],
    'users' => [
        'label_ar' => 'المستخدمون',
        'label_fr' => 'Utilisateurs',
        'label_en' => 'Users',
        'url' => 'users/',
        'icon' => '👥',
    ],
];

function getSidebarLabel(array $item, string $lang): string
{
    return $item['label_' . $lang] ?? $item['label_ar'];
}

function isMenuItemActive(array $item): bool
{
    $url = $item['url'];
    $currentFile = basename($_SERVER['PHP_SELF']);
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    $currentType = $_GET['type'] ?? '';
    
    // Check for content types
    if (strpos($url, 'content/?type=') === 0) {
        $type = str_replace('content/?type=', '', $url);
        return $currentDir === 'content' && $currentType === $type;
    }
    
    // Check for direct file matches
    if (strpos($url, '.php') !== false) {
        return $currentFile === $url;
    }
    
    // Check for directory matches (media/, messages/, settings/, users/)
    return $currentDir === rtrim($url, '/');
}
?>
<!-- Sidebar -->
<aside id="adminSidebar" class="w-64 bg-white/5 backdrop-blur-md border-l border-white/10 flex flex-col shrink-0 overflow-y-auto transition-all duration-300">
    <!-- Logo -->
    <div class="p-4 border-b border-white/10">
        <h2 class="text-lg font-bold text-white"><?= e(APP_NAME) ?></h2>
        <p class="text-xs text-emerald-300/60 truncate">
            <?php if ($lang === 'ar'): ?>لوحة الإدارة
            <?php elseif ($lang === 'fr'): ?>Panneau d'administration
            <?php else: ?>Admin Panel
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 p-3 space-y-1">
        <?php foreach ($menuItems as $key => $navItem): ?>
        <a href="<?= e(admin_url($navItem['url'])) ?>"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all
                  <?= isMenuItemActive($navItem)
                    ? 'bg-emerald-600/20 text-emerald-300 border border-emerald-500/20'
                    : 'text-white/70 hover:text-white hover:bg-white/5' ?>">
            <span class="text-base"><?= $navItem['icon'] ?></span>
            <span><?= e(getSidebarLabel($navItem, $lang)) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Bottom section -->
    <div class="p-3 border-t border-white/10">
        <a href="<?= e(BASE_URL) ?>/" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-white/50 hover:text-emerald-300 hover:bg-white/5 transition-all">
            <span>🌐</span>
            <span>
                <?php if ($lang === 'ar'): ?>عرض الموقع
                <?php elseif ($lang === 'fr'): ?>Voir le site
                <?php else: ?>View site
                <?php endif; ?>
            </span>
        </a>
        <form method="POST" action="<?= e(admin_url('logout.php')) ?>" style="display:contents">
            <?= csrf_field() ?>
            <button type="submit" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-white/50 hover:text-red-400 hover:bg-white/5 transition-all w-full text-start">
                <span>🚪</span>
                <span>
                    <?php if ($lang === 'ar'): ?>تسجيل الخروج
                    <?php elseif ($lang === 'fr'): ?>Déconnexion
                    <?php else: ?>Logout
                    <?php endif; ?>
                </span>
            </button>
        </form>
    </div>
</aside>

<!-- Mobile overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 hidden"></div>