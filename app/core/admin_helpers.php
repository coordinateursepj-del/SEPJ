<?php
/**
 * Admin Helper Functions - SEPJ Gabès
 */

/**
 * Get human-readable label for a content type
 */
function get_content_type_label(string $type, string $lang = 'ar'): string
{
    $labels = [
        'page'     => ['ar' => 'صفحة', 'fr' => 'Page', 'en' => 'Page'],
        'post'     => ['ar' => 'خبر', 'fr' => 'Article', 'en' => 'Post'],
        'project'  => ['ar' => 'مشروع', 'fr' => 'Projet', 'en' => 'Project'],
        'service'  => ['ar' => 'خدمة', 'fr' => 'Service', 'en' => 'Service'],
        'activity' => ['ar' => 'نشاط', 'fr' => 'Activité', 'en' => 'Activity'],
        'prize'    => ['ar' => 'تتويج', 'fr' => 'Distinction', 'en' => 'Award'],
        'rse'      => ['ar' => 'مسؤولية مجتمعية', 'fr' => 'RSE', 'en' => 'CSR'],
        'resource' => ['ar' => 'مورد', 'fr' => 'Ressource', 'en' => 'Resource'],
        'sport'    => ['ar' => 'رياضة', 'fr' => 'Sport', 'en' => 'Sport'],
        'video'    => ['ar' => 'فيديو', 'fr' => 'Vidéo', 'en' => 'Video'],
    ];
    
    return $labels[$type][$lang] ?? $labels[$type]['ar'] ?? $type;
}

/**
 * Get admin URL for a content type
 */
function content_admin_url(string $type, string $action = 'index'): string
{
    $base = '../content/';
    switch ($action) {
        case 'create':
            return $base . 'create.php?type=' . $type;
        case 'edit':
            return $base . 'edit.php?id=';
        case 'delete':
            return $base . 'delete.php?id=';
        default:
            return $base . 'index.php?type=' . $type;
    }
}

/**
 * Generate HTML for a status badge
 */
function status_badge(string $status): string
{
    $classes = [
        'published' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
        'draft'     => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
        'new'       => 'bg-red-500/20 text-red-300 border-red-500/30',
        'read'      => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
        'archived'  => 'bg-gray-500/20 text-gray-300 border-gray-500/30',
        'active'    => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
        'inactive'  => 'bg-gray-500/20 text-gray-300 border-gray-500/30',
    ];
    
    $class = $classes[$status] ?? 'bg-gray-500/20 text-gray-300 border-gray-500/30';
    
    return '<span class="inline-block px-2 py-0.5 text-xs rounded-full border ' . $class . '">' . e($status) . '</span>';
}

/**
 * Get allowed content types for CRUD
 */
function get_allowed_types(): array
{
    return ['page', 'post', 'project', 'service', 'activity', 'prize', 'rse', 'resource', 'sport', 'video'];
}

/**
 * Validate content type
 */
function validate_type(string $type): bool
{
    return in_array($type, get_allowed_types());
}

/**
 * Get RSE category labels
 */
function rse_category_labels(string $lang = 'ar'): array
{
    return [
        'engagement_social' => [
            'ar' => 'التزام اجتماعي',
            'fr' => 'Engagement Social',
            'en' => 'Social Engagement',
        ],
        'engagement_environmental' => [
            'ar' => 'التزامنا المجتمعي والبيئي',
            'fr' => 'Engagement Sociétal et Environnemental',
            'en' => 'Societal and Environmental Engagement',
        ],
        'rapport_rse' => [
            'ar' => 'تقرير المسؤولية المجتمعية',
            'fr' => 'Rapport RSE',
            'en' => 'CSR Report',
        ],
        'catalogue_rse' => [
            'ar' => 'كتالوج المسؤولية المجتمعية',
            'fr' => 'Catalogue RSE',
            'en' => 'CSR Catalog',
        ],
        'rapport_durabilite' => [
            'ar' => 'تقرير الاستدامة',
            'fr' => 'Rapport de Durabilité',
            'en' => 'Sustainability Report',
        ],
    ];
}

/**
 * Get single RSE category label
 */
function rse_category_label(?string $category, string $lang = 'ar'): string
{
    if ($category === null) {
        return rse_category_labels($lang)['engagement_social'][$lang] ?? 'Engagement Social';
    }
    $labels = rse_category_labels($lang);
    return $labels[$category][$lang] ?? $category;
}

/**
 * Generate slug suggestion from text
 */
function slugify(string $text): string
{
    // Convert to lowercase, replace spaces and special chars
    $text = mb_strtolower($text, 'UTF-8');
    
    // Replace non-alphanumeric characters with hyphens
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Get content type icon
 */
function get_type_icon(string $type): string
{
    $icons = [
        'page'     => '📄',
        'post'     => '📰',
        'project'  => '🏗️',
        'service'  => '🔧',
        'activity' => '📋',
        'prize'    => '🏆',
        'rse'      => '🌱',
        'resource' => '📚',
        'sport'    => '⚽',
        'video'    => '🎥',
    ];
    
    return $icons[$type] ?? '📄';
}

/**
 * Get page title based on content type
 */
function get_content_page_title(string $type, string $lang, string $action = 'list'): string
{
    $typeLabel = get_content_type_label($type, $lang);
    
    $titles = [
        'list'   => [
            'ar' => 'قائمة ' . $typeLabel,
            'fr' => 'Liste des ' . $typeLabel . 's',
            'en' => ucfirst($typeLabel) . ' List',
        ],
        'create' => [
            'ar' => 'إضافة ' . $typeLabel,
            'fr' => 'Ajouter ' . $typeLabel,
            'en' => 'Add ' . ucfirst($typeLabel),
        ],
        'edit'   => [
            'ar' => 'تعديل ' . $typeLabel,
            'fr' => 'Modifier ' . $typeLabel,
            'en' => 'Edit ' . ucfirst($typeLabel),
        ],
    ];
    
    return $titles[$action][$lang] ?? $titles[$action]['ar'] ?? $typeLabel;
}

/**
 * Build admin breadcrumb
 */
function admin_breadcrumb(string $type, string $action = 'list', ?string $title = null): string
{
    $lang = current_lang();
    $typeLabel = get_content_type_label($type, $lang);

    $home = $lang === 'ar' ? 'الرئيسية' : ($lang === 'fr' ? 'Accueil' : 'Home');

    $html = '<div class="breadcrumbs">';
    // Use absolute ADMIN_URL so breadcrumb works from any subdirectory depth
    $html .= '<a href="' . ADMIN_URL . '/dashboard.php">' . $home . '</a>';
    $html .= '<span class="separator">/</span>';
    $html .= '<a href="' . ADMIN_URL . '/content/index.php?type=' . $type . '">' . $typeLabel . '</a>';
    
    if ($action !== 'list') {
        $html .= '<span class="separator">/</span>';
        $actionLabel = $action === 'create' 
            ? ($lang === 'ar' ? 'إضافة' : ($lang === 'fr' ? 'Ajouter' : 'Add'))
            : ($lang === 'ar' ? 'تعديل' : ($lang === 'fr' ? 'Modifier' : 'Edit'));
        $html .= '<span>' . $actionLabel . '</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}