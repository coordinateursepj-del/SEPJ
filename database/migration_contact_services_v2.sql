-- ============================================================
-- SEPJ Gabès — Contact Services v2
-- Adds category grouping + correct Arabic spelling (قابس)
-- Re-seeds the two dropdown sections:
--   1) مصالح الإدارة العامة  (general administration departments)
--   2) الإدارات الفرعية للشركة (company sub-directorates)
-- Safe to run multiple times. MySQL 8.x compatible (no IF NOT EXISTS on ALTER).
-- ============================================================

-- 1) Add category column if missing (MySQL 8 does not support IF NOT EXISTS on ALTER)
SET @exist = (SELECT COUNT(*)
              FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'contact_services'
                AND COLUMN_NAME  = 'category');
SET @sql = IF(@exist = 0,
              'ALTER TABLE `contact_services` ADD COLUMN `category` VARCHAR(40) NOT NULL DEFAULT '''' COMMENT ''general | sub''',
              'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Remove the old non-executive rows so we can re-seed cleanly.
--    (contact_messages stores the subject as text, so no FK breakage.)
DELETE FROM `contact_services` WHERE `is_executive` = 0;

-- 3) Re-seed non-executive services grouped into two categories.
--    category = 'general'  ->  مصالح الإدارة العامة
--    category = 'sub'      ->  الإدارات الفرعية للشركة
INSERT INTO `contact_services`
    (`display_name_fr`, `display_name_ar`, `display_name_en`, `email`, `is_executive`, `cc_executives`, `category`, `sort_order`)
VALUES
    -- ── مصالح الإدارة العامة ───────────────────────────────
    ('Coordination & Moyens Généraux', 'مصلحة التنسيق والوسائل العامة', 'Coordination & General Means', 'coordination@sepjgabes.tn', 0, 0, 'general', 10),
    ("Bureau d'Ordre Central",         'مكتب الضبط المركزي',             'Central Registry',            'boc@sepjgabes.tn',         0, 0, 'general', 20),
    ('Informatique',                   'مصلحة الإعلامية',                'IT Department',               'informatique@sepjgabes.tn',0, 0, 'general', 30),
    ('Juridique',                      'مصلحة الشؤون القانونية',         'Legal Dept.',                 'juridique@sepjgabes.tn',   0, 0, 'general', 40),
    ('Archives',                       'الأرشيف',                        'Archives',                    'archives@sepjgabes.tn',    0, 0, 'general', 50),
    ('RSE',                            'مصلحة المسؤولية المجتمعية',      'CSR',                         'rse@sepjgabes.tn',         0, 0, 'general', 60),
    ('Projets Agricoles',              'مصلحة المشاريع الفلاحية',        'Agricultural Projects',       'agricole@sepjgabes.tn',    0, 0, 'general', 70),
    ('Ressources Humaines',            'مصلحة التصرف في الموارد البشرية','Human Resources',             'rh@sepjgabes.tn',          0, 0, 'general', 80),
    ('Hygiène & Sécurité',             'مصلحة الصحة والسلامة المهنية',   'Health & Safety',             'hse@sepjgabes.tn',         0, 0, 'general', 90),
    ('Affaires Sociales',              'مصلحة الشؤون الإجتماعية',        'Social Affairs',              'social@sepjgabes.tn',      0, 0, 'general', 100),
    ('Contact Général',                'الاتصال بالشركة',                'Company Contact',             'contact@sepjgabes.tn',     0, 0, 'general', 110),
    ('Financier',                      'مصلحة المالية',                  'Finance Dept.',               'financier@sepjgabes.tn',   0, 0, 'general', 120),
    ('Technique',                      'المصلحة التقنية',                'Technical Dept.',             'technique@sepjgabes.tn',   0, 0, 'general', 130),
    ('Contrôle',                       'مصلحة المراقبة',                 'Control & Audit',             'controle@sepjgabes.tn',    0, 0, 'general', 140),

    -- ── الإدارات الفرعية للشركة ─────────────────────────────
    ('Gabès Ville',   'قابس المدينة',    'Gabès City',     'gabes.ville@sepjgabes.tn', 0, 0, 'sub', 10),
    ('Gabès Ouest',   'قابس الغربية',    'Gabès West',     'gabes.ouest@sepjgabes.tn', 0, 0, 'sub', 20),
    ('Gabès Sud',     'قابس الجنوبية',   'Gabès South',    'gabes.sud@sepjgabes.tn',   0, 0, 'sub', 30),
    ('Métouia',       'المطوية',         'Métouia',        'metouia@sepjgabes.tn',     0, 0, 'sub', 40),
    ('Hamma',         'الحامة',          'Hamma',          'hamma@sepjgabes.tn',       0, 0, 'sub', 50),
    ('Mareth',        'مارث',            'Mareth',         'mareth@sepjgabes.tn',      0, 0, 'sub', 60),
    ('Zerkine',       'زقراطة',          'Zerkine',        'zerkine@sepjgabes.tn',     0, 0, 'sub', 70),
    ('Manzel Habib',  'منزل الحبيب',     'Manzel Habib',   'manzel.habib@sepjgabes.tn',0, 0, 'sub', 80),
    ('Ghannouch',     'غنوش',            'Ghannouch',      'ghannouch@sepjgabes.tn',   0, 0, 'sub', 90),
    ('Matmata Nouv.',  'مطماطة الجديدة',  'New Matmata',    'matmata.nv@sepjgabes.tn',  0, 0, 'sub', 100),
    ('Matmata Anc.',  'مطماطة القديمة',  'Old Matmata',    'matmata.anc@sepjgabes.tn', 0, 0, 'sub', 110);
