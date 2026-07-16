---
---

# [Nom de l'Établissement / Institut Supérieur]
### [Département — ex : Département Informatique]

<br><br>

# RAPPORT DE STAGE DE FIN D'ÉTUDES

## Développement d'une plateforme web institutionnelle trilingue —
## Module Authentification, Sécurité, Comptes Administrateurs & Médiathèque

<br>

**Élaboré par :** [Nom et prénom de l'étudiant — Membre C]

**Filière :** [ex : Licence Appliquée en Développement des Systèmes d'Information]

**Organisme d'accueil :** SEPJ Gabès — Société d'Environnement, Plantation et Jardinage de Gabès

**Encadrant professionnel :** [Nom, fonction — SEPJ Gabès]

**Encadrant académique :** [Nom, grade]

**Année universitaire :** [20XX / 20XX]

**Période de stage :** [du JJ/MM/20XX au JJ/MM/20XX]

<div style="page-break-after: always;"></div>

## Dédicace

*(Section personnelle — à rédiger par l'étudiant. Placeholder à remplacer.)*

Je dédie ce modeste travail à mes parents, pour leur soutien constant tout au long de mon parcours, ainsi qu'à toute personne ayant contribué, de près ou de loin, à sa réalisation.

<div style="page-break-after: always;"></div>

## Remerciements

Je tiens à exprimer ma sincère gratitude à l'ensemble du personnel de la SEPJ Gabès pour son accueil et sa disponibilité durant toute la période de mon stage.

Mes remerciements s'adressent tout particulièrement à **[Nom de l'encadrant professionnel]**, mon encadrant au sein de l'entreprise, pour la confiance qu'il/elle m'a accordée sur les aspects sensibles du projet — accès aux comptes, aux identifiants de production et à la configuration du serveur.

Je remercie également **[Nom de l'encadrant académique]**, mon encadrant académique, pour son suivi rigoureux et ses remarques constructives tout au long de ce projet.

Enfin, je remercie les membres du jury d'avoir accepté d'évaluer ce travail, ainsi que mes collègues de stage, **[Membre A]** et **[Membre B]**, dont les modules reposent directement sur le socle d'authentification, de sécurité et de médiathèque que j'ai eu la responsabilité de concevoir.

<div style="page-break-after: always;"></div>

## Résumé

Ce rapport présente le travail réalisé dans le cadre d'un stage de fin d'études au sein de la SEPJ Gabès (Société d'Environnement, Plantation et Jardinage de Gabès). Le projet global consistait à concevoir une plateforme web institutionnelle trilingue, doublée d'un back-office de gestion de contenu. Notre contribution, au sein d'une équipe de trois stagiaires, a porté sur le **module transverse de sécurité** : l'authentification par session, le contrôle d'accès par rôle, la protection contre les attaques CSRF, la sécurisation des dépôts de fichiers (médiathèque), la gestion des comptes administrateurs et le journal d'audit des actions sensibles. Ce document détaille l'analyse des besoins, la conception retenue, l'implémentation en PHP 8, ainsi qu'un incident réel de production — une perte de connexion à la base de données consécutive à une mauvaise séparation entre configuration versionnée et secrets de déploiement — et la démarche systématique employée pour le diagnostiquer et le corriger durablement.

**Mots-clés :** PHP, MySQL, authentification, sessions, CSRF, sécurité des uploads, contrôle d'accès, audit.

## Abstract

This report presents the work carried out during an end-of-studies internship at SEPJ Gabès (Environment, Plantation and Gardening Company of Gabès). The overall project consisted in designing a trilingual institutional web platform backed by a content-management back-office. Within a team of three interns, our contribution focused on the **cross-cutting security module**: session-based authentication, role-based access control, CSRF protection, secure file uploads (media library), administrator account management, and the audit log of sensitive actions. This document details the requirements analysis, the retained design, the PHP 8 implementation, and a real production incident — a loss of database connectivity caused by an improper separation between version-controlled configuration and deployment secrets — along with the systematic approach used to diagnose and durably fix it.

**Keywords:** PHP, MySQL, authentication, sessions, CSRF, upload security, access control, audit.

<div style="page-break-after: always;"></div>

## Table des matières

*(Table générée automatiquement à partir des titres ci-dessous une fois le document mis en page sous Word / LibreOffice / Google Docs.)*

- Introduction générale
- Chapitre 1 — Présentation de l'organisme d'accueil et cadre du projet
- Chapitre 2 — Analyse et spécification des besoins
- Chapitre 3 — Conception
- Chapitre 4 — Réalisation
- Conclusion générale
- Bibliographie et Netographie
- Annexes

## Liste des abréviations

| Abréviation | Signification |
|---|---|
| PFE | Projet de Fin d'Études |
| PDO | PHP Data Objects |
| CSRF | Cross-Site Request Forgery |
| MIME | Multipurpose Internet Mail Extensions |
| RBAC | Role-Based Access Control (contrôle d'accès par rôle) |
| CI/CD | Continuous Integration / Continuous Deployment |
| DSN | Data Source Name (chaîne de connexion base de données) |
| .htaccess | Fichier de configuration Apache par répertoire |
| XAMPP | X (multi-OS) Apache MySQL PHP Perl |

<div style="page-break-after: always;"></div>

## Introduction générale

Toute application web manipulant des comptes utilisateurs, des contenus modifiables et des fichiers déposés par des tiers repose sur un socle de sécurité qui, bien qu'invisible pour l'utilisateur final, conditionne la fiabilité de l'ensemble du système. C'est ce socle qui nous a été confié dans le cadre du projet mené pour la SEPJ Gabès, au sein d'une équipe de trois stagiaires, chacun en charge d'un module fonctionnel distinct.

Le module qui nous a été confié est celui de l'**authentification, de la sécurité et de la gestion des comptes** : il conditionne directement le fonctionnement des deux autres modules de l'application, puisque toute action d'administration — publier un article, traiter un message, déposer une image — transite par les mécanismes que nous avons conçus. Ce rapport documente également un incident de production réel, lié à la gestion des secrets de configuration, qui a constitué la difficulté technique la plus marquante de ce stage.

Ce rapport est organisé en quatre chapitres. Le premier présente l'organisme d'accueil, le contexte du projet global et la méthodologie de travail adoptée par l'équipe. Le deuxième développe l'analyse des besoins fonctionnels et non fonctionnels propres à notre module. Le troisième expose les choix de conception en matière de sessions, de contrôle d'accès et de sécurisation des dépôts de fichiers. Le quatrième détaille la réalisation technique, l'incident de production rencontré et la démarche de diagnostic adoptée. Une conclusion générale clôt ce rapport en dressant un bilan des compétences acquises.

<div style="page-break-after: always;"></div>

## Chapitre 1 — Présentation de l'organisme d'accueil et cadre du projet

### 1.1 Présentation de la SEPJ Gabès

La Société d'Environnement, Plantation et Jardinage de Gabès (شركة البيئة والغراسة والبستنة بقابس), désignée dans ce document par l'acronyme **SEPJ Gabès**, assure des missions d'aménagement paysager, de reboisement et de développement durable sur l'ensemble du gouvernorat de Gabès. Son organisation comprend une direction générale, une dizaine d'agences régionales et une quinzaine de directions fonctionnelles (Ressources Humaines, Financier, Juridique, Technique, Achats, Contrôle, Informatique, Archives, Bureau d'Ordre Central, Coordination, RSE, Social), pour un effectif dont une partie — les éditeurs de contenu et les administrateurs du site — devait pouvoir se voir attribuer un compte d'accès au back-office avec un niveau de droits adapté à sa fonction.

### 1.2 Contexte et problématique du projet

Le projet global visait à donner à la SEPJ Gabès une plateforme web institutionnelle gérable en autonomie par son propre personnel, sans dépendance systématique à un développeur externe. Cette autonomie suppose un système d'accès sécurisé : seules les personnes habilitées doivent pouvoir modifier le contenu public, et toute action doit pouvoir être tracée en cas de besoin.

La problématique confiée à l'équipe peut se formuler ainsi : *comment concevoir une plateforme web qui centralise la communication institutionnelle de la SEPJ Gabès, tout en donnant au personnel de l'entreprise les moyens de la maintenir de façon autonome ?*

En ce qui concerne plus spécifiquement notre module, la question se précise : *comment garantir que seules les personnes autorisées puissent accéder au back-office et le modifier, que les fichiers déposés par les utilisateurs ne puissent jamais compromettre le serveur, et que les secrets de connexion à la base de données et à la messagerie ne circulent jamais dans le dépôt de code public ?*

### 1.3 Objectifs du stage et périmètre confié

Le périmètre confié à notre module a couvert :

- l'authentification par session (connexion, déconnexion, régénération de session) ;
- le contrôle d'accès par rôle (administrateur / éditeur) sur l'ensemble des pages sensibles du back-office ;
- la protection CSRF, appliquée à tous les formulaires d'administration ;
- le moteur d'upload sécurisé et la médiathèque (import, recherche, suppression d'images) ;
- la gestion des comptes utilisateurs (création, modification, changement de rôle, suppression) ;
- le tableau de bord d'administration et l'ossature commune (en-tête, barre latérale) du back-office ;
- le journal d'audit des actions sensibles ;
- la séparation entre configuration versionnée et secrets de production (base de données, déploiement).

Les modules de contenu/RSE et de contact/messagerie, bien que reposant directement sur notre socle d'authentification et de sécurité, ont été pris en charge par les deux autres membres de l'équipe et ne sont pas détaillés dans ce rapport.

### 1.4 Méthodologie et organisation du travail

L'équipe a fonctionné selon une répartition modulaire du travail, chaque stagiaire disposant d'un périmètre fonctionnel propre tout en partageant un socle commun (`app/core/`, `app/config/`, base de données unique) versionné dans un dépôt Git unique. Le développement s'est déroulé en local sous **XAMPP**, avec des commits réguliers poussés sur **GitHub**, la mise en production étant assurée par un déploiement automatique vers l'hébergement mutualisé **OVH**. En tant que responsable du module de sécurité, nous avons également pris en charge la définition des règles `.gitignore` du projet et le suivi du comportement du déploiement automatique, notamment ses limites en matière de gestion des secrets — un point détaillé au chapitre 4.

### 1.5 Conclusion

Ce premier chapitre a permis de situer le projet dans son contexte organisationnel et d'en délimiter le périmètre. Le chapitre suivant détaille l'analyse des besoins fonctionnels et non fonctionnels propres au module de sécurité, de comptes et de médiathèque.

<div style="page-break-after: always;"></div>

## Chapitre 2 — Analyse et spécification des besoins

### 2.1 Étude de l'existant

En l'absence de système préexistant, l'étude de l'existant s'est appuyée sur les recommandations de sécurité applicative usuelles pour les applications PHP (guides OWASP), et sur l'observation du fonctionnement de l'hébergement mutualisé OVH retenu pour la production, notamment ses contraintes propres (absence d'accès à un tableau de bord de variables d'environnement pour un hébergement mutualisé standard, déploiement par simple récupération Git sans étape de build ni de secrets managés).

### 2.2 Recueil des besoins

Le recueil des besoins a permis d'identifier deux niveaux de droits suffisants pour couvrir les usages réels de l'équipe de la SEPJ Gabès : un rôle **éditeur**, limité à la gestion du contenu, et un rôle **administrateur**, disposant en plus de la gestion des comptes et des réglages sensibles (SMTP, paramètres du site). Il a également été établi que le dépôt de fichiers (images) devait rester ouvert à toute personne authentifiée, sans jamais permettre le dépôt d'un script exécutable, même déguisé en image.

### 2.3 Besoins fonctionnels

| Réf. | Besoin fonctionnel |
|---|---|
| BF-01 | Permettre à un utilisateur de se connecter par e-mail et mot de passe, avec message d'erreur générique ne révélant pas si l'e-mail existe. |
| BF-02 | Bloquer temporairement les tentatives de connexion après cinq échecs consécutifs. |
| BF-03 | Restreindre l'accès à certaines pages du back-office au seul rôle administrateur (gestion des comptes, réglages). |
| BF-04 | Protéger tous les formulaires d'administration contre les attaques CSRF. |
| BF-05 | Permettre à un administrateur de créer, modifier, désactiver ou supprimer un compte utilisateur, sans pouvoir se supprimer lui-même par erreur. |
| BF-06 | Permettre le dépôt d'une image (JPG, PNG, WebP) dans la médiathèque, avec vérification de son type réel et non de sa seule extension déclarée. |
| BF-07 | Bloquer explicitement le dépôt de tout fichier exécutable ou interprétable par le serveur (PHP, scripts, etc.), quelle que soit son extension déclarée. |
| BF-08 | Permettre la recherche, la pagination et la suppression d'un média dans la médiathèque. |
| BF-09 | Journaliser chaque action sensible (connexion, création, modification, suppression) avec l'utilisateur, l'action, l'entité concernée et l'adresse IP. |
| BF-10 | Afficher un tableau de bord synthétique (statistiques de contenu, messages récents) à la connexion. |
| BF-11 | Garantir que les identifiants réels de connexion à la base de données et au serveur SMTP ne soient jamais présents dans le dépôt de code source. |

### 2.4 Besoins non fonctionnels

- **Sécurité par défaut** : toute page d'administration doit exiger une authentification valide avant tout traitement, et vérifier le rôle requis avant toute action sensible.
- **Défense en profondeur** : la sécurité des uploads ne doit pas reposer sur un seul contrôle (extension déclarée) mais sur plusieurs contrôles indépendants (type MIME réel, liste blanche d'extensions, interdiction d'exécution au niveau du serveur web).
- **Traçabilité** : toute action sensible doit pouvoir être retracée a posteriori (qui, quoi, quand, depuis quelle adresse IP).
- **Portabilité de la configuration** : l'application doit pouvoir fonctionner immédiatement sur un poste de développement local avec des valeurs par défaut sûres, sans qu'aucun secret réel n'ait besoin d'être partagé entre les membres de l'équipe.
- **Continuité de service en production** : un déploiement Git ne doit jamais pouvoir écraser ou compromettre la configuration réelle du serveur de production.

### 2.5 Acteurs et cas d'utilisation

Deux acteurs interagissent avec ce module :

- **L'administrateur** : gère les comptes utilisateurs, consulte le journal d'audit, configure les paramètres sensibles.
- **L'éditeur** : se connecte, dépose des images dans la médiathèque, consulte le tableau de bord — sans accès à la gestion des comptes ni aux réglages.

```
┌──────────────────────────────────────────────────────────┐
│                     Cas d'utilisation                     │
│                                                            │
│   Éditeur ───── Se connecter / se déconnecter              │
│          ╲───── Déposer une image dans la médiathèque      │
│           ╲──── Consulter le tableau de bord                │
│                                                            │
│   Administrateur ── Gérer les comptes utilisateurs          │
│               ╲──── Consulter le journal d'audit             │
│                ╲─── Configurer les réglages sensibles        │
└──────────────────────────────────────────────────────────┘
```
*Figure 2.1 — Cas d'utilisation simplifiés du module Sécurité, Comptes & Médiathèque.*

### 2.6 Conclusion

L'analyse menée dans ce chapitre a permis de dégager un besoin central : garantir que seules les personnes autorisées agissent sur le système, que tout fichier déposé soit inoffensif pour le serveur, et que les secrets de production ne dépendent jamais du contenu du dépôt de code. Le chapitre suivant présente la conception retenue pour répondre à ces besoins.

<div style="page-break-after: always;"></div>

## Chapitre 3 — Conception

### 3.1 Architecture générale de l'application

Notre module constitue le socle transverse sur lequel s'appuient les deux autres modules de l'application :

```
┌───────────────────────────────────────────────────┐
│  Couche présentation                                │
│  admin/login.php, dashboard.php, users/, media/     │
├───────────────────────────────────────────────────┤
│  Couche logique métier / utilitaires                │
│  app/core/auth.php    (sessions, rôles)             │
│  app/core/csrf.php    (jetons anti-CSRF)            │
│  app/core/upload.php  (upload sécurisé)             │
│  app/config/database.php (config + secrets)         │
├───────────────────────────────────────────────────┤
│  Couche données                                     │
│  users, audit_logs, media                           │
└───────────────────────────────────────────────────┘
```
*Figure 3.1 — Architecture en couches du module Sécurité, Comptes & Médiathèque, socle des deux autres modules.*

### 3.2 Conception de la base de données

**Table `users` :**

| Colonne | Type | Rôle |
|---|---|---|
| `id` | INT, PK | Identifiant unique |
| `name` / `email` | VARCHAR | Identité, e-mail unique |
| `password_hash` | VARCHAR | Mot de passe haché (algorithme adaptatif) |
| `role` | ENUM | `admin` / `editor` |
| `status` | ENUM | `active` / `inactive` |

**Table `audit_logs` :**

| Colonne | Type | Rôle |
|---|---|---|
| `user_id` | INT, FK → `users` | Auteur de l'action |
| `action` | VARCHAR | Nature de l'action (login, create, update, delete…) |
| `entity_type` / `entity_id` | VARCHAR / INT | Élément concerné |
| `ip_address` | VARCHAR | Adresse IP source |
| `created_at` | DATETIME | Horodatage |

**Table `media` :** stocke le chemin, le type de fichier, les légendes multilingues et l'ordre d'affichage de chaque image, avec une clé étrangère optionnelle vers `content_items` (module de Membre A).

### 3.3 Conception de l'authentification et du contrôle d'accès

```
┌─────────────────────────────────────────────────────────┐
│  login(email, password)                                   │
│                                                            │
│  1. Vérifier le nombre de tentatives récentes en session   │
│     → blocage temporaire si ≥ 5 échecs en moins de 5 min   │
│  2. Rechercher l'utilisateur actif correspondant à l'e-mail │
│  3. Vérifier le mot de passe (password_verify)              │
│  4. Si succès : régénérer l'identifiant de session,         │
│     réinitialiser le compteur d'échecs, journaliser          │
│  5. Si échec : incrémenter le compteur, message générique    │
│     identique dans les deux cas (ne révèle jamais si          │
│     l'e-mail existe en base)                                 │
└─────────────────────────────────────────────────────────┘
```
*Figure 3.2 — Logique d'authentification.*

Le contrôle d'accès repose sur deux fonctions composables : `require_login()`, qui exige une session valide, et `require_role($roles)`, qui exige en plus un rôle précis — chaque page sensible du back-office déclare explicitement le niveau de droit requis dès sa première ligne exécutable.

### 3.4 Conception de la sécurité des dépôts de fichiers

```
┌─────────────────────────────────────────────────────────┐
│  upload_file(file, subdirectory)                           │
│                                                            │
│  1. Vérifier l'absence d'erreur d'upload PHP               │
│  2. Vérifier la taille du fichier                           │
│  3. Détecter le type MIME réel (finfo) — pas l'extension    │
│     déclarée par le navigateur                              │
│  4. Vérifier l'extension sur liste blanche                  │
│  5. Rejeter explicitement les extensions dangereuses         │
│     (.php, .phtml, .exe, .js, .svg, .html…)                  │
│  6. Générer un nom de fichier imprévisible                   │
│  7. Déposer un .htaccess interdisant l'exécution de          │
│     scripts dans le dossier de destination                   │
└─────────────────────────────────────────────────────────┘
```
*Figure 3.3 — Défense en profondeur du moteur d'upload : cinq contrôles indépendants avant qu'un fichier ne soit accepté.*

### 3.5 Conclusion

La conception retenue applique le principe de défense en profondeur à chaque niveau sensible de l'application : plusieurs contrôles indépendants pour les fichiers déposés, une séparation stricte entre secrets de production et code versionné, et une traçabilité systématique des actions. Le chapitre suivant détaille la réalisation concrète de ces choix, ainsi qu'un incident de production directement lié à cette séparation secrets/code.

<div style="page-break-after: always;"></div>

## Chapitre 4 — Réalisation

### 4.1 Environnement de travail

| Catégorie | Outil |
|---|---|
| Environnement local | XAMPP (Apache 2.4, PHP 8, MySQL/MariaDB) sous Windows |
| Éditeur de code | Visual Studio Code |
| Gestion de version | Git, dépôt distant GitHub |
| Inspection des sessions/cookies | Outils de développement du navigateur |
| Administration base de données | phpMyAdmin |
| Hébergement de production | OVH (mutualisé), déploiement par récupération automatique des commits GitHub |

### 4.2 Technologies et outils utilisés

L'implémentation repose sur les mécanismes natifs de session de **PHP 8** (cookies `HttpOnly`/`SameSite`/`Secure`, régénération d'identifiant), sur `password_hash()`/`password_verify()` pour le stockage des mots de passe, sur `finfo` pour la détection réelle du type des fichiers déposés, et sur **PDO** pour l'ensemble des accès à la base MySQL/MariaDB. Aucune bibliothèque tierce n'a été nécessaire pour ce module, l'ensemble reposant sur les fonctions de sécurité natives de PHP, volontairement préférées à une dépendance externe pour un périmètre aussi sensible.

### 4.3 Réalisation détaillée du module

**a) Démarrage sécurisé de session**

```php
function session_start_secure(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME, 'path' => '/', 'domain' => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
    if (!isset($_SESSION['_last_regenerated'])) {
        $_SESSION['_last_regenerated'] = time();
    } elseif (time() - $_SESSION['_last_regenerated'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regenerated'] = time();
    }
}
```
*Figure 4.1 — Extrait de `app/core/auth.php` : régénération périodique de l'identifiant de session contre la fixation de session.*

**b) Upload sécurisé — détection du type réel**

```php
$allowedMimes = unserialize(ALLOWED_MIME_TYPES);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimes)) {
    return ['success' => false, 'message' => 'Type de fichier non autorisé.'];
}
```
*Figure 4.2 — Extrait de `app/core/upload.php` : le type du fichier est déterminé à partir de son contenu réel, pas de son nom.*

**c) Contrôle d'accès par rôle**

```php
function require_role($roles): void
{
    require_login();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['user_role'], $roles)) {
        set_flash('error', 'Vous n\'avez pas la permission.');
        redirect(ADMIN_URL . '/dashboard.php');
    }
}
```
*Figure 4.3 — Extrait de `app/core/auth.php`.*

### 4.4 Difficultés rencontrées et démarche de résolution

La difficulté la plus significative rencontrée sur ce module est apparue **peu après une mise en production** : la connexion à la base de données a soudainement cessé de fonctionner sur le serveur de production, alors que tout fonctionnait normalement en local sous XAMPP, sans qu'aucun message d'erreur exploitable ne soit visible côté visiteur — l'application affichait une erreur générique, par sécurité.

La démarche de diagnostic a suivi les étapes suivantes :

1. **Isolement du risque de divulgation** : impossible d'afficher une erreur détaillée directement sur une page publique sans risquer d'exposer des informations sensibles à n'importe quel visiteur. Un script de diagnostic **temporaire**, volontairement séparé du reste de l'application, a donc été créé, affichant uniquement des informations non sensibles (hôte, port, nom de base, utilisateur, extensions PHP chargées) ainsi que le message d'erreur PDO exact — sans jamais afficher le mot de passe.
2. **Analyse du message obtenu** : l'erreur retournée a permis d'établir que la tentative de connexion utilisait des paramètres qui ne correspondaient pas à ceux attendus par le serveur de base de données de production.
3. **Recherche de la cause racine** : l'examen du fichier de configuration de la base de données a révélé qu'il était, à l'époque, **entièrement suivi par Git** : toute divergence entre la configuration nécessaire en local (XAMPP) et celle nécessaire en production pouvait donc être silencieusement écrasée ou mal reportée au déploiement suivant, puisqu'un même fichier versionné ne peut raisonnablement porter deux jeux de valeurs différents sans risque d'erreur humaine.
4. **Correction structurelle plutôt que ponctuelle** : plutôt que de corriger uniquement la valeur fautive dans le fichier versionné — ce qui aurait laissé le problème latent pour toute future divergence entre environnements — nous avons restructuré la configuration en deux fichiers : un fichier versionné ne contenant que des valeurs par défaut sûres pour un poste XAMPP local, et un fichier local, exclu du dépôt via `.gitignore`, chargé en priorité s'il est présent et destiné à porter les véritables identifiants de production. Un fichier d'exemple documente la structure attendue de ce second fichier pour le reste de l'équipe.
5. **Suppression de l'outil de diagnostic** : une fois la cause confirmée et corrigée, le script de diagnostic temporaire a été retiré du serveur, conformément à la mention explicite laissée dans son en-tête (« à supprimer après utilisation »), pour ne pas laisser en production un point d'entrée exposant, même partiellement, des informations sur la configuration du serveur.

Cette expérience a constitué un apprentissage marquant : un incident de sécurité ou de disponibilité ne provient pas toujours d'une faille de code, mais parfois d'une organisation de la configuration elle-même — ici, l'absence de séparation claire entre ce qui doit être versionné (la structure, les valeurs par défaut de développement) et ce qui ne doit jamais l'être (les secrets réels de production). La correction structurelle retenue rend depuis la configuration de production stable dans le temps et totalement indépendante du contenu du dépôt Git.

### 4.5 Tests et validation

| Scénario testé | Résultat attendu |
|---|---|
| Connexion avec identifiants valides | Session ouverte, identifiant de session régénéré, action journalisée |
| Cinq tentatives de connexion échouées consécutives | Blocage temporaire, message d'attente affiché |
| Accès à une page réservée à l'administrateur avec un compte éditeur | Redirection vers le tableau de bord, message d'erreur de permission |
| Dépôt d'un fichier `.php` renommé en `.jpg` | Rejet basé sur le type MIME réel détecté, quelle que soit l'extension |
| Dépôt d'une image valide (JPEG/PNG/WebP) | Acceptation, nom de fichier régénéré, `.htaccess` de sécurité créé si absent |
| Tentative de suppression de son propre compte administrateur | Action bloquée par l'application |
| Soumission d'un formulaire d'administration sans jeton CSRF valide | Rejet immédiat de la requête |
| Rejeu du script de vérification d'environnement (`install/check.php`) sur un nouveau poste | Détection correcte des extensions PHP manquantes et des droits d'écriture insuffisants |

### 4.6 Conclusion

Ce chapitre a détaillé la réalisation du module de sécurité, de comptes et de médiathèque, ainsi que le diagnostic et la correction structurelle d'un incident de production lié à la gestion des secrets de configuration. Les tests menés confirment que le module répond aux besoins fonctionnels et non fonctionnels identifiés au chapitre 2, et constitue un socle fiable pour les deux autres modules de l'application.

<div style="page-break-after: always;"></div>

## Conclusion générale

Ce stage nous a permis de concevoir et de réaliser, au sein d'une équipe de trois stagiaires, le module transverse de sécurité, de comptes administrateurs et de médiathèque d'une plateforme complète pour la SEPJ Gabès. Ce module, bien qu'invisible pour le visiteur final du site, conditionne directement le bon fonctionnement des deux autres modules de l'application, ce qui a imposé une exigence de fiabilité et de rigueur particulière tout au long du stage.

Sur le plan des **compétences techniques**, ce stage a permis d'approfondir la sécurisation de sessions PHP (fixation de session, cookies sécurisés), le hachage de mots de passe et le contrôle d'accès par rôle, la protection CSRF, la sécurisation d'uploads de fichiers par défense en profondeur, la conception d'un journal d'audit, ainsi que la séparation entre configuration versionnée et secrets pour un déploiement Git sans intégration continue. Sur le plan des **compétences transversales**, il a fallu faire preuve d'une culture de la sécurité par défaut, de rigueur dans la gestion des secrets, de sang-froid face à un incident de type « production en panne », et de capacité à documenter une solution pour le reste de l'équipe.

Comme perspectives d'amélioration, plusieurs pistes pourraient être envisagées : l'ajout d'une authentification à deux facteurs pour les comptes administrateurs, la mise en place d'une expiration automatique des mots de passe, l'externalisation de la gestion des secrets vers un gestionnaire dédié si l'hébergement venait à évoluer vers une offre plus avancée, ou encore l'ajout d'alertes automatiques en cas d'activité suspecte détectée dans le journal d'audit.

<div style="page-break-after: always;"></div>

## Bibliographie et Netographie

- Documentation officielle PHP — *Sessions, password_hash, finfo* : php.net
- OWASP — *Session Management Cheat Sheet* : owasp.org
- OWASP — *File Upload Cheat Sheet* : owasp.org
- OWASP — *Cross-Site Request Forgery Prevention Cheat Sheet* : owasp.org
- Documentation Apache — *Fichiers .htaccess, mod_authz_core* : httpd.apache.org
- Documentation officielle MySQL / MariaDB : dev.mysql.com
- Documentation Git — *gitignore* : git-scm.com

## Annexes

- Annexe A — Schéma complet des tables `users`, `audit_logs`, `media` (voir `database/schema.sql`)
- Annexe B — Extrait du fichier `database.local.php.example` documentant la structure attendue des secrets de production
- Annexe C — Captures d'écran de l'écran de connexion et du tableau de bord *(à insérer par l'étudiant)*
- Annexe D — Captures d'écran de la médiathèque et de la gestion des comptes utilisateurs *(à insérer par l'étudiant)*

---
*Document rédigé dans le cadre du rapport de stage de fin d'études — SEPJ Gabès. Les mentions entre crochets [ ] sont des emplacements à personnaliser avant impression ou soutenance.*
