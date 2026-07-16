<?php
/**
 * Quick Contact Page - SEPJ Gabès
 * Dynamic service routing via contact_services table
 */

require_once dirname(__DIR__) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/helpers.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/i18n.php';
require_once ROOT_PATH . '/app/core/mailer.php';
require_once ROOT_PATH . '/app/core/rate_limiter.php';

session_start_secure();

$lang     = current_lang();
$success  = false;
$errors   = [];
$values   = ['name'=>'','email'=>'','phone'=>'','service_id'=>'','subject'=>'','message'=>''];

// Load services from DB (non-executive only)
$services = get_contact_services($lang);

/* ── POST handling ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_validate()) {
        $errors[] = $lang === 'ar' ? 'طلب غير صالح.' : ($lang === 'fr' ? 'Requête invalide.' : 'Invalid request.');

    } elseif (!empty($_POST['website'])) {
        csrf_regenerate(); // honeypot — silently reject

    } elseif (!check_contact_rate_limit($_SERVER['REMOTE_ADDR'] ?? '', get_mail_config()['rate_limit_per_hour'])) {
        $errors[] = $lang === 'ar'
            ? 'لقد تجاوزت الحد المسموح به من الرسائل. يرجى المحاولة لاحقاً.'
            : ($lang === 'fr'
                ? 'Limite de messages dépassée. Veuillez réessayer plus tard.'
                : 'Too many messages. Please try again later.');

    } else {
        $name       = trim($_POST['name']       ?? '');
        $email      = trim($_POST['email']      ?? '');
        $phone      = preg_replace('/\s+/', '', trim($_POST['phone'] ?? ''));
        $service_id = (int)($_POST['service_id'] ?? 0);
        $subject    = trim($_POST['subject']    ?? '');
        $message    = trim($_POST['message']    ?? '');

        $values = [
            'name'       => $name,
            'email'      => $email,
            'phone'      => $_POST['phone'] ?? '',
            'service_id' => $service_id,
            'subject'    => $subject,
            'message'    => $message,
        ];

        // ── Validation ──────────────────────────────────────
        if (empty($name))
            $errors[] = $lang==='ar' ? 'الاسم الكامل مطلوب.' : ($lang==='fr' ? 'Le nom complet est requis.' : 'Full name is required.');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = $lang==='ar' ? 'بريد إلكتروني صحيح مطلوب.' : ($lang==='fr' ? 'Une adresse email valide est requise.' : 'A valid email is required.');

        if (empty($phone)) {
            $errors[] = $lang==='ar' ? 'رقم الهاتف مطلوب.' : ($lang==='fr' ? 'Le numéro de téléphone est requis.' : 'Phone number is required.');
        } elseif (!preg_match('/^\d{8}$/', $phone)) {
            $errors[] = $lang==='ar'
                ? 'رقم الهاتف يجب أن يتكون من 8 أرقام بالضبط.'
                : ($lang==='fr' ? 'Le numéro doit contenir exactement 8 chiffres.' : 'Phone must be exactly 8 digits.');
        }

        // Validate service_id against loaded services (prevents spoofing)
        $selectedService = isset($services[$service_id]) ? get_service_by_id($service_id) : null;
        if (!$selectedService)
            $errors[] = $lang==='ar' ? 'يرجى اختيار خدمة صحيحة.' : ($lang==='fr' ? 'Veuillez sélectionner un service valide.' : 'Please select a valid service.');

        if (empty($subject))
            $errors[] = $lang==='ar' ? 'الموضوع مطلوب.' : ($lang==='fr' ? 'Le sujet est requis.' : 'Subject is required.');

        if (empty($message))
            $errors[] = $lang==='ar' ? 'الرسالة مطلوبة.' : ($lang==='fr' ? 'Le message est requis.' : 'Message is required.');

        // ── Save + Route ─────────────────────────────────────
        if (empty($errors)) {
            try {
                $serviceColFr = $selectedService['display_name_fr'];

                // Persist to contact_messages for admin review
                $stmt = db()->prepare("
                    INSERT INTO contact_messages
                        (name, email, phone, subject, message, status, created_at)
                    VALUES
                        (:name, :email, :phone, :subject, :message, 'new', NOW())
                ");
                $stmt->execute([
                    'name'    => $name,
                    'email'   => $email,
                    'phone'   => $phone,
                    'subject' => "[{$serviceColFr}] {$subject}",
                    'message' => $message,
                ]);

                // Send routed email (TO = service email, CC = executives if needed)
                send_routed_email(
                    ['name'=>$name,'email'=>$email,'phone'=>$phone,'subject'=>$subject,'message'=>$message],
                    $selectedService,
                    $lang
                );

                record_contact_submission($_SERVER['REMOTE_ADDR'] ?? '');
                csrf_regenerate();
                $success = true;

            } catch (PDOException $e) {
                error_log('quick-contact save error: ' . $e->getMessage());
                $errors[] = $lang==='ar'
                    ? 'حدث خطأ. يرجى المحاولة مرة أخرى.'
                    : ($lang==='fr' ? 'Une erreur est survenue. Veuillez réessayer.' : 'An error occurred. Please try again.');
            }
        }
    }
}

/* ── UI labels ───────────────────────────────────────────── */
$labels = [
    'title'       => ['ar'=>'تواصل سريع',           'fr'=>'Contact Rapide',                 'en'=>'Quick Contact'],
    'subtitle'    => ['ar'=>'نرد عليك في أقرب وقت', 'fr'=>'Nous vous répondons rapidement.','en'=>"We'll get back to you shortly."],
    'name'        => ['ar'=>'الاسم الكامل',          'fr'=>'Nom complet',                    'en'=>'Full Name'],
    'email'       => ['ar'=>'البريد الإلكتروني',     'fr'=>'Adresse email',                  'en'=>'Email Address'],
    'phone'       => ['ar'=>'رقم الهاتف',            'fr'=>'Numéro de téléphone',            'en'=>'Phone Number'],
    'service'     => ['ar'=>'الخدمة / القسم',        'fr'=>'Service / Département',          'en'=>'Service / Department'],
    'service_ph'  => ['ar'=>'-- اختر القسم --',      'fr'=>'-- Sélectionner un service --',  'en'=>'-- Select a service --'],
    'subject'     => ['ar'=>'الموضوع',               'fr'=>'Sujet',                          'en'=>'Subject'],
    'message'     => ['ar'=>'رسالتك',                'fr'=>'Votre message',                  'en'=>'Your message'],
    'send'        => ['ar'=>'إرسال',                 'fr'=>'Envoyer',                        'en'=>'Send'],
    'back'        => ['ar'=>'العودة',                'fr'=>'Retour',                         'en'=>'Back'],
    'success_msg' => [
        'ar' => 'شكراً! تم استلام رسالتك بنجاح. سنتواصل معك قريباً.',
        'fr' => 'Merci ! Votre message a bien été reçu. Nous vous contacterons bientôt.',
        'en' => 'Thank you! Your message has been received. We will contact you shortly.',
    ],
];

function lbl(array $labels, string $key, string $lang): string {
    return $labels[$key][$lang] ?? $labels[$key]['en'] ?? $key;
}

function hasErr(array $values, string $field, array $errors): bool {
    return !empty($errors) && empty($values[$field]);
}

require_once 'includes/header.php';
?>

<main id="main-content" class="qc-page">
    <div class="qc-container">

        <a href="javascript:history.back()" class="qc-back">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <?= lbl($labels,'back',$lang) ?>
        </a>

        <div class="qc-card">

            <!-- Header -->
            <div class="qc-card-header">
                <div class="qc-icon-wrap" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="28" height="28">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="qc-title"><?= lbl($labels,'title',$lang) ?></h1>
                    <p class="qc-subtitle"><?= lbl($labels,'subtitle',$lang) ?></p>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="qc-success" role="alert">
                <div class="qc-success-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p><?= lbl($labels,'success_msg',$lang) ?></p>
                <a href="index.php" class="glass-btn glass-btn-primary qc-home-btn">
                    <?= $lang==='ar' ? 'الصفحة الرئيسية' : ($lang==='fr' ? 'Accueil' : 'Home') ?>
                </a>
            </div>

            <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div class="qc-errors" role="alert">
                <ul><?php foreach ($errors as $err): if ($err): ?><li><?= e($err) ?></li><?php endif; endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <form method="POST" class="qc-form" novalidate>
                <?= csrf_field() ?>
                <div style="display:none !important;position:absolute;left:-9999px;" aria-hidden="true">
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off">
                </div>

                <!-- 1. Name -->
                <div class="qc-field">
                    <label for="qc_name"><?= lbl($labels,'name',$lang) ?> <span class="qc-required" aria-hidden="true">*</span></label>
                    <input type="text" id="qc_name" name="name" required autocomplete="name"
                           class="form-input <?= hasErr($values,'name',$errors) ? 'qc-input-error' : '' ?>"
                           placeholder="<?= lbl($labels,'name',$lang) ?>"
                           value="<?= e($values['name']) ?>">
                </div>

                <!-- 2. Email -->
                <div class="qc-field">
                    <label for="qc_email"><?= lbl($labels,'email',$lang) ?> <span class="qc-required" aria-hidden="true">*</span></label>
                    <input type="email" id="qc_email" name="email" required autocomplete="email"
                           class="form-input <?= hasErr($values,'email',$errors) ? 'qc-input-error' : '' ?>"
                           placeholder="example@email.com"
                           value="<?= e($values['email']) ?>">
                </div>

                <!-- 3. Phone -->
                <div class="qc-field">
                    <label for="qc_phone"><?= lbl($labels,'phone',$lang) ?> <span class="qc-required" aria-hidden="true">*</span></label>
                    <input type="tel" id="qc_phone" name="phone" required autocomplete="tel"
                           pattern="\d{8}" minlength="8" maxlength="8" inputmode="numeric"
                           <?= $lang==='ar' ? 'dir="rtl"' : '' ?>
                           class="form-input <?= !empty($errors) && (empty($values['phone']) || !preg_match('/^\d{8}$/', preg_replace('/\s+/','',$values['phone']))) ? 'qc-input-error' : '' ?>"
                           placeholder="<?= $lang==='ar' ? 'رقم الهاتف' : ($lang==='fr' ? 'numero telephone' : 'phone number') ?>"
                           value="<?= e($values['phone']) ?>">
                </div>

                <!-- 4. Service (DB-driven, non-executive only) -->
                <div class="qc-field">
                    <label for="qc_service"><?= lbl($labels,'service',$lang) ?> <span class="qc-required" aria-hidden="true">*</span></label>
                    <select id="qc_service" name="service_id" required
                            class="form-input <?= hasErr($values,'service_id',$errors) ? 'qc-input-error' : '' ?>">
                        <option value="" disabled <?= empty($values['service_id']) ? 'selected' : '' ?>>
                            <?= lbl($labels,'service_ph',$lang) ?>
                        </option>
                        <?php foreach ($services as $svc): ?>
                        <option value="<?= (int)$svc['id'] ?>"
                            <?= (int)$values['service_id'] === (int)$svc['id'] ? 'selected' : '' ?>>
                            <?= e($svc['display_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 5. Subject -->
                <div class="qc-field">
                    <label for="qc_subject"><?= lbl($labels,'subject',$lang) ?> <span class="qc-required" aria-hidden="true">*</span></label>
                    <input type="text" id="qc_subject" name="subject" required
                           class="form-input <?= hasErr($values,'subject',$errors) ? 'qc-input-error' : '' ?>"
                           placeholder="<?= lbl($labels,'subject',$lang) ?>"
                           value="<?= e($values['subject']) ?>">
                </div>

                <!-- 6. Message -->
                <div class="qc-field">
                    <label for="qc_message"><?= lbl($labels,'message',$lang) ?> <span class="qc-required" aria-hidden="true">*</span></label>
                    <textarea id="qc_message" name="message" required rows="4"
                              class="form-input <?= hasErr($values,'message',$errors) ? 'qc-input-error' : '' ?>"
                              placeholder="<?= lbl($labels,'message',$lang) ?>"><?= e($values['message']) ?></textarea>
                </div>

                <button type="submit" class="glass-btn glass-btn-primary qc-submit">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <?= lbl($labels,'send',$lang) ?>
                </button>
            </form>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
