<?php
/**
 * Public Header - SEPJ Gabès
 */

require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/i18n.php';

session_start_secure();

$lang = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attribute($lang) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(get_setting('seo_title', $lang) ?: APP_NAME) ?></title>
    <meta name="description" content="<?= e(get_setting('seo_description', $lang)) ?>">
    <!-- Anti-flicker: apply saved theme before paint (default: light) -->
    <script>(function(){var t=localStorage.getItem('sepj-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');else document.documentElement.setAttribute('data-theme','light');}());</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Display serif (Fraunces) — non-blocking (display=swap), used for headings only -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: '1' ?>">
    <!-- Font Awesome for icons -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- Skip navigation — visible only on keyboard focus -->
    <a href="#main-content" class="skip-nav">
        <?php echo $lang === 'ar'
            ? 'تخطى إلى المحتوى الرئيسي'
            : ($lang === 'fr' ? 'Passer au contenu principal' : 'Skip to main content'); ?>
    </a>

    <!-- Background blobs -->
    <div class="blob blob-1" aria-hidden="true"></div>
    <div class="blob blob-2" aria-hidden="true"></div>
    <div class="blob blob-3" aria-hidden="true"></div>
    <div class="blob blob-4" aria-hidden="true"></div>
    <div class="bg-mesh" aria-hidden="true"></div>

    <?php include 'nav.php'; ?>