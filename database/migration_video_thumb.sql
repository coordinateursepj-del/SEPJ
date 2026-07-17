-- ============================================
-- SEPJ Gabès - Custom Video Thumbnail Migration
-- Adds video_thumb column to content_items
-- to hold an optional custom thumbnail for a YouTube
-- video attached to any content type (posts/news/etc).
-- When empty, the thumbnail is auto-fetched from YouTube.
--
-- Safe to run multiple times: the ADD COLUMN is a no-op
-- if the column already exists (MariaDB/MySQL ignore dupes
-- only with IF NOT EXISTS, so wrap defensively if needed).
-- ============================================

ALTER TABLE `content_items`
    ADD COLUMN `video_thumb` VARCHAR(255) DEFAULT NULL
        COMMENT 'Custom thumbnail for an attached YouTube video'
        AFTER `video_url`;
