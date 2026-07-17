<?php
/**
 * One-click DB column repair + diagnostic for SEPJ Gabès.
 * Upload to public/ and open in browser: https://yoursite/repair_db.php
 * Deletes itself after running for safety.
 */
require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';

header('Content-Type: text/plain; charset=utf-8');

function col_exists(string $table, string $col): bool {
    $stmt = db()->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
    ");
    $stmt->execute(['t' => $table, 'c' => $col]);
    return (int) $stmt->fetchColumn() > 0;
}

echo "DB: " . DB_NAME . "\n\n";

foreach (['video_url', 'video_thumb'] as $col) {
    $exists = col_exists('content_items', $col);
    echo "- column content_items.$col: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    if (!$exists) {
        try {
            db()->exec("ALTER TABLE content_items ADD COLUMN `$col` VARCHAR(255) DEFAULT NULL AFTER " . ($col === 'video_thumb' ? 'video_url' : 'featured_image'));
            echo "  -> added successfully\n";
        } catch (Exception $e) {
            echo "  -> FAILED: " . $e->getMessage() . "\n";
        }
    }
}

// Now inspect the actual post the user is testing (any post that has a video_url set, or the latest post)
try {
    $row = db()->query("SELECT id, type, LEFT(video_url,50) AS vu, LEFT(video_thumb,50) AS vt FROM content_items WHERE type='post' ORDER BY id DESC LIMIT 5")->fetchAll();
    echo "\nLatest posts:\n";
    foreach ($row as $r) {
        echo "  id={$r['id']} type={$r['type']} video_url=[" . ($r['vu'] ?? '') . "] video_thumb=[" . ($r['vt'] ?? '') . "]\n";
    }
} catch (Exception $e) {
    echo "\nCould not read posts: " . $e->getMessage() . "\n";
}

// Force OPcache to recompile all PHP files (fixes "deployed but old code still runs")
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "\nOPcache cleared — PHP will now recompile the latest files.\n";
} else {
    echo "\nOPcache not available (PHP runs fresh on every request).\n";
}

// Self-delete for safety
@unlink(__FILE__);
echo "Done. This script deleted itself.\n";
