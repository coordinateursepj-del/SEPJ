<?php
/**
 * Translation Service - SEPJ Gabès
 *
 * Uses LibreTranslate (free, open-source, no API key required).
 * Fills missing multilingual fields at save time — never at render time.
 *
 * Entry point: fill_missing_translations(array $item, bool $enabled): array
 *
 * Config constants (app/config/translation.php):
 *   ENABLE_TRANSLATION   – master on/off switch
 *   TRANSLATION_ENDPOINT – LibreTranslate HTTP endpoint
 *   TRANSLATION_TIMEOUT  – seconds per HTTP request
 */

// ─────────────────────────────────────────────────────────────────────────────
// LibreTranslate Service
// ─────────────────────────────────────────────────────────────────────────────

class LibreTranslateService
{
    private string $endpoint;
    private int    $timeout;

    public function __construct()
    {
        $this->endpoint = defined('TRANSLATION_ENDPOINT')
            ? TRANSLATION_ENDPOINT
            : 'https://libretranslate.com/translate';

        $this->timeout = defined('TRANSLATION_TIMEOUT')
            ? max(5, (int) TRANSLATION_TIMEOUT)
            : 30;
    }

    /**
     * Translate $text from $from to $to via LibreTranslate.
     *
     * @param string $text   Source text (plain or HTML)
     * @param string $from   ISO 639-1 source language code (ar, fr, en)
     * @param string $to     ISO 639-1 target language code (ar, fr, en)
     * @param bool   $isHtml Pass true to preserve HTML tags during translation
     * @return string Translated text, or original $text on any failure
     */
    public function translate(string $text, string $from, string $to, bool $isHtml = false): string
    {
        $payload = json_encode([
            'q'      => $text,
            'source' => $from,
            'target' => $to,
            'format' => $isHtml ? 'html' : 'text',
        ]);

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            // Required on XAMPP/Windows which ships without a CA bundle.
            // Remove these two lines on a production Linux server.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("[LibreTranslate] cURL error ({$from}→{$to}): {$curlErr}");
            return $text;
        }

        if ($httpCode !== 200) {
            error_log("[LibreTranslate] HTTP {$httpCode} ({$from}→{$to}): " . substr((string) $body, 0, 300));
            return $text;
        }

        $data       = json_decode($body, true);
        $translated = trim($data['translatedText'] ?? '');

        return $translated !== '' ? $translated : $text;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Google Translate Service
// ─────────────────────────────────────────────────────────────────────────────

class GoogleTranslateService
{
    private int $timeout;

    public function __construct()
    {
        $this->timeout = defined('TRANSLATION_TIMEOUT')
            ? max(5, (int) TRANSLATION_TIMEOUT)
            : 30;
    }

    /**
     * Translate $text from $from to $to via Google Translate's public endpoint.
     *
     * @param string $text   Source text (plain or HTML)
     * @param string $from   ISO 639-1 source language code (ar, fr, en)
     * @param string $to     ISO 639-1 target language code (ar, fr, en)
     * @param bool   $isHtml Pass true to preserve HTML tags during translation
     * @return string Translated text, or original $text on any failure
     */
    public function translate(string $text, string $from, string $to, bool $isHtml = false): string
    {
        // Google expects two-letter codes; map our ar/fr/en directly.
        $params = http_build_query([
            'client'   => 'gtx',
            'sl'       => $from,
            'tl'       => $to,
            'dt'       => 't',
            'q'        => $text,
        ]);

        $endpoint = 'https://translate.google.com/translate_a/single?' . $params;

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("[GoogleTranslate] cURL error ({$from}→{$to}): {$curlErr}");
            return $text;
        }

        if ($httpCode !== 200) {
            error_log("[GoogleTranslate] HTTP {$httpCode} ({$from}→{$to}): " . substr((string) $body, 0, 300));
            return $text;
        }

        $translated = self::parseResponse($body);

        return $translated !== '' ? $translated : $text;
    }

    /**
     * Parse Google's nested-JSON response (array of sentence chunks).
     */
    private static function parseResponse(string $body): string
    {
        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data[0])) {
            return '';
        }

        $out = '';
        foreach ($data[0] as $chunk) {
            if (is_array($chunk) && isset($chunk[0]) && is_string($chunk[0])) {
                $out .= $chunk[0];
            }
        }

        return trim($out);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Service Factory
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Return the translation service instance based on TRANSLATION_PROVIDER.
 * Defaults to Google Translate (more reliable), falls back to LibreTranslate.
 */
function translation_service_instance()
{
    $provider = defined('TRANSLATION_PROVIDER') ? strtolower(TRANSLATION_PROVIDER) : 'google';

    if ($provider === 'libretranslate') {
        return new LibreTranslateService();
    }

    return new GoogleTranslateService();
}

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/** True when value is null, empty string, or whitespace only. */
function is_blank(?string $value): bool
{
    return $value === null || trim($value) === '';
}

/** True when the string contains HTML markup. */
function text_has_html(string $text): bool
{
    return $text !== strip_tags($text);
}

/**
 * Score each language by how many of its title/summary/body fields are filled.
 * Returns the language with the highest score as the translation source.
 * On a tie, prefers ar → fr → en (Arabic is the CMS default language).
 * Returns null when every field across all languages is empty.
 */
function detect_source_language(array $item): ?string
{
    $bestLang  = null;
    $bestScore = 0;

    foreach (['ar', 'fr', 'en'] as $lang) {
        $score = 0;
        if (!is_blank($item['title_'   . $lang] ?? null)) $score++;
        if (!is_blank($item['summary_' . $lang] ?? null)) $score++;
        if (!is_blank($item['body_'    . $lang] ?? null)) $score++;

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestLang  = $lang;
        }
    }

    return $bestLang;
}

// ─────────────────────────────────────────────────────────────────────────────
// Main Entry Point
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Fill all missing multilingual fields in $item using LibreTranslate.
 *
 * Rules:
 *  - Only writes to blank (null / empty / whitespace-only) fields.
 *  - Never overwrites a field that already contains text.
 *  - Detects source language automatically from the most-filled language.
 *  - Uses HTML mode for body fields that contain markup.
 *  - API failures are caught, logged, and returned as $warnings (saving is not blocked).
 *
 * @param array $item    Content row with title_ar/fr/en, summary_ar/fr/en, body_ar/fr/en.
 * @param bool  $enabled Whether the admin checked "auto-translate".
 * @return array {
 *     item:       array    — $item with translated values injected,
 *     warnings:   string[] — non-fatal errors shown to the admin,
 *     translated: bool     — true if at least one field was translated,
 * }
 */
function fill_missing_translations(array $item, bool $enabled = true): array
{
    $warnings   = [];
    $translated = false;

    if (!$enabled || !defined('ENABLE_TRANSLATION') || !ENABLE_TRANSLATION) {
        return compact('item', 'warnings', 'translated');
    }

    $sourceLang = detect_source_language($item);

    if ($sourceLang === null) {
        return compact('item', 'warnings', 'translated');
    }

    $service     = translation_service_instance();
    $targetLangs = array_diff(['ar', 'fr', 'en'], [$sourceLang]);

    // [field name => may contain HTML]
    $fields = [
        'title'   => false,
        'summary' => false,
        'body'    => true,
    ];

    // Allow extra PHP execution time for sequential HTTP calls
    @set_time_limit(120);

    foreach ($targetLangs as $targetLang) {
        foreach ($fields as $field => $htmlCapable) {
            $targetKey = "{$field}_{$targetLang}";
            $sourceKey = "{$field}_{$sourceLang}";

            // Never overwrite existing content
            if (!is_blank($item[$targetKey] ?? null)) {
                continue;
            }

            $sourceText = $item[$sourceKey] ?? '';

            if (is_blank($sourceText)) {
                continue;
            }

            // Use HTML mode for body fields that actually contain markup
            $isHtml = $htmlCapable && text_has_html($sourceText);

            try {
                $result = $service->translate($sourceText, $sourceLang, $targetLang, $isHtml);

                if (!is_blank($result) && $result !== $sourceText) {
                    // Store only when we received an actual translation (different from source)
                    $item[$targetKey] = $result;
                    $translated       = true;
                } elseif ($result === $sourceText) {
                    // API returned the source text unchanged — call failed silently
                    $msg        = "Translation unavailable for {$targetKey} — API returned source text.";
                    $warnings[] = $msg;
                    error_log("[LibreTranslate] {$msg}");
                }
            } catch (Throwable $e) {
                $msg        = "LibreTranslate failed for {$targetKey}: " . $e->getMessage();
                $warnings[] = $msg;
                error_log("[Translation] {$msg}");
            }
        }
    }

    return compact('item', 'warnings', 'translated');
}
