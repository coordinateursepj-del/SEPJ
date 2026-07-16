# SEPJ Gabès — Répartition du travail de stage

Document de travail établi à partir du code source et de l'historique Git réel du projet SEPJ Gabès (juillet 2026), destiné à servir de trame individuelle. Chaque membre doit reformuler ce contenu avec ses propres mots dans son rapport final ; les trois rapports partant de modules distincts, aucune ressemblance de fond n'est nécessaire — seule la ressemblance de formulation doit être évitée.

**Légende** — chaque module est associé à un membre pour repérer immédiatement, dans le code, ce qui appartient à qui :
- 🟢 **Membre A** — Contenu Multilingue, Vitrine Publique & RSE
- 🟤 **Membre B** — Contact & Messagerie
- 🔵 **Membre C** — Sécurité, Comptes Administrateurs & Médiathèque

---

## Étape 1 — Synthèse générale du projet

La **Société d'Environnement, Plantation et Jardinage de Gabès (SEPJ Gabès)** est un établissement chargé de missions d'aménagement paysager, de reboisement et de développement durable à l'échelle du gouvernorat de Gabès. Son organisation interne — une direction générale, une dizaine d'agences régionales (Gabès Sud, Gabès Ville, Gabès Ouest, Ben Ghilouf, Mareth, Métouia, Ghannouch, Matmata Ancienne et Nouvelle, Hamma, Zerkine, Manzel Habib) et une quinzaine de directions fonctionnelles (Ressources Humaines, Financier, Juridique, Technique, Achats, Contrôle, Informatique, Archives, Bureau d'Ordre Central, RSE, Social) — se reflète directement dans l'architecture de l'application : c'est cette même liste de services qui alimente, par exemple, le formulaire de contact.

Le projet confié à l'équipe consistait à donner à cette organisation une présence numérique double : une **vitrine institutionnelle publique** à destination du grand public, des administrés et des partenaires, et un **back-office sécurisé** permettant au personnel de la SEPJ de gérer lui-même l'intégralité de ce contenu, sans dépendre d'un développeur pour la moindre modification.

**Vitrine publique (`public/`)** — Site trilingue arabe / français / anglais (arabe par défaut, affichage RTL complet), couvrant présentation institutionnelle, projets, services, actualités, activités, distinctions, sport, ressources, galeries photo/vidéo, moteur de recherche interne, rapports RSE et de durabilité, et un système de contact routant chaque message vers le bon département.

**Back-office (`admin/`)** — Espace authentifié à deux rôles (administrateur / éditeur) pour créer et publier tout le contenu multilingue, gérer la médiathèque, traiter les messages entrants, configurer le site et la messagerie, administrer les comptes et consulter un journal d'audit des actions sensibles.

**Choix technique** — Par souci de compatibilité avec un hébergement mutualisé OVH et de coût nul de licence, aucun framework n'est utilisé : PHP 8 natif avec PDO, un noyau de fonctions utilitaires maison (`app/core/`), MySQL/MariaDB en InnoDB/utf8mb4, Tailwind CSS et une feuille de style maison pour l'habillage.

Le développement s'est déroulé sous **XAMPP** (Apache, MySQL, PHP) en local, avec **Git/GitHub** pour le versionnement ; la mise en production repose sur un déploiement simplifié où **OVH récupère directement les commits** poussés sur la branche principale du dépôt — sans pipeline d'intégration continue, ce qui a des implications concrètes que l'on retrouve dans les difficultés techniques rencontrées par deux des trois membres de l'équipe.

Pour l'organisation, la plateforme représente une vitrine unique et cohérente dans les trois langues d'usage de ses publics, une communication RSE structurée et traçable, un point d'entrée de contact unique redistribuant automatiquement les demandes vers une trentaine de services internes sans boîte mail partagée, et une autonomie éditoriale complète du personnel — sans dépendance à une solution propriétaire.

---

## Étape 2 — Membre A

### 1. Intitulé du poste & module dédié

**Développeur Full-Stack — Contenu Multilingue, Vitrine Publique & Reporting RSE**

Fichiers/tables de référence : `public/{about,projects,services,news,activities}.php`, `public/rse.php`, `public/search.php`, `app/core/i18n.php`, `app/core/translation_service.php`, `admin/content/*`, tables `content_items`, `navigation_items`.

### 2. Déroulement des missions

**Volet Front-End (UI)**
Ce volet a consisté à construire l'ensemble des pages publiques consommant le contenu multilingue — accueil, à propos, projets, services, activités, distinctions, actualités, sport, ressources, galerie photo, galerie vidéo, moteur de recherche interne, et surtout la page RSE, bâtie autour d'une taxonomie à cinq catégories (engagement social, engagement sociétal et environnemental, rapport RSE, catalogue RSE, rapport de durabilité). Chaque page devait s'afficher correctement dans les trois langues, avec un renversement complet de la mise en page en arabe — y compris pour les composants partagés, la barre de navigation restant volontairement affichée de gauche à droite quelle que soit la langue active, pour ne pas désorienter l'utilisateur qui bascule d'une langue à l'autre. Membre A a également implémenté le sélecteur de langue (menu à drapeaux conservant la page et les paramètres de requête lors du changement) et le repli automatique vers l'arabe lorsque la traduction d'un article est absente dans la langue courante.

**Volet Back-End & Données**
Côté serveur, Membre A a conçu la couche d'internationalisation de l'interface et surtout le module d'administration du contenu (création, édition, liste filtrable, changement de statut, suppression) autour d'une table `content_items` unique portant, pour chaque élément, des colonnes parallèles par langue plutôt qu'un modèle plus complexe — un compromis assumé entre simplicité de requêtage et légère redondance de schéma. Sa contribution la plus significative est le service de traduction automatique : à l'enregistrement d'un article, le système détecte quelle langue a été renseignée le plus complètement, puis appelle l'API libre LibreTranslate pour ne compléter que les champs manquants dans les deux autres langues, sans jamais écraser un texte déjà saisi par un rédacteur. Membre A a aussi ajouté la colonne de catégorie RSE via un script de migration idempotent, exécutable sans risque sur la base de chaque membre de l'équipe.

**Outils communs utilisés**
Travail sur une base MySQL locale sous XAMPP, évolutions de schéma propagées via des scripts de migration versionnés sous Git plutôt que des modifications manuelles, pour que le reste de l'équipe puisse les rejouer à l'identique. Les gabarits publics étaient poussés sur GitHub puis déployés automatiquement sur OVH ; le mécanisme de cache-busting mis en place par l'équipe garantissait que les changements de mise en page étaient visibles immédiatement après déploiement.

### 3. Problématique technique majeure

Les premiers tests du service de traduction automatique échouaient systématiquement avec une erreur cURL liée à la vérification du certificat SSL, sans que le message n'en précise la cause exacte. La démarche a été méthodique : isolement de l'appel HTTP du reste de l'application, activation d'une journalisation détaillée de cURL, puis consultation de la documentation PHP relative à cURL sous Windows — ce qui a permis d'identifier que la distribution XAMPP ne fournit pas, par défaut, de fichier d'autorité de certification reconnu par cURL, une limitation connue de cet environnement plutôt qu'un véritable problème de sécurité serveur. Plutôt que de désactiver silencieusement la vérification partout, Membre A a isolé ce contournement strictement au service de traduction, l'a documenté comme spécifique à XAMPP/Windows, et a laissé la vérification stricte active pour tous les autres échanges sensibles de l'application. Il a de plus fallu rendre la détection de langue source tolérante aux échecs de l'API : en cas d'erreur réseau, le texte d'origine est conservé, un avertissement non bloquant est journalisé, et l'enregistrement de l'article n'est jamais interrompu — pour qu'une panne d'un service tiers gratuit ne puisse jamais empêcher un rédacteur de publier.

### 4. Bilan & compétences

**Hard skills** — PHP 8 avec PDO (requêtes préparées, clauses WHERE dynamiques), modélisation de données multilingues en MySQL, consommation d'une API HTTP tierce en JSON via cURL, gabarits PHP avec Tailwind CSS, bidirectionnalité RTL/LTR, bases d'accessibilité web (attributs lang/dir, régions aria-live), Git.

**Soft skills** — Rigueur terminologique pour la cohérence d'un contenu institutionnel en trois langues, autonomie dans la lecture de documentation technique d'un service externe, dialogue avec des parties prenantes non techniques pour définir une taxonomie RSE pertinente, sens du compromis entre robustesse et simplicité de modélisation.

---

## Étape 2 — Membre B

### 1. Intitulé du poste & module dédié

**Développeur Full-Stack — Communication Institutionnelle, Formulaires de Contact & Messagerie**

Fichiers/tables de référence : `public/contact.php`, `public/quick-contact.php`, `app/core/mailer.php`, `app/core/rate_limiter.php`, `admin/messages/*`, `admin/settings/*`, tables `contact_services`, `mail_log`.

### 2. Déroulement des missions

**Volet Front-End (UI)**
Membre B a conçu les deux points d'entrée de contact du site : un formulaire complet, présentant les coordonnées de l'établissement à côté d'un formulaire de saisie, et un formulaire de contact rapide pensé comme un parcours plus direct. Les deux partagent la même exigence : permettre à un visiteur de choisir, dans une liste déroulante alimentée dynamiquement depuis la base, le département de la SEPJ auquel il s'adresse parmi la trentaine de départements et d'agences régionales. Membre B a soigné les états d'interface (indicateur de chargement animé, message de succès, restitution des erreurs avec mise en évidence des champs concernés, région aria-live pour les lecteurs d'écran) ainsi que la validation côté client (numéro de téléphone tunisien à huit chiffres, format d'e-mail, pièce jointe optionnelle limitée en taille et en type).

**Volet Back-End & Données**
Le cœur du travail a consisté à bâtir la chaîne complète d'acheminement d'un message : validation serveur stricte, protection anti-spam (jeton CSRF fourni par le noyau commun, champ « pot de miel » invisible, limitation du nombre d'envois par IP et par heure — les IP étant hachées en SHA-256 avant stockage), enregistrement en base, puis envoi effectif via PHPMailer sur connexion SMTP authentifiée, l'adresse du visiteur étant placée en Reply-To et jamais en From pour éviter le rejet par les serveurs destinataires. Membre B a modélisé la table de routage centralisant la trentaine de départements avec leur adresse e-mail, et implémenté une règle métier propre à un service particulier dont la sélection met automatiquement en copie l'ensemble des dirigeants exécutifs. Il a construit, côté administration, l'espace de traitement des messages, un journal d'envoi d'e-mails consultable pour diagnostiquer une défaillance de livraison, et l'écran de configuration SMTP des réglages du site.

**Outils communs utilisés**
Le développement a nécessité de tester réellement l'envoi d'e-mails, avec deux configurations SMTP en parallèle (compte de test local sous XAMPP, configuration réelle de production), en s'appuyant sur Git pour isoler les fichiers de configuration contenant des identifiants et sur le déploiement GitHub → OVH pour vérifier le comportement une fois en ligne — l'envoi de courrier étant l'une des rares fonctionnalités impossibles à valider entièrement en local.

### 3. Problématique technique majeure

Après une mise en production, les visiteurs recevaient bien la confirmation de succès et leurs messages étaient correctement enregistrés en base, mais aucun e-mail n'arrivait jamais aux départements concernés — une panne silencieuse d'autant plus trompeuse qu'elle donnait l'illusion que tout fonctionnait normalement. L'investigation a commencé par l'exclusion des causes côté formulaire, les données étant bien présentes en base. Membre B a ensuite retracé l'ordre de priorité des sources de configuration SMTP — un fichier serveur non versionné, puis un fichier versionné par défaut, puis les réglages en base — pour constater que le fichier serveur contenant les vrais identifiants ne pouvait, par construction, jamais atteindre la production : exclu du dépôt Git pour des raisons de sécurité, il n'est jamais copié par le déploiement automatique OVH, qui ne récupère que les fichiers versionnés. Une fois la cause isolée, il a modifié la fonction de configuration pour qu'elle utilise en repli les identifiants SMTP enregistrés dans les réglages du back-office lorsqu'aucun fichier serveur n'en fournit déjà, tout en conservant la priorité au fichier serveur s'il existe. Il a complété la correction par un journal d'envoi, afin qu'une future défaillance soit visible par un administrateur directement depuis l'interface plutôt que de rester invisible dans les journaux serveur.

### 4. Bilan & compétences

**Hard skills** — Validation de formulaires côté serveur en PHP, notions de délivrabilité des e-mails (SPF/DKIM, distinction From/Reply-To), intégration de PHPMailer sur SMTP authentifié, techniques anti-spam (pot de miel, jeton CSRF, limitation de débit avec hachage d'IP), modélisation MySQL d'une table de routage métier, journalisation applicative, Git.

**Soft skills** — Compréhension de l'impact opérationnel réel d'une panne « silencieuse » sur la relation entre l'entreprise et ses administrés, traduction d'une organisation interne complexe en modèle de données exploitable, souci de rendre une fonctionnalité corrigible par un administrateur non technique, méthode de diagnostic systématique en production.

---

## Étape 2 — Membre C

### 1. Intitulé du poste & module dédié

**Développeur Full-Stack — Authentification, Sécurité, Comptes Administrateurs & Médiathèque**

Fichiers/tables de référence : `admin/login.php`, `admin/dashboard.php`, `admin/users/*`, `admin/media/*`, `app/core/auth.php`, `app/core/upload.php`, `app/config/database.php`, tables `users`, `audit_logs`.

### 2. Déroulement des missions

**Volet Front-End (UI)**
Membre C a conçu l'ensemble de l'expérience d'administration transverse : l'écran de connexion (sélecteur de langue, message d'erreur générique volontairement peu explicite pour ne pas révéler si un e-mail existe en base), le tableau de bord (cartes de statistiques agrégées, listes des contenus et messages récents, actions rapides), l'ossature commune du back-office (en-tête, barre latérale) partagée par tous les écrans d'administration, l'écran de gestion des comptes utilisateurs, et la médiathèque : grille de vignettes avec recherche, pagination, aperçu en fenêtre modale et formulaire d'import d'images.

**Volet Back-End & Données**
Le volet sécurité constitue l'apport principal de ce module. Membre C a mis en place l'authentification par session (hachage des mots de passe, régénération périodique de l'identifiant de session contre la fixation de session, cookies HttpOnly/SameSite/Secure), le contrôle d'accès par rôle protégeant chaque page sensible, la protection CSRF appliquée à l'ensemble des formulaires d'administration, et une limitation des tentatives de connexion (blocage temporaire après cinq échecs). Il a également développé le moteur d'upload sécurisé : vérification du type MIME réel du fichier plutôt que de la seule extension déclarée, liste blanche d'extensions autorisées, blocage explicite des extensions dangereuses, noms de fichiers imprévisibles, et génération automatique d'un fichier `.htaccess` interdisant l'exécution de scripts dans chaque dossier d'upload — une défense en profondeur contre le dépôt de fichiers malveillants déguisés en images. Chaque action sensible, quel que soit le module, est enfin tracée dans une table d'audit commune que Membre C a conçue pour l'ensemble du back-office.

**Outils communs utilisés**
Membre C a pris en charge la séparation entre configuration versionnée et secrets de production : les identifiants réels de connexion vivent dans des fichiers exclus du dépôt Git, le fichier versionné ne portant que des valeurs par défaut sûres pour un poste XAMPP local, accompagné d'un fichier d'exemple documentant la structure attendue pour le reste de l'équipe. Il a suivi de près le comportement du déploiement automatique GitHub → OVH — notamment l'absence d'accès aux variables d'environnement du serveur mutualisé — et écrit un script de vérification d'environnement utilisé par toute l'équipe pour valider qu'une configuration locale ou serveur remplissait les prérequis (extensions PHP, droits d'écriture du dossier d'upload, connexion à la base).

### 3. Problématique technique majeure

Peu après une mise en production, la connexion à la base de données a cessé de fonctionner sur le serveur d'hébergement alors que tout fonctionnait normalement en local sous XAMPP, sans qu'aucun message d'erreur exploitable ne soit visible côté visiteur. Pour isoler le problème sans exposer d'information sensible publiquement, Membre C a écrit un script de diagnostic temporaire, volontairement séparé de l'application, affichant les informations non sensibles de connexion (hôte, port, nom de base, utilisateur, extensions PHP chargées) ainsi que le message d'erreur exact — sans jamais afficher le mot de passe — avant d'être supprimé une fois la cause confirmée. L'analyse a révélé que le fichier de configuration de la base de données était jusque-là entièrement suivi par Git : toute divergence entre l'environnement local et le serveur de production pouvait être silencieusement écrasée ou mal reportée au déploiement suivant. Membre C a corrigé le problème structurellement plutôt que ponctuellement : le fichier versionné ne contient plus que des valeurs par défaut destinées à XAMPP, chargées uniquement si un fichier local — exclu du dépôt — n'est pas présent ; en production, ce fichier local porte les véritables identifiants et n'est jamais affecté par un déploiement Git, ce qui rend la configuration de production stable dans le temps et indépendante du contenu du dépôt.

### 4. Bilan & compétences

**Hard skills** — Sécurisation de sessions PHP (fixation de session, cookies sécurisés), hachage de mots de passe et contrôle d'accès par rôle, protection CSRF, sécurisation d'uploads de fichiers (détection MIME, listes blanches, durcissement `.htaccess`), conception d'un journal d'audit, séparation configuration/secrets pour un déploiement Git → hébergement mutualisé sans CI/CD, MySQL (clés étrangères, journalisation), Git.

**Soft skills** — Culture de la sécurité par défaut, rigueur dans la gestion des secrets, sang-froid et méthode face à un incident de type « production en panne », capacité à documenter une solution pour le reste de l'équipe, sens de la responsabilité vis-à-vis d'un système partagé par toute l'équipe.
