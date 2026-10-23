# Architecture du projet Expernet

Cette description correspond aux sources présentes dans le dossier de travail.
Le catalogue public et le portail pédagogique sont deux domaines distincts qui
partagent les vues, certains composants et le même document root.

## Organisation

| Dossier | Responsabilité |
| --- | --- |
| `public/` | Seul répertoire exposé par le serveur web, assets et front controller |
| `bootstrap/` | Chargement de l’environnement, autoload et initialisation |
| `config/` | Configuration serveur, routes publiques et paramètres métier |
| `app/Controllers/` | Coordination des requêtes HTTP et des réponses |
| `app/Services/` | Authentification, règles pédagogiques, discussions et notifications |
| `app/Repositories/` | Lecture du catalogue JSON et accès MySQL par PDO |
| `app/Support/` | Vues, sécurité, validation JSON, connexions et rendu du contenu importé |
| `app/Views/` | Gabarits PHP et composants de présentation partagés |
| `data/` | Instantanés des contenus publics et correspondances des URL officielles |
| `database/migrations/` | Évolutions versionnées de la base pédagogique |
| `bin/` | Commandes CLI de lancement et d’administration locale |
| `tests/` | Contrôles PHP/JS, couverture des contenus et scénarios HTTP/MySQL |
| `docs/` | Architecture, analyses, procédures et contrats OpenAPI |

Les dépendances tierces restent dans `vendor/` et `public/assets/vendor/`.
Elles ne sont pas modifiées pour supprimer des doublons applicatifs.

## Chemin d’une requête

`public/index.php` charge le bootstrap, normalise le chemin, contrôle la méthode,
puis dirige la requête vers le catalogue, le portail ou les discussions.
`public/router.php` adapte ce fonctionnement au serveur PHP de développement.
Sur Apache, la réécriture doit pointer vers le front controller et servir
uniquement le répertoire `public/`.

### Site public

`PageController` associe la route de `config/routes.json` à un gabarit et aux
données de `ContentRepository`. Le repository lit les fichiers JSON, et non
MySQL. Le même gabarit rend toutes les formations d’un même type.

`HtmlContent` reconstruit le contenu importé en échappant les valeurs et en
contrôlant les liens. `source-pages.json` et `source-map.json` conservent le relevé
et la correspondance entre les sites officiels et les routes locales.
Les compositions spécifiques peuvent afficher un complément des blocs importés.

Les API publiques GET `/api/formations/`, `/api/sessions/` et `/api/palettes/`
exposent les données publiques. Le formulaire de devis prépare un récapitulatif
local ; il ne doit pas être présenté comme un envoi commercial effectif.

### Portail pédagogique

`PedagogyController` utilise `PortalAuth`, `PedagogyService` et
`PedagogyRepository`. Les comptes, promotions, séances, devoirs, notes,
présences déclarées et autres données du portail sont dans MySQL.
Les vues `pedagogy/pages/` affichent l’état déjà filtré par les droits.

Le rôle ne suffit pas : les services vérifient aussi les affectations,
les participants, le propriétaire de la ressource et l’état du compte.
Les mutations passent par des requêtes préparées et des transactions adaptées.
Le prototype d’émargement n’est pas une signature électronique certifiée.

### Échanges

`ChatController` expose `/api/discussions/`. `ChatService` vérifie les droits
sur les promotions, conversations et participants avant de lire ou d’écrire.
Les messages sont persistés dans les tables `portal_chat_*`.
L’administrateur ne dispose pas d’un accès implicite aux discussions privées.
`ChatSocket` fournit les notifications WebSocket facultatives ; le contenu
reste obtenu par l’API HTTP authentifiée. Le repli HTTP actualise la messagerie.

## Présentation et composants communs

`View` rend les gabarits et permet aux sous-vues d’appeler d’autres composants.
Les noms de vues restent contrôlés et ne viennent pas directement d’un paramètre
utilisateur. Les données ne peuvent pas écraser les variables internes du rendu.

- `partials/hero.php` : en-tête, fil d’Ariane et présentation des pages publiques.
- `partials/legal-intro.php` : réutilise le hero et ajoute l’avertissement juridique.
- `partials/source-note.php` : provenance d’un contenu, avec URL échappée.
- `partials/header.php` / `footer.php` : navigation et pied de page.
- `partials/home-carousel.php` : photos de l’accueil, données dans `home-stories.php`.

Le CSS conserve le design V2 Océan. `base.css` précède `pages.css`, puis les
styles du module concerné. Des règles responsive différentes ne sont pas des
doublons à supprimer automatiquement : l’ordre de cascade fait partie du résultat.

`main.js` initialise les modules ES du site public. Le portail possède ses
modules pédagogiques et de discussions. Les fonctions communes de navigation,
échappement et chargement restent regroupées dans les utilitaires existants.
Aucun framework frontend supplémentaire n’est nécessaire.

## Données et configuration

Les fichiers JSON publics et MySQL ne sont pas deux sources interchangeables :
le premier alimente l’éditorial, le second les opérations privées.
Le dump `database.sql` est un jeu de création initiale contenant des suppressions
de tables. Les migrations servent aux évolutions d’une base existante.
Ne pas réimporter le dump pour corriger une migration ou ajouter des comptes.

Les secrets et fichiers privés restent hors de `public/` et de Git. Le bootstrap
charge la configuration locale ; les variables exportées restent prioritaires.
Les scripts CLI utilisent les mêmes paramètres de connexion que le site.
Les sessions, CSRF, mots de passe hachés et contrôles d’affectation doivent être
conservés lors d’un nettoyage de code.

## Déploiement et vérification

LAMP et Docker constituent des modes de déploiement, pas des sources de données
à fusionner automatiquement. Leurs bases et stockages peuvent être indépendants.
Le code d’une image Docker ou d’une copie Apache doit être mis à jour pour refléter
les changements du dossier de développement. Les versions disponibles sur des
branches séparées doivent être intégrées et testées avant une livraison commune.

Le catalogue compte actuellement 294 routes rendues par les tests. Le contrôle de
couverture vérifie les 273 pages sources et leurs 23 565 blocs.
Voir `docs/AUDIT-ARCHITECTURE-DOUBLONS.md` pour le nettoyage mesuré, les tests et
les limites. Les contrats API sont dans les fichiers OpenAPI de `docs/`.
