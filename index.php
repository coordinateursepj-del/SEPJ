<?php
// Only redirect to the public site for non-admin, non-public requests.
// Admin paths (/admin) and already-public paths (/public) must pass through.
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (str_starts_with($requestUri, '/admin') || str_starts_with($requestUri, '/public')) {
    return;
}
header('Location: /public/');
exit;
