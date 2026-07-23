<?php
/**
 * Admin 404 Page - SEPJ Gabès
 */
require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
session_start_secure();
require_login();
http_response_code(404);
$lang = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attribute($lang) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body class="admin-theme-bg min-h-screen">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="relative z-10 flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden pt-16">
            <?php include 'includes/header.php'; ?>
            <main class="flex-1 overflow-y-auto p-6 flex items-center justify-center">
                <div class="text-center">
                    <h1 class="text-6xl font-bold text-white mb-4">404</h1>
                    <p class="text-xl text-emerald-200/80 mb-8">
                        <?php if ($lang === 'ar'): ?>الصفحة غير موجودة
                        <?php elseif ($lang === 'fr'): ?>Page non trouvée
                        <?php else: ?>Page not found
                        <?php endif; ?>
                    </p>
                    <a href="dashboard.php" class="glass-btn glass-btn-primary">
                        <?php if ($lang === 'ar'): ?>العودة إلى لوحة القيادة
                        <?php elseif ($lang === 'fr'): ?>Retour au tableau de bord
                        <?php else: ?>Back to Dashboard
                        <?php endif; ?>
                    </a>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
    <script src="../public/assets/js/admin.js"></script>
</body>
</html>