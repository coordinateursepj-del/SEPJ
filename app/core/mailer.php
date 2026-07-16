<?php
/**
 * SEPJ Gabès — Contact Email Routing & Mailer
 *
 * Routing rules (enforced server-side, not trust client input):
 *  - Any service            → TO = service's email from DB
 *  - cc_executives = 1      → CC all is_executive=1 addresses automatically
 *
 * Mail stack:
 *  - Transport : PHPMailer over SMTP (no mail() fallback)
 *  - From      : fixed authenticated sender (app/config/mail.php)
 *  - Reply-To  : visitor's email (so staff can reply directly)
 *  - To        : the department address resolved from selected service
 */

require_once __DIR__ . '/smtp.php';

/* ════════════════════════════════════════════════════════════════
   Config loader
   ════════════════════════════════════════════════════════════════ */

/**
 * Load mail configuration.
 *
 * Priority (highest → lowest):
 *  1. app/config/mail.local.php  — server-only file with real credentials
 *  2. app/config/mail.php        — version-controlled defaults
 *  3. site_settings DB rows      — Admin → Settings → Email Configuration
 *  4. Hard-coded defaults below
 *
 * mail.local.php is .gitignore'd and is NOT deployed by the GitHub → OVH
 * pull, so production has no way to receive it except manual server
 * access. The DB (site_settings) is therefore the SMTP credentials path
 * for admins without file access: it's used only as a fallback when
 * mail.local.php doesn't already provide a real username/password, so a
 * server file — where present — still wins.
 */
function get_mail_config(): array
{
    static $cached = null;
    if ($cached !== null) return $cached;

    $defaults = [
        'host'                    => 'smtp.gmail.com',
        'port'                    => 587,
        'secure'                  => 'tls',
        'username'                => '',
        'password'                => '',
        'from_email'              => 'no-reply@sepjgabes.tn',
        'from_name'               => 'SEPJ Gabès',
        'ssl_verify_peer'         => true,
        'rate_limit_per_hour'     => 5,
        'log_emails'              => true,
        'service_routing_fallback'=> [],
    ];

    // Load PHP config files (local overrides the shared one)
    $base  = ROOT_PATH . '/app/config/mail.php';
    $local = ROOT_PATH . '/app/config/mail.local.php';

    $fileCfg = [];
    if (file_exists($base))  $fileCfg = array_merge($fileCfg, require $base);
    if (file_exists($local)) $fileCfg = array_merge($fileCfg, require $local);

    $cfg = array_merge($defaults, $fileCfg);

    try {
        $rows = db()->query("
            SELECT setting_key, value_raw FROM site_settings
            WHERE setting_key IN (
                'smtp_from_email', 'smtp_from_name',
                'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_username', 'smtp_password'
            )
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        // Display identity: admin-editable regardless of file config.
        if (!empty($rows['smtp_from_email'])) $cfg['from_email'] = $rows['smtp_from_email'];
        if (!empty($rows['smtp_from_name']))  $cfg['from_name']  = $rows['smtp_from_name'];

        // SMTP transport/credentials: only fall back to the DB when no
        // server file already supplied a real username/password.
        if (empty($fileCfg['username']) && !empty($rows['smtp_username']) && !empty($rows['smtp_password'])) {
            $cfg['username'] = $rows['smtp_username'];
            $cfg['password'] = $rows['smtp_password'];
            if (!empty($rows['smtp_host']))   $cfg['host']   = $rows['smtp_host'];
            if (!empty($rows['smtp_port']))   $cfg['port']   = (int)$rows['smtp_port'];
            if (!empty($rows['smtp_secure'])) $cfg['secure'] = $rows['smtp_secure'];
        }
    } catch (PDOException $e) {
        error_log('get_mail_config DB read: ' . $e->getMessage());
    }

    $cached = $cfg;
    return $cfg;
}

/* ════════════════════════════════════════════════════════════════
   DB helpers — service lookup
   ════════════════════════════════════════════════════════════════ */

/**
 * Load all active non-executive services for the contact form dropdown.
 * Returns array keyed by service id.
 */
function get_contact_services(string $lang = 'fr'): array
{
    $col = in_array($lang, ['ar', 'en']) ? "display_name_{$lang}" : 'display_name_fr';

    try {
        $stmt = db()->prepare("
            SELECT id,
                   {$col}            AS display_name,
                   display_name_fr,
                   display_name_ar,
                   display_name_en,
                   email,
                   cc_executives
            FROM   contact_services
            WHERE  is_executive = 0
              AND  is_active    = 1
            ORDER  BY sort_order ASC, display_name_fr ASC
        ");
        $stmt->execute();
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[(int)$r['id']] = $r;
        }
        return $out;
    } catch (PDOException $e) {
        error_log('get_contact_services: ' . $e->getMessage());
        return [];
    }
}

/**
 * Fetch a single non-executive service by id.
 */
function get_service_by_id(int $id): ?array
{
    try {
        $stmt = db()->prepare("
            SELECT id,
                   display_name_fr, display_name_ar, display_name_en,
                   email, is_executive, cc_executives
            FROM   contact_services
            WHERE  id           = :id
              AND  is_executive = 0
              AND  is_active    = 1
            LIMIT  1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('get_service_by_id: ' . $e->getMessage());
        return null;
    }
}

/**
 * Fetch all executive CC addresses (PDG first by sort_order).
 */
function get_executive_emails(): array
{
    try {
        return db()->query("
            SELECT email FROM contact_services
            WHERE  is_executive = 1 AND is_active = 1
            ORDER  BY sort_order ASC
        ")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (PDOException $e) {
        error_log('get_executive_emails: ' . $e->getMessage());
        return [];
    }
}

/* ════════════════════════════════════════════════════════════════
   Core send function
   ════════════════════════════════════════════════════════════════ */

/**
 * Route and send a contact-form email via PHPMailer/SMTP.
 *
 * @param array      $fields      ['name','email','phone','subject','message']
 * @param array      $service     Row from get_service_by_id()
 * @param string     $lang        'ar' | 'fr' | 'en'
 * @param array|null $attachment  ['path' => '/tmp/phpXXX', 'name' => 'original.pdf'] or null
 * @return bool
 */
function send_routed_email(array $fields, array $service, string $lang = 'fr', ?array $attachment = null): bool
{
    $to = filter_var($service['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$to) {
        error_log("send_routed_email: invalid TO [{$service['email']}]");
        return false;
    }

    // Resolve service display name for current language
    $nameKey     = "display_name_{$lang}";
    $serviceName = $service[$nameKey] ?? $service['display_name_fr'] ?? 'Service';

    // Build subject
    $userSubject = trim($fields['subject'] ?? '');
    $subject     = "[SEPJ Gabès — {$serviceName}]" . ($userSubject !== '' ? " {$userSubject}" : '');

    // Build body (both text and HTML)
    $bodyText = build_email_body_text($fields, $serviceName);
    $bodyHtml = build_email_body_html($fields, $serviceName, $lang);

    // Visitor's email for Reply-To (validated)
    $replyTo = filter_var($fields['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';

    // CC executives automatically if this service requires it (e.g. Manzel Habib)
    $cc = [];
    if (!empty($service['cc_executives'])) {
        $cc = array_values(array_filter(
            get_executive_emails(),
            fn($addr) => filter_var($addr, FILTER_VALIDATE_EMAIL)
        ));
    }

    $cfg     = get_mail_config();
    $success = false;
    $error   = null;

    $attachments = ($attachment !== null) ? [$attachment] : [];

    try {
        $mailer = new MailerService($cfg);
        $success = $mailer->send($to, $subject, $bodyText, $bodyHtml, $replyTo, $cc, $attachments);
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
        error_log("send_routed_email failed [{$to}]: {$error}");
        $success = false;
    }

    // Audit log (respects config toggle)
    if (!empty($cfg['log_emails'])) {
        log_mail_send(
            to:        $to,
            subject:   $subject,
            serviceId: (int)($service['id'] ?? 0),
            replyTo:   $replyTo,
            success:   $success,
            error:     $error
        );
    }

    return $success;
}

/* ════════════════════════════════════════════════════════════════
   Email body builders
   ════════════════════════════════════════════════════════════════ */

/**
 * Plain-text body — rendered as fallback in all email clients.
 */
function build_email_body_text(array $fields, string $serviceName): string
{
    $name    = $fields['name']    ?? '';
    $email   = $fields['email']   ?? '';
    $phone   = $fields['phone']   ?? '-';
    $subject = $fields['subject'] ?? '';
    $message = $fields['message'] ?? '';

    $sep  = str_repeat('═', 52);
    $line = str_repeat('─', 40);

    $body  = "{$sep}\n";
    $body .= " NOUVEAU MESSAGE — " . mb_strtoupper($serviceName) . "\n";
    $body .= "{$sep}\n\n";
    $body .= "Nom       : {$name}\n";
    $body .= "Email     : {$email}\n";
    $body .= "Téléphone : {$phone}\n";
    if ($subject !== '') {
        $body .= "Sujet     : {$subject}\n";
    }
    $body .= "\nMessage :\n{$line}\n{$message}\n{$line}\n\n";
    $body .= "Service destinataire : {$serviceName}\n";
    $body .= "Source               : SEPJ Gabès — Formulaire de contact\n";
    $body .= "Date                 : " . date('d/m/Y H:i') . "\n";

    return $body;
}

/**
 * HTML body — displayed in modern email clients.
 * Uses inline CSS for maximum email-client compatibility.
 */
function build_email_body_html(array $fields, string $serviceName, string $lang): string
{
    $name    = htmlspecialchars($fields['name']    ?? '',    ENT_QUOTES, 'UTF-8');
    $email   = htmlspecialchars($fields['email']   ?? '',    ENT_QUOTES, 'UTF-8');
    $phone   = htmlspecialchars($fields['phone']   ?? '-',   ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars($fields['subject'] ?? '',    ENT_QUOTES, 'UTF-8');
    $message = nl2br(htmlspecialchars($fields['message'] ?? '', ENT_QUOTES, 'UTF-8'));
    $svc     = htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8');
    $date    = date('d/m/Y H:i');

    $dir     = ($lang === 'ar') ? 'rtl' : 'ltr';

    $subjectRow = $subject !== ''
        ? "<tr><td style='padding:8px 12px;color:#6b7280;font-weight:600;width:140px'>Sujet</td>
               <td style='padding:8px 12px;color:#111827'>{$subject}</td></tr>"
        : '';

    return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}" dir="{$dir}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Message — {$svc}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
         style="background:#f3f4f6;padding:32px 16px">
    <tr><td align="center">

      <!-- Card -->
      <table role="presentation" width="600" cellpadding="0" cellspacing="0"
             style="background:#ffffff;border-radius:8px;overflow:hidden;
                    box-shadow:0 1px 3px rgba(0,0,0,.12);max-width:600px;width:100%">

        <!-- Header -->
        <tr>
          <td style="background:#065f46;padding:24px 32px">
            <p style="margin:0;color:#6ee7b7;font-size:13px;font-weight:600;
                      text-transform:uppercase;letter-spacing:.05em">SEPJ Gabès</p>
            <h1 style="margin:6px 0 0;color:#ffffff;font-size:20px;font-weight:700">
              Nouveau message — {$svc}
            </h1>
          </td>
        </tr>

        <!-- Sender details -->
        <tr>
          <td style="padding:24px 32px 0">
            <p style="margin:0 0 12px;font-size:14px;font-weight:700;
                      color:#065f46;text-transform:uppercase;letter-spacing:.04em">
              Coordonnées de l'expéditeur
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="border:1px solid #e5e7eb;border-radius:6px;font-size:14px;
                          border-collapse:collapse">
              <tr style="border-bottom:1px solid #e5e7eb">
                <td style="padding:8px 12px;color:#6b7280;font-weight:600;width:140px">Nom</td>
                <td style="padding:8px 12px;color:#111827">{$name}</td>
              </tr>
              <tr style="border-bottom:1px solid #e5e7eb">
                <td style="padding:8px 12px;color:#6b7280;font-weight:600">Email</td>
                <td style="padding:8px 12px">
                  <a href="mailto:{$email}" style="color:#065f46;text-decoration:none">{$email}</a>
                </td>
              </tr>
              <tr style="border-bottom:1px solid #e5e7eb">
                <td style="padding:8px 12px;color:#6b7280;font-weight:600">Téléphone</td>
                <td style="padding:8px 12px;color:#111827">{$phone}</td>
              </tr>
              {$subjectRow}
            </table>
          </td>
        </tr>

        <!-- Message body -->
        <tr>
          <td style="padding:24px 32px">
            <p style="margin:0 0 12px;font-size:14px;font-weight:700;
                      color:#065f46;text-transform:uppercase;letter-spacing:.04em">
              Message
            </p>
            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;
                        padding:16px;font-size:14px;color:#374151;line-height:1.6">
              {$message}
            </div>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb">
            <p style="margin:0;font-size:12px;color:#9ca3af">
              Service : <strong style="color:#374151">{$svc}</strong> &nbsp;·&nbsp;
              Date : <strong style="color:#374151">{$date}</strong> &nbsp;·&nbsp;
              Source : Formulaire de contact SEPJ Gabès
            </p>
          </td>
        </tr>

      </table>
      <!-- /Card -->

    </td></tr>
  </table>
</body>
</html>
HTML;
}

/* ════════════════════════════════════════════════════════════════
   Audit log
   ════════════════════════════════════════════════════════════════ */

/**
 * Insert a row into mail_log.
 * Silently ignores failures (logging must not break the main flow).
 */
function log_mail_send(
    string  $to,
    string  $subject,
    int     $serviceId = 0,
    string  $replyTo   = '',
    bool    $success   = true,
    ?string $error     = null
): void {
    try {
        db()->prepare("
            INSERT INTO mail_log (to_address, subject, service_id, reply_to, status, error_message, sent_at)
            VALUES (:to, :subject, :sid, :reply, :status, :error, NOW())
        ")->execute([
            ':to'     => $to,
            ':subject'=> $subject,
            ':sid'    => $serviceId ?: null,
            ':reply'  => $replyTo   ?: null,
            ':status' => $success ? 'sent' : 'failed',
            ':error'  => $error,
        ]);
    } catch (PDOException $e) {
        error_log('log_mail_send DB insert: ' . $e->getMessage());
    }
}
