-- ============================================
-- SEPJ Gabès - RSE Category Migration
-- Adds rse_category column to content_items
-- to support the new RSE taxonomy
-- ============================================

-- Safely add column (IF NOT EXISTS check using stored procedure)
-- This is safe to run multiple times

SET @dbname = DATABASE();
SET @col_exists = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'content_items'
      AND COLUMN_NAME = 'rse_category');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `content_items`
        ADD COLUMN `rse_category` VARCHAR(50) DEFAULT NULL
            COMMENT ''RSE subcategory: engagement_social, rapport_durabilite, rapport_rse, catalogue_rse''
            AFTER `type`,
        ADD INDEX `idx_rse_category` (`rse_category`)',
    'SELECT "Column rse_category already exists — skipping migration."');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;