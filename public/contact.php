<?php
/**
 * Public Contact Page - SEPJ Gabès
 * Full form with dynamic service routing
 */

require_once 'includes/header.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/mailer.php';
require_once ROOT_PATH . '/app/core/rate_limiter.php';

$lang     = current_lang();
$success  = false;
$errors   = [];
$values   = ['name'=>'','email'=>'','phone'=>'','service_id'=>'','subject'=>'','message'=>''];

$attachLabel = $lang==='ar' ? 'إرفاق ملف (اختياري)' : ($lang==='fr' ? 'Joindre un fichier (optionnel)' : 'Attach a file (optional)');
$attachHint  = $lang==='ar'
    ? 'PDF، صورة، Word — 5 ميغابايت كحد أقصى'
    : ($lang==='fr' ? 'PDF, image, Word — 5 Mo max' : 'PDF, image, Word — max 5 MB');

$ALLOWED_MIME = [
    'application/pdf',
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

// Load active non-executive services for dropdown
$services = get_contact_services($lang);

$svcLabel    = $lang==='ar' ? 'الخدمة / القسم'       : ($lang==='fr' ? 'Service / Département'         : 'Service / Department');
$svcPh       = $lang==='ar' ? '-- اختر القسم --'       : ($lang==='fr' ? '-- Sélectionner un service --' : '-- Select a service --');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $errors[] = $lang==='ar' ? 'طلب غير صالح.' : ($lang==='fr' ? 'Requête invalide.' : 'Invalid request.');
    } elseif (!empty($_POST['website'])) {
        // Honeypot triggered — silently discard
        csrf_regenerate();
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

        if (empty($name))
            $errors[] = $lang==='ar' ? 'الاسم مطلوب.' : ($lang==='fr' ? 'Nom requis.' : 'Name is required.');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = $lang==='ar' ? 'بريد إلكتروني صحيح مطلوب.' : ($lang==='fr' ? 'Email valide requis.' : 'Valid email is required.');
        if (!empty($phone) && !preg_match('/^\d{8}$/', $phone))
            $errors[] = $lang==='ar'
                ? 'رقم الهاتف يجب أن يتكون من 8 أرقام بالضبط.'
                : ($lang==='fr' ? 'Le numéro doit contenir exactement 8 chiffres.' : 'Phone must be exactly 8 digits.');

        // Validate service selection (prevents spoofed IDs)
        $selectedService = ($service_id > 0 && isset($services[$service_id]))
            ? get_service_by_id($service_id)
            : null;
        if (!$selectedService)
            $errors[] = $lang==='ar' ? 'يرجى اختيار قسم.' : ($lang==='fr' ? 'Veuillez sélectionner un service.' : 'Please select a service.');

        if (empty($message))
            $errors[] = $lang==='ar' ? 'الرسالة مطلوبة.' : ($lang==='fr' ? 'Message requis.' : 'Message is required.');

        // File upload validation (optional field)
        $attachment = null;
        $uploadedFile = $_FILES['attachment'] ?? null;
        if (!empty($uploadedFile['name'])) {
            if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                $errors[] = $lang==='ar'
                    ? 'حدث خطأ أثناء تحميل الملف.'
                    : ($lang==='fr' ? 'Erreur lors du téléchargement du fichier.' : 'File upload error.');
            } elseif ($uploadedFile['size'] > $MAX_FILE_SIZE) {
                $errors[] = $lang==='ar'
                    ? 'حجم الملف يتجاوز الحد المسموح به (5 ميغابايت).'
                    : ($lang==='fr' ? 'Le fichier dépasse 5 Mo.' : 'File exceeds the 5 MB limit.');
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $detectedMime = $finfo->file($uploadedFile['tmp_name']);
                if (!in_array($detectedMime, $ALLOWED_MIME, true)) {
                    $errors[] = $lang==='ar'
                        ? 'نوع الملف غير مسموح به. المسموح: PDF، صورة، Word.'
                        : ($lang==='fr'
                            ? 'Type de fichier non autorisé. Autorisés : PDF, image, Word.'
                            : 'File type not allowed. Allowed: PDF, image, Word.');
                } else {
                    $attachment = [
                        'path' => $uploadedFile['tmp_name'],
                        'name' => basename($uploadedFile['name']),
                    ];
                }
            }
        }

        if (empty($errors)) {
            try {
                $serviceNameFr = $selectedService['display_name_fr'];
                $stmt = db()->prepare(
                    "INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at)
                     VALUES (:name, :email, :phone, :subject, :message, 'new', NOW())"
                );
                $stmt->execute([
                    'name'    => $name,
                    'email'   => $email,
                    'phone'   => $phone,
                    'subject' => "[{$serviceNameFr}] {$subject}",
                    'message' => $message,
                ]);

                send_routed_email(
                    ['name'=>$name,'email'=>$email,'phone'=>$phone,'subject'=>$subject,'message'=>$message],
                    $selectedService,
                    $lang,
                    $attachment
                );

                record_contact_submission($_SERVER['REMOTE_ADDR'] ?? '');
                csrf_regenerate();
                $success = true;
            } catch (PDOException $e) {
                error_log('contact.php save error: ' . $e->getMessage());
                $errors[] = $lang==='ar' ? 'خطأ في إرسال الرسالة.' : ($lang==='fr' ? "Erreur d'envoi." : 'Error sending message.');
            }
        }
    }
}
?>
<main id="main-content">
    <div class="page-hero"><div class="max-w-4xl mx-auto px-4"><h1><?= __('nav_contact', $lang) ?></h1></div></div>
    
    <section class="py-8 relative z-10">
        <div class="max-w-5xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Contact Info -->
                <div class="glass-card-static p-8">
                    <h2 class="text-xl font-bold text-white mb-6"><?= __('contact_us', $lang) ?></h2>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3"><span class="text-xl mt-1">📍</span><div><p class="text-white font-medium"><?= $lang === 'ar' ? 'العنوان' : ($lang === 'fr' ? 'Adresse' : 'Address') ?></p><p class="text-emerald-200/70"><?= e(get_setting('address', $lang)) ?></p></div></div>
                        <div class="flex items-start gap-3"><span class="text-xl mt-1">📞</span><div><p class="text-white font-medium"><?= $lang === 'ar' ? 'الهاتف' : ($lang === 'fr' ? 'Téléphone' : 'Phone') ?></p><p class="text-emerald-200/70"><?= e(get_setting('phone')) ?></p></div></div>
                        <div class="flex items-start gap-3"><span class="text-xl mt-1">📧</span><div><p class="text-white font-medium">Email</p><p class="text-emerald-200/70"><?= e(get_setting('email_primary')) ?><br><?= e(get_setting('email_secondary')) ?></p></div></div>
                        <div class="flex items-start gap-3"><span class="text-xl mt-1">📮</span><div><p class="text-white font-medium"><?= $lang === 'ar' ? 'صندوق البريد' : ($lang === 'fr' ? 'Boîte postale' : 'PO Box') ?></p><p class="text-emerald-200/70"><?= e(get_setting('po_box')) ?></p></div></div>
                        <div class="flex items-start gap-3"><span class="text-xl mt-1">📠</span><div><p class="text-white font-medium">Fax</p><p class="text-emerald-200/70"><?= e(get_setting('fax')) ?></p></div></div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="glass-card-static p-8">
                    <h2 class="text-xl font-bold text-white mb-6"><?= __('send_message', $lang) ?></h2>
                    
                    <?php if ($success): ?>
                    <div class="p-4 rounded-lg bg-emerald-600/30 border border-emerald-500/30 text-emerald-300"><?= $lang === 'ar' ? 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.' : ($lang === 'fr' ? 'Message envoyé avec succès. Nous vous contacterons bientôt.' : 'Message sent successfully. We will contact you soon.') ?></div>
                    <?php else: ?>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="mb-4 p-4 rounded-lg bg-red-500/20 border border-red-500/30"><ul class="list-disc list-inside text-red-300 text-sm"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    
                    <form id="contactForm" method="POST" enctype="multipart/form-data" class="space-y-4" novalidate>
                        <?= csrf_field() ?>
                        <!-- Honeypot - invisible to users and bots -->
                        <div style="display:none !important;position:absolute;left:-9999px;" aria-hidden="true">
                            <input type="text" name="website" value="" tabindex="-1" autocomplete="off">
                        </div>
                        <!-- Service / Department (routes email to correct recipient) -->
                        <div>
                            <label for="contact_service" class="sr-only"><?= $svcLabel ?></label>
                            <select id="contact_service" name="service_id" required
                                    class="form-input <?= !empty($errors) && empty($values['service_id']) ? 'border-red-500/50' : '' ?>">
                                <option value="" disabled <?= empty($values['service_id']) ? 'selected' : '' ?>>
                                    <?= $svcPh ?>
                                </option>
                                <?php foreach ($services as $svc): ?>
                                <option value="<?= (int)$svc['id'] ?>"
                                    <?= (int)$values['service_id'] === (int)$svc['id'] ? 'selected' : '' ?>>
                                    <?= e($svc['display_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="contact_name" class="sr-only"><?= __('your_name', $lang) ?></label>
                            <input type="text" id="contact_name" name="name" required
                                   class="form-input"
                                   placeholder="<?= __('your_name', $lang) ?>"
                                   autocomplete="name">
                        </div>
                        <div>
                            <label for="contact_email" class="sr-only"><?= __('your_email', $lang) ?></label>
                            <input type="email" id="contact_email" name="email" required
                                   class="form-input"
                                   placeholder="<?= __('your_email', $lang) ?>"
                                   autocomplete="email">
                        </div>
                        <div>
                            <label for="contact_phone" class="sr-only"><?= __('your_phone', $lang) ?></label>
                            <input type="tel" id="contact_phone" name="phone"
                                   class="form-input"
                                   pattern="\d{8}"
                                   minlength="8"
                                   maxlength="8"
                                   inputmode="numeric"
                                   placeholder="Number"
                                   autocomplete="tel">
                        </div>
                        <div>
                            <label for="contact_subject" class="sr-only"><?= __('subject', $lang) ?></label>
                            <input type="text" id="contact_subject" name="subject"
                                   class="form-input"
                                   placeholder="<?= __('subject', $lang) ?>">
                        </div>
                        <div>
                            <label for="contact_message" class="sr-only"><?= __('your_message', $lang) ?></label>
                            <textarea id="contact_message" name="message" required
                                      class="form-input" rows="5"
                                      placeholder="<?= __('your_message', $lang) ?>"></textarea>
                        </div>
                        <div>
                            <label for="contact_attachment" class="block text-sm text-emerald-200/80 mb-1">
                                <?= $attachLabel ?>
                            </label>
                            <input type="file" id="contact_attachment" name="attachment"
                                   accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx"
                                   class="w-full text-sm text-emerald-200/70
                                          file:mr-3 file:py-2 file:px-4
                                          file:rounded-lg file:border-0
                                          file:text-sm file:font-medium
                                          file:bg-emerald-700/40 file:text-emerald-200
                                          hover:file:bg-emerald-600/50 cursor-pointer">
                            <p class="mt-1 text-xs text-emerald-300/50"><?= $attachHint ?></p>
                        </div>
                        <button type="submit" id="contactSubmitBtn" class="glass-btn glass-btn-primary w-full justify-center">
                            <span class="btn-text"><?= __('send', $lang) ?></span>
                            <span class="btn-loading hidden" aria-hidden="true">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                        </button>
                        <!-- ARIA live region for screen reader announcements -->
                        <div id="formStatus" role="status" aria-live="polite" class="sr-only"></div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>