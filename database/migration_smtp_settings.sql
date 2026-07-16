-- ============================================================
-- SEPJ Gabès — SMTP Mail Settings Migration
-- Table: site_settings (setting_key / value_raw)
-- Safe to run multiple times (INSERT IGNORE)
-- ============================================================

INSERT IGNORE INTO `site_settings` (`setting_key`, `value_raw`)
VALUES
    ('mail_driver',     'php'),
    ('smtp_host',       ''),
    ('smtp_port',       '587'),
    ('smtp_secure',     'tls'),
    ('smtp_username',   ''),
    ('smtp_password',   ''),
    ('smtp_from_email', 'no-reply@sepjgabes.tn'),
    ('smtp_from_name',  'SEPJ Gabès');
