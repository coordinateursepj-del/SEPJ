<?php
require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/translation_service.php';

session_start_secure();
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['text']) || empty($input['source']) || empty($input['target'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: text, source, target']);
    exit;
}

if (!in_array($input['source'], ['ar', 'fr', 'en']) || !in_array($input['target'], ['ar', 'fr', 'en'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid language code']);
    exit;
}

if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$text   = $input['text'];
$source = $input['source'];
$target = $input['target'];
$isHtml = !empty($input['html']);

$service = translation_service_instance();
$result  = $service->translate($text, $source, $target, $isHtml);

echo json_encode(['translatedText' => $result]);
