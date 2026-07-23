/**
 * SEPJ Gabès — Public JavaScript
 * Production-grade: ARIA, focus management, debouncing,
 * lazy-load, lightbox with touch/keyboard/counter, reduced-motion.
 */

document.addEventListener('DOMContentLoaded', function () {
    initMobileMenu();
    initScrollReveal();
    initBackToTop();
    initNavbarScroll();
    initMoreDropdown();
    initLangDropdown();
    initLazyImages();
    initContactForm();
    initThemeToggle();
    initVideoThumbnails();
    initStatCounters();
    initCursorFX();
    initCardSpotlight();
});

/* ─────────────────────────────────────────────
   Mobile Menu
   ───────────────────────────────────────────── */
function initMobileMenu() {
    const btn  = document.getElementById('mobileMenuBtn');
    const menu = document.getElementById('mobileMenu');
    if (!btn || !menu) return;

    function openMenu() {
        menu.classList.add('active');
        btn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        const first = menu.querySelector('a');
        if (first) first.focus();
    }

    function closeMenu() {
        menu.classList.remove('active');
        btn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        btn.focus();
    }

    btn.addEventListener('click', () => {
        menu.classList.contains('active') ? closeMenu() : openMenu();
    });

    // Close on any nav link click
    menu.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.classList.contains('active')) closeMenu();
    });
}

/* ─────────────────────────────────────────────
   Scroll Reveal (with stagger + reduced-motion)
   ───────────────────────────────────────────── */
function initScrollReveal() {
    const elements = document.querySelectorAll('.reveal');
    if (elements.length === 0) return;

    // Respect reduced-motion: reveal all immediately
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        elements.forEach(el => el.classList.add('visible'));
        return;
    }

    // Auto-stagger sibling reveals inside the same parent container
    const parents = new Map();
    elements.forEach(el => {
        const p = el.parentElement;
        if (!parents.has(p)) parents.set(p, []);
        parents.get(p).push(el);
    });
    parents.forEach(siblings => {
        if (siblings.length > 1) {
            siblings.forEach((el, i) => {
                el.style.transitionDelay = (i * 100) + 'ms';
            });
        }
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    elements.forEach(el => observer.observe(el));
}

/* ─────────────────────────────────────────────
   Back to Top Button (debounced, ARIA-labelled)
   ───────────────────────────────────────────── */
function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;

    // Set multilingual accessible label
    const lang = document.documentElement.lang || 'ar';
    btn.setAttribute('aria-label',
        lang === 'ar' ? 'العودة إلى الأعلى' :
        lang === 'fr' ? 'Retour en haut' :
        'Back to top'
    );

    let t;
    window.addEventListener('scroll', () => {
        clearTimeout(t);
        t = setTimeout(() => {
            btn.classList.toggle('visible', window.scrollY > 400);
        }, 50);
    }, { passive: true });
}

/* ─────────────────────────────────────────────
   Navbar Scroll (debounced)
   ───────────────────────────────────────────── */
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    let t;
    window.addEventListener('scroll', () => {
        clearTimeout(t);
        t = setTimeout(() => {
            navbar.classList.toggle('scrolled', window.scrollY > 80);
        }, 50);
    }, { passive: true });
}

/* ─────────────────────────────────────────────
   "More" Dropdown — Keyboard accessible
   ───────────────────────────────────────────── */
function initMoreDropdown() {
    const btn  = document.getElementById('moreDropdownBtn');
    const menu = document.getElementById('moreDropdownMenu');
    if (!btn || !menu) return;

    function openDropdown() {
        btn.setAttribute('aria-expanded', 'true');
        menu.classList.add('open');
        const first = menu.querySelector('[role="menuitem"]');
        if (first) first.focus();
    }

    function closeDropdown() {
        btn.setAttribute('aria-expanded', 'false');
        menu.classList.remove('open');
    }

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        btn.getAttribute('aria-expanded') === 'true' ? closeDropdown() : openDropdown();
    });

    // Arrow key navigation inside the menu
    menu.addEventListener('keydown', (e) => {
        const items = [...menu.querySelectorAll('[role="menuitem"]')];
        const idx   = items.indexOf(document.activeElement);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            items[(idx + 1) % items.length]?.focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            items[(idx - 1 + items.length) % items.length]?.focus();
        } else if (e.key === 'Escape' || e.key === 'Tab') {
            closeDropdown();
            btn.focus();
        }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!btn.contains(e.target) && !menu.contains(e.target)) closeDropdown();
    });
}

/* ─────────────────────────────────────────────
   Lazy Image Loading (IntersectionObserver)
   ───────────────────────────────────────────── */
function initLazyImages() {
    const images = document.querySelectorAll('img[data-src]');
    if (!images.length) return;

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    if (img.dataset.srcset) img.srcset = img.dataset.srcset;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '200px 0px' });

        images.forEach(img => observer.observe(img));
    } else {
        images.forEach(img => { img.src = img.dataset.src; });
    }
}

/* ─────────────────────────────────────────────
   Contact Form — Loading state
   ───────────────────────────────────────────── */
function initContactForm() {
    const form    = document.getElementById('contactForm');
    const btn     = document.getElementById('contactSubmitBtn');
    const btnText = btn?.querySelector('.btn-text');
    const btnLoad = btn?.querySelector('.btn-loading');
    const status  = document.getElementById('formStatus');
    if (!form) return;

    form.addEventListener('submit', () => {
        if (btn)     btn.disabled = true;
        btnText?.classList.add('hidden');
        btnLoad?.classList.remove('hidden');
        if (status) {
            const lang = document.documentElement.lang || 'ar';
            status.textContent =
                lang === 'ar' ? 'جاري الإرسال...' :
                lang === 'fr' ? 'Envoi en cours...' :
                'Sending...';
        }
    });
}

/* ─────────────────────────────────────────────
   Language Dropdown — keyboard accessible
   ───────────────────────────────────────────── */
function initLangDropdown() {
    const btn  = document.getElementById('langDropdownBtn');
    const menu = document.getElementById('langDropdownMenu');
    if (!btn || !menu) return;

    function openLang() {
        btn.setAttribute('aria-expanded', 'true');
        menu.classList.add('open');
        const first = menu.querySelector('[role="menuitem"]');
        if (first) first.focus();
    }

    function closeLang() {
        btn.setAttribute('aria-expanded', 'false');
        menu.classList.remove('open');
    }

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        btn.getAttribute('aria-expanded') === 'true' ? closeLang() : openLang();
    });

    menu.addEventListener('keydown', (e) => {
        const items = [...menu.querySelectorAll('[role="menuitem"]')];
        const idx   = items.indexOf(document.activeElement);
        if (e.key === 'ArrowDown') { e.preventDefault(); items[(idx + 1) % items.length]?.focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); items[(idx - 1 + items.length) % items.length]?.focus(); }
        else if (e.key === 'Escape' || e.key === 'Tab') { closeLang(); btn.focus(); }
    });

    document.addEventListener('click', (e) => {
        if (!btn.contains(e.target) && !menu.contains(e.target)) closeLang();
    });
}

/* ─────────────────────────────────────────────
   Theme Toggle — localStorage persistence
   ───────────────────────────────────────────── */
function initThemeToggle() {
    // Desktop (#themeToggle) and mobile (#themeToggleMobileBar) both carry
    // this class and must stay in sync — clicking either updates both.
    const btns = document.querySelectorAll('.theme-toggle');
    if (!btns.length) return;

    const html = document.documentElement;
    const lang = html.lang || 'ar';

    const labels = {
        dark:  { ar: 'تفعيل الوضع الفاتح', fr: 'Passer en mode clair', en: 'Switch to light mode' },
        light: { ar: 'تفعيل الوضع الداكن', fr: 'Passer en mode sombre', en: 'Switch to dark mode' },
    };

    function applyState() {
        const isLight = html.getAttribute('data-theme') === 'light';
        const label = labels[isLight ? 'light' : 'dark'][lang] || labels[isLight ? 'light' : 'dark']['en'];
        btns.forEach(btn => {
            btn.setAttribute('aria-checked', isLight ? 'true' : 'false');
            btn.setAttribute('aria-label', label);
        });
    }

    applyState();

    btns.forEach(btn => btn.addEventListener('click', () => {
        const isLight = html.getAttribute('data-theme') === 'light';
        const next = isLight ? 'dark' : 'light';
        html.setAttribute('data-theme', next);
        localStorage.setItem('sepj-theme', next);
        applyState();
        document.dispatchEvent(new CustomEvent('sepj:theme-change'));
    }));
}

/* ─────────────────────────────────────────────
   Lightbox — Accessible, touch-swipe, counter
   ───────────────────────────────────────────── */
let _lbIndex    = 0;
let _lbImages   = [];
let _lbTrigger  = null;

function openLightbox(index, images, triggerEl) {
    _lbIndex   = index;
    _lbImages  = images;
    _lbTrigger = triggerEl || document.activeElement;

    let lb = document.querySelector('.lightbox');
    if (!lb) {
        const lang = document.documentElement.lang || 'ar';
        const label =
            lang === 'ar' ? 'عارض الصور' :
            lang === 'fr' ? 'Visionneuse d\'images' :
            'Image viewer';
        const closeLbl =
            lang === 'ar' ? 'خروج' : lang === 'fr' ? 'Quitter' : 'Exit';
        const prevLbl =
            lang === 'ar' ? 'الصورة السابقة' : lang === 'fr' ? 'Image précédente' : 'Previous image';
        const nextLbl =
            lang === 'ar' ? 'الصورة التالية' : lang === 'fr' ? 'Image suivante' : 'Next image';

        lb = document.createElement('div');
        lb.className = 'lightbox';
        lb.setAttribute('role', 'dialog');
        lb.setAttribute('aria-modal', 'true');
        lb.setAttribute('aria-label', label);
        lb.innerHTML = `
            <button class="lightbox-close" aria-label="${closeLbl}">${closeLbl}</button>
            <button class="lightbox-prev"  aria-label="${prevLbl}">&#8249;</button>
            <img src="" alt="" id="lightboxImg">
            <button class="lightbox-next"  aria-label="${nextLbl}">&#8250;</button>
            <div class="lightbox-counter" aria-live="polite" aria-atomic="true"></div>
        `;
        document.body.appendChild(lb);

        lb.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
        lb.querySelector('.lightbox-prev').addEventListener('click', () => changeLightbox(-1));
        lb.querySelector('.lightbox-next').addEventListener('click', () => changeLightbox(1));

        // Touch swipe support
        let touchX = 0;
        lb.addEventListener('touchstart', (e) => { touchX = e.touches[0].clientX; }, { passive: true });
        lb.addEventListener('touchend', (e) => {
            const diff = touchX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) changeLightbox(diff > 0 ? 1 : -1);
        });

        // Click backdrop (not image) to close
        lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });
    }

    _updateLightbox();
    lb.classList.add('active');
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', _lightboxKeyHandler);
    lb.querySelector('.lightbox-close').focus();
}

function _updateLightbox() {
    const img     = document.getElementById('lightboxImg');
    const counter = document.querySelector('.lightbox-counter');
    if (img) {
        img.src = _lbImages[_lbIndex];
        img.alt = (_lbIndex + 1) + ' / ' + _lbImages.length;
    }
    if (counter) counter.textContent = (_lbIndex + 1) + ' / ' + _lbImages.length;
}

function closeLightbox() {
    const lb = document.querySelector('.lightbox');
    if (lb) lb.classList.remove('active');
    document.body.style.overflow = '';
    document.removeEventListener('keydown', _lightboxKeyHandler);
    if (_lbTrigger && typeof _lbTrigger.focus === 'function') _lbTrigger.focus();
}

function changeLightbox(dir) {
    _lbIndex = (_lbIndex + dir + _lbImages.length) % _lbImages.length;
    _updateLightbox();
}

function _lightboxKeyHandler(e) {
    if (e.key === 'Escape')     { closeLightbox(); return; }
    if (e.key === 'ArrowLeft')  { changeLightbox(-1); return; }
    if (e.key === 'ArrowRight') { changeLightbox(1);  return; }

    // Focus trap: keep Tab inside the dialog
    const lb = document.querySelector('.lightbox');
    if (!lb) return;
    const focusable = [...lb.querySelectorAll('button')];
    const first = focusable[0];
    const last  = focusable[focusable.length - 1];
    if (e.key === 'Tab') {
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault(); last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault(); first.focus();
        }
    }
}

/* ─────────────────────────────────────────────
   Stat Counters — animated count-up on scroll
   ───────────────────────────────────────────── */
function initStatCounters() {
    const els = document.querySelectorAll('.stat-number[data-count]');
    if (!els.length) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const target = parseFloat(el.dataset.count);
            const suffix = el.dataset.suffix || '';
            const duration = 1800;
            const start = performance.now();
            function step(now) {
                const p = Math.min((now - start) / duration, 1);
                const ease = 1 - Math.pow(1 - p, 3);
                const formatted = el.dataset.locale !== 'false' ? Math.round(target * ease).toLocaleString() : Math.round(target * ease).toString();
                el.textContent = formatted + suffix;
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
            observer.unobserve(el);
        });
    }, { threshold: 0.5 });

    els.forEach(el => observer.observe(el));
}

/* ─────────────────────────────────────────────
   Cursor FX — glow trail + light particle sparks.
   Desktop / fine-pointer only; respects reduced-motion;
   idles the rAF loop when the cursor settles or the tab
   is hidden so it costs nothing when not actively moving.
   ───────────────────────────────────────────── */
function initCursorFX() {
    const canHover = window.matchMedia('(pointer: fine)').matches &&
                      !window.matchMedia('(hover: none)').matches;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!canHover || reducedMotion) return;

    const halo = document.createElement('div');
    halo.id = 'cursorHalo';
    halo.setAttribute('aria-hidden', 'true');

    const core = document.createElement('div');
    core.id = 'cursorCore';
    core.setAttribute('aria-hidden', 'true');

    const canvas = document.createElement('canvas');
    canvas.id = 'cursorParticles';
    canvas.setAttribute('aria-hidden', 'true');

    document.body.append(halo, core, canvas);

    const ctx = canvas.getContext('2d');
    let dpr = 1;

    function resize() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = window.innerWidth * dpr;
        canvas.height = window.innerHeight * dpr;
        canvas.style.width = window.innerWidth + 'px';
        canvas.style.height = window.innerHeight + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    resize();
    window.addEventListener('resize', resize, { passive: true });

    function accentColor() {
        const v = getComputedStyle(document.documentElement).getPropertyValue('--brand-bright').trim();
        return v || '#5FAE82';
    }
    let color = accentColor();
    document.addEventListener('sepj:theme-change', () => { color = accentColor(); });

    let mouseX = window.innerWidth / 2;
    let mouseY = window.innerHeight / 2;
    let haloX = mouseX, haloY = mouseY;
    let coreX = mouseX, coreY = mouseY;
    let lastSpawnX = mouseX, lastSpawnY = mouseY;
    let particles = [];
    let rafId = null;
    let idleTimer = null;

    function spawnParticles(x, y) {
        if (Math.hypot(x - lastSpawnX, y - lastSpawnY) < 24) return;
        lastSpawnX = x; lastSpawnY = y;
        const count = Math.random() < 0.5 ? 1 : 2;
        for (let i = 0; i < count; i++) {
            particles.push({
                x, y,
                vx: (Math.random() - 0.5) * 0.6,
                vy: (Math.random() - 0.5) * 0.6 - 0.15,
                life: 1,
                size: 1.5 + Math.random() * 2,
            });
        }
        if (particles.length > 60) particles.splice(0, particles.length - 60);
    }

    function scheduleIdleFade() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => {
            halo.classList.remove('active');
            core.classList.remove('active');
        }, 2200);
    }

    function tick() {
        haloX += (mouseX - haloX) * 0.10;
        haloY += (mouseY - haloY) * 0.10;
        coreX += (mouseX - coreX) * 0.35;
        coreY += (mouseY - coreY) * 0.35;

        halo.style.transform = 'translate3d(' + haloX + 'px,' + haloY + 'px,0)';
        core.style.transform = 'translate3d(' + coreX + 'px,' + coreY + 'px,0)';

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => {
            p.x += p.vx; p.y += p.vy; p.life -= 0.018;
            if (p.life > 0) {
                ctx.globalAlpha = Math.max(p.life, 0) * 0.55;
                ctx.fillStyle = color;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size * p.life, 0, Math.PI * 2);
                ctx.fill();
            }
        });
        particles = particles.filter(p => p.life > 0);
        ctx.globalAlpha = 1;

        const settled = Math.hypot(mouseX - haloX, mouseY - haloY) < 0.5 && particles.length === 0;
        if (!settled) {
            rafId = requestAnimationFrame(tick);
        } else {
            rafId = null;
        }
    }

    function ensureLoop() {
        if (!rafId) rafId = requestAnimationFrame(tick);
    }

    window.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
        spawnParticles(mouseX, mouseY);
        halo.classList.add('active');
        core.classList.add('active');
        scheduleIdleFade();
        ensureLoop();
    }, { passive: true });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden && rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        } else if (!document.hidden) {
            ensureLoop();
        }
    });
}

/* ─────────────────────────────────────────────
   Card Spotlight — subtle cursor-proximity glow
   inside .glass-card / .glass-card-static (CSS
   in style.css handles the visual, this just
   tracks pointer position per card).
   ───────────────────────────────────────────── */
function initCardSpotlight() {
    if (!window.matchMedia('(pointer: fine)').matches) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const cards = document.querySelectorAll('.glass-card, .glass-card-static');
    if (!cards.length) return;

    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            card.style.setProperty('--spot-x', ((e.clientX - rect.left) / rect.width) * 100 + '%');
            card.style.setProperty('--spot-y', ((e.clientY - rect.top) / rect.height) * 100 + '%');
        }, { passive: true });
    });
}

/* ─────────────────────────────────────────────
   Video thumbnails — click the red play button
   to swap the thumbnail for the YouTube iframe.
   ───────────────────────────────────────────── */
function initVideoThumbnails() {
    const thumbs = document.querySelectorAll('.video-thumb');
    thumbs.forEach(box => {
        const embed = box.dataset.embed;
        if (!embed) return;

        function play() {
            box.innerHTML = '<iframe src="' + embed + '?autoplay=1&rel=0" class="w-full h-full" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen title="YouTube video"></iframe>';
        }

        box.setAttribute('role', 'button');
        box.setAttribute('tabindex', '0');
        box.addEventListener('click', play);
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); play(); }
        });
    });
}
