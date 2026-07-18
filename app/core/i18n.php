<?php
/**
 * Internationalization - Translation Arrays
 * SEPJ Gabès
 * 
 * Support for Arabic (ar), French (fr), English (en)
 */

function __(string $key, string $lang = null): string
{
    static $translations = null;
    
    if ($translations === null) {
        $translations = get_translations();
    }
    
    $lang = $lang ?? current_lang();
    
    return $translations[$key][$lang] ?? $translations[$key]['ar'] ?? $key;
}

function get_translations(): array
{
    return [
        // Navigation
        'nav_home' => [
            'ar' => 'استقبال',
            'fr' => 'Accueil',
            'en' => 'Home',
        ],
        'nav_about' => [
            'ar' => 'تقديم الشركة',
            'fr' => 'À propos',
            'en' => 'About us',
        ],
        'nav_director' => [
            'ar' => 'كلمة السيد المدير العام',
            'fr' => 'Mot du Directeur Général',
            'en' => 'Director\'s Message',
        ],
        'nav_projects' => [
            'ar' => 'مشاريعنا',
            'fr' => 'Nos Projets',
            'en' => 'Our Projects',
        ],
        'nav_services' => [
            'ar' => 'خدماتنا',
            'fr' => 'Nos Services',
            'en' => 'Our Services',
        ],
        'nav_rse' => [
            'ar' => 'المسؤولية المجتمعية',
            'fr' => 'RSE',
            'en' => 'CSR',
        ],
        'nav_social_commitment' => [
            'ar' => 'التزام مجتمعي',
            'fr' => 'Engagement Social',
            'en' => 'Social Commitment',
        ],
        'nav_environmental' => [
            'ar' => 'المساهمة البيئية',
            'fr' => 'Contribution Environnementale',
            'en' => 'Environmental Contribution',
        ],
        'nav_resources' => [
            'ar' => 'الموارد والتنمية',
            'fr' => 'Ressources et Développement',
            'en' => 'Resources & Development',
        ],
        'nav_sports' => [
            'ar' => 'الرياضة والعمل',
            'fr' => 'Sports et Travail',
            'en' => 'Sports & Work',
        ],
        'nav_news' => [
            'ar' => 'أخبار',
            'fr' => 'Actualités',
            'en' => 'News',
        ],
        'nav_activities' => [
            'ar' => 'أنشطة',
            'fr' => 'Activités',
            'en' => 'Activities',
        ],
        'nav_prizes' => [
            'ar' => 'تتويجات',
            'fr' => 'Distinctions',
            'en' => 'Awards',
        ],
        'nav_gallery' => [
            'ar' => 'معرض الصور',
            'fr' => 'Galerie Photos',
            'en' => 'Gallery',
        ],
        'nav_videos' => [
            'ar' => 'معرض الفيديو',
            'fr' => 'Galerie Vidéo',
            'en' => 'Videos',
        ],
        'nav_contact' => [
            'ar' => 'الاتصال بنا',
            'fr' => 'Nous Contacter',
            'en' => 'Contact Us',
        ],
        'nav_search' => [
            'ar' => 'بحث',
            'fr' => 'Rechercher',
            'en' => 'Search',
        ],
        
        // Language labels
        'lang_ar' => ['ar' => 'العربية', 'fr' => 'Arabe', 'en' => 'Arabic'],
        'lang_fr' => ['ar' => 'الفرنسية', 'fr' => 'Français', 'en' => 'French'],
        'lang_en' => ['ar' => 'الإنجليزية', 'fr' => 'Anglais', 'en' => 'English'],
        
        // Common UI
        'read_more' => [
            'ar' => 'اقرأ المزيد',
            'fr' => 'Lire la suite',
            'en' => 'Read more',
        ],
        'view_all' => [
            'ar' => 'عرض الكل',
            'fr' => 'Voir tout',
            'en' => 'View all',
        ],
        'latest_news' => [
            'ar' => 'آخر الأخبار',
            'fr' => 'Dernières actualités',
            'en' => 'Latest news',
        ],
        'our_projects' => [
            'ar' => 'مشاريعنا',
            'fr' => 'Nos projets',
            'en' => 'Our projects',
        ],
        'our_activities' => [
            'ar' => 'أنشطتنا',
            'fr' => 'Nos activités',
            'en' => 'Our activities',
        ],
        'photo_gallery' => [
            'ar' => 'معرض الصور',
            'fr' => 'Galerie photos',
            'en' => 'Photo gallery',
        ],
        'about_company' => [
            'ar' => 'عن الشركة',
            'fr' => 'À propos de la société',
            'en' => 'About the company',
        ],
        'contact_us' => [
            'ar' => 'اتصل بنا',
            'fr' => 'Contactez-nous',
            'en' => 'Contact us',
        ],
        'send_message' => [
            'ar' => 'إرسال رسالة',
            'fr' => 'Envoyer un message',
            'en' => 'Send a message',
        ],
        'your_name' => [
            'ar' => 'الاسم',
            'fr' => 'Nom',
            'en' => 'Name',
        ],
        'your_email' => [
            'ar' => 'البريد الإلكتروني',
            'fr' => 'Email',
            'en' => 'Email',
        ],
        'your_phone' => [
            'ar' => 'رقم الهاتف',
            'fr' => 'Téléphone',
            'en' => 'Phone',
        ],
        'subject' => [
            'ar' => 'الموضوع',
            'fr' => 'Sujet',
            'en' => 'Subject',
        ],
        'your_message' => [
            'ar' => 'رسالتك',
            'fr' => 'Votre message',
            'en' => 'Your message',
        ],
        'send' => [
            'ar' => 'إرسال',
            'fr' => 'Envoyer',
            'en' => 'Send',
        ],
        'search' => [
            'ar' => 'بحث',
            'fr' => 'Recherche',
            'en' => 'Search',
        ],
        'no_results' => [
            'ar' => 'لا توجد نتائج',
            'fr' => 'Aucun résultat',
            'en' => 'No results',
        ],
        'page_not_found' => [
            'ar' => 'الصفحة غير موجودة',
            'fr' => 'Page non trouvée',
            'en' => 'Page not found',
        ],
        'back_to_home' => [
            'ar' => 'العودة إلى الرئيسية',
            'fr' => 'Retour à l\'accueil',
            'en' => 'Back to home',
        ],
        'published_on' => [
            'ar' => 'نشر في',
            'fr' => 'Publié le',
            'en' => 'Published on',
        ],
        'share' => [
            'ar' => 'مشاركة',
            'fr' => 'Partager',
            'en' => 'Share',
        ],
        'related_content' => [
            'ar' => 'محتوى ذو صلة',
            'fr' => 'Contenu connexe',
            'en' => 'Related content',
        ],
        'all_rights_reserved' => [
            'ar' => 'جميع الحقوق محفوظة',
            'fr' => 'Tous droits réservés',
            'en' => 'All rights reserved',
        ],
        'quick_links' => [
            'ar' => 'روابط سريعة',
            'fr' => 'Liens rapides',
            'en' => 'Quick links',
        ],
        'follow_us' => [
            'ar' => 'تابعنا',
            'fr' => 'Suivez-nous',
            'en' => 'Follow us',
        ],
        'company_name' => [
            'ar' => 'شركة البيئة والغراسة والبستنة بقابس',
            'fr' => "Société d'Environnement, Plantation et Jardinage de Gabès",
            'en' => 'Environment, Plantation and Gardening Company of Gabès',
        ],
        'hero_subtitle' => [
            'ar' => 'شركة رائدة في مجال البيئة والتنمية المستدامة',
            'fr' => 'Entreprise leader dans le domaine de l\'environnement et du développement durable',
            'en' => 'Leading company in environment and sustainable development',
        ],
        'our_stats' => [
            'ar' => 'إحصائياتنا',
            'fr' => 'Nos statistiques',
            'en' => 'Our statistics',
        ],
        'founded' => [
            'ar' => 'تأسست في',
            'fr' => 'Fondée en',
            'en' => 'Founded in',
        ],
        'trees_planted' => [
            'ar' => 'شجرة مزروعة',
            'fr' => 'Arbres plantés',
            'en' => 'Trees planted',
        ],
        'hectares_transformed' => [
            'ar' => 'هكتار محولة',
            'fr' => 'Hectares transformés',
            'en' => 'Hectares transformed',
        ],
        'activation_rate' => [
            'ar' => 'نسبة التفعيل',
            'fr' => "Taux d'activation",
            'en' => 'Activation rate',
        ],
        'at_end_of' => [
            'ar' => 'نهاية',
            'fr' => 'Fin',
            'en' => 'End of',
        ],
        'goal_to_reach' => [
            'ar' => 'هدف الوصول إلى',
            'fr' => 'Objectif d\'atteindre',
            'en' => 'Goal to reach',
        ],

        // Admin dashboard labels
        'content_posts' => [
            'ar' => 'الأخبار',
            'fr' => 'Articles',
            'en' => 'Posts',
        ],
        'content_projects' => [
            'ar' => 'المشاريع',
            'fr' => 'Projets',
            'en' => 'Projects',
        ],
        'content_services' => [
            'ar' => 'الخدمات',
            'fr' => 'Services',
            'en' => 'Services',
        ],
        'content_media' => [
            'ar' => 'الوسائط',
            'fr' => 'Médias',
            'en' => 'Media',
        ],
        'content_total' => [
            'ar' => 'إجمالي المحتوى',
            'fr' => 'Contenu total',
            'en' => 'Total content',
        ],
        'new_messages' => [
            'ar' => 'رسائل جديدة',
            'fr' => 'Nouveaux messages',
            'en' => 'New messages',
        ],

        // Content / media labels
        'video_thumbnail' => [
            'ar' => 'صورة مصغرة مخصصة للفيديو',
            'fr' => 'Vignette personnalisée pour la vidéo',
            'en' => 'Custom thumbnail for your video',
        ],
        'video_thumbnail_help' => [
            'ar' => 'ارفع صورة واحدة فقط تُستخدم كغلاف لليوتيوب. إن لم ترفع صورة، سيتم جلب الصورة المصغرة تلقائياً من يوتيوب.',
            'fr' => 'Téléchargez une seule image utilisée comme vignette YouTube. Si vous n\'en mettez pas, la miniature sera récupérée automatiquement depuis YouTube.',
            'en' => 'Upload a single image used as the YouTube thumbnail. If you leave it empty, the thumbnail is fetched automatically from YouTube.',
        ],
        'article_images' => [
            'ar' => 'صور المقال',
            'fr' => 'Images de l\'article',
            'en' => 'Article images',
        ],
        'video_section_label' => [
            'ar' => 'فيديو',
            'fr' => 'Vidéo',
            'en' => 'Video',
        ],
        'attach_video_help' => [
            'ar' => 'أضف رابط يوتيوب لعرض الفيديو تحت المقال وفوق الصور.',
            'fr' => 'Ajoutez un lien YouTube pour afficher la vidéo sous l\'article et au-dessus des photos.',
            'en' => 'Add a YouTube link to show the video below the article and above the photos.',
        ],
        'watch_video' => [
            'ar' => 'شاهد الفيديو',
            'fr' => 'Regarder la vidéo',
            'en' => 'Watch video',
        ],
    ];
}
