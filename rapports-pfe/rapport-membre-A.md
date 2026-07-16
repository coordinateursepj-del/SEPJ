---
---

# [Nom de l'Établissement / Institut Supérieur]
### [Département — ex : Département Informatique]

<br><br>

# RAPPORT DE STAGE DE FIN D'ÉTUDES

## Développement d'une plateforme web institutionnelle trilingue —
## Module Contenu Multilingue, Vitrine Publique & Reporting RSE

<br>

**Élaboré par :** [Nom et prénom de l'étudiant — Membre A]

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

Mes remerciements s'adressent tout particulièrement à **[Nom de l'encadrant professionnel]**, mon encadrant au sein de l'entreprise, pour ses conseils avisés, sa patience et le temps qu'il/elle m'a consacré, notamment lors de la définition de la taxonomie de contenu et de la validation fonctionnelle des pages publiques du site.

Je remercie également **[Nom de l'encadrant académique]**, mon encadrant académique, pour son suivi rigoureux, ses remarques constructives et sa disponibilité tout au long de ce projet.

Enfin, je remercie les membres du jury d'avoir accepté d'évaluer ce travail, ainsi que mes collègues de stage, **[Membre B]** et **[Membre C]**, avec qui la collaboration sur les parties transverses de l'application (authentification, base de données commune, déploiement) a été précieuse.

<div style="page-break-after: always;"></div>

## Résumé

Ce rapport présente le travail réalisé dans le cadre d'un stage de fin d'études au sein de la SEPJ Gabès (Société d'Environnement, Plantation et Jardinage de Gabès), établissement chargé de missions d'aménagement paysager et de développement durable dans le gouvernorat de Gabès. Le projet global consistait à concevoir une plateforme web institutionnelle trilingue (arabe, français, anglais), doublée d'un back-office de gestion de contenu. Notre contribution, au sein d'une équipe de trois stagiaires, a porté sur le **module de gestion et de diffusion du contenu multilingue** : l'ensemble des pages publiques du site vitrine, un système de traduction assistée s'appuyant sur un service externe, et la section dédiée à la Responsabilité Sociétale et Environnementale (RSE) de l'entreprise. Ce document détaille l'analyse des besoins, la conception retenue, les choix d'implémentation en PHP 8 natif et MySQL, ainsi que les difficultés techniques rencontrées — en particulier autour de la fiabilité d'un appel à une API de traduction externe en environnement de développement local — et la démarche méthodique employée pour les résoudre.

**Mots-clés :** PHP, MySQL, internationalisation, multilinguisme, RTL, traduction automatique, RSE, CMS.

## Abstract

This report presents the work carried out during an end-of-studies internship at SEPJ Gabès (Environment, Plantation and Gardening Company of Gabès), a public organisation in charge of landscaping and sustainable-development missions in the Gabès region. The overall project consisted in designing a trilingual institutional web platform (Arabic, French, English) backed by a content-management back-office. Within a team of three interns, our contribution focused on the **multilingual content module**: the public-facing pages of the showcase site, a machine-assisted translation pipeline built on an external service, and the section dedicated to the company's Corporate Social Responsibility (CSR) reporting. This document details the requirements analysis, the retained design, the native PHP 8 / MySQL implementation choices, and the technical difficulties encountered — notably around the reliability of a call to an external translation API in a local development environment — along with the systematic approach used to resolve them.

**Keywords:** PHP, MySQL, internationalization, multilingualism, RTL, machine translation, CSR, CMS.

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
| CMS | Content Management System (système de gestion de contenu) |
| i18n | Internationalisation |
| RTL / LTR | Right-to-Left / Left-to-Right (sens d'écriture) |
| RSE | Responsabilité Sociétale et Environnementale |
| API | Application Programming Interface |
| CRUD | Create, Read, Update, Delete |
| SGBD | Système de Gestion de Base de Données |
| CDN | Content Delivery Network |
| CA (bundle) | Certificate Authority (autorité de certification) |
| CSRF | Cross-Site Request Forgery |
| XAMPP | X (multi-OS) Apache MySQL PHP Perl |

<div style="page-break-after: always;"></div>

## Introduction générale

Dans un contexte où la présence numérique est devenue un vecteur essentiel de communication institutionnelle, la SEPJ Gabès a souhaité se doter d'une plateforme web moderne, capable de présenter ses missions, ses projets et son engagement environnemental à un public tunisien majoritairement arabophone, tout en restant accessible à des partenaires francophones et anglophones. C'est dans ce cadre que s'inscrit notre stage de fin d'études, réalisé au sein d'une équipe de trois stagiaires, chacun en charge d'un module fonctionnel distinct de l'application.

Le module qui nous a été confié est celui du **contenu institutionnel multilingue** : il s'agit à la fois de la partie la plus visible du site — puisqu'elle constitue la quasi-totalité des pages consultées par un visiteur — et de la partie qui porte la contrainte la plus structurante du projet, le trilinguisme arabe/français/anglais avec inversion complète du sens de lecture en arabe. Ce module inclut également la section consacrée à la Responsabilité Sociétale et Environnementale, qui occupe une place particulière puisqu'elle constitue, pour une entreprise dont la mission même est environnementale, un espace de communication stratégique.

Ce rapport est organisé en quatre chapitres. Le premier chapitre présente l'organisme d'accueil, le contexte du projet global et la méthodologie de travail adoptée par l'équipe. Le deuxième chapitre développe l'analyse des besoins fonctionnels et non fonctionnels propres à notre module. Le troisième chapitre expose les choix de conception, tant au niveau de l'architecture générale de l'application qu'au niveau du modèle de données et de la logique de traduction assistée. Le quatrième chapitre détaille la réalisation technique, les principales difficultés rencontrées — en particulier la fiabilisation de l'appel à un service de traduction externe — ainsi que la démarche de test adoptée. Une conclusion générale clôt ce rapport en dressant un bilan des compétences acquises et des perspectives d'amélioration.

<div style="page-break-after: always;"></div>

## Chapitre 1 — Présentation de l'organisme d'accueil et cadre du projet

### 1.1 Présentation de la SEPJ Gabès

La Société d'Environnement, Plantation et Jardinage de Gabès (شركة البيئة والغراسة والبستنة بقابس), désignée dans ce document par l'acronyme **SEPJ Gabès**, est un établissement dont la mission couvre l'aménagement paysager, le reboisement et la mise en œuvre d'actions de développement durable à l'échelle du gouvernorat de Gabès. Son organisation interne reflète l'étendue géographique de ses missions : elle comprend une direction générale, une dizaine d'agences réparties sur les délégations du gouvernorat (Gabès Ville, Gabès Sud, Gabès Ouest, Ghannouch, Mareth, Métouia, Matmata Ancienne et Nouvelle, Ben Ghilouf, Manzel Habib, Hamma, Zerkine), ainsi qu'une quinzaine de directions fonctionnelles transverses (Ressources Humaines, Financier, Juridique, Technique, Achats, Contrôle, Informatique, Archives, Bureau d'Ordre Central, Coordination, RSE, Social).

Cette structuration en de multiples entités, chacune dotée de sa propre adresse de contact, s'est révélée être une donnée d'entrée directement exploitée dans la conception applicative du projet — notamment pour le module de messagerie développé par un autre membre de l'équipe — et témoigne de la complexité organisationnelle que la plateforme devait être capable de représenter fidèlement.

### 1.2 Contexte et problématique du projet

Avant ce projet, la communication institutionnelle de la SEPJ Gabès reposait sur des supports disparates, sans vitrine web centralisée capable de présenter de façon structurée ses activités, ses projets, ses actualités et surtout sa démarche de Responsabilité Sociétale et Environnementale — pourtant centrale pour une entreprise dont l'objet social est directement lié à l'environnement. L'absence d'un tel outil limitait la visibilité de l'entreprise auprès du grand public, des administrés et des partenaires institutionnels, et ne permettait pas de valoriser les rapports de durabilité et catalogues RSE produits par l'entreprise.

La problématique confiée à l'équipe peut donc se formuler ainsi : *comment concevoir une plateforme web qui centralise l'ensemble de la communication institutionnelle de la SEPJ Gabès, dans les trois langues d'usage de ses publics, tout en donnant au personnel non technique de l'entreprise les moyens de la maintenir de façon autonome ?*

En ce qui concerne plus spécifiquement notre module, la question se précise : *comment structurer et afficher un contenu institutionnel dans trois langues sans multiplier le travail de saisie par trois, et comment organiser la communication RSE de l'entreprise de façon lisible pour un visiteur ?*

### 1.3 Objectifs du stage et périmètre confié

Le périmètre confié à notre module a couvert :

- l'ensemble des pages publiques de contenu du site (accueil, à propos, projets, services, activités, actualités, distinctions, sport, ressources, galerie photo, galerie vidéo) ;
- le moteur de recherche interne au site ;
- la section RSE, organisée en cinq catégories (engagement social, engagement sociétal et environnemental, rapport RSE, catalogue RSE, rapport de durabilité) ;
- côté back-office, le module d'administration du contenu (création, modification, publication, suppression) pour l'ensemble des types de contenu ci-dessus ;
- un système de traduction assistée permettant de compléter automatiquement les champs non renseignés dans une langue à partir d'une autre.

Les modules d'authentification/sécurité et de messagerie/contact, bien que consommés indirectement par notre module (un article publié utilise par exemple le même système de connexion et peut contenir une pièce jointe passant par le même moteur d'upload), ont été pris en charge par les deux autres membres de l'équipe et ne sont pas détaillés dans ce rapport.

### 1.4 Méthodologie et organisation du travail

L'équipe a fonctionné selon une répartition modulaire du travail : chaque stagiaire disposait d'un périmètre fonctionnel propre (contenu/RSE, contact/messagerie, sécurité/comptes), tout en partageant un socle commun (`app/core/`, `app/config/`, base de données unique) versionné dans un unique dépôt Git. Le développement s'est déroulé en local sous environnement **XAMPP** (Apache, MySQL, PHP), avec des commits réguliers poussés sur un dépôt **GitHub** partagé, la mise en production étant assurée par un déploiement automatique du dépôt vers l'hébergement mutualisé **OVH**. Les évolutions du schéma de base de données ont été propagées sous forme de scripts SQL de migration versionnés, plutôt que par modification manuelle de chaque base locale, afin que les trois membres de l'équipe travaillent en permanence sur un schéma cohérent.

### 1.5 Conclusion

Ce premier chapitre a permis de situer le projet dans son contexte organisationnel et d'en délimiter le périmètre. Le chapitre suivant détaille l'analyse des besoins fonctionnels et non fonctionnels propres au module de contenu multilingue et de reporting RSE.

<div style="page-break-after: always;"></div>

## Chapitre 2 — Analyse et spécification des besoins

### 2.1 Étude de l'existant

En l'absence de plateforme préexistante, l'étude de l'existant s'est appuyée sur les supports de communication déjà produits par la SEPJ Gabès (rapports de durabilité, catalogues RSE, documents de présentation institutionnelle) ainsi que sur des sites vitrines d'organismes publics tunisiens comparables, afin d'identifier les rubriques attendues par un visiteur (présentation, actualités, projets, contact) et les spécificités propres à une entreprise à mission environnementale (mise en avant de la démarche RSE, des chiffres clés — arbres plantés, hectares transformés).

### 2.2 Recueil des besoins

Le recueil des besoins a été mené conjointement avec l'encadrant professionnel et, pour la partie RSE, avec les personnes en charge de la communication institutionnelle au sein de la SEPJ, afin de définir une taxonomie de contenu pertinente. Il en est ressorti que la communication RSE de l'entreprise ne pouvait pas être traitée comme une simple catégorie d'actualités : elle nécessitait une structuration propre en cinq axes (engagement social, engagement sociétal et environnemental, rapport RSE, catalogue RSE, rapport de durabilité), chacun affichable indépendamment et regroupé au sein d'une page de synthèse.

### 2.3 Besoins fonctionnels

| Réf. | Besoin fonctionnel |
|---|---|
| BF-01 | Afficher chaque page publique dans la langue choisie par le visiteur (arabe, français, anglais), avec repli automatique vers l'arabe si une traduction est absente. |
| BF-02 | Permettre le changement de langue depuis n'importe quelle page, en conservant la page courante et les paramètres de recherche. |
| BF-03 | Adapter la mise en page (sens d'écriture, alignement) selon que la langue active est l'arabe (RTL) ou le français/anglais (LTR). |
| BF-04 | Permettre à un éditeur de créer, modifier, publier/dépublier et supprimer un contenu (article, projet, service, activité, distinction, ressource, élément RSE) depuis le back-office. |
| BF-05 | Générer automatiquement un identifiant d'URL (slug) à partir du titre saisi, tout en permettant sa modification manuelle. |
| BF-06 | Compléter automatiquement, à l'enregistrement, les champs de traduction laissés vides par l'éditeur, sans jamais écraser un texte déjà saisi. |
| BF-07 | Classer chaque élément de type RSE dans l'une des cinq catégories de la taxonomie RSE et l'afficher dans la section correspondante de la page RSE. |
| BF-08 | Permettre la recherche plein texte d'un contenu publié, tous types confondus, à partir d'un mot-clé. |
| BF-09 | Afficher une galerie photo et une galerie vidéo regroupant les médias associés aux contenus publiés. |
| BF-10 | Filtrer et paginer la liste des contenus dans le back-office (par type, statut, catégorie RSE, mot-clé). |

### 2.4 Besoins non fonctionnels

- **Performance** : les listes de contenu et les résultats de recherche doivent rester réactifs malgré la multiplication par trois des colonnes textuelles (une par langue) ; les requêtes critiques sont indexées (type, statut, slug, catégorie RSE).
- **Accessibilité** : respect des attributs `lang` et `dir` sur chaque page, régions `aria-live` pour les messages dynamiques, navigation clavier complète.
- **Robustesse** : une indisponibilité du service externe de traduction ne doit jamais empêcher la publication d'un contenu.
- **Maintenabilité** : toute évolution du schéma de base de données doit pouvoir être rejouée à l'identique sur les postes de travail de toute l'équipe (scripts de migration idempotents).
- **Cohérence linguistique** : un même identifiant de catégorie RSE doit être libellé de façon cohérente dans les trois langues, aussi bien côté public que côté back-office.

### 2.5 Acteurs et cas d'utilisation

Deux acteurs interagissent avec ce module :

- **Le visiteur du site** (non authentifié) : consulte les pages publiques, change de langue, effectue une recherche, parcourt la galerie et la section RSE.
- **L'éditeur / administrateur** (authentifié, module transverse pris en charge par Membre C) : crée, modifie, publie et supprime les contenus ; déclenche ou non la traduction automatique ; classe les éléments RSE.

```
┌──────────────────────────────────────────────────────────┐
│                     Cas d'utilisation                     │
│                                                            │
│   Visiteur ──── Consulter une page de contenu             │
│           ╲──── Changer de langue                         │
│            ╲─── Rechercher un contenu                     │
│             ╲── Parcourir la galerie / la section RSE     │
│                                                            │
│   Éditeur ───── Créer / modifier un contenu               │
│          ╲───── Publier / dépublier un contenu            │
│           ╲──── Classer un élément RSE par catégorie      │
│            ╲─── Déclencher la traduction automatique      │
└──────────────────────────────────────────────────────────┘
```
*Figure 2.1 — Cas d'utilisation simplifiés du module Contenu Multilingue & RSE.*

### 2.6 Conclusion

L'analyse menée dans ce chapitre a permis de dégager un besoin central : afficher et gérer un contenu institutionnel cohérent dans trois langues, avec une section RSE structurée par taxonomie, sans multiplier la charge de saisie. Le chapitre suivant présente la conception retenue pour répondre à ces besoins.

<div style="page-break-after: always;"></div>

## Chapitre 3 — Conception

### 3.1 Architecture générale de l'application

L'application repose sur une architecture en couches simple, sans framework, organisée en trois répertoires principaux :

```
┌───────────────────────────────────────────────┐
│  Couche présentation                            │
│  public/   (vitrine)     admin/  (back-office)  │
├───────────────────────────────────────────────┤
│  Couche logique métier / utilitaires            │
│  app/core/   (i18n, traduction, upload, auth…)  │
│  app/config/ (paramètres applicatifs)           │
├───────────────────────────────────────────────┤
│  Couche données                                 │
│  MySQL / MariaDB (PDO)                          │
└───────────────────────────────────────────────┘
```
*Figure 3.1 — Architecture en couches de l'application.*

Notre module s'appuie principalement sur `app/core/i18n.php` (dictionnaire de traduction de l'interface), `app/core/translation_service.php` (traduction assistée du contenu) et sur les scripts de `admin/content/` pour la gestion du contenu, tout en réutilisant le noyau d'authentification et le moteur d'upload conçus par Membre C.

### 3.2 Conception de la base de données

Le modèle retenu pour le contenu multilingue privilégie des **colonnes parallèles par langue** (`title_ar`, `title_fr`, `title_en`, etc.) au sein d'une unique table `content_items`, plutôt qu'un modèle EAV (Entity-Attribute-Value) plus flexible mais plus coûteux à requêter. Ce choix a été fait consciemment : il simplifie considérablement les requêtes d'affichage (`COALESCE(NULLIF(title_fr,''), title_ar)`) au prix d'une légère redondance de schéma, jugée acceptable pour trois langues fixes et connues à l'avance.

**Table `content_items` (extrait) :**

| Colonne | Type | Rôle |
|---|---|---|
| `id` | INT, PK | Identifiant unique |
| `type` | ENUM | Nature du contenu (post, project, service, activity, prize, rse, resource, sport, video…) |
| `rse_category` | VARCHAR(50) | Sous-catégorie RSE, uniquement pour `type = 'rse'` |
| `slug` | VARCHAR(255), UNIQUE | Identifiant d'URL |
| `title_ar / title_fr / title_en` | TEXT | Titre par langue |
| `summary_ar / summary_fr / summary_en` | TEXT | Résumé par langue |
| `body_ar / body_fr / body_en` | LONGTEXT | Corps de l'article par langue (HTML) |
| `featured_image` | VARCHAR(255) | Image mise en avant |
| `status` | ENUM | `draft` / `published` |
| `is_featured` | TINYINT(1) | Mise en avant sur la page d'accueil |
| `created_by / updated_by` | INT, FK → `users` | Traçabilité (module de Membre C) |

La colonne `rse_category` a été ajoutée après la conception initiale du schéma, via un script de migration conditionnel (vérification de son existence dans `information_schema` avant l'`ALTER TABLE`), afin de pouvoir être rejoué sans erreur sur les bases déjà initialisées de chaque membre de l'équipe.

### 3.3 Conception du service de traduction assistée

Le service de traduction repose sur le principe suivant : ne jamais écraser un contenu existant, ne compléter que les champs vides, et déterminer automatiquement quelle langue sert de source.

```
┌─────────────────────────────────────────────────────────┐
│  fill_missing_translations(item, enabled)                 │
│                                                            │
│  1. detect_source_language(item)                          │
│     → score chaque langue selon le nombre de champs       │
│       renseignés (titre / résumé / corps)                 │
│     → retient la langue la mieux renseignée                │
│       (égalité : priorité ar → fr → en)                    │
│                                                            │
│  2. Pour chaque langue cible ≠ langue source :             │
│       Pour chaque champ (titre, résumé, corps) :           │
│         si le champ cible est déjà rempli → ignorer        │
│         sinon → appeler LibreTranslateService::translate() │
│                 et stocker le résultat                     │
│                                                            │
│  3. Retourne l'item complété + une liste d'avertissements  │
│     non bloquants en cas d'échec ponctuel de l'API          │
└─────────────────────────────────────────────────────────┘
```
*Figure 3.2 — Logique du service de traduction assistée.*

Ce service s'appuie sur **LibreTranslate**, une API de traduction automatique libre et gratuite, interrogée en HTTP/JSON via cURL, choisie pour ne dépendre d'aucune clé d'API payante — un critère important dans le contexte d'un établissement public à budget contraint.

### 3.4 Conception de la section RSE

La section RSE regroupe les contenus de type `rse` selon cinq catégories, chacune affichée dans une sous-section dédiée de la page publique, avec un compteur d'éléments et un lien vers une page dédiée pour les rapports et catalogues les plus volumineux (`sustainability-report.php`). Ce regroupement est effectué côté serveur par un simple tri des résultats de la requête sur `content_items` selon la valeur de `rse_category`, plutôt que par cinq requêtes séparées, pour limiter le nombre d'allers-retours vers la base de données.

### 3.5 Conclusion

La conception retenue privilégie systématiquement la simplicité et la robustesse : modèle de données dénormalisé mais rapide à requêter, service de traduction non bloquant, taxonomie RSE fixe mais extensible via migration. Le chapitre suivant détaille la réalisation concrète de ces choix.

<div style="page-break-after: always;"></div>

## Chapitre 4 — Réalisation

### 4.1 Environnement de travail

| Catégorie | Outil |
|---|---|
| Environnement local | XAMPP (Apache 2.4, PHP 8, MySQL/MariaDB) sous Windows |
| Éditeur de code | Visual Studio Code |
| Gestion de version | Git, dépôt distant GitHub |
| Administration base de données | phpMyAdmin |
| Test de l'API de traduction | Requêtes cURL en ligne de commande / client REST |
| Hébergement de production | OVH (mutualisé), déploiement par récupération automatique des commits GitHub |
| Navigateurs de test | Chrome, Firefox (rendu RTL vérifié spécifiquement) |

### 4.2 Technologies et outils utilisés

L'implémentation repose sur **PHP 8** natif (sans framework), l'accès aux données étant systématiquement réalisé via **PDO** avec requêtes préparées. La mise en forme combine **Tailwind CSS** (classes utilitaires) et une feuille de style maison définissant un système de jetons de couleur réutilisables. Le service de traduction consomme l'API **LibreTranslate** via **cURL**. Aucune bibliothèque JavaScript tierce n'a été utilisée pour l'interactivité des pages de contenu : les quelques comportements dynamiques (compteurs de caractères, onglets de langue dans le formulaire d'édition) sont écrits en JavaScript natif.

### 4.3 Réalisation détaillée du module

**a) Fonction d'internationalisation de l'interface**

L'ensemble des libellés fixes de l'interface (navigation, boutons, messages) transite par une fonction unique `__()`, qui va chercher la traduction correspondante dans un dictionnaire statique et retombe sur l'arabe si la langue demandée est absente :

```php
function __(string $key, string $lang = null): string
{
    static $translations = null;
    if ($translations === null) {
        $translations = get_translations();
    }
    $lang = $lang ?? current_lang();
    return $translations[$key][$lang] ?? $translations[$key]['ar'] ?? $key;
}
```
*Figure 4.1 — Extrait de `app/core/i18n.php`.*

**b) Détection de la langue source pour la traduction assistée**

```php
function detect_source_language(array $item): ?string
{
    $bestLang = null; $bestScore = 0;
    foreach (['ar', 'fr', 'en'] as $lang) {
        $score = 0;
        if (!is_blank($item['title_'.$lang]   ?? null)) $score++;
        if (!is_blank($item['summary_'.$lang] ?? null)) $score++;
        if (!is_blank($item['body_'.$lang]    ?? null)) $score++;
        if ($score > $bestScore) { $bestScore = $score; $bestLang = $lang; }
    }
    return $bestLang;
}
```
*Figure 4.2 — Extrait de `app/core/translation_service.php` : détermination de la langue la mieux renseignée, utilisée comme source de traduction.*

**c) Page RSE — regroupement par catégorie**

La page publique RSE interroge l'ensemble des éléments publiés de type `rse`, puis les répartit en mémoire dans cinq tableaux correspondant aux catégories de la taxonomie, avant de générer une sous-section par catégorie non vide — ce qui évite d'exécuter cinq requêtes SQL distinctes pour une seule page.

**d) Back-office — formulaire de contenu multilingue**

Le formulaire d'édition présente les champs `title`, `summary` et `body` sous forme d'onglets par langue (arabe par défaut, RTL), avec génération automatique du slug à partir du titre saisi, compteur de caractères sur le résumé, et prévisualisation de l'image mise en avant avant envoi.

### 4.4 Difficultés rencontrées et démarche de résolution

La principale difficulté technique rencontrée dans ce module a concerné la **fiabilité du service de traduction automatique en environnement de développement local**. Lors des premiers tests, chaque appel au service LibreTranslate échouait systématiquement, l'application retombant sur le texte d'origine sans traduction, avec une erreur cURL peu explicite dans les journaux serveur.

La démarche de résolution a suivi les étapes suivantes :

1. **Isolement du problème** : l'appel HTTP a été extrait du reste de l'application et testé indépendamment, en ligne de commande, afin d'écarter toute cause liée à la logique métier environnante.
2. **Journalisation détaillée** : l'activation de l'option de récupération d'erreur cURL (`curl_error()`) a permis d'obtenir un message précis relatif à l'échec de la vérification du certificat SSL du service distant.
3. **Recherche documentaire** : la consultation de la documentation PHP relative à cURL sous Windows a permis d'identifier que la distribution XAMPP ne fournit, par défaut, aucun fichier d'autorité de certification (CA bundle) reconnu par cURL — une limitation connue et documentée de cet environnement de développement, à ne pas confondre avec une faille de sécurité du serveur de production.
4. **Correction ciblée** : plutôt que de désactiver la vérification SSL de façon globale — ce qui aurait affaibli la sécurité de tous les échanges chiffrés de l'application, y compris ceux du module de messagerie — la désactivation a été strictement circonscrite au service de traduction, documentée en commentaire comme spécifique à l'environnement XAMPP/Windows, avec une note explicite indiquant qu'elle devait être retirée sur tout serveur de production disposant d'un CA bundle correctement configuré.
5. **Tolérance aux pannes** : au-delà de ce cas précis, la fonction de traduction a été conçue pour qu'une erreur réseau ou une réponse invalide de l'API n'interrompe jamais l'enregistrement de l'article : le texte d'origine est conservé, un avertissement non bloquant est journalisé et affiché à l'éditeur, mais la sauvegarde aboutit toujours.

Cette expérience a été particulièrement formatrice : elle a montré qu'une erreur technique en apparence obscure (échec cURL) provient souvent d'une cause environnementale bien documentée, et que la bonne réponse à une dépendance externe non fiable est de la rendre non bloquante plutôt que de chercher à la fiabiliser à 100 %.

### 4.5 Tests et validation

| Scénario testé | Résultat attendu |
|---|---|
| Création d'un article en arabe uniquement, traduction automatique activée | Les champs français et anglais sont complétés automatiquement à l'enregistrement |
| Création d'un article avec les trois langues déjà saisies | Aucun appel de traduction n'est déclenché, aucun champ n'est modifié |
| Coupure réseau simulée pendant l'enregistrement | L'article est tout de même enregistré, un avertissement non bloquant s'affiche |
| Changement de langue sur une page listant des articles partiellement traduits | Le titre arabe s'affiche en repli pour les articles non traduits |
| Affichage d'une page en arabe | Mise en page entièrement inversée (RTL), navigation restant LTR |
| Filtrage de la page RSE par catégorie inexistante en base | La section correspondante s'affiche vide avec un message « aucun résultat », sans erreur |
| Migration `rse_category` rejouée une seconde fois | Le script détecte la colonne existante et n'effectue aucune modification |

### 4.6 Conclusion

Ce chapitre a détaillé la réalisation du module de contenu multilingue et de reporting RSE, ainsi que la démarche méthodique suivie pour fiabiliser son point le plus fragile : l'appel à un service de traduction externe. Les tests menés confirment que le module répond aux besoins fonctionnels et non fonctionnels identifiés au chapitre 2.

<div style="page-break-after: always;"></div>

## Conclusion générale

Ce stage nous a permis de concevoir et de réaliser, au sein d'une équipe de trois stagiaires, le module de contenu multilingue et de reporting RSE d'une plateforme institutionnelle complète pour la SEPJ Gabès. Au-delà de l'aspect purement technique, ce travail a nécessité de traduire une exigence organisationnelle — communiquer de façon cohérente dans trois langues, dont une s'écrivant de droite à gauche — en un modèle de données et une logique applicative simples à maintenir par une équipe non technique.

Sur le plan des **compétences techniques**, ce stage a permis d'approfondir la manipulation de PHP 8 avec PDO, la modélisation de données multilingues en MySQL, la consommation d'une API HTTP tierce, ainsi que les bonnes pratiques de mise en page bidirectionnelle (RTL/LTR) et d'accessibilité web. Sur le plan des **compétences transversales**, il a fallu faire preuve de rigueur terminologique pour garantir la cohérence d'un contenu institutionnel dans trois langues, d'autonomie pour exploiter la documentation d'un service tiers, et de capacité de dialogue avec des parties prenantes non techniques pour définir une taxonomie RSE pertinente.

Comme perspectives d'amélioration, plusieurs pistes pourraient être envisagées : la mise en cache des traductions déjà obtenues pour réduire le nombre d'appels à l'API externe, l'ajout d'une revue humaine obligatoire des traductions automatiques avant publication, ou encore l'extension de la taxonomie RSE à des sous-catégories plus fines à mesure que le volume de contenu RSE de l'entreprise augmentera.

<div style="page-break-after: always;"></div>

## Bibliographie et Netographie

- Documentation officielle PHP — *PDO, cURL, finfo* : php.net
- Documentation officielle MySQL / MariaDB — *Data Types, Indexes* : dev.mysql.com
- Documentation Tailwind CSS : tailwindcss.com
- Documentation de l'API LibreTranslate : libretranslate.com
- Web Content Accessibility Guidelines (WCAG) — attributs `lang`/`dir`, régions live : w3.org/WAI
- Mozilla Developer Network (MDN) — Internationalisation, bidirectionnalité du texte : developer.mozilla.org

## Annexes

- Annexe A — Schéma complet de la table `content_items` (voir `database/schema.sql`)
- Annexe B — Script de migration `rse_category` (voir `database/migration_rse_category.sql`)
- Annexe C — Captures d'écran des pages publiques dans les trois langues *(à insérer par l'étudiant)*
- Annexe D — Captures d'écran du formulaire d'administration du contenu *(à insérer par l'étudiant)*

---
*Document rédigé dans le cadre du rapport de stage de fin d'études — SEPJ Gabès. Les mentions entre crochets [ ] sont des emplacements à personnaliser avant impression ou soutenance.*
