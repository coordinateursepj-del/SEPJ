<?php
require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';

session_start_secure();
require_role('admin');
csrf_verify();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

$body = trim($_POST['body'] ?? '');
$lang = trim($_POST['lang'] ?? 'ar');

if (strlen($body) < 20) {
    echo json_encode(['success' => false, 'error' => 'Body too short']);
    exit;
}

$supported = unserialize(SUPPORTED_LANGUAGES);
if (!in_array($lang, $supported)) {
    $lang = 'ar';
}

$langNames = ['ar' => 'Arabic', 'fr' => 'French', 'en' => 'English'];
$langName = $langNames[$lang] ?? 'Arabic';

$prompt = <<<PROMPT
You are a helpful assistant for a news/article website. Given the article body below in {$langName}, generate:
1. A concise, engaging title (max 15 words) in {$langName}
2. A brief summary (max 40 words) in {$langName}

Respond ONLY with valid JSON in this exact format, no other text:
{"title": "...", "summary": "..."}

Article body:
{$body}
PROMPT;

$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
if (empty($apiKey)) {
    echo json_encode(['success' => false, 'error' => 'Gemini API key not configured']);
    exit;
}

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 300,
    ],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'cURL error: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'error' => "Gemini API returned HTTP {$httpCode}"]);
    exit;
}

$data = json_decode($response, true);
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Extract JSON from the response (Gemini sometimes wraps in markdown)
if (preg_match('/\{.*"title".*"summary".*\}/s', $text, $m)) {
    $result = json_decode($m[0], true);
    if ($result && isset($result['title'], $result['summary'])) {
        echo json_encode([
            'success' => true,
            'title' => trim($result['title']),
            'summary' => trim($result['summary']),
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Failed to parse Gemini response']);
