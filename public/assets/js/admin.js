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
    initGalleryPicker();
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
 * Multi-image gallery picker for content create/edit.
 *
 * Images are uploaded ONE AT A TIME via AJAX to ajax_upload.php (kept small so we
 * never hit OVH's FastCGI request-length limit with one huge multipart POST). Each
 * successful upload returns a media id; we store it in a hidden field and offer a
 * "cover" radio. The cover is posted as `cover_media_id`; ids as `uploaded_media_ids[]`.
 */
function initGalleryPicker() {
    const input = document.getElementById('galleryInput');
    if (!input) return;

    const max     = parseInt(input.dataset.max) || 20;
    const mode     = input.dataset.mode || 'create';          // create | edit
    const contentId = parseInt(input.dataset.contentId) || 0;
    const preview  = document.getElementById('galleryPreview');
    const counter  = document.getElementById('galleryCount');
    const fields   = document.getElementById('galleryFields');
    if (!preview || !fields) return;

    const coverLabel = (typeof SEPJ_LABELS !== 'undefined' && SEPJ_LABELS.cover)
        ? SEPJ_LABELS.cover
        : 'Couverture';
    const uploadingLabel = (typeof SEPJ_LABELS !== 'undefined' && SEPJ_LABELS.uploading)
        ? SEPJ_LABELS.uploading
        : 'Uploading…';
    const errorLabel = (typeof SEPJ_LABELS !== 'undefined' && SEPJ_LABELS.uploadError)
        ? SEPJ_LABELS.uploadError
        : 'Upload failed';

    // How many images already present (existing gallery + already-uploaded)?
    function currentCount() {
        const existing = document.querySelectorAll('#existingGallery [data-media-id]').length;
        const uploaded = fields.querySelectorAll('input[name="uploaded_media_ids[]"]').length;
        return existing + uploaded;
    }

    function addHiddenId(id) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'uploaded_media_ids[]';
        hidden.value = id;
        fields.appendChild(hidden);
    }

    function makeCell() {
        const cell = document.createElement('div');
        cell.className = 'relative glass-card overflow-hidden group';
        return cell;
    }

    function appendUploaded(cell, mediaId, url, autoCover) {
        cell.innerHTML =
            '<div class="aspect-square overflow-hidden">' +
                '<img src="' + url + '" alt="" class="w-full h-full object-cover">' +
            '</div>' +
            '<label class="flex items-center gap-1 p-1 text-xs text-emerald-200 cursor-pointer">' +
                '<input type="radio" name="cover_media_id" value="' + mediaId + '"' + (autoCover ? ' checked' : '') + '>' +
                coverLabel +
            '</label>';
        addHiddenId(mediaId);
        if (autoCover && !document.querySelector('input[name="cover_media_id"]:checked')) {
            const radio = cell.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }
        updateCounter();
    }

    function updateCounter() {
        if (counter) {
            counter.textContent = currentCount() + ' / ' + max;
        }
    }

    async function uploadOne(file, cell) {
        const fd = new FormData();
        fd.append('image', file);
        fd.append('content_id', contentId);
        fd.append('subdir', 'content');
        fd.append('csrf_token', (typeof SEPJ_CSRF !== 'undefined' ? SEPJ_CSRF : ''));

        try {
            const resp = await fetch('ajax_upload.php', {
                method: 'POST',
                body: fd,
                headers: (typeof SEPJ_CSRF !== 'undefined') ? { 'X-CSRF-Token': SEPJ_CSRF } : {}
            });
            const data = await resp.json();
            if (data.success) {
                appendUploaded(cell, data.id, data.url, currentCount() === 1);
            } else {
                cell.innerHTML = '<div class="aspect-square flex items-center justify-center text-xs text-red-300 p-2 text-center">' + errorLabel + '<br>' + (data.message || '') + '</div>';
            }
        } catch (e) {
            cell.innerHTML = '<div class="aspect-square flex items-center justify-center text-xs text-red-300 p-2 text-center">' + errorLabel + '</div>';
        }
    }

    input.addEventListener('change', function() {
        const files = Array.from(this.files || []);
        this.value = ''; // reset so the same file can be re-picked

        let room = max - currentCount();
        if (room <= 0) {
            alert((typeof SEPJ_LABELS !== 'undefined' && SEPJ_LABELS.maxReached) ? SEPJ_LABELS.maxReached : ('Max ' + max + ' images.'));
            return;
        }

        const toUpload = files.slice(0, room);
        toUpload.forEach(file => {
            const cell = makeCell();
            cell.innerHTML = '<div class="aspect-square flex items-center justify-center text-xs text-emerald-300">' + uploadingLabel + '</div>';
            preview.appendChild(cell);
            uploadOne(file, cell);
        });
        updateCounter();
    });

    updateCounter();
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