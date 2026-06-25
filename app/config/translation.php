<?php
/**
 * Translation Configuration - SEPJ Gabès
 * Powered by LibreTranslate — free, open-source, no API key required.
 *
 * Self-hosting (recommended for production):
 *   https://github.com/LibreTranslate/LibreTranslate
 *   Then set TRANSLATION_ENDPOINT to your own server, e.g. http://localhost:5000/translate
 */

// Set to false to disable auto-translation completely without removing any code.
define('ENABLE_TRANSLATION', true);

// LibreTranslate HTTP endpoint (no API key required).
// Using fedilab public instance — tested and working.
// Alternative: run locally with: pip install libretranslate && libretranslate --load-only ar,fr,en
//              then set this to: http://localhost:5000/translate
define('TRANSLATION_ENDPOINT', 'https://translate.fedilab.app/translate');

// Seconds to wait for a response before giving up.
define('TRANSLATION_TIMEOUT', 30);
