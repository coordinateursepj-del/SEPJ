/**
 * SEPJ Gabès - Admin JavaScript
 * 
 * Features: Slug suggestion, image preview, character counter, confirmations, mobile sidebar
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebarToggle();
    initSlugSuggestions();
    initImagePreview();
    initCharacterCounters();
    initConfirmationDialogs();
    initLanguageTabs();
    initSearchFilters();
});

/**
 * Admin sidebar mobile toggle (moved from inline script in sidebar.php)
 */
function initSidebarToggle() {
    const sidebar   = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const overlay   = document.getElementById('sidebarOverlay');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('-translate-x-full');
            if (overlay) overlay.classList.toggle('hidden');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            if (sidebar) sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }
}

/**
 * Live slug suggestion from title fields
 */
function initSlugSuggestions() {
    const titleInputs = document.querySelectorAll('[data-slug-source]');
    const slugInput = document.getElementById('slug');
    
    if (!slugInput) return;
    
    titleInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Only auto-generate if slug field is empty or was auto-generated
            if (slugInput.dataset.auto === 'true' || !slugInput.value) {
                slugInput.value = slugify(this.value);
                slugInput.dataset.auto = 'true';
            }
        });
    });
    
    // When user manually edits slug, stop auto-generation
    if (slugInput) {
        slugInput.addEventListener('input', function() {
            this.dataset.auto = 'false';
        });
    }
}

/**
 * Simple slugify function
 */
function slugify(text) {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')  // Remove non-word chars
        .replace(/[\s_]+/g, '-')   // Replace spaces/underscores with hyphens
        .replace(/-+/g, '-')       // Collapse multiple hyphens
        .replace(/^-|-$/g, '');    // Remove leading/trailing hyphens
}

/**
 * Image preview before upload
 */
function initImagePreview() {
    const fileInputs = document.querySelectorAll('[data-preview]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            
            if (!preview) return;
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.src = '';
                preview.classList.add('hidden');
            }
        });
    });
}

/**
 * Character counters for summary fields
 * Also enforces maxlength natively so browser prevents over-limit input.
 */
function initCharacterCounters() {
    const textareas = document.querySelectorAll('[data-maxlength]');

    textareas.forEach(textarea => {
        const maxlength = parseInt(textarea.dataset.maxlength);
        // Enforce limit at the browser level
        textarea.setAttribute('maxlength', maxlength);

        const counter = document.getElementById(textarea.id + '_counter');
        if (!counter) return;

        function updateCounter() {
            const remaining = maxlength - textarea.value.length;
            counter.textContent = remaining;
            if (remaining < 20) {
                counter.classList.add('text-red-400');
                counter.classList.remove('text-emerald-400');
            } else {
                counter.classList.remove('text-red-400');
                counter.classList.add('text-emerald-400');
            }
        }

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
}

/**
 * Confirmation dialogs for delete actions
 */
function initConfirmationDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Language tabs inside forms — ARIA Tabs pattern with full keyboard navigation.
 * Arrow Left/Right moves between tabs; Home/End jump to first/last.
 */
function initLanguageTabs() {
    const tabContainers = document.querySelectorAll('.lang-tabs');

    tabContainers.forEach(container => {
        const tabRow  = container.querySelector('.flex.gap-2.mb-4');
        const tabs    = [...container.querySelectorAll('.lang-tab')];
        const panels  = [...container.querySelectorAll('.lang-content')];

        // Wire up ARIA roles
        if (tabRow) tabRow.setAttribute('role', 'tablist');

        tabs.forEach((tab, i) => {
            tab.setAttribute('role', 'tab');
            const isActive = tab.classList.contains('bg-emerald-600/30');
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.setAttribute('tabindex', isActive ? '0' : '-1');

            const lang  = tab.dataset.lang;
            const panel = panels.find(p => p.dataset.lang === lang);
            if (panel) {
                const panelId = 'lang-panel-' + lang + '-' + i;
                panel.id = panelId;
                panel.setAttribute('role', 'tabpanel');
                tab.setAttribute('aria-controls', panelId);
            }
        });

        function activateTab(tab) {
            const lang  = tab.dataset.lang;
            tabs.forEach(t => {
                t.classList.remove('active', 'bg-emerald-600/30', 'text-emerald-300', 'border-emerald-500');
                t.classList.add('text-white/50', 'border-transparent');
                t.setAttribute('aria-selected', 'false');
                t.setAttribute('tabindex', '-1');
            });
            tab.classList.add('active', 'bg-emerald-600/30', 'text-emerald-300', 'border-emerald-500');
            tab.classList.remove('text-white/50', 'border-transparent');
            tab.setAttribute('aria-selected', 'true');
            tab.setAttribute('tabindex', '0');

            panels.forEach(panel => {
                panel.classList.toggle('hidden', panel.dataset.lang !== lang);
            });
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => activateTab(tab));

            tab.addEventListener('keydown', (e) => {
                const idx = tabs.indexOf(tab);
                let next = null;
                if      (e.key === 'ArrowRight' || e.key === 'ArrowDown')  next = tabs[(idx + 1) % tabs.length];
                else if (e.key === 'ArrowLeft'  || e.key === 'ArrowUp')    next = tabs[(idx - 1 + tabs.length) % tabs.length];
                else if (e.key === 'Home')                                  next = tabs[0];
                else if (e.key === 'End')                                   next = tabs[tabs.length - 1];
                if (next) { e.preventDefault(); activateTab(next); next.focus(); }
            });
        });
    });
}

/**
 * Search/filter debounce
 */
function initSearchFilters() {
    const searchInputs = document.querySelectorAll('[data-search]');
    
    searchInputs.forEach(input => {
        let timeout = null;
        
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    });
}

/**
 * Select all / deselect all checkbox
 */
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('[data-select-item]');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

/**
 * Bulk action confirmation
 */
function confirmBulkAction(action) {
    const message = {
        delete: 'Are you sure you want to delete selected items?'
    };
    
    return confirm(message[action] || 'Are you sure?');
}

/**
 * Media preview modal — keyboard accessible with focus trap and focus return.
 */
let _mediaTrigger = null;

function openMediaPreview(src, title, triggerEl) {
    const modal   = document.getElementById('mediaPreviewModal');
    const img     = document.getElementById('mediaPreviewImage');
    const caption = document.getElementById('mediaPreviewCaption');
    if (!modal || !img) return;

    _mediaTrigger = triggerEl || document.activeElement;
    img.src = src;
    if (caption) caption.textContent = title || '';

    // Use style.display so flex centering works (Tailwind 'hidden' removes display entirely)
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    modal.addEventListener('keydown', _mediaKeyHandler);

    const closeBtn = modal.querySelector('[data-close-modal], button');
    if (closeBtn) closeBtn.focus();
}

function closeMediaPreview() {
    const modal = document.getElementById('mediaPreviewModal');
    if (modal) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeEventListener('keydown', _mediaKeyHandler);
        document.body.style.overflow = '';
    }
    if (_mediaTrigger && typeof _mediaTrigger.focus === 'function') _mediaTrigger.focus();
}

function _mediaKeyHandler(e) {
    if (e.key === 'Escape') { closeMediaPreview(); return; }
    // Basic focus trap
    const modal    = document.getElementById('mediaPreviewModal');
    const focusable = [...modal.querySelectorAll('button, [href], input, [tabindex]:not([tabindex="-1"])')];
    if (focusable.length < 2) return;
    const first = focusable[0], last = focusable[focusable.length - 1];
    if (e.key === 'Tab') {
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
}