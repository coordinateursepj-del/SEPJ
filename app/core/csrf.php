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
 * Verify CSRF token or die with error
 */
function csrf_verify(): void
{
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