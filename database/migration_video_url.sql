-- ============================================
-- SEPJ Gabès - Video URL Migration
-- Adds video_url column to content_items
-- to hold the YouTube link for type='video' items
-- ============================================

-- Safely add column (IF NOT EXISTS check using information_schema)
-- This is safe to run multiple times

SET @dbname = DATABASE();
SET @col_exists = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'content_items'
      AND COLUMN_NAME = 'video_url');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `content_items`
        ADD COLUMN `video_url` VARCHAR(255) DEFAULT NULL
            COMMENT ''YouTube URL for type=video items''
            AFTER `featured_image`',
    'SELECT "Column video_url already exists — skipping migration."');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
