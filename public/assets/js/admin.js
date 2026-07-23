/**
 * SEPJ Gabès - Admin JavaScript
 * 
 * Features: Slug suggestion, image preview, character counter, confirmations, mobile sidebar
 */

document.addEventListener('DOMContentLoaded', function() {
    initAdminTheme();
    initSidebarToggle();
    initSlugSuggestions();
    initImagePreview();
    initCharacterCounters();
    initConfirmationDialogs();
    initLanguageTabs();
    initSearchFilters();
    initGalleryPicker();
    initVideoThumbUpload();
    initFileInputs();
    initTranslateButton();
});

/**
 * Admin light/dark theme — defaults to light to match the public site, persisted
 * in localStorage under the same key the public site uses ('sepj-theme').
 */
function initAdminTheme() {
    const STORAGE_KEY = 'sepj-theme';
    const btn = document.getElementById('adminThemeToggle');
    const html = document.documentElement;

    // Apply saved theme immediately (header already defaulted the <html> to light).
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'dark') {
        html.setAttribute('data-theme', 'dark');
    } else {
        html.setAttribute('data-theme', 'light');
    }
    syncToggle();

    if (!btn) return;

    btn.addEventListener('click', function () {
        const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem(STORAGE_KEY, next);
        syncToggle();
    });

    function syncToggle() {
        if (!btn) return;
        const isLight = html.getAttribute('data-theme') !== 'dark';
        btn.setAttribute('aria-checked', isLight ? 'false' : 'true');
    }
}

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
 * Live slug suggestion from title fields with date prefix
 */
function initSlugSuggestions() {
    const titleInputs = document.querySelectorAll('[data-slug-source]');
    const slugInput = document.getElementById('slug');
    const dateInput = document.querySelector('input[name="published_at"]');
    
    if (!slugInput) return;
    
    function generateSlug() {
        let title = '';
        // Find the first filled title field (prioritize Arabic)
        titleInputs.forEach(input => {
            if (input.value && !title) {
                title = input.value;
            }
        });
        
        if (!title) return;
        
        // Get date from published_at field (format: YYYY-MM-DD)
        let datePrefix = '';
        if (dateInput && dateInput.value) {
            const date = new Date(dateInput.value);
            if (!isNaN(date.getTime())) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                datePrefix = `${year}-${month}-${day}-`;
            }
        }
        
        // Only auto-generate if slug field is empty or was auto-generated
        if (slugInput.dataset.auto === 'true' || !slugInput.value) {
            slugInput.value = datePrefix + slugify(title);
            slugInput.dataset.auto = 'true';
        }
    }
    
    titleInputs.forEach(input => {
        input.addEventListener('input', generateSlug);
    });
    
    // Also regenerate when date changes
    if (dateInput) {
        dateInput.addEventListener('change', generateSlug);
    }
    
    // When user manually edits slug, stop auto-generation
    if (slugInput) {
        slugInput.addEventListener('input', function() {
            this.dataset.auto = 'false';
        });
    }
}

/**
 * Slugify function with Arabic transliteration support
 */
function slugify(text) {
    // Arabic to Latin transliteration map
    const arabicMap = {
        'ا': 'a', 'أ': 'a', 'إ': 'i', 'آ': 'a', 'ء': '',
        'ب': 'b', 'ت': 't', 'ث': 'th', 'ج': 'j', 'ح': 'h',
        'خ': 'kh', 'د': 'd', 'ذ': 'dh', 'ر': 'r', 'ز': 'z',
        'س': 's', 'ش': 'sh', 'ص': 's', 'ض': 'd', 'ط': 't',
        'ظ': 'z', 'ع': 'a', 'غ': 'gh', 'ف': 'f', 'ق': 'q',
        'ك': 'k', 'ل': 'l', 'م': 'm', 'ن': 'n', 'ه': 'h',
        'و': 'w', 'ي': 'y', 'ى': 'a', 'ة': 'h',
        'ً': 'an', 'ٌ': 'un', 'ٍ': 'in', 'َ': 'a', 'ُ': 'u', 'ِ': 'i', 'ْ': '', 'ّ': '',
        '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
    };
    
    // Transliterate Arabic characters
    text = text.split('').map(char => arabicMap[char] || char).join('');
    
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')   // Remove non-word chars
        .replace(/[\s_]+/g, '-')    // Replace spaces/underscores with hyphens
        .replace(/-+/g, '-')        // Collapse multiple hyphens
        .replace(/^-|-$/g, '');     // Remove leading/trailing hyphens
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
 * Toggle cover selection for existing gallery images (media_id based).
 * Sets the hidden radio, updates visual frame + active state.
 */
function toggleCover(btn, mediaId, type) {
    const item = btn.closest('.gallery-item');
    if (!item) return;
    if (item.classList.contains('marked-for-delete')) return;

    const container = item.closest('#existingGallery, #galleryPreview');
    if (container) {
        container.querySelectorAll('.gallery-item.is-cover').forEach(el => {
            el.classList.remove('is-cover');
            const otherBtn = el.querySelector('.gallery-btn-cover');
            if (otherBtn) otherBtn.classList.remove('is-active');
        });
    }

    item.classList.add('is-cover');
    btn.classList.add('is-active');

    const radio = item.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

/**
 * Mark an existing gallery image for deletion (removed on save).
 */
function markExistingForDelete(btn, mediaId) {
    const item = btn.closest('.gallery-item');
    if (!item) return;

    // If already marked, restore it
    if (item.classList.contains('marked-for-delete')) {
        return restoreExistingImage(btn, mediaId);
    }

    item.classList.add('marked-for-delete');
    if (item.classList.contains('is-cover')) {
        item.classList.remove('is-cover');
        const coverBtn = item.querySelector('.gallery-btn-cover');
        if (coverBtn) coverBtn.classList.remove('is-active');
    }

    // Change button to restore
    btn.innerHTML =
        '<svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>';

    // Add hidden input
    let hidden = document.querySelector('input[name="deleted_media_ids[]"][value="' + mediaId + '"]');
    if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'deleted_media_ids[]';
        hidden.value = mediaId;
        item.appendChild(hidden);
    }

    // Deselect the hidden radio
    const radio = item.querySelector('input[type="radio"]');
    if (radio) radio.checked = false;
}

/**
 * Restore an image previously marked for deletion.
 */
function restoreExistingImage(btn, mediaId) {
    const item = btn.closest('.gallery-item');
    if (!item) return;

    item.classList.remove('marked-for-delete');

    // Change button back to trash
    btn.innerHTML =
        '<svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';

    // Remove hidden input
    const hidden = item.querySelector('input[name="deleted_media_ids[]"][value="' + mediaId + '"]');
    if (hidden) hidden.remove();
}

/**
 * Toggle cover selection for newly AJAX-uploaded images (path based).
 */
function toggleCoverUploaded(btn, path) {
    const container = btn.closest('#galleryPreview');
    if (container) {
        container.querySelectorAll('.gallery-item.is-cover').forEach(el => {
            el.classList.remove('is-cover');
            const otherBtn = el.querySelector('.gallery-btn-cover');
            if (otherBtn) otherBtn.classList.remove('is-active');
        });
    }

    const item = btn.closest('.gallery-item');
    if (!item) return;
    item.classList.add('is-cover');
    btn.classList.add('is-active');

    // Uncheck all cover_path radios, then check this one
    document.querySelectorAll('input[name="cover_path"]').forEach(r => r.checked = false);
    const radio = item.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

/**
 * Remove a newly AJAX-uploaded image from the preview + hidden fields.
 */
function deleteUploadedImage(btn) {
    const item = btn.closest('.gallery-item');
    if (!item) return;

    const path = item.dataset.path;
    if (path) {
        const fields = document.getElementById('galleryFields');
        if (fields) {
            fields.querySelectorAll('input[name="uploaded_images[]"]').forEach(inp => {
                if (inp.value === path) inp.remove();
            });
        }
    }
    item.remove();

    // Update counter if present
    const counter = document.getElementById('galleryCount');
    const maxInput = document.getElementById('galleryInput');
    const max = maxInput ? parseInt(maxInput.dataset.max) || 20 : 20;
    if (counter) {
        const existingCount = document.querySelectorAll('#existingGallery [data-media-id]').length;
        const uploadedCount = document.querySelectorAll('#galleryPreview .gallery-item').length;
        counter.textContent = (existingCount + uploadedCount) + ' / ' + max;
    }
}

/**
 * Multi-image gallery picker for content create/edit.
 *
 * Images are uploaded ONE AT A TIME via AJAX to ajax_upload.php (kept small so we
 * never hit OVH's FastCGI request-length limit with one huge multipart POST). Each
 * successful upload returns the saved file path; we store it in a hidden field and
 * offer a "cover" button. New images are posted as `uploaded_images[]` (paths); the
 * chosen cover as `cover_path` (new) or `cover_media_id` (existing gallery image).
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
    const retryLabel = (typeof SEPJ_LABELS !== 'undefined' && SEPJ_LABELS.retry)
        ? SEPJ_LABELS.retry
        : '⟳ Retry';
    const deleteConfirmLabel = (typeof SEPJ_LABELS !== 'undefined' && SEPJ_LABELS.deleteConfirm)
        ? SEPJ_LABELS.deleteConfirm
        : 'Remove this image?';

    // How many images already present (existing gallery + already-uploaded)?
    function currentCount() {
        const existing = document.querySelectorAll('#existingGallery [data-media-id]').length;
        const uploaded = fields.querySelectorAll('input[name="uploaded_images[]"]').length;
        return existing + uploaded;
    }

    function addHiddenPath(path) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'uploaded_images[]';
        hidden.value = path;
        fields.appendChild(hidden);
    }

    function makeCell() {
        const cell = document.createElement('div');
        cell.className = 'gallery-item';
        return cell;
    }

    function appendUploaded(cell, path, url, autoCover) {
        const escapedPath = path.replace(/'/g, "\\'");
        cell.dataset.path = path;
        if (autoCover) cell.classList.add('is-cover');
        cell.innerHTML =
            '<div class="gallery-item-img">' +
                '<img src="' + url + '" alt="">' +
                '<span class="gallery-item-badge">' + coverLabel + '</span>' +
            '</div>' +
            '<div class="gallery-item-actions">' +
                '<button type="button" class="gallery-btn gallery-btn-cover' + (autoCover ? ' is-active' : '') + '" onclick="toggleCoverUploaded(this, \'' + escapedPath + '\')">' +
                    '<svg viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>' +
                    coverLabel +
                '</button>' +
                '<button type="button" class="gallery-btn gallery-btn-delete" onclick="if(confirm(\'' + deleteConfirmLabel.replace(/'/g, "\\'") + '\')){deleteUploadedImage(this);}">' +
                    '<svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>' +
                '</button>' +
            '</div>' +
            '<input type="radio" name="cover_path" value="' + path + '"' + (autoCover ? ' checked' : '') + ' class="hidden">';
        addHiddenPath(path);
        if (autoCover) {
            // Ensure only one cover_path radio is checked
            document.querySelectorAll('input[name="cover_path"]').forEach(r => r.checked = false);
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

        cell._file = file;
        cell._retry = () => uploadOne(file, cell);
        cell.innerHTML = '<div class="aspect-square flex items-center justify-center text-xs text-emerald-300">' + uploadingLabel + '</div>';

        try {
            const resp = await fetch('ajax_upload.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });
            let data = null;
            const text = await resp.text();
            try {
                data = JSON.parse(text);
            } catch (parseErr) {
                showUploadError(cell, text || ('HTTP ' + resp.status));
                return;
            }
            if (data && data.success) {
                delete cell._file;
                delete cell._retry;
                appendUploaded(cell, data.path, data.url, currentCount() === 1);
            } else {
                showUploadError(cell, (data && data.message) || '');
            }
        } catch (e) {
            showUploadError(cell, (e && e.message ? e.message : ''));
        }
    }

    function showUploadError(cell, msg) {
        cell.innerHTML =
            '<div class="aspect-square flex flex-col items-center justify-center text-xs text-red-300 p-2 text-center">' +
                '<span class="mb-1 opacity-80">' + errorLabel + '</span>' +
                '<span class="break-all mb-2 opacity-60">' + msg.slice(0, 150) + '</span>' +
                '<button type="button" class="gallery-btn gallery-btn-retry" onclick="retryUpload(this)">' + retryLabel + '</button>' +
            '</div>';
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
 * Retry a failed gallery image upload.
 * Reads the stored File object and retry callback from the parent cell.
 */
function retryUpload(btn) {
    const cell = btn.closest('.gallery-item');
    if (cell && cell._file && cell._retry) {
        cell._retry();
    }
}

/**
 * Custom video thumbnail upload (posts/news) — single image, stored in
 * video_thumb_path and shown with a remove button. Reuses ajax_upload.php.
 */
function initVideoThumbUpload() {
    const input = document.getElementById('videoThumbInput');
    if (!input) return;

    const preview = document.getElementById('videoThumbPreview');
    const pathField = document.getElementById('videoThumbPath');
    const removeBtn = document.getElementById('videoThumbRemove');
    const uploadingLabel = (typeof SEPJ_LABELS !== 'undefined' && SEPJ_LABELS.uploading) ? SEPJ_LABELS.uploading : 'Uploading…';
    const errorLabel = (typeof SEPJ_LABELS !== 'undefined' && SEPJ_LABELS.uploadError) ? SEPJ_LABELS.uploadError : 'Upload failed';
    const contentId = parseInt(input.dataset.contentId) || 0;
    const csrf = (typeof SEPJ_CSRF !== 'undefined' ? SEPJ_CSRF : '');

    function showPreview(path, url) {
        pathField.value = path;
        preview.classList.remove('hidden');
        preview.innerHTML =
            '<div class="relative inline-block">' +
                '<img src="' + url + '" alt="" class="w-40 rounded-lg object-cover">' +
                '<button type="button" id="videoThumbRemove" class="absolute -top-2 -right-2 text-xs text-red-400 bg-black/50 rounded-full px-2 py-1">✕</button>' +
            '</div>';
        wireRemove();
    }

    function wireRemove() {
        const btn = document.getElementById('videoThumbRemove');
        if (btn) btn.addEventListener('click', function () {
            pathField.value = '';
            preview.classList.add('hidden');
            preview.innerHTML = '';
            input.value = '';
        });
    }

    wireRemove();

    input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        preview.classList.remove('hidden');
        preview.innerHTML = '<div class="text-xs text-emerald-300">' + uploadingLabel + '</div>';

        const fd = new FormData();
        fd.append('image', file);
        fd.append('content_id', contentId);
        fd.append('subdir', 'content');
        fd.append('csrf_token', csrf);

        fetch('ajax_upload.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(r => r.text())
            .then(text => {
                let data = null;
                try { data = JSON.parse(text); } catch (e) { data = null; }
                if (data && data.success) {
                    showPreview(data.path, data.url);
                } else {
                    preview.innerHTML = '<div class="text-xs text-red-300">' + errorLabel + '<br>' + ((data && data.message) || '') + '</div>';
                }
            })
            .catch(e => {
                preview.innerHTML = '<div class="text-xs text-red-300">' + errorLabel + '</div>';
            });
        this.value = '';
    });
}

/**
 * Custom file input — shows selected filename instead of browser-native "No file chosen" text.
 */
function initFileInputs() {
    document.querySelectorAll('.file-input-wrap input[type="file"]').forEach(input => {
        const nameSpan = input.closest('.file-input-wrap').querySelector('.file-input-name');
        if (!nameSpan) return;

        input.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                const names = Array.from(this.files).map(f => f.name).join(', ');
                nameSpan.textContent = names.length > 40 ? names.slice(0, 37) + '...' : names;
            } else {
                nameSpan.textContent = nameSpan.dataset.empty || nameSpan.textContent;
            }
        });
    });
}


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

function _detectSourceLanguage() {
    const langs = ['ar', 'fr', 'en'];
    let bestLang = null, bestScore = 0;
    for (const lang of langs) {
        let score = 0;
        if (document.querySelector(`[name="title_${lang}"]`)?.value?.trim()) score++;
        if (document.querySelector(`[name="summary_${lang}"]`)?.value?.trim()) score++;
        if (document.querySelector(`[name="body_${lang}"]`)?.value?.trim()) score++;
        if (score > bestScore) { bestScore = score; bestLang = lang; }
    }
    return bestLang;
}

function _getTexts(lang) {
    if (lang === 'ar') return {
        generating: 'جاري الترجمة...',
        button: 'توليد الترجمة الآن',
        done: 'اكتملت الترجمة',
        none: 'لا توجد حقول فارغة للترجمة',
        error: 'فشلت الترجمة',
        translated: (n) => `تمت ترجمة ${n} ${n === 1 ? 'حقل' : 'حقول'}`
    };
    if (lang === 'fr') return {
        generating: 'Traduction en cours...',
        button: 'Générer la traduction',
        done: 'Traduction terminée',
        none: 'Aucun champ vide à traduire',
        error: 'Échec de la traduction',
        translated: (n) => `${n} champ${n > 1 ? 's' : ''} traduit${n > 1 ? 's' : ''}`
    };
    return {
        generating: 'Translating...',
        button: 'Generate translation now',
        done: 'Translation complete',
        none: 'No empty fields to translate',
        error: 'Translation failed',
        translated: (n) => `${n} field${n > 1 ? 's' : ''} translated`
    };
}

function translateEmptyFields() {
    const btn = document.getElementById('translateNowBtn');
    const status = document.getElementById('translateStatus');
    const uiLang = document.documentElement.lang || 'en';
    const t = _getTexts(uiLang);

    const sourceLang = _detectSourceLanguage();
    if (!sourceLang) {
        if (status) { status.textContent = t.error; status.className = 'text-xs text-red-400 mt-1'; }
        return;
    }

    const targetLangs = ['ar', 'fr', 'en'].filter(l => l !== sourceLang);
    const fieldNames = ['title', 'summary', 'body'];
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;

    if (!csrfToken) {
        if (status) { status.textContent = t.error; status.className = 'text-xs text-red-400 mt-1'; }
        return;
    }

    btn.disabled = true;
    btn.textContent = t.generating;
    if (status) { status.textContent = t.generating; status.className = 'text-xs text-emerald-400 mt-1'; }

    let translatedCount = 0;
    let errorCount = 0;
    const promises = [];

    for (const targetLang of targetLangs) {
        for (const field of fieldNames) {
            const sourceEl = document.querySelector(`[name="${field}_${sourceLang}"]`);
            const targetEl = document.querySelector(`[name="${field}_${targetLang}"]`);

            if (!sourceEl || !targetEl) continue;

            const sourceText = sourceEl.value.trim();
            const targetText = targetEl.value.trim();

            if (!sourceText || targetText) continue;

            const isHtml = field === 'body';

            promises.push(
                fetch('ajax/translate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        text: sourceText,
                        source: sourceLang,
                        target: targetLang,
                        html: isHtml,
                        csrf_token: csrfToken
                    })
                })
                .then(resp => resp.json())
                .then(data => {
                    if (data.translatedText && data.translatedText !== sourceText) {
                        targetEl.value = data.translatedText;
                        translatedCount++;
                        targetEl.dispatchEvent(new Event('input', { bubbles: true }));
                        targetEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                })
                .catch(() => { errorCount++; })
            );
        }
    }

    Promise.allSettled(promises).then(() => {
        btn.disabled = false;
        btn.textContent = t.button;

        if (status) {
            if (translatedCount > 0) {
                status.textContent = t.translated(translatedCount) + (errorCount > 0 ? ` (${errorCount} ${uiLang === 'ar' ? 'فشل' : uiLang === 'fr' ? 'échec(s)' : 'failed'})` : '');
                status.className = 'text-xs text-emerald-400 mt-1';
            } else if (errorCount > 0) {
                status.textContent = t.error;
                status.className = 'text-xs text-red-400 mt-1';
            } else {
                status.textContent = t.none;
                status.className = 'text-xs text-white/50 mt-1';
            }
        }
    });
}

function initTranslateButton() {
    const btn = document.getElementById('translateNowBtn');
    if (!btn) return;
    btn.addEventListener('click', translateEmptyFields);
}

function togglePassword(btn) {
    var input = btn.parentElement.querySelector('input');
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.querySelector('.pw-eye').classList.toggle('hidden');
    btn.querySelector('.pw-eye-off').classList.toggle('hidden');
}