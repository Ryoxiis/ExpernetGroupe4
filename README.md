# Expernet Groupe 4

Refonte d’**Expernet** et d’**Expernet Campus**, avec un catalogue public et un espace pédagogique privé. Le projet utilise PHP 8.2+, HTML, CSS et JavaScript natif, sans compilation frontend.

Le site conserve le logo officiel et la palette **Océan — Calme & ancrée**. La navigation a été simplifiée, les images ont été rapprochées des contenus officiels et la police **Inter** est hébergée localement.

## Démarrer tout le site sur Windows

Prérequis : **PHP 8.2+**, avec PDO MySQL, mbstring, fileinfo et sodium, et **MySQL 8**. Composer est nécessaire pour installer les dépendances de la messagerie après un clone.

```powershell
git clone https://github.com/Ryoxiis/ExpernetGroupe4.git
cd ExpernetGroupe4
composer install
```

Double-cliquer ensuite sur **demarrer-local.cmd**, ou exécuter depuis PowerShell :

```powershell
.\demarrer-local.cmd
```

Ouvrir [http://127.0.0.1:8093/](http://127.0.0.1:8093/) et garder le terminal ouvert. L’espace privé se trouve sur [/connexion/](http://127.0.0.1:8093/connexion/).

Le lanceur prépare une instance MySQL propre au projet sur le port **33306**, crée `.env` avec des secrets aléatoires s’il est absent, applique les migrations et initialise cinq comptes fictifs uniquement si aucun compte n’existe. Les accès temporaires s’affichent dans le terminal et expirent après 24 heures ; un changement de mot de passe est obligatoire.

Ne pas supprimer `storage/mysql` : il contient les données locales. Les comptes existants ne sont pas recréés au démarrage. Le premier démarrage complet du lanceur Windows reste à vérifier sur le poste ; voir [le guide local](docs/LOCAL.md) pour les prérequis, le lancement manuel et le diagnostic.

**Aucun `npm install` ni `npm run dev` n’est nécessaire pour lancer le site.** Node sert aux vérifications et à l’audit des doublons.

## Lancer uniquement le site public

Le catalogue public fonctionne sans MySQL et sans installation Composer :

```powershell
php -S 127.0.0.1:8080 -t public public/router.php
```

Ouvrir [http://127.0.0.1:8080/](http://127.0.0.1:8080/). `npm start` lance également cette commande si Node est installé. Le répertoire exposé doit toujours être **public/**. Le serveur PHP intégré est réservé au développement.

## Fonctionnalités

- Catalogue de **149 formations**, recherche, filtres, programmes et calendrier des formations.
- Espace Campus avec **11 diplômes**, les pages pratiques et les actualités des deux sites.
- **294 routes publiques**, avec des gabarits partagés plutôt que des copies HTML.
- Préparation d’une demande de devis et accès aux documents officiels.
- Portail pédagogique avec connexion, rôles administrateur/formateur/étudiant, planning, ressources, travaux, résultats, présences et examens.
- Discussions privées et par groupe, chat et partage de fichiers.

Les données pédagogiques de démonstration sont fictives. L’envoi des accès par e-mail, les appels Jitsi et la signature électronique officielle nécessitent une configuration ou des développements complémentaires. La démonstration locale désactive le MFA avec un avertissement visible ; le mécanisme TOTP reste disponible. Les formulaires publics ne réalisent pas d’envoi automatique.

Voir [le portail pédagogique](docs/PEDAGOGIE.md) et [la collaboration](docs/COLLABORATION.md) pour le détail des fonctions et de leurs limites.

## Organisation du code

```text
app/
  Controllers/       traitement des requêtes HTTP
  Services/          règles métier et autorisations
  Repositories/      lecture JSON et accès MySQL avec PDO
  Support/           outils partagés, sessions et rendu
  Views/             pages, composants et layouts communs
    pedagogy/pages/  écrans du portail séparés par fonction
bin/                 démarrage local, migrations et comptes de démonstration
bootstrap/           chargement de l’application et de l’environnement
config/              routes et configuration
data/                contenus publics importés
database/migrations/ migrations du portail
docs/                guides, architecture, design et audits
public/              seul répertoire exposé par le serveur
  assets/            CSS, JavaScript, images, polices et bibliothèques tierces
tests/               contrôles PHP, JavaScript et HTTP
storage/             fichiers privés et données locales, ignorés par Git
```

Les fonctions communes et les vues partagées évitent la répétition du code. Les commentaires expliquent les blocs utiles. Les dépendances tierces restent séparées du code applicatif.

Pour modifier la navigation : `app/Views/partials/`. Pour une fiche formation : `app/Views/pages/course.php`. Les contenus se trouvent dans `data/content.json` et `data/source-pages.json`, les routes dans `config/routes.json`, les styles dans `public/assets/css/` et les interactions dans `public/assets/js/modules/`.

## Vérifications

Avec PHP et Node disponibles :

```powershell
npm test
php -d extension=sodium tests/portal_security.php
npm run audit:duplicates
```

Si Sodium est déjà activé, omettre `-d extension=sodium`. L’audit utilise jscpd 5.4.2 et peut télécharger cet outil au premier lancement. Il recherche les blocs applicatifs identiques d’au moins 5 lignes et 50 tokens ; les données, migrations, tests et bibliothèques tierces sont hors de ce périmètre.

Avec le serveur public lancé sur le port 8080 et Python installé :

```powershell
python -X utf8 tests/http_smoke.py
```

Pour un autre port, définir `TEST_BASE_URL`, par exemple en PowerShell :

```powershell
$env:TEST_BASE_URL = 'http://127.0.0.1:8093'
python -X utf8 tests/http_smoke.py
```

Les contrôles couvrent notamment les 294 routes, les contenus importés, 36 combinaisons de vues pédagogiques et les références aux ressources. GitHub Actions exécute les contrôles définis dans [checks.yml](.github/workflows/checks.yml). Les limites et résultats détaillés sont dans [VERIFICATIONS.md](docs/VERIFICATIONS.md).

## Images, contenus et design

Les contenus publics proviennent d’[Expernet](https://expernet.re/) et d’[Expernet Campus](https://expernet-campus.re/). Les images intégrées sont stockées localement et les fichiers strictement identiques sont mutualisés. Les PDF et vidéos conservent leurs destinations officielles.

L’inventaire des médias, les corrections d’association et les limites du relevé sont documentés dans [l’audit images et typographie](docs/audit/IMAGES-ET-TYPOGRAPHIE.md). La couverture des contenus figure dans [le rapport Expernet/Campus](docs/audit/COUVERTURE-EXPERNET-CAMPUS.md). Les tarifs et dates du relevé du 22 octobre 2026 restent à confirmer auprès d’Expernet.

## Docker

Le projet dispose d’un environnement Apache/PHP 8.4 et MySQL 8.4 avec données
persistantes. La configuration Docker est séparée du `.env` LAMP.

```bash
php bin/setup-docker.php
docker compose --env-file .env.docker up -d --build
```

Ouvrir http://127.0.0.1:8080/. La base de démonstration est importée uniquement
au premier démarrage d’un volume neuf. Les migrations suivantes sont appliquées
sans réimporter le dump. Les échanges fonctionnent par HTTP ; WebSocket est optionnel.

Docker est installé et le serveur Apache du conteneur répond sur le port 8080.
Les tests HTTP publics et pédagogiques passent. Lire
[le guide Docker](docs/DOCKER.md) pour installer Docker, démarrer, tester, sauvegarder
et utiliser les mises à jour du code.

## Travail en équipe et droits

Lire [CONTRIBUTING.md](CONTRIBUTING.md), [l’architecture](docs/ARCHITECTURE.md) et [la référence de design](docs/design/DESIGN-A-CONSERVER.md). Les consignes IA sont dans `AGENTS.md` et `GEMINI.md`. Le contexte complémentaire `.ai/` est local et se partage séparément avec le binôme.

Ne jamais versionner `.env`, des mots de passe, les données MySQL ou des documents privés. Le lanceur du portail charge `.env` ; les variables déjà présentes dans le processus restent prioritaires.

Les textes, logos et images Expernet servent au prototype et ne sont pas placés sous une nouvelle licence libre. Les composants tiers conservent leurs licences, dont Bootstrap et [Inter](public/assets/fonts/INTER-LICENSE.txt). Les droits de publication doivent être validés avec Expernet.

## Linux avec LAMP

Pour servir le site avec **Apache + PHP et le MySQL persistant** :
`php bin/serve-apache.php 8088`, puis ouvrir http://127.0.0.1:8088/.
Pour installer le site dans le service Apache système :
`sudo bash bin/install-lamp.sh` depuis ce dossier, puis http://expernet.local/.
Le mot de passe administrateur Ubuntu doit être saisi dans votre terminal.
Voir [la procédure LAMP](docs/LAMP.md) pour les prérequis, permissions, tests et limites.
