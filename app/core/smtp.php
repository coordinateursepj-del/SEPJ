<?php
/**
 * SEPJ Gabès — PHPMailer-based Mail Service
 *
 * Requires PHPMailer installed via Composer:
 *   composer install
 *
 * Design principles:
 *  - SMTP only — never falls back to mail()
 *  - Visitor's email is set as Reply-To, not From
 *  - SSL certificate verification is enforced (configurable for local dev)
 *  - Supports both HTML + plain-text (multipart/alternative)
 *  - Reusable: clearAddresses() between sends, one instance per request
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailerService
{
    private PHPMailer $mailer;

    /**
     * @param array $cfg  Keys: host, port, secure, username, password,
     *                          from_email, from_name, ssl_verify_peer
     * @throws RuntimeException if PHPMailer is not installed
     */
    public function __construct(array $cfg)
    {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new RuntimeException(
                'PHPMailer is not installed. Run: composer install  ' .
                '(in ' . dirname(__DIR__, 2) . ')'
            );
        }
        require_once $autoload;

        $this->mailer = new PHPMailer(true); // true = throw exceptions

        // ── Transport ──────────────────────────────────────────────────
        $this->mailer->isSMTP();
        $this->mailer->Host    = $cfg['host'];
        $this->mailer->Port    = (int)($cfg['port'] ?? 587);
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $cfg['username'];
        $this->mailer->Password = $cfg['password'];

        $this->mailer->SMTPSecure = strtolower($cfg['secure'] ?? 'tls') === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS      // port 465
            : PHPMailer::ENCRYPTION_STARTTLS;  // port 587 (default)

        $this->mailer->Timeout = 30;

        // ── SSL Certificate Verification ──────────────────────────────
        // verify_peer MUST be true in production to prevent MITM attacks.
        // Set ssl_verify_peer => false ONLY when testing with a local
        // self-signed certificate (e.g. XAMPP).
        $verify = isset($cfg['ssl_verify_peer']) ? (bool)$cfg['ssl_verify_peer'] : true;
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => $verify,
                'verify_peer_name'  => $verify,
                'allow_self_signed' => !$verify,
            ],
        ];

        // ── Encoding ──────────────────────────────────────────────────
        $this->mailer->CharSet  = PHPMailer::CHARSET_UTF8;
        $this->mailer->Encoding = PHPMailer::ENCODING_BASE64;

        // ── Fixed Authenticated Sender ────────────────────────────────
        // from_email must match the SMTP authenticated account.
        // This is the address that ISPs see as the actual sender.
        // The visitor's address ONLY appears in Reply-To.
        $this->mailer->setFrom($cfg['from_email'], $cfg['from_name']);
    }

    /**
     * Send an email.
     *
     * @param string   $to          Destination — the internal department address
     * @param string   $subject
     * @param string   $bodyText    Plain-text body (always required)
     * @param string   $bodyHtml    HTML body; if provided, sends multipart/alternative
     * @param string   $replyTo     Visitor's email → Reply-To header ONLY, never From
     * @param string[] $cc          CC addresses (executive escalation etc.)
     * @param array[]  $attachments [['path' => '/tmp/...', 'name' => 'file.pdf'], ...]
     * @return bool    true on success
     * @throws RuntimeException on SMTP/PHPMailer failure
     */
    public function send(
        string $to,
        string $subject,
        string $bodyText,
        string $bodyHtml = '',
        string $replyTo  = '',
        array  $cc       = [],
        array  $attachments = []
    ): bool {
        try {
            // Reset per-message state (one instance, multiple calls)
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearReplyTos();
            $this->mailer->clearAttachments();

            // ── Recipients ────────────────────────────────────────────
            $this->mailer->addAddress($to);

            foreach ($cc as $addr) {
                if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                    $this->mailer->addCC($addr);
                }
            }

            // ── Reply-To: visitor's email ─────────────────────────────
            // This lets the team reply directly to the visitor without
            // manually copying their address from the message body.
            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addReplyTo($replyTo);
            }

            // ── Content ───────────────────────────────────────────────
            $this->mailer->Subject = $subject;

            if (!empty($bodyHtml)) {
                $this->mailer->isHTML(true);
                $this->mailer->Body    = $bodyHtml;   // shown in modern clients
                $this->mailer->AltBody = $bodyText;   // plain-text fallback
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $bodyText;
            }

            // ── Attachments ───────────────────────────────────────────
            foreach ($attachments as $att) {
                if (!empty($att['path']) && is_file($att['path'])) {
                    $this->mailer->addAttachment($att['path'], $att['name'] ?? '');
                }
            }

            $this->mailer->send();
            return true;

        } catch (PHPMailerException $e) {
            throw new RuntimeException(
                'PHPMailer send failed: ' . $this->mailer->ErrorInfo,
                0,
                $e
            );
        }
    }
}
