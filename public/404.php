<?php
/**
 * Public 404 Page - SEPJ Gabès
 */
http_response_code(404);
require_once 'includes/header.php';
$lang = current_lang();
?>
<main id="main-content">
    <div class="page-hero">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-6xl font-bold text-white mb-4">404</h1>
            <p class="text-xl text-emerald-200/80 mb-8"><?= __('page_not_found', $lang) ?></p>
            <a href="index.php" class="glass-btn glass-btn-primary"><?= __('back_to_home', $lang) ?></a>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>