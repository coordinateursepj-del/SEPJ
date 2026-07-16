-- ============================================================
-- SEPJ Gabès — Contact Services Routing Table
-- Safe to run multiple times (idempotent)
-- Source: Emails.csv
-- ============================================================

CREATE TABLE IF NOT EXISTS `contact_services` (
    `id`             INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `display_name_fr` VARCHAR(255)  NOT NULL,
    `display_name_ar` VARCHAR(255)  NOT NULL DEFAULT '',
    `display_name_en` VARCHAR(255)  NOT NULL DEFAULT '',
    `email`          VARCHAR(255)   NOT NULL,
    `is_executive`   TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = executive-only, never in dropdown',
    `cc_executives`  TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = selecting this service auto-CCs all executives',
    `sort_order`     SMALLINT      NOT NULL DEFAULT 0,
    `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_email` (`email`),
    INDEX `idx_executive` (`is_executive`),
    INDEX `idx_active`    (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Normal services (shown in dropdown)
-- ============================================================
INSERT IGNORE INTO `contact_services`
    (`display_name_fr`, `display_name_ar`, `display_name_en`, `email`, `is_executive`, `cc_executives`, `sort_order`)
VALUES
    ('Gabès Sud',              'ڨابس الجنوب',            'Gabès South',            'gabes.sud@sepjgabes.tn',      0, 0,  10),
    ('Social',                 'الشؤون الاجتماعية',      'Social Affairs',         'social@sepjgabes.tn',         0, 0,  20),
    ('Gabès Ville',            'ڨابس المدينة',           'Gabès City',             'gabes.ville@sepjgabes.tn',    0, 0,  30),
    ('Ben Ghilouf',            'بن غيلوف',               'Ben Ghilouf',            'ben.ghilouf@sepjgabes.tn',    0, 0,  40),
    ('Agricole',               'الفلاحة',                'Agriculture',            'agricole@sepjgabes.tn',       0, 0,  50),
    ("Bureau d'Ordre Central", 'مكتب الضبط المركزي',     'Central Registry',       'boc@sepjgabes.tn',            0, 0,  60),
    ('Contact Général',        'الاتصال العام',          'General Contact',        'contact@sepjgabes.tn',        0, 0,  70),
    ('Archives',               'الأرشيف',                'Archives',               'archives@sepjgabes.tn',       0, 0,  80),
    ('Gestion du Stock',       'تسيير المخزون',          'Stock Management',       'stock@sepjgabes.tn',          0, 0,  90),
    ('RSE',                    'المسؤولية الاجتماعية',   'CSR',                    'rse@sepjgabes.tn',            0, 0, 100),
    ('Mareth',                 'مارث',                   'Mareth',                 'mareth@sepjgabes.tn',         0, 0, 110),
    ('Métouia',                'المطوية',                'Métouia',                'metouia@sepjgabes.tn',        0, 0, 120),
    ('Achats',                 'المشتريات',              'Procurement',            'achats@sepjgabes.tn',         0, 0, 130),
    ('Technique',              'التقنية',                'Technical Dept.',        'technique@sepjgabes.tn',      0, 0, 140),
    ('Financier',              'المالية',                'Finance',                'financier@sepjgabes.tn',      0, 0, 150),
    ('Ghannouch',              'غنوش',                   'Ghannouch',              'ghannouch@sepjgabes.tn',      0, 0, 160),
    ('Matmata Nouvelle',       'مطماطة الجديدة',         'New Matmata',            'matmata.nv@sepjgabes.tn',     0, 0, 170),
    ('Juridique',              'الشؤون القانونية',       'Legal Dept.',            'juridique@sepjgabes.tn',      0, 0, 180),
    ('Matmata Ancienne',       'مطماطة القديمة',         'Old Matmata',            'matmata.anc@sepjgabes.tn',    0, 0, 190),
    ('Gabès Ouest',            'ڨابس الغرب',             'Gabès West',             'gabes.ouest@sepjgabes.tn',    0, 0, 200),
    ('Contrôle',               'المراقبة',               'Control & Audit',        'controle@sepjgabes.tn',       0, 0, 210),
    ('Ressources Humaines',    'الموارد البشرية',        'Human Resources',        'rh@sepjgabes.tn',             0, 0, 220),
    -- *** Special: selecting this service auto-CCs all executives ***
    ('Manzel Habib',           'منزل الحبيب',            'Manzel Habib',           'manzel.habib@sepjgabes.tn',   0, 1, 230),
    ('Hamma',                  'الحامة',                 'Hamma',                  'hamma@sepjgabes.tn',          0, 0, 240),
    ('Coordinateur',           'المنسق',                 'Coordinator',            'coordinateur@sepjgabes.tn',   0, 0, 250),
    ('Informatique',           'الإعلامية',              'IT Department',          'informatique@sepjgabes.tn',   0, 0, 260),
    ('Zerkine',                'زرقين',                  'Zerkine',                'zerkine@sepjgabes.tn',        0, 0, 270);

-- ============================================================
-- Executive emails (is_executive=1 — never shown in dropdown)
-- ============================================================
INSERT IGNORE INTO `contact_services`
    (`display_name_fr`, `display_name_ar`, `display_name_en`, `email`, `is_executive`, `cc_executives`, `sort_order`)
VALUES
    ('PDG — Abdel Salem Bsissa',   'المدير العام — عبد السلام بسيسة', 'CEO — Abdel Salem Bsissa',   'bsissa_a@yahoo.fr',           1, 0, 900),
    ('DGA — Fakhri Hajji',         'نائب المدير — فخري حجي',          'Deputy GM — Fakhri Hajji',   'hajjifakhri1970@gmail.com',   1, 0, 910),
    ('DGA — Mostafa KhalfAllah',   'نائب المدير — مصطفى خلف الله',    'Deputy GM — Mostafa KhalfAllah', 'stoufa1979@hotmail.fr',   1, 0, 920);
