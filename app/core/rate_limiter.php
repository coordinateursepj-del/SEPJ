<?php
/**
 * SEPJ Gabès — Contact Form Rate Limiter
 *
 * DB-backed, per-IP sliding window.
 * IPs are stored as SHA-256 hashes — never in plain text.
 *
 * Required table: contact_rate_limit
 * Run: database/migration_mail_log.sql
 */

/**
 * Check whether the given IP is under the hourly submission limit.
 *
 * Returns true  → request is allowed.
 * Returns false → rate limit exceeded; caller should reject the submission.
 *
 * Fails open on DB error (returns true) to avoid blocking real users
 * when the database is temporarily unavailable.
 */
function check_contact_rate_limit(string $ip, int $maxPerHour = 5): bool
{
    try {
        $ipHash = _rl_hash($ip);

        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM contact_rate_limit
             WHERE ip_hash   = :ip
               AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        $stmt->execute([':ip' => $ipHash]);

        return (int)$stmt->fetchColumn() < $maxPerHour;

    } catch (PDOException $e) {
        error_log('rate_limit check: ' . $e->getMessage());
        return true; // fail open
    }
}

/**
 * Record a successful form submission for the given IP.
 * Call this AFTER the email has been sent, not before validation.
 *
 * Also prunes records older than 24 hours to keep the table small.
 */
function record_contact_submission(string $ip): void
{
    try {
        $ipHash = _rl_hash($ip);

        db()->prepare(
            "INSERT INTO contact_rate_limit (ip_hash, created_at) VALUES (:ip, NOW())"
        )->execute([':ip' => $ipHash]);

        // Lazy cleanup — runs probabilistically to avoid overhead on every request
        if (random_int(1, 20) === 1) {
            db()->exec(
                "DELETE FROM contact_rate_limit
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
        }

    } catch (PDOException $e) {
        error_log('rate_limit record: ' . $e->getMessage());
    }
}

/**
 * SHA-256 hash of IP + application salt.
 * The salt prevents rainbow-table reversal of the IP addresses.
 */
function _rl_hash(string $ip): string
{
    // Change this salt value to invalidate all existing rate-limit records
    return hash('sha256', 'sepj_gabes_rl_v1_' . $ip);
}
