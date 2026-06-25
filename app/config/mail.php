<?php
/**
 * SEPJ Gabès — Mail Configuration
 *
 * SECURITY CHECKLIST:
 *  1. This file must NOT be committed if it contains real credentials.
 *     Copy as app/config/mail.local.php and add that path to .gitignore.
 *  2. The recommended way to supply credentials in production is via
 *     server-level environment variables (Apache SetEnv / php.ini).
 *  3. For Gmail: use an App Password, not your Google account password.
 *     Generate one at: https://myaccount.google.com/apppasswords  (2FA required)
 *  4. ssl_verify_peer MUST stay true in production. Set false only when
 *     testing against a local server with a self-signed certificate.
 *
 * Override precedence (highest → lowest):
 *   getenv()  →  this file  →  hard-coded defaults inside get_mail_config()
 */

return [

    /* ── SMTP Transport ──────────────────────────────────────────────────── */

    // Gmail  : smtp.gmail.com, port 587, secure tls
    // Office : smtp.office365.com, port 587, secure tls
    // Custom : your mail-server host
    'host'   => getenv('MAIL_SMTP_HOST')   ?: 'smtp.gmail.com',
    'port'   => (int)(getenv('MAIL_SMTP_PORT') ?: 587),
    'secure' => getenv('MAIL_SMTP_SECURE') ?: 'tls',   // 'tls' (STARTTLS/587) | 'ssl' (SMTPS/465)

    /* ── Authentication ───────────────────────────────────────────────────
     * username  : the full email address of the authenticated sender account
     * password  : App Password (Gmail) or regular SMTP password
     * These credentials are NEVER exposed to the visitor.
     * The visitor's address is only set as Reply-To.
     * ──────────────────────────────────────────────────────────────────── */
    'username' => getenv('MAIL_SMTP_USERNAME') ?: '',
    'password' => getenv('MAIL_SMTP_PASSWORD') ?: '',

    /* ── Sender Identity ─────────────────────────────────────────────────
     * from_email MUST match the authenticated SMTP username.
     * Do NOT put a visitor's address here — that breaks SPF/DKIM and
     * causes messages to be rejected or marked as spam.
     * ──────────────────────────────────────────────────────────────────── */
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'your-smtp-sender@gmail.com',
    'from_name'  => getenv('MAIL_FROM_NAME')  ?: 'SEPJ Gabès — Contact',

    /* ── SSL/TLS Certificate Verification ───────────────────────────────
     * true  → validate the server certificate (required in production)
     * false → skip validation (local dev with self-signed certs ONLY)
     * ──────────────────────────────────────────────────────────────────── */
    'ssl_verify_peer' => true,

    /* ── Rate Limiting ───────────────────────────────────────────────────
     * Maximum contact-form submissions allowed per unique IP per hour.
     * Submissions exceeding this are rejected with an error message.
     * ──────────────────────────────────────────────────────────────────── */
    'rate_limit_per_hour' => 5,

    /* ── Audit Logging ───────────────────────────────────────────────────
     * When true, every send attempt is recorded in the mail_log DB table
     * (status: 'sent' | 'failed', with error details on failure).
     * Run database/migration_mail_log.sql to create the table.
     * ──────────────────────────────────────────────────────────────────── */
    'log_emails' => true,

    /* ── Static Service Routing Fallback ─────────────────────────────────
     * This map is ONLY used if the contact_services DB table is offline.
     * In normal operation, routing comes from the database.
     * Add new services here to cover the DB-unavailable edge case.
     *
     * Keys must match the email column in contact_services; the actual
     * routing logic uses DB IDs, not these string keys.
     * ──────────────────────────────────────────────────────────────────── */
    'service_routing_fallback' => [
        'general'      => 'contact@sepjgabes.tn',
        'rh'           => 'rh@sepjgabes.tn',
        'technique'    => 'technique@sepjgabes.tn',
        'financier'    => 'financier@sepjgabes.tn',
        'juridique'    => 'juridique@sepjgabes.tn',
        'achats'       => 'achats@sepjgabes.tn',
        'rse'          => 'rse@sepjgabes.tn',
        'social'       => 'social@sepjgabes.tn',
        'boc'          => 'boc@sepjgabes.tn',
        'informatique' => 'informatique@sepjgabes.tn',
        'archives'     => 'archives@sepjgabes.tn',
        'stock'        => 'stock@sepjgabes.tn',
        'coordinateur' => 'coordinateur@sepjgabes.tn',
    ],

];
