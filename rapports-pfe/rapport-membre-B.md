---
---

# [Nom de l'Établissement / Institut Supérieur]
### [Département — ex : Département Informatique]

<br><br>

# RAPPORT DE STAGE DE FIN D'ÉTUDES

## Développement d'une plateforme web institutionnelle trilingue —
## Module Communication Institutionnelle, Formulaires de Contact & Messagerie

<br>

**Élaboré par :** [Nom et prénom de l'étudiant — Membre B]

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

Mes remerciements s'adressent tout particulièrement à **[Nom de l'encadrant professionnel]**, mon encadrant au sein de l'entreprise, pour m'avoir aidé à comprendre l'organisation interne de la société — indispensable pour concevoir un système de routage des messages fidèle à la réalité des différents départements et agences.

Je remercie également **[Nom de l'encadrant académique]**, mon encadrant académique, pour son suivi rigoureux et ses remarques constructives tout au long de ce projet.

Enfin, je remercie les membres du jury d'avoir accepté d'évaluer ce travail, ainsi que mes collègues de stage, **[Membre A]** et **[Membre C]**, pour la collaboration sur les parties transverses de l'application partagées avec mon module (protection CSRF, base de données commune, déploiement).

<div style="page-break-after: always;"></div>

## Résumé

Ce rapport présente le travail réalisé dans le cadre d'un stage de fin d'études au sein de la SEPJ Gabès (Société d'Environnement, Plantation et Jardinage de Gabès). Le projet global consistait à concevoir une plateforme web institutionnelle trilingue, doublée d'un back-office de gestion de contenu. Notre contribution, au sein d'une équipe de trois stagiaires, a porté sur le **module de communication et de messagerie** : les formulaires de contact publics, le routage automatique de chaque message vers l'un des trente départements et agences de l'entreprise, l'envoi effectif des e-mails via une passerelle SMTP authentifiée, la protection contre les soumissions abusives, et l'espace d'administration permettant de consulter les messages reçus et de diagnostiquer d'éventuelles défaillances d'envoi. Ce document détaille l'analyse des besoins, la conception retenue, l'implémentation en PHP 8 et PHPMailer, ainsi qu'une défaillance réelle rencontrée en production — des e-mails silencieusement non délivrés malgré un enregistrement réussi en base — et la démarche systématique employée pour la diagnostiquer et la corriger durablement.

**Mots-clés :** PHP, MySQL, PHPMailer, SMTP, formulaire de contact, anti-spam, routage de messages.

## Abstract

This report presents the work carried out during an end-of-studies internship at SEPJ Gabès (Environment, Plantation and Gardening Company of Gabès). The overall project consisted in designing a trilingual institutional web platform backed by a content-management back-office. Within a team of three interns, our contribution focused on the **communication and messaging module**: the public contact forms, the automatic routing of each message to one of the company's thirty departments and regional offices, the actual e-mail delivery through an authenticated SMTP gateway, protection against abusive submissions, and the back-office area used to review incoming messages and diagnose delivery failures. This document details the requirements analysis, the retained design, the PHP 8 / PHPMailer implementation, and a real production incident — e-mails silently failing to deliver despite messages being correctly saved — along with the systematic approach used to diagnose and durably fix it.

**Keywords:** PHP, MySQL, PHPMailer, SMTP, contact form, anti-spam, message routing.

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
| SMTP | Simple Mail Transfer Protocol |
| TLS/SSL | Transport Layer Security / Secure Sockets Layer |
| SPF / DKIM | Sender Policy Framework / DomainKeys Identified Mail |
| CSRF | Cross-Site Request Forgery |
| CC | Copie Carbone (champ e-mail) |
| IP | Internet Protocol |
| SHA-256 | Secure Hash Algorithm, 256 bits |
| XAMPP | X (multi-OS) Apache MySQL PHP Perl |

<div style="page-break-after: always;"></div>

## Introduction générale

Toute plateforme institutionnelle a besoin d'un point de contact fiable entre ses visiteurs et l'organisation qu'elle représente. Dans le cas de la SEPJ Gabès, cette exigence est renforcée par la structure même de l'entreprise : une trentaine de départements et d'agences régionales, chacun susceptible d'être le destinataire légitime d'une demande, sans qu'un visiteur externe ne puisse raisonnablement connaître l'organigramme interne pour adresser son message au bon endroit. C'est dans ce cadre que s'inscrit notre stage de fin d'études, réalisé au sein d'une équipe de trois stagiaires, chacun en charge d'un module fonctionnel distinct de l'application.

Le module qui nous a été confié est celui de la **communication institutionnelle et de la messagerie** : il s'agit de concevoir un point d'entrée de contact unique, capable de rediriger automatiquement chaque message vers le bon service, tout en filtrant les soumissions abusives et en garantissant que l'échec d'un envoi ne passe jamais inaperçu — une exigence qui, comme le chapitre 4 le montrera, n'allait pas de soi une fois l'application déployée en production.

Ce rapport est organisé en quatre chapitres. Le premier présente l'organisme d'accueil, le contexte du projet global et la méthodologie de travail adoptée par l'équipe. Le deuxième développe l'analyse des besoins fonctionnels et non fonctionnels propres à notre module. Le troisième expose les choix de conception, tant au niveau du routage des messages que de l'architecture d'envoi des e-mails. Le quatrième détaille la réalisation technique, la défaillance de production rencontrée et la démarche de diagnostic et de correction adoptée. Une conclusion générale clôt ce rapport en dressant un bilan des compétences acquises.

<div style="page-break-after: always;"></div>

## Chapitre 1 — Présentation de l'organisme d'accueil et cadre du projet

### 1.1 Présentation de la SEPJ Gabès

La Société d'Environnement, Plantation et Jardinage de Gabès (شركة البيئة والغراسة والبستنة بقابس), désignée dans ce document par l'acronyme **SEPJ Gabès**, assure des missions d'aménagement paysager, de reboisement et de développement durable sur l'ensemble du gouvernorat de Gabès. Son organisation se compose d'une direction générale, d'une dizaine d'agences régionales (Gabès Ville, Gabès Sud, Gabès Ouest, Ghannouch, Mareth, Métouia, Matmata Ancienne et Nouvelle, Ben Ghilouf, Manzel Habib, Hamma, Zerkine) et d'une quinzaine de directions fonctionnelles (Ressources Humaines, Financier, Juridique, Technique, Achats, Contrôle, Informatique, Archives, Bureau d'Ordre Central, Coordination, RSE, Social) — soit une trentaine de destinataires potentiels pour un message entrant, en comptant la direction générale et les directions générales adjointes.

Cette granularité organisationnelle constitue précisément la donnée d'entrée de notre module : chaque département possède une adresse e-mail propre, et un même formulaire de contact doit pouvoir acheminer un message vers n'importe lequel d'entre eux selon le choix du visiteur.

### 1.2 Contexte et problématique du projet

Avant ce projet, la SEPJ Gabès ne disposait d'aucun canal de contact numérique centralisé : un visiteur souhaitant joindre un service devait déjà en connaître l'adresse e-mail exacte, ce qui limitait fortement l'accessibilité de l'entreprise pour le grand public et les partenaires ne connaissant pas son organigramme interne.

La problématique confiée à l'équipe peut se formuler ainsi : *comment concevoir une plateforme web qui centralise la communication institutionnelle de la SEPJ Gabès, tout en donnant au personnel de l'entreprise les moyens de la maintenir de façon autonome ?*

En ce qui concerne plus spécifiquement notre module, la question se précise : *comment permettre à un visiteur externe, ignorant tout de l'organisation interne de l'entreprise, d'adresser un message qui parvienne malgré tout au bon service, de façon fiable, sécurisée et traçable ?*

### 1.3 Objectifs du stage et périmètre confié

Le périmètre confié à notre module a couvert :

- le formulaire de contact complet et le formulaire de contact rapide, côté public ;
- la modélisation et l'alimentation de la table de routage des départements de l'entreprise ;
- la validation, la protection anti-spam et l'enregistrement de chaque message ;
- l'envoi effectif de l'e-mail correspondant, via une passerelle SMTP authentifiée, avec gestion des cas particuliers (mise en copie automatique des dirigeants pour certains services) ;
- côté back-office, l'espace de consultation et de traitement des messages, ainsi que le journal d'envoi d'e-mails et l'écran de configuration SMTP.

Les modules d'authentification/sécurité et de contenu/RSE, bien que consommés indirectement (le formulaire de contact réutilise par exemple le jeton CSRF fourni par le noyau de sécurité commun), ont été pris en charge par les deux autres membres de l'équipe et ne sont pas détaillés dans ce rapport.

### 1.4 Méthodologie et organisation du travail

L'équipe a fonctionné selon une répartition modulaire du travail, chaque stagiaire disposant d'un périmètre fonctionnel propre tout en partageant un socle commun (`app/core/`, `app/config/`, base de données unique) versionné dans un dépôt Git unique. Le développement s'est déroulé en local sous **XAMPP**, avec des commits réguliers poussés sur **GitHub**, la mise en production étant assurée par un déploiement automatique vers l'hébergement mutualisé **OVH**. Le développement de notre module a nécessité une contrainte particulière : la validation de l'envoi réel d'e-mails, fonctionnalité qui ne peut être entièrement vérifiée en environnement local et qui a donc demandé des allers-retours supplémentaires entre le poste de développement et le serveur de production.

### 1.5 Conclusion

Ce premier chapitre a permis de situer le projet dans son contexte organisationnel et d'en délimiter le périmètre. Le chapitre suivant détaille l'analyse des besoins fonctionnels et non fonctionnels propres au module de communication et de messagerie.

<div style="page-break-after: always;"></div>

## Chapitre 2 — Analyse et spécification des besoins

### 2.1 Étude de l'existant

En l'absence de tout canal numérique préexistant, l'étude de l'existant s'est appuyée sur la liste des départements et agences de la SEPJ Gabès (fournie par l'encadrant professionnel sous forme de tableur des adresses e-mail internes) ainsi que sur l'observation de systèmes de contact « intelligents » d'autres sites institutionnels, redirigeant un message vers le bon service sans exposer d'adresses e-mail brutes au visiteur.

### 2.2 Recueil des besoins

Le recueil des besoins a permis d'identifier une exigence métier non triviale : certains services, en raison de leur sensibilité (exemple : l'agence Manzel Habib), doivent voir leurs messages automatiquement mis en copie à l'ensemble des dirigeants exécutifs de l'entreprise, sans que le visiteur n'ait à le demander explicitement. Il a également été établi que les adresses e-mail des dirigeants exécutifs ne devaient jamais apparaître dans la liste déroulante proposée au visiteur.

### 2.3 Besoins fonctionnels

| Réf. | Besoin fonctionnel |
|---|---|
| BF-01 | Permettre à un visiteur de choisir un département/service parmi une liste alimentée dynamiquement depuis la base de données. |
| BF-02 | Valider côté serveur l'ensemble des champs du formulaire (nom, e-mail, téléphone à 8 chiffres, message), indépendamment de la validation déjà faite côté navigateur. |
| BF-03 | Permettre l'envoi d'une pièce jointe optionnelle (PDF, image, document Word), avec contrôle de taille et de type réel de fichier. |
| BF-04 | Enregistrer chaque message reçu dans une table dédiée, consultable depuis le back-office. |
| BF-05 | Acheminer automatiquement chaque message par e-mail vers l'adresse du département sélectionné, en plaçant l'adresse du visiteur en « Répondre à » et jamais en expéditeur. |
| BF-06 | Mettre automatiquement en copie l'ensemble des dirigeants exécutifs lorsque le service sélectionné l'exige. |
| BF-07 | Rejeter silencieusement toute soumission automatisée détectée par un champ « pot de miel » invisible. |
| BF-08 | Limiter le nombre de messages autorisés par adresse IP et par heure. |
| BF-09 | Permettre à un administrateur de consulter, marquer comme lu, archiver ou supprimer un message reçu. |
| BF-10 | Journaliser chaque tentative d'envoi d'e-mail (succès ou échec, avec message d'erreur) et la rendre consultable depuis le back-office. |
| BF-11 | Permettre à un administrateur de configurer les paramètres SMTP (hôte, port, identifiants) depuis l'interface, sans intervention sur le serveur. |

### 2.4 Besoins non fonctionnels

- **Confidentialité** : les adresses IP des visiteurs sont hachées (SHA-256) avant stockage — jamais conservées en clair — afin de limiter le rate-limiting à son strict usage technique sans constituer un fichier de données personnelles.
- **Délivrabilité** : l'adresse d'expédition (`From`) doit systématiquement correspondre au compte SMTP authentifié, jamais à l'adresse du visiteur, pour éviter que les messages ne soient marqués comme frauduleux (SPF/DKIM) par les serveurs de messagerie destinataires.
- **Disponibilité en cas de panne tierce** : l'échec de l'envoi d'un e-mail ne doit jamais empêcher l'enregistrement du message en base — mais il doit être visible par un administrateur.
- **Sécurité** : chaque soumission de formulaire doit être protégée par un jeton anti-CSRF et une limitation de débit par IP.
- **Autonomie de configuration** : un administrateur sans accès aux fichiers serveur doit pouvoir modifier les identifiants SMTP depuis le navigateur.

### 2.5 Acteurs et cas d'utilisation

Deux acteurs interagissent avec ce module :

- **Le visiteur du site** (non authentifié) : remplit un formulaire de contact, choisit un service, joint éventuellement un fichier.
- **L'administrateur** (authentifié, module transverse pris en charge par Membre C) : consulte, traite et archive les messages ; consulte le journal d'envoi ; configure les paramètres SMTP.

```
┌───────────────────────────────────────────────────────────┐
│                     Cas d'utilisation                       │
│                                                              │
│   Visiteur ──── Remplir un formulaire de contact             │
│           ╲──── Sélectionner un service / département        │
│            ╲─── Joindre un fichier (optionnel)                │
│                                                              │
│   Administrateur ── Consulter / archiver les messages         │
│               ╲──── Consulter le journal d'envoi d'e-mails    │
│                ╲─── Configurer les paramètres SMTP            │
└───────────────────────────────────────────────────────────┘
```
*Figure 2.1 — Cas d'utilisation simplifiés du module Contact & Messagerie.*

### 2.6 Conclusion

L'analyse menée dans ce chapitre a permis de dégager un besoin central : acheminer de façon fiable, sécurisée et traçable un message d'un visiteur anonyme vers l'un des trente destinataires internes de l'entreprise. Le chapitre suivant présente la conception retenue pour répondre à ce besoin.

<div style="page-break-after: always;"></div>

## Chapitre 3 — Conception

### 3.1 Architecture générale de l'application

Comme l'ensemble de la plateforme, notre module s'inscrit dans une architecture en couches sans framework :

```
┌───────────────────────────────────────────────┐
│  Couche présentation                            │
│  public/contact.php, quick-contact.php          │
│  admin/messages/  (traitement, journal d'envoi) │
├───────────────────────────────────────────────┤
│  Couche logique métier / utilitaires            │
│  app/core/mailer.php   (envoi, routage)         │
│  app/core/rate_limiter.php (anti-abus)          │
│  app/config/mail.php   (configuration)          │
├───────────────────────────────────────────────┤
│  Couche données                                 │
│  contact_messages, contact_services,            │
│  contact_rate_limit, mail_log, site_settings    │
└───────────────────────────────────────────────┘
```
*Figure 3.1 — Architecture en couches du module Contact & Messagerie.*

### 3.2 Conception de la base de données

**Table `contact_services` — routage des messages :**

| Colonne | Type | Rôle |
|---|---|---|
| `id` | INT, PK | Identifiant unique du service |
| `display_name_ar / fr / en` | VARCHAR | Nom affiché dans la liste déroulante, par langue |
| `email` | VARCHAR, UNIQUE | Adresse e-mail réelle du département |
| `is_executive` | TINYINT(1) | 1 = adresse de dirigeant, jamais affichée dans la liste |
| `cc_executives` | TINYINT(1) | 1 = met automatiquement en copie tous les dirigeants |
| `sort_order` | SMALLINT | Ordre d'affichage dans la liste déroulante |
| `is_active` | TINYINT(1) | Permet de désactiver un service sans le supprimer |

**Table `mail_log` — journal d'envoi :**

| Colonne | Type | Rôle |
|---|---|---|
| `to_address` | VARCHAR | Destinataire réel de l'e-mail |
| `subject` | VARCHAR | Objet envoyé |
| `service_id` | INT, FK → `contact_services` | Service concerné |
| `status` | ENUM | `sent` / `failed` |
| `error_message` | TEXT | Détail de l'erreur en cas d'échec |

**Table `contact_rate_limit` :** stocke uniquement un hachage SHA-256 de l'adresse IP et un horodatage, avec purge automatique des entrées de plus de 24 heures — conçue pour ne jamais permettre de retrouver une IP en clair.

### 3.3 Conception du routage et de l'envoi

```
┌────────────────────────────────────────────────────────────┐
│  send_routed_email(fields, service, lang, attachment?)        │
│                                                                │
│  1. Valider l'adresse du service (filter_var FILTER_VALIDATE_  │
│     EMAIL) → abandon si invalide                               │
│  2. Construire l'objet et le corps (texte brut + HTML)         │
│  3. Adresse du visiteur → Reply-To uniquement (jamais From)     │
│  4. Si service.cc_executives = 1 → ajouter tous les              │
│     dirigeants exécutifs en copie                               │
│  5. Envoyer via MailerService (PHPMailer / SMTP authentifié)     │
│  6. Journaliser le résultat (succès / échec) dans mail_log       │
└────────────────────────────────────────────────────────────┘
```
*Figure 3.2 — Logique de routage et d'envoi d'un message de contact.*

La configuration SMTP effective est résolue selon un ordre de priorité décroissant : variables d'environnement serveur, puis fichier de configuration versionné, puis réglages enregistrés en base de données (modifiables depuis le back-office), puis valeurs par défaut. Cette hiérarchie, précisée au chapitre 4, s'est révélée être au cœur de la principale difficulté rencontrée durant ce stage.

### 3.4 Conclusion

La conception retenue sépare clairement la logique de routage (déterminer le bon destinataire), la logique d'envoi (PHPMailer/SMTP) et la logique de traçabilité (journal d'envoi), ce qui a permis, comme le montre le chapitre suivant, de localiser rapidement l'origine d'une défaillance de production sans remettre en cause l'ensemble du module.

<div style="page-break-after: always;"></div>

## Chapitre 4 — Réalisation

### 4.1 Environnement de travail

| Catégorie | Outil |
|---|---|
| Environnement local | XAMPP (Apache 2.4, PHP 8, MySQL/MariaDB) sous Windows |
| Éditeur de code | Visual Studio Code |
| Gestion de version | Git, dépôt distant GitHub |
| Gestion des dépendances PHP | Composer (PHPMailer) |
| Test d'envoi d'e-mails | Compte SMTP de test en local, configuration réelle en production |
| Hébergement de production | OVH (mutualisé), déploiement par récupération automatique des commits GitHub |
| Administration base de données | phpMyAdmin |

### 4.2 Technologies et outils utilisés

L'implémentation repose sur **PHP 8** natif avec **PDO** pour l'ensemble des accès aux données, et sur la bibliothèque **PHPMailer** (installée via Composer) pour l'envoi d'e-mails exclusivement par **SMTP authentifié** — le projet a délibérément exclu tout recours à la fonction native `mail()` de PHP, peu fiable et souvent classée comme indésirable par les serveurs de messagerie destinataires. Les échanges SMTP sont chiffrés (STARTTLS sur le port 587 ou SMTPS sur le port 465), avec vérification stricte du certificat du serveur distant.

### 4.3 Réalisation détaillée du module

**a) Validation et anti-spam côté serveur**

```php
if (!csrf_validate()) {
    $errors[] = 'Requête invalide.';
} elseif (!empty($_POST['website'])) {
    // Pot de miel déclenché — rejet silencieux
    csrf_regenerate();
} elseif (!check_contact_rate_limit($_SERVER['REMOTE_ADDR'] ?? '', $maxPerHour)) {
    $errors[] = 'Limite de messages dépassée. Veuillez réessayer plus tard.';
} else {
    // … validation des champs, puis enregistrement et envoi
}
```
*Figure 4.1 — Extrait de `public/contact.php` : enchaînement des protections avant traitement du formulaire.*

**b) Hachage des adresses IP pour la limitation de débit**

```php
function _rl_hash(string $ip): string
{
    return hash('sha256', 'sepj_gabes_rl_v1_' . $ip);
}
```
*Figure 4.2 — Extrait de `app/core/rate_limiter.php` : les adresses IP ne sont jamais stockées en clair.*

**c) Mise en copie automatique des dirigeants**

```php
$cc = [];
if (!empty($service['cc_executives'])) {
    $cc = array_values(array_filter(
        get_executive_emails(),
        fn($addr) => filter_var($addr, FILTER_VALIDATE_EMAIL)
    ));
}
```
*Figure 4.3 — Extrait de `app/core/mailer.php`.*

**d) Espace d'administration des messages**

Le back-office propose une liste filtrable par statut (nouveau, lu, archivé), une vue détaillée par message, ainsi qu'un journal d'envoi d'e-mails distinct, ajouté spécifiquement pour rendre visible toute défaillance de livraison — fonctionnalité directement issue de l'incident détaillé ci-après.

### 4.4 Difficultés rencontrées et démarche de résolution

La difficulté la plus significative rencontrée sur ce module est apparue **après une mise en production** : les visiteurs recevaient bien la confirmation de succès, et leurs messages étaient correctement enregistrés dans la table `contact_messages` — mais **aucun e-mail n'arrivait jamais** aux boîtes des départements concernés. Il s'agissait d'une panne particulièrement trompeuse, puisqu'elle donnait l'illusion que tout fonctionnait normalement, l'utilisateur final ne voyant qu'un message de succès.

La démarche de diagnostic a suivi les étapes suivantes :

1. **Élimination des causes côté formulaire** : la présence des messages dans la table `contact_messages` confirmait que la validation et l'enregistrement fonctionnaient correctement ; le problème se situait donc uniquement dans la chaîne d'envoi de l'e-mail.
2. **Analyse de l'ordre de priorité des configurations** : la fonction `get_mail_config()` combine plusieurs sources de configuration SMTP (fichier serveur non versionné, fichier versionné par défaut, réglages en base de données). L'analyse a montré que le fichier serveur contenant les véritables identifiants SMTP — volontairement exclu du dépôt Git pour ne jamais exposer de secrets — ne pouvait, par construction, **jamais atteindre le serveur de production** : le déploiement automatique GitHub → OVH ne fait que récupérer les fichiers versionnés, il ne dépose donc jamais ce fichier local, qui doit être créé manuellement sur le serveur.
3. **Constat** : en l'absence d'accès direct au système de fichiers du serveur mutualisé pour un administrateur non technique, ce fichier n'était tout simplement jamais créé en production, et l'envoi d'e-mail échouait silencieusement à chaque tentative, sans qu'aucune alerte ne soit visible.
4. **Correction structurelle** : la fonction de résolution de configuration a été modifiée pour qu'elle utilise, en dernier recours, les identifiants SMTP enregistrés dans la table `site_settings` — éditables directement depuis l'écran *Réglages* du back-office — lorsqu'aucun fichier serveur ne fournit déjà des identifiants réels, tout en conservant la priorité à ce fichier serveur s'il existe. Ainsi, un administrateur sans accès aux fichiers du serveur peut désormais configurer ou corriger lui-même les paramètres d'envoi depuis son navigateur.
5. **Prévention des récidives silencieuses** : la correction a été complétée par la création d'un journal d'envoi (`mail_log`), consultable depuis le back-office, afin qu'une future défaillance de livraison — quelle qu'en soit la cause — soit immédiatement visible par un administrateur, plutôt que de rester invisible dans les journaux du serveur.

Cette expérience a constitué un apprentissage marquant sur l'écart entre un environnement de développement local, où tous les fichiers de configuration sont présents par construction, et un environnement de production réel, où certains fichiers volontairement exclus du contrôle de version peuvent tout simplement ne jamais exister — un problème qu'aucun test unitaire classique n'aurait révélé, seule l'observation du comportement réel en production l'a mis en évidence.

### 4.5 Tests et validation

| Scénario testé | Résultat attendu |
|---|---|
| Soumission valide d'un formulaire de contact | Message enregistré, e-mail reçu par le service sélectionné, adresse du visiteur en Reply-To |
| Sélection du service à mise en copie automatique | Les dirigeants exécutifs reçoivent bien une copie |
| Remplissage du champ « pot de miel » (simulation de robot) | Message rejeté silencieusement, aucun e-mail envoyé |
| Sixième soumission depuis la même IP en moins d'une heure | Message rejeté avec message d'erreur explicite |
| Envoi avec pièce jointe de type non autorisé (.exe renommé en .pdf) | Rejet basé sur le type MIME réel, pas sur la seule extension |
| Configuration SMTP serveur absente, réglages en base présents | L'e-mail part malgré tout, en utilisant le repli base de données |
| Échec volontaire de connexion SMTP (mauvais mot de passe) | Le message reste enregistré en base ; l'échec est visible dans le journal d'envoi |

### 4.6 Conclusion

Ce chapitre a détaillé la réalisation du module de contact et de messagerie, ainsi que le diagnostic et la correction d'un incident de production réel ayant affecté la délivrabilité des e-mails. Les tests menés confirment que le module répond désormais aux besoins fonctionnels et non fonctionnels identifiés au chapitre 2, tout en étant devenu observable et corrigible par un administrateur non technique.

<div style="page-break-after: always;"></div>

## Conclusion générale

Ce stage nous a permis de concevoir et de réaliser, au sein d'une équipe de trois stagiaires, le module de communication institutionnelle et de messagerie d'une plateforme complète pour la SEPJ Gabès. Au-delà de l'aspect purement technique, ce travail a nécessité de traduire une organisation interne complexe — une trentaine de départements et d'agences — en un modèle de données de routage simple et fiable, et de comprendre les contraintes réelles de la délivrabilité des e-mails en environnement de production.

Sur le plan des **compétences techniques**, ce stage a permis d'approfondir la validation de formulaires côté serveur en PHP, les notions de délivrabilité des e-mails (SPF/DKIM, distinction From/Reply-To), l'intégration de PHPMailer sur connexion SMTP authentifiée, les techniques anti-spam (pot de miel, jeton CSRF, limitation de débit avec hachage d'IP) et la modélisation MySQL d'une table de routage métier. Sur le plan des **compétences transversales**, il a fallu faire preuve de méthode face à un incident de production silencieux, de capacité à traduire une organisation réelle en modèle de données, et du souci de rendre une fonctionnalité corrigible par un administrateur non technique plutôt que par le seul développeur.

Comme perspectives d'amélioration, plusieurs pistes pourraient être envisagées : l'ajout d'une notification automatique (par exemple via un second canal) lorsqu'un e-mail échoue plusieurs fois de suite, la mise en place d'une file d'attente pour réessayer automatiquement les envois échoués, ou encore l'extension du journal d'envoi à des statistiques de délivrabilité par service.

<div style="page-break-after: always;"></div>

## Bibliographie et Netographie

- Documentation officielle PHP — *PDO, filter_var, finfo* : php.net
- Documentation officielle PHPMailer : github.com/PHPMailer/PHPMailer
- Documentation SMTP / STARTTLS : RFC 5321, RFC 3207
- OWASP — *Cross-Site Request Forgery Prevention Cheat Sheet* : owasp.org
- OWASP — *Input Validation Cheat Sheet* : owasp.org
- Documentation officielle MySQL / MariaDB : dev.mysql.com

## Annexes

- Annexe A — Schéma complet des tables `contact_services`, `mail_log`, `contact_rate_limit` (voir `database/migration_contact_services.sql`, `migration_mail_log.sql`)
- Annexe B — Extrait complet de la fonction `get_mail_config()` avant/après correction
- Annexe C — Captures d'écran du formulaire de contact dans les trois langues *(à insérer par l'étudiant)*
- Annexe D — Captures d'écran du journal d'envoi d'e-mails et de l'écran de configuration SMTP *(à insérer par l'étudiant)*

---
*Document rédigé dans le cadre du rapport de stage de fin d'études — SEPJ Gabès. Les mentions entre crochets [ ] sont des emplacements à personnaliser avant impression ou soutenance.*
