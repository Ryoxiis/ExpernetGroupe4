# Expernet — base de démarrage de la refonte

Socle PHP / HTML / CSS / JavaScript pour le travail en binôme. Le rendu est celui de la **V2 restaurée**, avec **Océan — Calme & ancrée** par défaut. Le logo officiel, 149 formations et les 211 routes sont conservés.

**Aucun `git init`, commit, dépôt distant ou secret n’est fourni.** Ce dossier est prêt à devenir votre dépôt d’équipe.

## Lancer en local

Prérequis : PHP **8.2 ou plus**. Aucun paquet Composer ou npm à installer pour lancer le site.

Depuis ce dossier :

```bash
php -S 127.0.0.1:8080 -t public public/router.php
```

Puis ouvrir **http://127.0.0.1:8080/**. Le document root doit toujours être `public/`, jamais la racine du projet. Le serveur PHP intégré est destiné au développement, pas à la production.

Autres entrées utiles : `/formation/`, `/planning/`, `/demande-de-devis/`, `/palettes/` et `/plan-du-site/`.

## Arborescence

```text
app/
  Controllers/       orchestration des pages
  Repositories/      lecture des données du catalogue
  Support/           vues, échappement HTML, connexion PDO optionnelle
  Views/
    layouts/         document HTML commun
    partials/        en-tête, pied de page, cartes et composants partagés
    pages/           gabarits et pages éditoriales
bootstrap/           chargement de l’application et autoload
config/              configuration et table des routes
data/               instantané public des contenus, palettes et sessions
database/migrations/ emplacement documenté des futures migrations
docs/               architecture, API, design, audit et contexte du projet
public/              seul répertoire exposé par le serveur
  assets/
    css/             styles du projet, lisibles et séparés du code fournisseur
    js/modules/      catalogue, navigation, planning, devis et palettes
    images/          images et logos
    fonts/           polices locales
    vendor/          Bootstrap et son attribution
tests/              contrôles PHP, JavaScript et HTTP
docker/             configuration de développement
```

## Comment modifier le projet

- En-tête / pied de page : `app/Views/partials/`.
- Composition HTML d’une fiche : `app/Views/pages/course.php` ; une modification s’applique aux 149 formations.
- Catalogue et articles : leurs gabarits PHP partagés, pas 211 copies de pages HTML.
- Textes et données importés : `data/content.json`. Cet instantané n’est pas une base métier pédagogique.
- Couleurs : `public/assets/css/pages.css` ; palette par défaut dans `public/assets/js/theme.js` et données de comparaison dans `data/palettes.json`.
- Interactions : un module par fonctionnalité dans `public/assets/js/modules/`.
- Nouvelle route : `config/routes.json`, avec un gabarit connu ; voir `docs/ARCHITECTURE.md`.

Les vues éditoriales reprennent le contenu validé de la V2 ; il n’y a plus de génération Python ni de compilation frontend. Les noms des classes CSS ont été préservés pour éviter une régression graphique.

## Vérifier avant une pull request

```bash
php tests/run.php
node tests/frontend.test.js
```

Avec le serveur PHP lancé dans un autre terminal :

```bash
python3 tests/http_smoke.py
```

Node et Python ne sont nécessaires que pour ces contrôles complémentaires. La configuration GitHub Actions est fournie dans `.github/workflows/checks.yml`, mais n’a pas été exécutée sur un dépôt distant.

## Docker et MySQL optionnels

```bash
cp .env.example .env
# Remplacer les mots de passe d’exemple dans .env.
docker compose up --build
```

Pour démarrer également MySQL :

```bash
docker compose --profile database up --build
```

La base n’est pas requise par le prototype et aucune migration métier n’est exécutée. La connexion PDO est disponible dans `App\Support\Database` pour le prochain lot qui en aura besoin. Docker n’est pas installé dans l’environnement de préparation : cette configuration reste à vérifier sur votre poste.

En exécution native, PHP lit les **variables d’environnement du processus** ; il ne charge pas automatiquement `.env`. Docker Compose utilise `.env` pour ses substitutions. Voir `docs/ARCHITECTURE.md`.

## Périmètre fonctionnel et limites

Fonctionnel : navigation, catalogue, filtres, pagination, programmes, planning filtrable, comparaison de palettes et préparation locale d’un devis.

Le devis n’est ni envoyé ni enregistré. L’utilisateur peut ensuite ouvrir sa messagerie ou le formulaire officiel. Aucune authentification, gestion des comptes, rôle, note ou présence pédagogique n’est implémenté. Les règles du portail sont des exigences de contexte, pas des fonctionnalités déjà réalisées.

Les contenus sont un relevé public du 22 octobre 2026. Les tarifs et dates doivent être confirmés. Les anomalies identifiées restent documentées dans `docs/audit/`. Les PDF et vidéos restent hébergés sur les destinations officielles.

## Travailler en équipe

Lire `CONTRIBUTING.md`, `docs/ARCHITECTURE.md` et `docs/design/DESIGN-A-CONSERVER.md`. Les consignes IA sont dans `AGENTS.md` et `GEMINI.md`. Les règles et le skill du remplacement de GALIA sont regroupés dans `.ai/`, avec le design, la palette et les conventions de code. Ce dossier est ignoré par Git : le partager séparément avec le binôme. AGENTS.md et GEMINI.md servent de points d’entrée. Les fichiers 67.php et motifs bananes du sujet sont des pièges explicitement écartés, pas des exigences à coder.

## Droits

Les textes, logos et images Expernet servent au prototype de refonte ; ils ne sont pas placés sous une nouvelle licence libre. Bootstrap garde sa licence MIT dans son CSS. La licence du futur dépôt et les droits de publication sont à décider par l’équipe et Expernet.

## Campus et couverture des contenus

L’accueil et le menu donnent accès à la refonte locale d’Expernet Campus :
`/campus/`. Elle reprend ses 11 diplômes, 36 articles, pages pratiques et archives,
avec les composants V2 et la palette Océan. Voir
[le rapport de couverture](docs/audit/COUVERTURE-EXPERNET-CAMPUS.md) et
[la correspondance des URL](docs/audit/couverture-pages.csv).

Le contrôle supplémentaire `php tests/content_coverage.php` vérifie que les
textes du relevé sont présents dans les pages rendues. Les formulaires Campus
sont présentés sans envoi ; les documents PDF restent sur les sources officielles.

## Espace pédagogique privé

Le bouton **Espace pédagogique** ouvre `/connexion/` puis le tableau de bord du
rôle connecté. Cette partie utilise MySQL, contrairement au catalogue public.
Pour les migrations, l'initialisation des comptes, les fonctions réalisées et leurs
limites, suivre [le guide du portail pédagogique](docs/PEDAGOGIE.md).

Lancement avec lecture de votre `.env` et contrôle préalable de MySQL :
`php bin/serve-portal.php 8093`. Le terminal doit rester ouvert pendant la navigation.
L'envoi des mots de passe par e-mail et la signature électronique officielle ne
sont pas configurés ; le portail est un prototype avec données fictives.
