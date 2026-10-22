# Discussions et collaboration

Le module s’intègre à l’espace pédagogique PHP/MySQL/JavaScript, sur la branche
`feat/codex-collaboration`. Ce n’est pas une connexion à Microsoft Teams.

## Réalisé

- Espace Discussions sur `/espace-pedagogique/messages/` et fenêtre flottante sur
  toutes les pages privées après authentification complète.
- Conversations privées uniques par paire ET par formation ; canaux de formation ;
  groupes personnalisés créés et modifiés par l’administration.
- Recherche limitée aux personnes actives autorisées dans la formation.
- Règles par formation : activation, messages privés, fichiers, réunions ; exceptions
  personnelles autoriser/interdire/hériter et journalisation des changements.
- Affectations pédagogiques héritées ; un personnel administratif peut être rattaché
  explicitement au chat sans modifier ses affectations pédagogiques.
- Aucun droit implicite de lecture des conversations privées pour un administrateur.
  Une personne ajoutée à un groupe peut consulter tout son historique : indiqué dans l’UI.
- Messages texte de 5 000 caractères, 30 envois par minute par personne ; reprise
  idempotente, pagination par curseur, récupération des messages après déconnexion.
- Non lus par personne, lecture explicite lorsque la discussion est visible et en bas.
- Pièces jointes privées PDF/PNG/JPEG/TXT/DOCX/XLSX/PPTX, 10 Mo maximum, MIME
  vérifié, conteneurs Office inspectés (macros rejetées), noms serveur aléatoires.
- Scan ClamAV via `CHAT_AV_BINARY` ; fichiers bloqués sans scanner en production.
- Conversations ouvertes et brouillons restaurés après navigation, stockés par
  utilisateur dans sessionStorage (aucun fichier, jeton ou permission dans ce stockage).
- WebSocket Ratchet avec ticket de 60 secondes à usage unique, stocké haché,
  origine autorisée explicite, vérification du compte et renouvellement toutes les
  15 minutes. Notifications d’invalidation sans contenu ; les messages transitent
  exclusivement par l’API qui vérifie les droits. Repli HTTP toutes les 5 secondes.
- Planification des réunions et appel immédiat depuis une conversation ; lien
  d’accès avec JWT de 5 minutes limité à la salle et à la personne autorisée.

## Installation native

PHP 8.2+, PDO MySQL, mbstring, fileinfo, sodium et zip ; Composer pour le daemon.
Le catalogue public reste utilisable sans Composer ni base.

```bash
composer install
php bin/serve-portal.php migrate
php bin/serve-portal.php 8093
```

Les migrations 003 et 004 sont non destructives : elles conservent les anciens
messages de promotion, les copient avec une clé unique dans les nouveaux canaux,
et gardent la table historique. L’ancienne action d’envoi retourne 410 pour ne
pas créer un deuxième circuit de messagerie. Les nouveaux messages ne sont pas
recopiés dans l’ancienne table.

L’administration ouvre « Les échanges », puis « Administrer les droits de
discussion ». Elle choisit une formation et configure ses règles. Pour pouvoir
participer elle-même, elle s’autorise explicitement pour cette formation.

### Temps réel

Dans `.env`, pour l’exemple local :

```dotenv
CHAT_WS_URL=ws://127.0.0.1:8094
CHAT_WS_BIND=127.0.0.1
CHAT_WS_PORT=8094
CHAT_ALLOWED_ORIGINS=http://127.0.0.1:8093
```

Dans un deuxième terminal : `php bin/chat-server.php`. Plusieurs origines exactes
peuvent être séparées par une virgule. Sans `CHAT_WS_URL`, le chat continue par HTTP.
En production : WSS derrière un reverse proxy TLS avec prise en charge Upgrade,
processus supervisé et timeouts adaptés ; ne pas exposer directement le port CLI.
La CSP autorise uniquement l’URL WebSocket configurée en plus de l’origine du site.
Le daemon consulte la base chaque seconde : architecture de prototype mono-instance,
sans Redis ni garantie de latence à grande charge.

### Fichiers et antivirus

Le stockage par défaut est `storage/chat`, hors de `public/`. Une autre destination
privée peut être définie via `CHAT_STORAGE`. Adapter `upload_max_filesize=10M` et
`post_max_size=12M` pour PHP natif ; ces limites sont fournies dans l’image Docker.
Le fichier n’est disponible qu’après succès de l’enregistrement du message.
Les fichiers sont téléchargés en pièce jointe, sans aperçu intégré pour le moment.

`CHAT_AV_BINARY` doit pointer vers un exécutable ClamAV `clamscan` avec signatures
actualisées. `CHAT_REQUIRE_SCAN=true` exige un scan en développement également.
`APP_ENV=production` l’exige toujours. Un code antivirus non nul bloque l’envoi.
L’image Docker n’installe pas ClamAV ni ses signatures : ajouter le scanner dans
l’image de déploiement et maintenir ses signatures avant d’activer les fichiers.
Les formats non acceptés et les fichiers contenant des macros sont refusés.

### Appels, vidéo et partage d’écran

Configurer une instance Jitsi dédiée, HTTPS, avec authentification JWT et refus
des connexions sans jeton. Aucun domaine public ni partage externe automatique
n’est fourni. Définir :

```dotenv
CHAT_MEETING_URL=https://votre-instance-jitsi.example
CHAT_MEETING_APP_ID=identifiant-app-jitsi
CHAT_MEETING_SECRET=secret-configure-sur-votre-instance
```

L’URL de base est l’origine HTTPS du serveur, sans chemin. Le service Jitsi doit
valider la signature HS256, l’issuer, l’audience `jitsi`, le domaine, l’expiration
et le nom exact de la salle (jamais un joker). Une personne non authentifiée ne
doit pas pouvoir rejoindre une salle en devinant ou recevant son nom.
Les jetons sont des capacités temporaires : ne pas les journaliser ni les partager.
La révocation du portail empêche les nouvelles entrées ; expulser une personne déjà
présente nécessite une gestion de session côté fournisseur, non réalisée ici.

L’appel s’ouvre dans une fenêtre du fournisseur, ce qui permet de continuer à
naviguer dans le portail. Audio, vidéo, partage d’écran, modération et capacités
mobiles dépendent du déploiement Jitsi. Ils ne sont pas implémentés dans Ratchet.
Les boutons sont désactivés si l’intégration n’est pas configurée. Pas de création
de compte Microsoft, connexion Graph, enregistrement, transcription ou appels téléphoniques.
Référence : https://jitsi.github.io/handbook/docs/devops-guide/authentication/

## Docker

```bash
docker compose --profile database --profile collaboration up --build
```

Configurer l’origine du site (par défaut `http://127.0.0.1:8080`) et l’URL WS dans
`.env`. Appliquer les migrations dans le service app avec la même configuration.
Les appels restent un service distinct à configurer ; Docker ne démarre pas Jitsi.
Le profil collaboration ajoute uniquement le daemon Ratchet. Les fichiers locaux
`.env`, `.ai`, `.git`, `storage` et `vendor` sont exclus du contexte de l’image.

## Vérifications

```bash
php tests/run.php
php tests/portal_security.php
node tests/frontend.test.js
php tests/content_coverage.php
APP_ENV=test DB_DATABASE=expernet_chat_qa php tests/chat_mysql.php --fixture
```

Le test MySQL refuse de démarrer sans `APP_ENV=test` et nom de base suffixé `_qa`.
Il utilise les identifiants DB habituels et conserve uniquement des fixtures fictives
pour les tests suivants. Il ne supprime ni ne réinitialise une base. Le fichier
`/tmp/expernet-chat-fixture.json` contient seulement des accès QA fictifs.

Démarrer le serveur et le daemon avec cette même base QA, MFA désactivé uniquement
pour ces comptes fictifs, puis :

```bash
TEST_CHAT_URL=http://127.0.0.1:8095 python3 tests/chat_http.py
```

La suite couvre les accès, les pièces jointes, les permissions et les tickets WS.
Contrat HTTP : `docs/openapi-chat.yaml`, référencé depuis `docs/openapi.yaml`.

## Limites de livraison

Pas de synchronisation Microsoft Teams, d’envoi e-mail/push, de présence en ligne,
de réactions, de réponses en fil, d’édition/suppression de messages, d’enregistrement
d’appel ou de transcription. Les réunions apparaissent dans leur conversation,
pas encore dans le planning pédagogique ni dans l’export ICS.
La création des groupes est réservée à l’administration ; l’étudiant dispose des
canaux de ses formations et des conversations privées autorisées.
L’interface mobile est responsive ; qualification mobile réelle, charge, audit
accessibilité complet, Docker et audiovisuel nécessitent une recette dédiée.
Politique de conservation, purge des fichiers, sauvegardes et modération à définir
avant usage réel. Aucun délai de conservation n’est inventé dans le code.

## Recette réalisée sur cette branche

- 43 contrôles MySQL de collaboration réussis, dont deux processus concurrents
  pour la création d’une conversation et la reprise d’un même envoi.
- 21 contrôles HTTP/WebSocket réussis : sessions, CSRF, IDOR, fichiers privés,
  fichiers Office, rejet des macros, politiques, authentification WS, rejeu et origine.
- 890 contrôles PHP existants, 5 contrôles de sécurité PHP, 5 contrôles JavaScript.
- Couverture conservée : 273 pages et 23 563 blocs ; contrôle HTTP de 294 pages,
  3 API publiques, liens, ressources, refus d’écriture et erreurs 404.
- Syntaxe de 64 fichiers PHP vérifiée ; module JavaScript vérifié ; diff sans
  erreurs d’espacement. Composer valide, avec l’avertissement préexistant sur
  l’absence de licence déclarée ; aucune licence n’a été inventée.
- Parcours navigateur : connexion fictive, envoi, maintien de la discussion au
  changement de page, fenêtre compacte et affichage des commandes administratives.
- Migrations 003/004 appliquées à la base locale du portail après la recette QA,
  sans suppression des anciens échanges.
- Portail de la branche lancé sur `http://127.0.0.1:8096/connexion/`, WebSocket
  local sur 8097 ; le port habituel 8093 était déjà utilisé et a été conservé.
- Docker, antivirus ClamAV réel, caméra/microphone et serveur Jitsi non testés.

Pour reproduire les deux processus du portail de la branche :

```bash
CHAT_WS_URL=ws://127.0.0.1:8097 php bin/serve-portal.php 8096
CHAT_WS_PORT=8097 CHAT_ALLOWED_ORIGINS=http://127.0.0.1:8096 php bin/chat-server.php
```

Chaque commande occupe son propre terminal. Les comptes du portail existant sont
réutilisés ; aucun mot de passe réel n’a été changé. La recette QA utilisait une
base distincte avec des personnes et fichiers fictifs.

Commit proposé après review du propriétaire :
`feat(collaboration): add private discussions, chat widget, files and meeting integration`.
Aucun commit ni push n’a été créé.
