-- ============================================================
-- SEPJ Gabès — RSE Category Mapping Migration
-- Safe to run multiple times (idempotent)
-- Maps existing RSE records to the new category taxonomy
-- ============================================================

-- 1. Ensure rse_category column exists (idempotent guard)
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'content_items'
      AND COLUMN_NAME  = 'rse_category'
);

SET @add_col = IF(@col_exists = 0,
    'ALTER TABLE `content_items`
        ADD COLUMN `rse_category` VARCHAR(50) DEFAULT NULL
        AFTER `type`,
        ADD INDEX `idx_rse_category` (`rse_category`)',
    'SELECT "rse_category already exists — skipping ALTER."'
);
PREPARE s FROM @add_col; EXECUTE s; DEALLOCATE PREPARE s;

-- 2. Map NULL rse_category → engagement_social (safe fallback)
--    Only touches rows with type='rse' that have no category set
UPDATE `content_items`
SET    `rse_category` = 'engagement_social'
WHERE  `type`         = 'rse'
AND    (`rse_category` IS NULL OR `rse_category` = '');

-- 3. Verify result
SELECT
    rse_category,
    COUNT(*) AS total
FROM content_items
WHERE type = 'rse'
GROUP BY rse_category
ORDER BY rse_category;
