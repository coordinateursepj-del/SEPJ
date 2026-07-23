<?php
// Fallback: redirect to public/ when mod_rewrite is not available.
// The .htaccess file handles clean URLs on production.
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (str_starts_with($requestUri, '/admin') || str_starts_with($requestUri, '/public')) {
    return;
}
header('Location: /public/');
exit;
