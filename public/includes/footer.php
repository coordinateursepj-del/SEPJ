<?php
/**
 * Public Footer - SEPJ Gabès
 */

$lang = current_lang();
?>
<!-- Footer -->
<footer class="site-footer">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div>
                <div class="flex justify-center mb-5">
                    <a href="index.php" aria-label="<?= e(APP_NAME) ?>">
                        <img src="assets/logo-sepj.png" alt="SEPJ Gabès" class="h-28 w-auto opacity-90" loading="lazy">
                    </a>
                </div>
                <h3 class="font-bold text-white mb-3 text-center"><?= e(get_setting('company_name', $lang) ?: __('company_name', $lang)) ?></h3>
                <p class="text-sm text-emerald-200/70 leading-relaxed"><?= e(get_setting('about_summary', $lang)) ?></p>
            </div>
            <div>
                <h3 class="font-bold text-white mb-4"><?= __('quick_links', $lang) ?></h3>
                <div class="space-y-2">
                    <a href="projects.php" class="footer-link text-sm"><?= __('nav_projects', $lang) ?></a>
                    <a href="services.php" class="footer-link text-sm"><?= __('nav_services', $lang) ?></a>
                    <a href="news.php" class="footer-link text-sm"><?= __('nav_news', $lang) ?></a>
                    <a href="contact.php" class="footer-link text-sm"><?= __('nav_contact', $lang) ?></a>
                </div>
            </div>
            <div>
                <h3 class="font-bold text-white mb-4"><?= __('contact_us', $lang) ?></h3>
                <div class="space-y-3 text-sm text-emerald-200/70">
                    <p class="flex items-center gap-2"><i class="fa-solid fa-location-dot text-emerald-400 w-4 shrink-0" aria-hidden="true"></i><?= e(get_setting('address', $lang)) ?></p>
                    <p class="flex items-center gap-2"><i class="fa-solid fa-phone text-emerald-400 w-4 shrink-0" aria-hidden="true"></i><?= e(get_setting('phone', $lang)) ?></p>
                    <p class="flex items-center gap-2"><i class="fa-solid fa-envelope text-emerald-400 w-4 shrink-0" aria-hidden="true"></i><?= e(get_setting('email_primary', $lang)) ?></p>
                    <p class="flex items-center gap-2"><i class="fa-solid fa-box text-emerald-400 w-4 shrink-0" aria-hidden="true"></i><?= e(get_setting('po_box', $lang)) ?></p>
                </div>
            </div>
        </div>
        <div class="border-t border-white/10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-emerald-300/40">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. <?= __('all_rights_reserved', $lang) ?></p>
            <div class="flex items-center gap-3">
                <a href="https://www.linkedin.com/in/sepjgabes/" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-400 transition-colors" aria-label="LinkedIn">
                    <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                </a>
                <a href="https://www.facebook.com/SEPJGabes/" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-400 transition-colors" aria-label="Facebook">
                    <i class="fab fa-facebook-f" aria-hidden="true"></i>
                </a>
                <a href="https://www.instagram.com/sepjgabes" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-400 transition-colors" aria-label="Instagram">
                    <i class="fab fa-instagram" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<button id="backToTop"
        class="back-to-top"
        onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
        aria-label="Back to top">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
    </svg>
</button>

<!-- Left Floating Group: Contact + Socials -->
<?php
$contactLabel     = $lang === 'ar' ? 'اتصل بنا' : ($lang === 'fr' ? 'Contactez-nous' : 'Contact us');
$contactAriaLabel = $lang === 'ar' ? 'تواصل معنا' : ($lang === 'fr' ? 'Nous contacter' : 'Contact us');
?>
<div class="float-stack">
<!-- Floating Contact Button -->
<a href="contact.php"
   id="floatContact"
   class="float-contact"
   aria-label="<?= e($contactAriaLabel) ?>">
    <svg class="float-contact-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
    </svg>
    <span class="float-contact-label"><?= e($contactLabel) ?></span>
</a>

<!-- Social Buttons Group -->
<div class="social-float-group">
    <a href="https://www.linkedin.com/in/sepjgabes/"
       target="_blank" rel="noopener noreferrer"
       class="social-float-btn social-float-linkedin"
       aria-label="LinkedIn — SEPJ Gabès">
        <i class="fab fa-linkedin-in" aria-hidden="true"></i>
        <span class="social-float-label">LinkedIn</span>
    </a>
    <a href="https://www.facebook.com/SEPJGabes/"
       target="_blank" rel="noopener noreferrer"
       class="social-float-btn social-float-facebook"
       aria-label="Facebook — SEPJ Gabès">
        <i class="fab fa-facebook-f" aria-hidden="true"></i>
        <span class="social-float-label">Facebook</span>
    </a>
    <a href="https://www.instagram.com/sepjgabes"
       target="_blank" rel="noopener noreferrer"
       class="social-float-btn social-float-instagram"
       aria-label="Instagram — SEPJ Gabès">
        <i class="fab fa-instagram" aria-hidden="true"></i>
        <span class="social-float-label">Instagram</span>
    </a>
</div>
</div><!-- /.float-stack -->

<script src="assets/js/main.js?v=<?= @filemtime(__DIR__ . '/../assets/js/main.js') ?: '1' ?>"></script>
</body>
</html>