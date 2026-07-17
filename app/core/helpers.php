<?php
/**
 * Helper Functions - SEPJ Gabès
 */

/**
 * Get a public-facing URL path (relative to project root)
 */
function public_url(string $path = ''): string
{
    $base = APP_BASE_PATH . '/public';
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '');
}
/**
 * Get an admin URL path (relative to project root)
 */
function admin_url(string $path = ''): string
{
    $base = ADMIN_URL;
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '');
}

/**
 * Get a public asset URL path (relative to project root)
 */
function asset_url(string $path = ''): string
{
    $base = APP_BASE_PATH . '/public/assets';
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '');
}

/**
 * Escape HTML output safely
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Get current language from URL parameter, session, or default
 */
function current_lang(): string
{
    $supported = unserialize(SUPPORTED_LANGUAGES);
    
    if (isset($_GET['lang']) && in_array($_GET['lang'], $supported)) {
        $_SESSION['lang'] = $_GET['lang'];
        return $_GET['lang'];
    }
    
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $supported)) {
        return $_SESSION['lang'];
    }
    
    return DEFAULT_LANGUAGE;
}

/**
 * Check if a language is RTL (Right-to-Left)
 */
function is_rtl(?string $lang = null): bool
{
    $lang = $lang ?? current_lang();
    return $lang === 'ar';
}

/**
 * Get the direction attribute for HTML
 */
function dir_attribute(?string $lang = null): string
{
    return is_rtl($lang) ? 'rtl' : 'ltr';
}

/**
 * Get text alignment class based on language
 */
function text_align_class(?string $lang = null): string
{
    return is_rtl($lang) ? 'text-right' : 'text-left';
}

/**
 * Truncate text to a certain length
 */
function excerpt(string $text, int $limit = 160): string
{
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    
    $excerpt = mb_substr($text, 0, $limit);
    $lastSpace = mb_strrpos($excerpt, ' ');
    
    if ($lastSpace !== false) {
        $excerpt = mb_substr($excerpt, 0, $lastSpace);
    }
    
    return $excerpt . '...';
}

/**
 * Return 'active' class if current page matches
 */
function active_class(string $page, string $className = 'active'): string
{
    $currentPage = basename($_SERVER['PHP_SELF']);
    $currentQuery = $_SERVER['QUERY_STRING'] ?? '';
    
    if ($currentPage === $page) {
        return $className;
    }
    
    // Check query string parameters like type=post
    if (strpos($currentQuery, $page) !== false) {
        return $className;
    }
    
    return '';
}

/**
 * Flash message helper - set a flash message in session
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * Flash message helper - display and clear flash message
 */
function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get full URL for a given path
 */
function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    return $base . '/' . $path;
}

/**
 * Get upload URL for a file
 */
function upload_url(string $filePath): string
{
    return UPLOAD_URL . '/' . ltrim($filePath, '/');
}

/**
 * Extract the 11-character YouTube video id from any common URL format.
 *
 * Accepts watch?v=ID, youtu.be/ID, youtube.com/embed/ID, youtube.com/shorts/ID,
 * with optional www./m. prefix and extra query parameters.
 *
 * @return string|null The video id, or null if the input is not a YouTube URL.
 */
function youtube_id_from_url(string $url): ?string
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }

    $id = '[A-Za-z0-9_-]{11}';
    $patterns = [
        "~[?&]v=({$id})~",              // watch?v=ID (and variants)
        "~youtu\.be/({$id})~",          // youtu.be/ID
        "~/embed/({$id})~",             // youtube.com/embed/ID
        "~/shorts/({$id})~",            // youtube.com/shorts/ID
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $m)) {
            return $m[1];
        }
    }

    return null;
}

/**
 * Check whether a column exists on a table (cached per request).
 * Used to keep save logic working even before a DB migration has run.
 */
function table_column_exists(string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = db()->prepare("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
        ");
        $stmt->execute(['t' => $table, 'c' => $column]);
        $cache[$key] = (int) $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

/**
 * Build a canonical YouTube embed URL from any YouTube link.
 *
 * @return string|null https://www.youtube.com/embed/ID, or null if not a YouTube URL.
 */
function youtube_embed_url(string $url): ?string
{
    $id = youtube_id_from_url($url);
    return $id === null ? null : "https://www.youtube.com/embed/{$id}";
}

/**
 * Build the YouTube thumbnail URL for a video.
 *
 * @param string      $url           Any YouTube link (or the raw id).
 * @param string|null $customImage   A custom uploaded thumbnail path (upload_url
 *                                    already applied, or null). If provided it wins.
 * @return string|null The thumbnail URL, or null if no valid video id.
 */
function youtube_thumbnail_url(string $url, ?string $customImage = null): ?string
{
    if ($customImage !== null && $customImage !== '') {
        return $customImage;
    }
    $id = youtube_id_from_url($url);
    return $id === null ? null : "https://img.youtube.com/vi/{$id}/hqdefault.jpg";
}

/**
 * Generate language switching URL while preserving current page and query parameters
 * 
 * @param string $lang The language code to switch to
 * @return string The URL with updated language parameter
 */
function lang_url(string $lang): string
{
    $currentFile = basename($_SERVER['PHP_SELF']);
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    
    // Parse current query parameters
    parse_str($queryString, $params);
    
    // Update language parameter
    $params['lang'] = $lang;
    
    // Rebuild query string
    $newQueryString = http_build_query($params);
    
    // Build the new URL
    if ($newQueryString) {
        return $currentFile . '?' . $newQueryString;
    }
    
    return $currentFile;
}

/**
 * Check if request method is POST
 */
function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Get a setting value from the database by key.
 * Uses a keyed array for O(1) lookups after the first DB call.
 */
function get_setting(string $key, string $lang = 'ar'): string
{
    static $settings = null;

    if ($settings === null) {
        try {
            $rows = db()->query("SELECT setting_key, value_ar, value_fr, value_en, value_raw FROM site_settings")->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row;
            }
        } catch (PDOException $e) {
            $settings = [];
            return '';
        }
    }

    if (!isset($settings[$key])) {
        return '';
    }

    $setting = $settings[$key];
    $field   = 'value_' . $lang;

    if (!empty($setting[$field])) {
        return $setting[$field];
    }
    if (!empty($setting['value_raw'])) {
        return $setting['value_raw'];
    }
    return $setting['value_ar'] ?? '';
}

/**
 * Sanitize HTML content for safe output
 * Allows only safe HTML tags and strips dangerous content
 */
function sanitize_body(string $html): string
{
    // If no HTML tags, escape normally
    if ($html === strip_tags($html)) {
        return e($html);
    }

    // Strip dangerous tags and attributes
    $allowedTags = '<p><br><b><i><u><a><img><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><table><tr><td><th><strong><em><span><div><hr><pre><code><sub><sup>';
    $html = strip_tags($html, $allowedTags);

    // Remove all event handler attributes (onclick, onload, etc.)
    $html = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|\S+)/i', '', $html);

    // Block javascript: and data: URIs in href, src, action and formaction attributes
    $html = preg_replace_callback(
        '/\b(href|src|action|formaction)\s*=\s*(["\'])(.*?)\2/is',
        function ($m) {
            $attr  = $m[1];
            $quote = $m[2];
            $value = trim($m[3]);
            // Strip leading whitespace/control chars that browsers ignore
            $clean = preg_replace('/[\x00-\x20]+/', '', $value);
            if (preg_match('/^(javascript|vbscript|data)\s*:/i', $clean)) {
                return $attr . '=' . $quote . '#' . $quote;
            }
            return $m[0];
        },
        $html
    );

    return $html;
}

/**
 * Get content field by language with fallback
 */
function content_field(array $row, string $field, string $lang): string
{
    $fieldName = $field . '_' . $lang;
    
    if (!empty($row[$fieldName])) {
        return $row[$fieldName];
    }
    
    // Fallback to Arabic
    return $row[$field . '_ar'] ?? '';
}

/**
 * Log admin action
 */
function log_audit(int $userId, string $action, string $entityType, ?int $entityId = null): void
{
    try {
        $stmt = db()->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, created_at)
            VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, NOW())
        ");
        $stmt->execute([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    } catch (PDOException $e) {
        // Silently fail - audit should not break main functionality
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Allowed database tables for pagination
 */
function get_allowed_pagination_tables(): array
{
    return ['content_items', 'media', 'contact_messages', 'users', 'audit_logs', 'navigation_items', 'site_settings'];
}

/**
 * Pagination helper - generates LIMIT and OFFSET, returns pagination data
 * 
 * SECURITY: This function is currently unused in the codebase. It's provided
 * for future development. The WHERE clause parameter is validated but still
 * represents a potential SQL injection vector if misused. Always prefer passing
 * WHERE conditions through prepared statement parameters when possible.
 *
 * @param string $table Table name (validated against allowlist)
 * @param string $where SQL WHERE clause (uses prepared statements for values)
 * @param array $params Parameters for WHERE clause
 * @param int|null $perPage Items per page
 * @return array Pagination data
 * @throws InvalidArgumentException If table name is not in allowlist
 */
function paginate(string $table, string $where = '1=1', array $params = [], int $perPage = null): array
{
    $allowedTables = get_allowed_pagination_tables();
    if (!in_array($table, $allowedTables)) {
        error_log("SECURITY: Attempted pagination on invalid table: {$table}");
        throw new InvalidArgumentException("Invalid table name specified for pagination.");
    }
    
    $perPage = $perPage ?? ITEMS_PER_PAGE;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;
    
    // Enhanced WHERE clause validation - prevent SQL injection
    $where = trim($where);
    
    // Check for dangerous SQL patterns
    $dangerousPatterns = [
        '/;\s*/', // Statement terminators
        '/\/\*.*?\*\//s', // Block comments
        '/--\s*/', // Line comments
        '/\b(UNION|DROP|INSERT|UPDATE|DELETE|CREATE|ALTER|TRUNCATE|EXEC|EXECUTE)\b/i', // SQL keywords
        '/\b(INTO|FROM|SELECT)\s*\(/i', // Subqueries
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $where)) {
            error_log("SECURITY: Suspicious WHERE clause detected in pagination: {$where}");
            $where = '1=1'; // Fallback to safe default
            break;
        }
    }
    
    // Ensure WHERE clause uses parameter placeholders, not raw values
    if (!preg_match('/:\w+/', $where) && $where !== '1=1') {
        error_log("WARNING: paginate() WHERE clause does not use parameter placeholders: {$where}");
    }
    
    $countSql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
    $countStmt = db()->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    
    $totalPages = max(1, (int) ceil($total / $perPage));
    
    return [
        'page'        => $page,
        'perPage'     => $perPage,
        'offset'      => $offset,
        'total'       => $total,
        'totalPages'  => $totalPages,
        'hasPrev'     => $page > 1,
        'hasNext'     => $page < $totalPages,
        'prevPage'    => $page - 1,
        'nextPage'    => $page + 1,
    ];
}

/**
 * Format date for display
 */
function format_date(string $date, string $format = 'd/m/Y'): string
{
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '';
    }
    
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Build pagination HTML — multilingual prev/next labels.
 */
function pagination_links(array $paginate, string $urlTemplate): string
{
    if ($paginate['totalPages'] <= 1) {
        return '';
    }

    $lang    = current_lang();
    $prevLbl = $lang === 'ar' ? '&laquo; السابق' : ($lang === 'fr' ? '&laquo; Précédent' : '&laquo; Prev');
    $nextLbl = $lang === 'ar' ? 'التالي &raquo;' : ($lang === 'fr' ? 'Suivant &raquo;' : 'Next &raquo;');

    $html = '<nav aria-label="Pagination" class="flex justify-center gap-2 mt-8">';
    $sep  = strpos($urlTemplate, '?') !== false ? '&' : '?';

    // Previous
    $prevDisabled = $paginate['hasPrev'] ? '' : ' opacity-50 pointer-events-none';
    $html .= sprintf(
        '<a href="%s' . $sep . 'page=%d" class="px-4 py-2 rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 text-white hover:bg-emerald-600/40 transition-all%s">%s</a>',
        $urlTemplate, $paginate['prevPage'], $prevDisabled, $prevLbl
    );

    // Pages
    for ($i = 1; $i <= $paginate['totalPages']; $i++) {
        $active  = $i === $paginate['page'] ? ' bg-emerald-600/40 border-emerald-500' : ' bg-white/10';
        $current = $i === $paginate['page'] ? ' aria-current="page"' : '';
        $html .= sprintf(
            '<a href="%s' . $sep . 'page=%d" class="px-4 py-2 rounded-lg backdrop-blur-sm border border-white/20 text-white hover:bg-emerald-600/40 transition-all pagination-btn%s"%s>%d</a>',
            $urlTemplate, $i, $active, $current, $i
        );
    }

    // Next
    $nextDisabled = $paginate['hasNext'] ? '' : ' opacity-50 pointer-events-none';
    $html .= sprintf(
        '<a href="%s' . $sep . 'page=%d" class="px-4 py-2 rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 text-white hover:bg-emerald-600/40 transition-all%s">%s</a>',
        $urlTemplate, $paginate['nextPage'], $nextDisabled, $nextLbl
    );

    $html .= '</nav>';

    return $html;
}