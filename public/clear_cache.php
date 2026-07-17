<?php
/**
 * Persistent OPcache clear + DB diagnostic for SEPJ Gabès.
 * Deploy this, then open: https://sepjgabes.tn/clear_cache.php
 * It does NOT delete itself, so you can use it after every Deploy Git.
 */
require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/helpers.php';

header('Content-Type: text/plain; charset=utf-8');
echo "=== SEPJ cache + DB check ===\n\n";

// 1) Clear OPcache so the newly deployed PHP actually runs.
// On OVH shared hosting opcache_reset() often returns false (no effect),
// so we also force-invalidate the specific files we care about and bump
// their mtime as a fallback that timestamp-based validation will honor.
$resetOk = false;
if (function_exists('opcache_reset')) {
    $resetOk = @opcache_reset();
}
echo "[1] opcache_reset() returned: " . var_export($resetOk, true) . "\n";

$invalidateTargets = [
    ROOT_PATH . '/public/page.php',
    ROOT_PATH . '/app/core/helpers.php',
];
foreach ($invalidateTargets as $f) {
    if (function_exists('opcache_invalidate') && file_exists($f)) {
        @opcache_invalidate($f, true);
    }
    @touch($f); // change mtime so validate_timestamps picks up the new source
}

if (function_exists('opcache_get_status')) {
    $st = @opcache_get_status(false);
    echo "[1b] OPcache enabled: " . (empty($st) ? "no" : "yes") . "\n";
}

// 2) Check the video_thumb column and create it if missing
function col_exists(string $t, string $c): bool {
    $s = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:t AND COLUMN_NAME=:c");
    $s->execute(['t'=>$t,'c'=>$c]);
    return (int)$s->fetchColumn() > 0;
}
foreach (['video_url','video_thumb'] as $col) {
    $ok = col_exists('content_items', $col);
    echo "[2] content_items.$col: " . ($ok ? "EXISTS" : "MISSING") . "\n";
    if (!$ok) {
        try {
            db()->exec("ALTER TABLE content_items ADD COLUMN `$col` VARCHAR(255) DEFAULT NULL AFTER " . ($col==='video_thumb'?'video_url':'featured_image'));
            echo "    -> created.\n";
        } catch (Exception $e) {
            echo "    -> create FAILED: " . $e->getMessage() . "\n";
        }
    }
}

// 3) Confirm the deployed page.php actually contains the video block
$pageFile = ROOT_PATH . '/public/page.php';
$src = @file_get_contents($pageFile);
$hasBlock = $src !== false && strpos($src, 'video-thumb') !== false && strpos($src, 'pageVideoEmbed') !== false;
echo "[3] public/page.php video block present: " . ($hasBlock ? "YES" : "NO") . "\n";

// 4) Show latest posts and their stored video_url
try {
    $rows = db()->query("SELECT id, type, slug, LEFT(video_url,60) AS vu FROM content_items WHERE type='post' ORDER BY id DESC LIMIT 8")->fetchAll();
    echo "[4] Latest posts:\n";
    foreach ($rows as $r) {
        echo "    id={$r['id']} slug=[{$r['slug']}] video_url=[" . ($r['vu'] ?? '') . "]\n";
    }
} catch (Exception $e) {
    echo "[4] could not read posts: " . $e->getMessage() . "\n";
}

// 5) Look up a specific slug and actually run the SAME resolution page.php uses
$slugArg = $_GET['slug'] ?? '';
if ($slugArg !== '') {
    try {
        $s = db()->prepare("SELECT * FROM content_items WHERE slug=:s AND status='published' LIMIT 1");
        $s->execute(['s' => $slugArg]);
        $item = $s->fetch();
        echo "[5] slug='$slugArg':\n";
        if (!$item) {
            echo "    NOT FOUND / not published\n";
        } else {
            $body = $item['body'] ?? $item['content'] ?? '';
            $pageVideoEmbed = youtube_embed_url($item['video_url'] ?? '') ?? youtube_embed_url($body ?? '');
            echo "    status=[" . ($item['status'] ?? 'NULL') . "]\n";
            echo "    video_url=[" . ($item['video_url'] ?? '') . "]\n";
            echo "    \$pageVideoEmbed=[" . var_export($pageVideoEmbed, true) . "]\n";
            echo "    will_render_block=" . ($pageVideoEmbed ? 'YES' : 'NO') . "\n";
        }
    } catch (Exception $e) {
        echo "[5] lookup failed: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Reload the article page now.\n";
