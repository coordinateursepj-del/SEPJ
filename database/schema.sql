-- ============================================
-- SEPJ Gabès - Database Schema
-- Société d'Environnement, Plantation et Jardinage de Gabès
-- شركة البيئة والغراسة والبستنة بقابس
-- Compatible: MySQL / MariaDB (XAMPP)
-- ============================================

CREATE DATABASE IF NOT EXISTS `sepj_gabes`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `sepj_gabes`;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. CONTENT ITEMS TABLE (Generic CMS)
-- ============================================
CREATE TABLE IF NOT EXISTS `content_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('page', 'post', 'project', 'service', 'activity', 'prize', 'rse', 'resource', 'sport', 'video') NOT NULL,
    `rse_category` VARCHAR(50) DEFAULT NULL COMMENT 'RSE subcategory: engagement_social, rapport_durabilite, rapport_rse, catalogue_rse',
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `title_ar` TEXT,
    `title_fr` TEXT,
    `title_en` TEXT,
    `summary_ar` TEXT,
    `summary_fr` TEXT,
    `summary_en` TEXT,
    `body_ar` LONGTEXT,
    `body_fr` LONGTEXT,
    `body_en` LONGTEXT,
    `featured_image` VARCHAR(255) DEFAULT NULL,
    `video_url` VARCHAR(255) DEFAULT NULL COMMENT 'YouTube URL for type=video items',
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `published_at` DATETIME DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `updated_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_type_status` (`type`, `status`),
    INDEX `idx_featured` (`is_featured`),
    INDEX `idx_rse_category` (`rse_category`),
    CONSTRAINT `fk_content_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_content_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. MEDIA TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `media` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `content_item_id` INT UNSIGNED DEFAULT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100) NOT NULL,
    `alt_ar` VARCHAR(500) DEFAULT NULL,
    `alt_fr` VARCHAR(500) DEFAULT NULL,
    `alt_en` VARCHAR(500) DEFAULT NULL,
    `caption_ar` TEXT,
    `caption_fr` TEXT,
    `caption_en` TEXT,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_content_item` (`content_item_id`),
    CONSTRAINT `fk_media_content` FOREIGN KEY (`content_item_id`) REFERENCES `content_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. SITE SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(255) NOT NULL UNIQUE,
    `value_ar` LONGTEXT,
    `value_fr` LONGTEXT,
    `value_en` LONGTEXT,
    `value_raw` LONGTEXT,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. NAVIGATION ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `navigation_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `label_ar` VARCHAR(255) NOT NULL,
    `label_fr` VARCHAR(255) NOT NULL,
    `label_en` VARCHAR(255) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,

    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_sort` (`sort_order`),
    CONSTRAINT `fk_nav_parent` FOREIGN KEY (`parent_id`) REFERENCES `navigation_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. CONTACT MESSAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `subject` VARCHAR(500) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'read', 'archived') NOT NULL DEFAULT 'new',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. AUDIT LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(255) NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_created` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;