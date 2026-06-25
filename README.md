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

## 🚀 Installation sur XAMPP

### Prérequis
- [XAMPP](https://www.apachefriends.org/) installé (PHP 8+, MySQL/MariaDB)
- Git (optionnel)

### Étapes d'installation

1. **Copier le projet**
   ```bash
   # Depuis le dossier racine de ce projet, copier vers htdocs
   cp -r sepj-gabes /c/xampp/htdocs/
   
   # OU simplement placer ce dossier dans C:\xampp\htdocs\
   ```

   Le projet doit être accessible à : `C:\xampp\htdocs\sepj-gabes`

2. **Créer la base de données**
   - Ouvrez phpMyAdmin : http://localhost/phpmyadmin
   - Cliquez sur "Nouveau" (New)
   - Nom de la base : `sepj_gabes`
   - Collation : `utf8mb4_unicode_ci`
   - Cliquez sur "Créer"

3. **Importer le schéma SQL**
   - Sélectionnez la base `sepj_gabes`
   - Cliquez sur l'onglet "Importer" (Import)
   - Choisissez le fichier : `database/schema.sql`
   - Cliquez sur "Exécuter" (Go)

4. **Importer les données initiales**
   - Répétez l'opération avec `database/seed.sql`

5. **Générer le mot de passe admin** (si nécessaire)
   ```bash
   php database/create_admin_hash.php
   ```
   - Copiez le hash généré
   - Mettez à jour dans phpMyAdmin : `UPDATE users SET password_hash = 'HASH_ICI' WHERE email = 'admin@sepj.local';`

6. **Vérifier les permissions**
   - Le dossier `public/uploads` doit être accessible en écriture
   - Sous Windows, les permissions sont généralement déjà correctes

7. **Accéder au site**
   - **Site public** : http://localhost/sepj-gabes/public/
   - **Administration** : http://localhost/sepj-gabes/admin/
   - **Email admin** : `admin@sepj.local`
   - **Mot de passe** : `Admin12345!`

## 🔑 Comptes par défaut

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| Administrateur | admin@sepj.local | Admin12345! |

**⚠️ Changez le mot de passe immédiatement après la première connexion !**

## 🌐 Utilisation multilingue

- **URL** : Ajoutez `?lang=ar`, `?lang=fr`, ou `?lang=en` à n'importe quelle page
- **Défaut** : Arabe (العربية)
- **RTL** : Les pages arabes utilisent la direction droite-à-gauche automatiquement
- **LTR** : Les pages françaises et anglaises utilisent la direction gauche-à-droite

### Navigation dans l'administration
L'interface d'administration supporte également le changement de langue :
```
http://localhost/sepj-gabes/admin/dashboard.php?lang=fr
```

## 🧪 Test de l'installation

Utilisez le script de vérification :
```
http://localhost/sepj-gabes/install/check.php
```

Ce script vérifie :
- Version PHP (8+ requis)
- Extension PDO MySQL activée
- Connexion à la base de données
- Existence des tables requises
- Permissions du dossier uploads

## 📝 Guide d'utilisation rapide

### Ajouter un article/news
1. Connectez-vous : http://localhost/sepj-gabes/admin/
2. Allez dans "Posts / الأخبار" dans le menu latéral
3. Cliquez sur "Créer"
4. Remplissez les champs en Arabe, Français, Anglais
5. Choisissez une image mise en avant
6. Publiez

### Gérer les médias
1. Allez dans "Media Library / الوسائط"
2. Téléchargez des images (JPG, PNG, WEBP max 5MB)
3. Modifiez les légendes et textes alternatifs par langue

### Modifier les paramètres du site
1. Allez dans "Settings / الإعدادات"
2. Modifiez le nom de l'entreprise, coordonnées, etc.
3. Les modifications sont instantanées sur le site public

## 🔒 Checklist de sécurité pour le déploiement

- [ ] Changer le mot de passe admin par défaut
- [ ] Mettre le serveur derrière un pare-feu
- [ ] Utiliser HTTPS si le réseau le permet
- [ ] Restreindre l'URL d'administration par IP si possible
- [ ] Sauvegarder régulièrement la base de données
- [ ] Sauvegarder le dossier `public/uploads`
- [ ] Maintenir XAMPP/PHP à jour
- [ ] Désactiver l'affichage des répertoires
- [ ] Utiliser des mots de passe forts
- [ ] Vérifier les logs d'audit régulièrement

## 📊 Pages disponibles

### Site Public
| Page | URL | Description |
|------|-----|-------------|
| Accueil | `/public/` | Page d'accueil avec héros, actualités, projets |
| Actualités | `/public/post.php?slug=...` | Détail d'un article |
| Projets | `/public/projects.php` | Liste des projets |
| Services | `/public/services.php` | Liste des services |
| Activités | `/public/activities.php` | Liste des activités |
| Distinctions | `/public/prizes.php` | Prix et récompenses |
| RSE | `/public/rse.php` | Responsabilité sociale |
| Ressources | `/public/resources.php` | Ressources et développement |
| Sports | `/public/sports.php` | Sports et travail |
| Galerie | `/public/gallery.php` | Galerie d'images avec lightbox |
| Vidéos | `/public/videos.php` | Galerie vidéo |
| Contact | `/public/contact.php` | Formulaire de contact |
| Recherche | `/public/search.php` | Recherche dans le contenu |

### Administration
| Page | URL | Accès |
|------|-----|-------|
| Dashboard | `/admin/dashboard.php` | admin, editor |
| Contenu | `/admin/content/?type=post` | admin, editor |
| Médias | `/admin/media/` | admin, editor |
| Messages | `/admin/messages/` | admin, editor |
| Paramètres | `/admin/settings/` | admin, editor |
| Utilisateurs | `/admin/users/` | admin seulement |

## 🗄️ Structure de la base de données

8 tables principales :
- `users` : Utilisateurs de l'administration
- `content_items` : Tous les contenus (articles, pages, projets, services, etc.)
- `media` : Fichiers multimédias téléchargés
- `site_settings` : Paramètres du site (nom, contact, etc.)
- `navigation_items` : Éléments de navigation
- `contact_messages` : Messages du formulaire de contact
- `audit_logs` : Journal des actions administratives

## 🤝 Contribution

Pour les développeurs travaillant sur ce projet :
1. Gardez la structure de fichiers claire
2. Utilisez les helpers existants (`e()`, `db()`, `__()`, etc.)
3. Utilisez des requêtes préparées PDO pour toutes les interactions SQL
4. Échappez toutes les sorties avec `e()`
5. Ajoutez la protection CSRF à tous les formulaires
6. Testez les trois langues avant de valider

## 📞 Support

Pour toute question technique concernant ce site, contactez l'équipe technique SEPJ Gabès.

---

**SEPJ Gabès** - Société d'Environnement, Plantation et Jardinage de Gabès
📍 المنطقة الصناعية بڨابس
📞 75 266 037
✉️ Contact@SEPJGabes.tn