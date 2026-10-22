# Espace pédagogique Expernet

## Lancement et diagnostic

Le catalogue public reste alimenté par JSON. L'espace pédagogique nécessite MySQL
et les migrations, puis un serveur PHP **qui doit rester lancé**. Arrêter le
terminal ou interrompre son processus rend l'URL locale inaccessible ; ce n'est
pas une perte des données MySQL.

PHP 8.2+ avec PDO MySQL, mbstring, fileinfo et sodium est requis pour le portail.

1. Préparer `.env` depuis `.env.example`, renseigner les paramètres de votre MySQL
   et générer une clé privée avec `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`.
   La placer dans `APP_KEY`, sans l'ajouter à Git. Conserver cette clé pour pouvoir
   relire les secrets MFA chiffrés. Sur un MySQL natif, `DB_HOST=127.0.0.1` ; le nom
   `database` est réservé au réseau Docker.
2. Appliquer les migrations : `php bin/serve-portal.php migrate`.
3. Sur une base vide, choisir **une seule** initialisation :
   - `php bin/serve-portal.php seed-demo` pour des données fictives et cinq comptes ;
   - `php bin/serve-portal.php bootstrap-admin adresse@expernet.re` pour le premier administrateur.
4. Garder ouverts les accès temporaires affichés une seule fois dans le terminal.
   Aucun e-mail n'est envoyé. Ne pas copier ces accès dans un dépôt ou une capture.
5. Démarrer : `php bin/serve-portal.php 8093`.
6. Ouvrir `http://127.0.0.1:8093/connexion/`. Le serveur reste au premier plan ;
   laisser son terminal ouvert. Ctrl+C l'arrête.

Le lanceur lit `.env` sans exécuter de commande et sans remplacer les variables
qui sont déjà définies dans l'environnement. `bin/portal.php`, utilisé directement,
ne lit pas `.env` : exporter alors les variables avant son exécution.

Si une page ne charge plus : vérifier le terminal PHP, le port puis MySQL. Si la
base est indisponible, le portail retourne un écran d'indisponibilité sans exposer
la trace SQL. Le catalogue public et le formulaire de connexion restent accessibles
sans initialiser MySQL. Une ancienne session privée peut nécessiter la base pour
vérifier si elle est toujours autorisée.

## Routes et parcours

Le bouton **Espace pédagogique** est partagé par l'en-tête et le pied de page.
Les pages publiques Campus restent indépendantes et conservent leur design.

- `/connexion/` : adresse Expernet et mot de passe, sans inscription ni choix de rôle.
- `/connexion/premiere-connexion/` : changement initial obligatoire ; aucun accès
  pédagogique tant que cette étape n'est pas terminée.
- `/connexion/verification/` : inscription ou vérification TOTP, si MFA activé.
- `/connexion/aide/` : récupération auprès de l'administration, après vérification d'identité.
- `/espace-pedagogique/` : tableau de bord adapté au rôle.
- Sous-pages : `planning/`, `seance/?id=…`, `classes/`, `ressources/`, `travaux/`,
  `resultats/`, `presences/`, `messages/`, `parcours/`, `examens/`, `administration/`.
- `document/?id=…` : téléchargement autorisé d'un document privé.
- `calendrier/` : export ICS ponctuel des séances accessibles, sans abonnement automatique.
- `attestation/` : relevé imprimable de démonstration, explicitement non officiel.

Les comptes Microsoft 365/Teams ne sont pas modifiés. L'adresse interne identifie
le compte web, dont le mot de passe est indépendant.

## Fonctions réalisées dans le prototype

- Comptes administratifs avec secret aléatoire temporaire, expiration 24 heures,
  consommation unique, changement obligatoire et session d'activation de 15 minutes.
- Sessions serveur, CSRF, limitation de tentatives, contrôles par rôle et affectation,
  révocation des sessions lors d'une désactivation ou d'une nouvelle invitation.
- MFA TOTP avec clé chiffrée par sodium, rejet du rejeu d'un même code et enrôlement
  manuel dans une application compatible. `PORTAL_MFA=true` par défaut ; la
  désactivation pour un test local est signalée dans l'interface. Pas de QR code.
- Promotions, affectations, création des séances et détection des conflits de
  formateur, promotion, salle ou indisponibilité.
- Indisponibilités immédiatement enregistrées ; une séance existante est conservée
  et le conflit signalé. La résolution opérationnelle reste à préciser avec Expernet.
- Devoirs, rendus textuels ou liens en texte, saisie et publication explicite des notes.
  Les brouillons sont invisibles aux étudiants. Notes sur 20 dans ce prototype.
- Documents privés PDF/TXT/PNG/JPG, jusqu'à 5 Mo, hors du document root et téléchargés
  après contrôle d'accès. Les formats bureautiques et le dépôt de pièces jointes
  aux rendus ne sont pas réalisés.
- Échanges par promotion, livret d'activités, avis identifiés et synthèse de satisfaction.
- Examens, jury de formateurs affectés et grilles de compétences avec historique.

## Présences et limites à respecter

La présence numérique est une **nouvelle fonction**, remplaçant pour le prototype
la procédure papier déclarée. Le formateur ouvre une fenêtre pendant une séance,
pour 15 minutes au maximum et sans dépasser la fin du cours. L'étudiant déclare
pour lui-même ; le formateur valide ou corrige avec un motif historisé.

L'heure est enregistrée côté serveur en UTC et affichée à La Réunion. Une contrainte
unique étudiant/période et les transactions empêchent les doubles déclarations,
y compris concurrentes. Déclaration et validation sont distinctes.

Ce n'est ni une preuve de présence pendant tout le cours, ni un service de signature
électronique conforme, ni une attestation officielle. L'accès à la visioconférence
ne déclenche aucune présence automatique. Les règles d'ouverture, retards,
justificatifs, durée de conservation et signatures doivent être validées avant
une utilisation réelle. Le relevé imprimable conserve ces limites visibles.

## Ce qui n'est pas finalisé

- Envoi des accès par Microsoft 365/SMTP : le secret est affiché une seule fois à
  l'administrateur ; aucune transmission automatique ni réinitialisation publique.
- Circuit officiel de signature, justificatifs et attestations certifiées.
- Délégations fines entre agents administratifs, publication officielle des résultats
  et règles complètes des examens. Les règles présentes sont des propositions de prototype.
- Administration complète du cycle de vie : réactivation d'un compte désactivé,
  modification/suppression des séances et retrait d'affectation restent à développer.
- Notifications automatiques et visioconférence intégrée : aucun service externe connecté.
- Déploiement de production, sauvegarde/restauration et audit RGAA complet.

## Vérifications reproductibles

Sans serveur ni base :

```bash
php tests/run.php
php tests/content_coverage.php
php tests/portal_security.php
node tests/frontend.test.js
```

Avec serveur : `TEST_BASE_URL=http://127.0.0.1:8093 python3 tests/http_smoke.py`.

Les tests pédagogiques sont réservés à une **base isolée de données fictives** :

- `tests/pedagogy_http.py` exige une base fraîchement initialisée avec `seed-demo
  --json`. Passer le chemin du JSON temporaire dans `TEST_PORTAL_ACCOUNTS` et l'URL
  dans `TEST_PORTAL_URL`, avec MFA désactivé pour ce test. Il active et modifie des
  comptes fictifs ; ne pas l'exécuter sur une base utilisée pour une vraie formation.
- `tests/pedagogy_regression.py` parcourt les trois rôles sur des comptes de démonstration
  déjà activés. Paramètres : `TEST_PORTAL_URL` et `TEST_PORTAL_PASSWORD`. Il ne modifie
  aucune donnée métier mais ouvre/ferme des sessions et teste un refus CSRF.
- `tests/pedagogy_mysql.php` exige `APP_ENV=test` et une base dont le nom finit par
  `_qa`. Il crée ses propres données fictives et teste MFA, expiration, rejeu et
  concurrence d'émargement. L'extension pcntl est nécessaire à ce test concurrent.

Ne jamais publier le fichier JSON contenant les secrets temporaires. Les migrations
sont versionnées et ne suppriment pas une base existante ; les jeux de démonstration
refusent de remplacer une base contenant déjà des comptes.
