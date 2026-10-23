# Expernet - Groupe 4

Prototype de refonte réunissant le catalogue Expernet/Campus et un espace pédagogique
qui remplace les usages étudiés de GALIA et centralise les échanges. Projet réalisé
en binôme avec **PHP, JavaScript natif, HTML, CSS et MySQL**, sans framework frontend.

**Démonstration : [expernet.vercel.app](https://expernet.vercel.app/)** ·
[Architecture](docs/ARCHITECTURE.md) · [Fonctionnalités et évolutions](docs/PROJET-ET-EVOLUTIONS.md)

## Le problème traité

L’existant répartit le catalogue dans WordPress, le planning et les notes dans
GALIA, les documents et les échanges dans Teams, et les présences sur papier.
Notre proposition offre un point d’entrée commun, avec une navigation adaptée
aux étudiants, formateurs et administrateurs. Le portail possède ses propres
données : **aucun connecteur GALIA ni synchronisation Microsoft Teams n’est installé**.

## Ce qui fonctionne et ses limites

| Domaine | Réalisé | Limite à connaître |
| --- | --- | --- |
| Site public | 149 formations, 11 diplômes Campus, 294 routes, recherche et filtres | Contenu JSON importé ; pas de CMS d’administration |
| Accueil | Six photos officielles, carrousel, accès pédagogique | Les droits de publication des médias restent à valider avec Expernet |
| Interface | Palette Océan, Inter locale, mode sombre mémorisé, responsive | Recette sur téléphones physiques et audit d’accessibilité complet à poursuivre |
| Comptes | Création administrative, accès temporaire 24 h, changement initial obligatoire, MFA TOTP configurable | Aucun e-mail automatique ni compte Microsoft 365 créé |
| Planning | Calendrier horizontal PC, liste mobile, journée/semaine, recherche, export ICS, conflits et indisponibilités | Export ponctuel ; pas d’abonnement calendrier automatique |
| Pédagogie | Ressources privées, devoirs/rendus textuels, notes publiées explicitement, examens/compétences | Règles métier de prototype à valider avec l’organisme |
| Présences | Fenêtre ouverte par le formateur, déclaration personnelle, validation/correction historisée | Pas de signature électronique certifiée ni preuve de présence continue |
| Discussions | Messages persistants entre comptes autorisés, canaux, groupes, fichiers privés ; repli HTTP et WebSocket optionnel | L’administrateur ne lit pas implicitement les conversations privées |
| Fichiers du chat | Validation MIME/Office/macros, scan requis en production ; worker ClamAV privé de démonstration | Le worker dépend d’un ordinateur actif ; pas une infrastructure antivirus permanente |
| Conservation | Messages du chat exclus après 24 h ; commande de purge par lots et nettoyage opportuniste | Choix de prototype à valider ; suppression physique différée sans ordonnanceur |
| Hébergement | Façade Vercel vers PHP/MySQL persistants sur Alwaysdata ; LAMP et Docker locaux | Vercel n’exécute pas le PHP ; configuration, quotas, sauvegardes et exploitation restent à suivre |

Les formulaires publics préparent un récapitulatif ; ils n’envoient pas de demande.
Les appels utilisent une intégration Jitsi/JWT **à configurer**, pas une visioconférence
réalisée dans le projet. Les données de démonstration sont fictives.

## Démarrage recommandé avec Docker

Prérequis : Docker Engine avec Compose (ou Docker Desktop), PHP CLI pour générer
les secrets. Sur Ubuntu, ajouter `sudo` aux commandes Docker si nécessaire.

```bash
git clone https://github.com/Ryoxiis/ExpernetGroupe4.git
cd ExpernetGroupe4
php bin/setup-docker.php
docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker up -d --build
```

Ouvrir **http://127.0.0.1:8080/** et **/connexion/**. Le générateur conserve un
`.env.docker` existant et ne touche pas au `.env` LAMP. MySQL n’est pas exposé à
l’extérieur ; les données et fichiers privés persistent dans des volumes.
Le dump est importé seulement sur un volume vide ; les migrations complètent
ensuite le schéma. **Ne pas réimporter `database.sql` sur une base existante** :
il contient une remise à zéro de tables.

Les comptes de démonstration sont décrits dans [DOCKER.md](docs/DOCKER.md).
Ne jamais utiliser leurs mots de passe publics en production. Les discussions
fonctionnent par HTTP sans activer le service WebSocket optionnel.

Pour intégrer les dernières corrections PHP à une image existante :

```bash
docker compose --env-file .env.docker build --pull app
docker compose --env-file .env.docker up -d --force-recreate app
```

[Guide Docker](docs/DOCKER.md) : installation, volumes, sauvegardes, comptes et tests.

## Linux / LAMP ou PHP natif

PHP **maintenu et corrigé** est requis, avec PDO MySQL, mbstring, fileinfo, sodium
et zip pour les documents Office du chat. Compatibilité de langage PHP >= 8.2 ;
ce minimum ne signifie pas qu’une ancienne version est sécurisée. Pour l’alerte
CERTFR-2026-ALE-014, voir [la note PHP](docs/SECURITE-PHP-CERTFR-2026-ALE-014.md).

```bash
composer install
# Sur une base dédiée configurée dans .env :
php bin/serve-portal.php migrate
php bin/serve-portal.php 8093
```

Sur une base vide, initialiser soit des comptes fictifs (`seed-demo`), soit le
premier administrateur (`bootstrap-admin adresse@expernet.re`) avec ce même lanceur.
Ne pas diffuser les accès temporaires affichés. Pour importer le dump de test sur
un MySQL natif neuf : [PEDAGOGIE.md](docs/PEDAGOGIE.md).

Apache local : `php bin/serve-apache.php 8088`. Installation LAMP système :
`sudo bash bin/install-lamp.sh`, puis http://expernet.local/.
[Guide LAMP](docs/LAMP.md). Le terminal d’un serveur de développement doit rester ouvert.

### Windows

Installer PHP et ses extensions, MySQL et Composer ; exécuter `composer install`,
puis `demarrer-local.cmd`. Ce lanceur prépare une instance MySQL dédiée sur 33306
et ouvre le site sur http://127.0.0.1:8093/. Son fonctionnement complet doit être
vérifié sur le poste Windows. [Prérequis et diagnostic](docs/LOCAL.md).

### Site public seul

```bash
php -S 127.0.0.1:8080 -t public public/router.php
```

Le catalogue fonctionne sans MySQL ni Composer. **Aucun `npm install`, build frontend
ou `npm run dev` n’est requis pour servir le site.** Node intervient dans les tests,
l’audit et la préparation de la façade Vercel.

## Architecture

```text
Navigateur -> public/index.php -> Contrôleur -> Service -> Repository
                                      |                     |
                                      v                  JSON public
                                Vue + layout             ou MySQL privé
```

| Dossier | Rôle |
| --- | --- |
| `app/Controllers/` | Routage applicatif, coordination HTTP et réponses |
| `app/Services/` | Authentification, droits, pédagogie, planning et discussions |
| `app/Repositories/` | JSON du catalogue et requêtes PDO préparées |
| `app/Support/` | Sessions, CSRF, chiffrement TOTP, dates, texte et rendu |
| `app/Views/` | Gabarits, composants partagés et pages pédagogiques |
| `bootstrap/`, `config/` | Chargement de l’environnement, autoload et paramètres |
| `data/` | Relevé des contenus publics ; pas les données privées |
| `database/migrations/` | Quatre migrations, 30 tables déclarées |
| `public/` | **Seul document root**, assets et front controller |
| `storage/` | Documents privés et état local, hors Git |
| `bin/`, `docker/`, `deploy/` | Administration, environnements et déploiement |
| `tests/`, `docs/` | Vérifications, contrats OpenAPI et explications |

Le CSS est chargé du socle vers les composants, puis l’apparence et les corrections
responsive. Le planning utilise des styles numériques autorisés par nonce CSP.
Les droits sont vérifiés côté serveur par rôle, affectation et propriété, jamais
par le seul affichage d’un bouton.

## Vérifier le projet

```bash
npm test
npm run audit:duplicates
npm run audit:duplicates:detailed
npm run test:antivirus
python3 tests/docker_config.py
```

La suite principale couvre le rendu des 294 routes, la conservation de 273 pages
sources et 23 565 blocs, les 36 vues pédagogiques, les validations, la sécurité,
le planning/CSP, les thèmes, le carrousel, les brouillons, la purge et les médias.
Les tests antivirus utilisent des sockets temporaires et un daemon simulé.

Serveur lancé sur 8080 : `python3 tests/http_smoke.py`. Pour un autre port, définir
`TEST_BASE_URL`. Les tests MySQL/HTTP qui créent des comptes doivent cibler une
**base QA isolée**, jamais la production ni la base de travail du binôme.

jscpd recherche les clones applicatifs d’au moins **5 lignes et 50 tokens** ;
l’audit détaillé descend à 3 lignes/25 tokens. Les imports, petites déclarations
CSS et contenus éditoriaux ne doivent pas être supprimés automatiquement.
Résultats et limites du lot : [audit de consolidation](docs/audit/CONSOLIDATION-2026-10-23.md).
GitHub Actions exécute les contrôles de [checks.yml](.github/workflows/checks.yml).
Un test local réussi ne prouve pas une CI distante réussie ou une sécurité complète.

## Comprendre le code et préparer l’oral

- [Présentation du projet, acquis et évolutions](docs/PROJET-ET-EVOLUTIONS.md).
- [Architecture](docs/ARCHITECTURE.md), [portail](docs/PEDAGOGIE.md), [discussions](docs/COLLABORATION.md).
- [Antivirus de démonstration](docs/ANTIVIRUS-PRIVE.md), [configuration PHP](docs/SECURITE-PHP-CERTFR-2026-ALE-014.md).
- [Contrat pédagogique](docs/openapi.yaml) et [contrat discussions](docs/openapi-chat.yaml).

Le dossier PDF de préparation à l’oral et ses sources sont privés au binôme :
ils ne sont pas publiés dans ce dépôt.

## Évolutions prioritaires

1. Valider les droits et procédures avec les équipes Expernet, notamment les présences.
2. Terminer l’exploitation : mises à jour, sauvegardes/restaurations, supervision et antivirus permanent.
3. Finaliser le cycle de vie des comptes et des séances, les e-mails et les notifications.
4. Ajouter un vrai CMS, un abonnement calendrier et des intégrations justifiées par les usages.
5. Qualifier l’accessibilité, les appareils mobiles et la charge ; envisager Redis/worker si nécessaire.

Détail et critères de réussite : [PROJET-ET-EVOLUTIONS.md](docs/PROJET-ET-EVOLUTIONS.md).

## Travail en équipe, confidentialité et droits

Branches courtes, tests puis relecture et fusion ; voir [CONTRIBUTING.md](CONTRIBUTING.md).
Ne jamais versionner `.env`, clés, sauvegardes, mots de passe, pièces jointes ou
bases MySQL. Les exports `.env` du processus priment sur le fichier local.
Le contexte `.ai/` reste local et n’est pas publié.

Les contenus, logos et photos proviennent d’Expernet et d’Expernet Campus et
conservent leurs droits. Les PDF officiels restent externes. Les bibliothèques
conservent leurs licences, dont Bootstrap et Inter. Aucune licence des contenus
Expernet n’est inventée dans ce dépôt.
