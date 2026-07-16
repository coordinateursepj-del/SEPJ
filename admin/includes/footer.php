<?php
/**
 * Admin Footer - SEPJ Gabès
 * 
 * Variables expected: $lang
 */

$lang = $lang ?? current_lang();
?>
<!-- Admin Footer -->
<footer class="bg-white/5 backdrop-blur-md border-t border-white/10 px-6 py-3 shrink-0">
    <div class="flex items-center justify-between text-xs text-white/40">
        <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
        <p>
            <?php if ($lang === 'ar'): ?>جميع الحقوق محفوظة
            <?php elseif ($lang === 'fr'): ?>Tous droits réservés
            <?php else: ?>All rights reserved
            <?php endif; ?>
        </p>
    </div>
</footer>

<!-- Flash message display -->
<?php $flash = get_flash(); ?>
<?php if ($flash): ?>
<div id="flashMessage" class="fixed top-4 right-4 z-50 px-6 py-3 rounded-lg backdrop-blur-md shadow-xl transition-all duration-500 
    <?= $flash['type'] === 'success' ? 'bg-emerald-600/30 border border-emerald-500/30 text-emerald-300' : '' ?>
    <?= $flash['type'] === 'error' ? 'bg-red-600/30 border border-red-500/30 text-red-300' : '' ?>
    <?= $flash['type'] === 'warning' ? 'bg-yellow-600/30 border border-yellow-500/30 text-yellow-300' : '' ?>
    <?= $flash['type'] === 'info' ? 'bg-blue-600/30 border border-blue-500/30 text-blue-300' : '' ?>">
    <div class="flex items-center gap-2">
        <span><?= e($flash['message']) ?></span>
        <button onclick="this.parentElement.parentElement.remove()" class="text-white/50 hover:text-white">&times;</button>
    </div>
</div>
<script>
    setTimeout(function() {
        var el = document.getElementById('flashMessage');
        if (el) { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 500); }
    }, 5000);
</script>
<?php endif; ?>