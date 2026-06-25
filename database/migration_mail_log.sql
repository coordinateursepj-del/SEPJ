-- ============================================================
-- SEPJ Gabès — Email Audit Log & Rate Limit Tables
-- Safe to run multiple times (CREATE TABLE IF NOT EXISTS)
-- Run this before enabling mail logging or rate limiting.
-- ============================================================

-- ── Email audit log ──────────────────────────────────────────
-- Records every send attempt: recipient, subject, outcome, error.
-- Useful for debugging delivery failures and monitoring volume.
CREATE TABLE IF NOT EXISTS `mail_log` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `to_address`    VARCHAR(255) NOT NULL,
    `subject`       VARCHAR(500) NOT NULL,
    `service_id`    INT UNSIGNED DEFAULT NULL   COMMENT 'FK to contact_services.id (nullable)',
    `reply_to`      VARCHAR(255) DEFAULT NULL   COMMENT 'Visitor email used as Reply-To',
    `status`        ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    `error_message` TEXT         DEFAULT NULL,
    `sent_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status`     (`status`),
    INDEX `idx_sent_at`    (`sent_at`),
    INDEX `idx_service_id` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Contact form rate limiting ────────────────────────────────
-- Stores hashed IP addresses with submission timestamps.
-- IPs are SHA-256 hashed — never stored in plain text.
-- The idx_ip_time index makes the per-IP COUNT query very fast.
CREATE TABLE IF NOT EXISTS `contact_rate_limit` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_hash`    CHAR(64)     NOT NULL           COMMENT 'SHA-256 of salt+IP',
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_time` (`ip_hash`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
