<?php
/**
 * Persistent OPcache clear + DB diagnostic for SEPJ Gabès.
 * Deploy this, then open: https://sepjgabes.tn/clear_cache.php
 * It does NOT delete itself, so you can use it after every Deploy Git.
 */
require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';

header('Content-Type: text/plain; charset=utf-8');
echo "=== SEPJ cache + DB check ===\n\n";

// 1) Clear OPcache so the newly deployed PHP actually runs
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "[1] OPcache cleared.\n";
} else {
    echo "[1] OPcache not enabled (files run fresh each request).\n";
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

// 5) Look up a specific slug passed via ?slug= and show its raw fields
$slugArg = $_GET['slug'] ?? '';
if ($slugArg !== '') {
    try {
        $s = db()->prepare("SELECT id, type, slug, video_url, LEFT(video_thumb,40) AS vt, LEFT(featured_image,40) AS fi FROM content_items WHERE slug=:s LIMIT 1");
        $s->execute(['s' => $slugArg]);
        $row = $s->fetch();
        echo "[5] slug='$slugArg':\n";
        if (!$row) {
            echo "    NOT FOUND\n";
        } else {
            foreach ($row as $k => $v) {
                echo "    $k=[" . ($v ?? 'NULL') . "]\n";
            }
        }
    } catch (Exception $e) {
        echo "[5] lookup failed: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Reload the article page now.\n";
