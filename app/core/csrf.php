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
 */
function post_size_exceeded(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && empty($_POST) && empty($_FILES)
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
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