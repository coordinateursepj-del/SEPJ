<?php
/**
 * Admin Dashboard - SEPJ Gabès
 */

require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/i18n.php';

session_start_secure();
require_login();

$lang = current_lang();
$user = current_user();

// Get statistics — all counts in one round trip
try {
    $pdo = db();

    $counts = $pdo->query("
        SELECT
            SUM(type = 'post'    AND status = 'published') AS posts,
            SUM(type = 'project' AND status = 'published') AS projects,
            SUM(type = 'service' AND status = 'published') AS services,
            COUNT(*)                                       AS total_content
        FROM content_items
    ")->fetch();

    $totalPosts    = (int)($counts['posts']         ?? 0);
    $totalProjects = (int)($counts['projects']      ?? 0);
    $totalServices = (int)($counts['services']      ?? 0);
    $totalContent  = (int)($counts['total_content'] ?? 0);

    $mediaRow     = $pdo->query("SELECT COUNT(*) FROM media")->fetchColumn();
    $totalMedia   = (int)$mediaRow;

    $userRow      = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalUsers   = (int)$userRow;

    $msgCounts    = $pdo->query("SELECT COUNT(*) AS total, SUM(status='new') AS new_msgs FROM contact_messages")->fetch();
    $newMessages  = (int)($msgCounts['new_msgs'] ?? 0);
    $totalMessages= (int)($msgCounts['total']    ?? 0);

    // Recent content items
    $recentItems = $pdo->query("
        SELECT id, type, slug,
               COALESCE(NULLIF(title_ar,''),NULLIF(title_fr,''),NULLIF(title_en,''),'No title') AS title,
               status, created_at
        FROM content_items
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll();

    // Recent messages
    $recentMessages = $pdo->query("
        SELECT id, name, email, subject, status, created_at
        FROM contact_messages
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $totalPosts = $totalProjects = $totalServices = $totalMedia = $totalContent = $totalUsers = $newMessages = $totalMessages = 0;
    $recentItems = $recentMessages = [];
}

// Stats cards data
$statsCards = [
    ['label' => __('content_posts'), 'value' => $totalPosts, 'icon' => '📰', 'color' => 'from-emerald-500 to-green-600'],
    ['label' => __('content_projects'), 'value' => $totalProjects, 'icon' => '🏗️', 'color' => 'from-blue-500 to-cyan-600'],
    ['label' => __('content_services'), 'value' => $totalServices, 'icon' => '🔧', 'color' => 'from-purple-500 to-violet-600'],
    ['label' => __('content_media'), 'value' => $totalMedia, 'icon' => '🖼️', 'color' => 'from-amber-500 to-orange-600'],
    ['label' => __('content_total'), 'value' => $totalContent, 'icon' => '📄', 'color' => 'from-pink-500 to-rose-600'],
    ['label' => __('new_messages'), 'value' => $newMessages, 'icon' => '✉️', 'color' => 'from-red-500 to-pink-600'],
];
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attribute($lang) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة القيادة - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body class="admin-theme-bg min-h-screen">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    
    <div class="relative z-10 flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-6">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-white">
                        <?php if ($lang === 'ar'): ?>لوحة القيادة
                        <?php elseif ($lang === 'fr'): ?>Tableau de bord
                        <?php else: ?>Dashboard
                        <?php endif; ?>
                    </h1>
                    <p class="text-emerald-300/70 mt-1">
                        <?php if ($lang === 'ar'): ?>مرحباً بك يا <?= e($user['name']) ?>
                        <?php elseif ($lang === 'fr'): ?>Bienvenue, <?= e($user['name']) ?>
                        <?php else: ?>Welcome, <?= e($user['name']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
                    <?php foreach ($statsCards as $card): ?>
                    <div class="glass-card p-4 text-center hover:scale-105 transition-transform cursor-default">
                        <div class="text-3xl mb-2"><?= $card['icon'] ?></div>
                        <div class="stat-number text-2xl"><?= $card['value'] ?></div>
                        <div class="text-emerald-200/80 text-xs mt-1"><?= e($card['label']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Content -->
                    <div class="glass-card-static p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">
                            <?php if ($lang === 'ar'): ?>أحدث المحتويات
                            <?php elseif ($lang === 'fr'): ?>Contenu récent
                            <?php else: ?>Recent Content
                            <?php endif; ?>
                        </h2>
                        
                        <?php if (empty($recentItems)): ?>
                        <div class="empty-state py-8">
                            <div class="empty-state-icon text-4xl">📄</div>
                            <p class="text-sm">
                                <?php if ($lang === 'ar'): ?>لا يوجد محتوى بعد
                                <?php elseif ($lang === 'fr'): ?>Aucun contenu pour le moment
                                <?php else: ?>No content yet
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recentItems as $item): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="text-xs px-2 py-1 rounded-full bg-emerald-500/20 text-emerald-300 uppercase whitespace-nowrap">
                                        <?= e($item['type']) ?>
                                    </span>
                                    <span class="text-sm text-white truncate"><?= e(mb_substr($item['title'], 0, 50)) ?></span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs px-2 py-0.5 rounded <?= $item['status'] === 'published' ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300' ?>">
                                        <?= e($item['status']) ?>
                                    </span>
                                    <span class="text-xs text-emerald-300/50"><?= format_date($item['created_at'], 'd/m/Y') ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Messages -->
                    <div class="glass-card-static p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">
                            <?php if ($lang === 'ar'): ?>آخر الرسائل
                            <?php elseif ($lang === 'fr'): ?>Messages récents
                            <?php else: ?>Recent Messages
                            <?php endif; ?>
                            <?php if ($newMessages > 0): ?>
                            <span class="text-xs bg-red-500/20 text-red-300 px-2 py-0.5 rounded-full mr-2"><?= $newMessages ?> <?php if ($lang === 'ar'): ?>جديدة
                            <?php elseif ($lang === 'fr'): ?>nouveau(x)
                            <?php else: ?>new
                            <?php endif; ?></span>
                            <?php endif; ?>
                        </h2>
                        
                        <?php if (empty($recentMessages)): ?>
                        <div class="empty-state py-8">
                            <div class="empty-state-icon text-4xl">✉️</div>
                            <p class="text-sm">
                                <?php if ($lang === 'ar'): ?>لا توجد رسائل بعد
                                <?php elseif ($lang === 'fr'): ?>Aucun message pour le moment
                                <?php else: ?>No messages yet
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recentMessages as $msg): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm text-white truncate"><?= e($msg['name']) ?></p>
                                    <p class="text-xs text-emerald-300/60 truncate"><?= e($msg['subject'] ?: '—') ?></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs px-2 py-0.5 rounded 
                                        <?= $msg['status'] === 'new' ? 'bg-red-500/20 text-red-300' : 
                                           ($msg['status'] === 'read' ? 'bg-blue-500/20 text-blue-300' : 'bg-gray-500/20 text-gray-300') ?>">
                                        <?= e($msg['status']) ?>
                                    </span>
                                    <span class="text-xs text-emerald-300/50"><?= format_date($msg['created_at'], 'd/m/Y') ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <a href="messages/" class="text-sm text-emerald-400 hover:text-emerald-300 transition-colors">
                                <?php if ($lang === 'ar'): ?>عرض كل الرسائل →
                                <?php elseif ($lang === 'fr'): ?>Voir tous les messages →
                                <?php else: ?>View all messages →
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="mt-6 glass-card-static p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">
                        <?php if ($lang === 'ar'): ?>إجراءات سريعة
                        <?php elseif ($lang === 'fr'): ?>Actions rapides
                        <?php else: ?>Quick Actions
                        <?php endif; ?>
                    </h2>
                    <div class="flex flex-wrap gap-3">
                        <a href="content/create.php?type=post" class="glass-btn text-sm">📰 <?= __('nav_news', $lang) ?></a>
                        <a href="content/create.php?type=project" class="glass-btn text-sm">🏗️ <?= __('nav_projects', $lang) ?></a>
                        <a href="content/create.php?type=activity" class="glass-btn text-sm">📋 <?= __('nav_activities', $lang) ?></a>
                        <a href="media/upload.php" class="glass-btn text-sm">🖼️ <?php if ($lang === 'ar'): ?>رفع صور
                        <?php elseif ($lang === 'fr'): ?>Uploader
                        <?php else: ?>Upload
                        <?php endif; ?></a>
                        <a href="messages/" class="glass-btn text-sm">✉️ <?php if ($lang === 'ar'): ?>الرسائل
                        <?php elseif ($lang === 'fr'): ?>Messages
                        <?php else: ?>Messages
                        <?php endif; ?></a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="../public/assets/js/admin.js"></script>
</body>
</html>