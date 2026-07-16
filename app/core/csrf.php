<?php
/**
 * CSRF Protection - SEPJ Gabès
 * 
 * Generates and validates CSRF tokens for all forms.
 */

/**
 * Generate a CSRF token and store it in the session
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate a hidden input field with CSRF token
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validate CSRF token from POST data
 */
function csrf_validate(): bool
{
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * True when PHP silently discarded the POST body for exceeding post_max_size.
 * In that case PHP empties $_POST and $_FILES with no upload error set, which
 * would otherwise look like a CSRF token mismatch.
 *
 * We check two signals:
 *  1. The classic one: both $_POST and $_FILES are empty despite a non-zero
 *     Content-Length (body was dropped entirely).
 *  2. The Content-Length is larger than the server's post_max_size, which
 *     means the body was truncated/dropped even if a few fields squeaked
 *     through. This is the authoritative signal on OVH where limits are low.
 */
function post_size_exceeded(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }

    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
        return true;
    }

    $maxPost = (int) (trim(ini_get('post_max_size')) ?: 0);
    // Convert common ini shorthand (8M, 120M, 1K, 512) to bytes.
    $maxPost = parse_ini_shorthand($maxPost > 0 ? ini_get('post_max_size') : '0');
    if ($contentLength > 0 && $maxPost > 0 && $contentLength > $maxPost) {
        return true;
    }

    return false;
}

/**
 * Convert a php.ini shorthand size (e.g. "8M", "120M", "1K") to bytes.
 */
function parse_ini_shorthand(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $unit = strtoupper(substr($value, -1));
    $num  = (int) $value;
    switch ($unit) {
        case 'G': $num *= 1024;
        case 'M': $num *= 1024;
        case 'K': $num *= 1024;
    }
    return $num;
}

/**
 * Verify CSRF token or die with error
 */
function csrf_verify(): void
{
    if (post_size_exceeded()) {
        die('الملف الذي حاولت رفعه كبير جداً بالنسبة للخادم. الرجاء استخدام صورة أصغر والمحاولة مرة أخرى.<br>Le fichier envoyé est trop volumineux pour le serveur. Utilisez une image plus petite et réessayez.<br>The file you tried to upload is too large for the server. Please use a smaller image and try again.');
    }

    if (!csrf_validate()) {
        die('طلب غير صالح. الرجاء المحاولة مرة أخرى.<br>Requête invalide. Veuillez réessayer.<br>Invalid request. Please try again.');
    }
}

/**
 * Regenerate CSRF token after successful form submission
 */
function csrf_regenerate(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}