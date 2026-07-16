# 🌿 SEPJ Gabès - Système de Gestion de Site Web Bilingue/Trilingue

**Société d'Environnement, Plantation et Jardinage de Gabès**
**شركة البيئة والغراسة والبستنة بقابس**

Un système complet de gestion de site web pour la SEPJ Gabès, comprenant un site public moderne et un tableau de bord d'administration sécurisé.

## 📋 Aperçu

Ce projet fournit :
- **Site public** : Site vitrine moderne avec support trilingue (Arabe/Français/Anglais)
- **Administration** : Tableau de bord privé pour la gestion de contenu
- **CMS complet** : Gestion des actualités, projets, services, activités, galeries, vidéos, etc.

## 🛠️ Technologies

| Technologie | Version |
|------------|---------|
| PHP | 8.x (compatible XAMPP) |
| MySQL/MariaDB | Compatible XAMPP |
| Tailwind CSS | CDN |
| JavaScript | Vanilla JS |
| HTML5 | - |

**Aucun framework lourd** (pas de Laravel, React, Vue, Next.js, Node.js, ou Composer)

## 📁 Structure du Projet

```
/sepj-gabes/
├── app/                    # Logique applicative
│   ├── config/             # Configuration (base de données, application)
│   └── core/               # Fonctions noyau (DB, auth, helpers, i18n)
├── admin/                  # Interface d'administration
│   ├── includes/           # Header, sidebar, footer admin
│   ├── content/            # CRUD contenu
│   ├── media/              # Gestion des médias
│   ├── messages/           # Gestion des messages contact
│   ├── settings/           # Paramètres du site
│   └── users/              # Gestion des utilisateurs
├── public/                 # Site public
│   ├── includes/           # Header, nav, footer public
│   ├── assets/
│   │   ├── css/            # Feuilles de style
│   │   ├── js/             # Scripts JavaScript
│   │   └── uploads/        # Fichiers téléchargés
│   └── page.php, post.php, etc.
├── database/               # Scripts SQL
│   ├── schema.sql          # Structure de la base de données
│   └── seed.sql            # Données initiales
└── install/                # Vérificateur d'installation
```


## 📞 Support

Pour toute question technique concernant ce site, contactez l'équipe technique SEPJ Gabès.

---

**SEPJ Gabès** - Société d'Environnement, Plantation et Jardinage de Gabès
📍 المنطقة الصناعية بڨابس
📞 75 266 037
✉️ Contact@SEPJGabes.tn
