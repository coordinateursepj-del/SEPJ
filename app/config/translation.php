<?php
/**
 * Translation Configuration - SEPJ Gabès
 *
 * Providers:
 *   'gemini'        – uses Gemini 2.0 Flash + your GEMINI_API_KEY (best Arabic accuracy)
 *   'google'        – free public endpoint or Google Cloud Translation API v2 with key
 *   'libretranslate' – free, open-source, no key required
 *
 * For Gemini: set GEMINI_API_KEY in app/config/app.php and TRANSLATION_PROVIDER to 'gemini'.
 * That way one key powers both the ✦ AI button AND translation.
 */

// Set to false to disable auto-translation completely without removing any code.
define('ENABLE_TRANSLATION', true);

// Translation provider: 'gemini', 'google', or 'libretranslate'
define('TRANSLATION_PROVIDER', 'gemini');

// LibreTranslate HTTP endpoint (no API key required).
// Using fedilab public instance — tested and working.
// Alternative: run locally with: pip install libretranslate && libretranslate --load-only ar,fr,en
//              then set this to: http://localhost:5000/translate
define('TRANSLATION_ENDPOINT', 'https://translate.fedilab.app/translate');

// Google Cloud Translation API key (v2). Leave empty to use the free public endpoint.
// Get a key at https://console.cloud.google.com/apis/credentials (enable "Cloud Translation API").
define('GOOGLE_TRANSLATE_API_KEY', '');

// Seconds to wait for a response before giving up.
define('TRANSLATION_TIMEOUT', 30);
