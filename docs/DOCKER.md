# Expernet avec Docker

## Ce qui est préparé

| Service | Rôle | Accès depuis le PC |
| --- | --- | --- |
| `app` | Apache, PHP 8.4 et dépendances Composer | http://127.0.0.1:8080/ |
| `database` | MySQL 8.4, comptes et données pédagogiques | Réseau Docker uniquement |
| `chat` | Notifications WebSocket facultatives | Port 8094 local, profil collaboration |

Les discussions fonctionnent déjà sans `chat`, via les requêtes HTTP.
Apache sert uniquement `public/`, et non les sources, secrets ou téléchargements
privés. La base et les fichiers déposés ont chacun un volume Docker persistant.
L’environnement fourni est une démonstration locale, pas un déploiement de production.

## 1. Installer Docker sous Ubuntu

Docker est maintenant installé sur le poste de développement. Pour un nouveau
poste Ubuntu, depuis la racine du projet :

```bash
sudo bash bin/install-docker-ubuntu.sh
```

Le script suit le dépôt officiel Docker pour Ubuntu. Il refuse les paquets
potentiellement incompatibles au lieu de les supprimer automatiquement. Il
n’ajoute pas l’utilisateur au groupe `docker`. Le mot de passe Ubuntu est saisi
dans votre terminal. Vérifier ensuite :

```bash
sudo docker version
sudo docker compose version
```

Référence : https://docs.docker.com/engine/install/ubuntu/
Sur Windows/macOS, installer Docker Desktop et utiliser les commandes Compose
sans `sudo`.

## 2. Préparer les secrets et lancer

```bash
php bin/setup-docker.php
sudo docker compose --env-file .env.docker config --quiet
sudo docker compose --env-file .env.docker up -d --build
sudo docker compose --env-file .env.docker ps
```

Le générateur crée `.env.docker` avec des secrets aléatoires. Il conserve le
`.env` LAMP et ne remplace pas un `.env.docker` existant. Le fichier est ignoré
par Git et exclu de l’image. Éviter de partager la sortie de `compose config`
sans `--quiet`, car elle peut contenir les secrets résolus.

Adresse : http://127.0.0.1:8080/ ; connexion : http://127.0.0.1:8080/connexion/.
Si 8080 est occupé, modifier `DOCKER_HTTP_PORT` dans `.env.docker`, puis relancer.
Le site LAMP `expernet.local` reste indépendant.

## 3. Données au démarrage

Sur un volume MySQL vide, l’image MySQL importe le `database.sql` de la racine.
Sur un volume existant, ce script n’est pas réimporté. Le serveur web attend
que MySQL réponde et que les tables initiales existent, puis exécute les
migrations manquantes avant de démarrer Apache.

Cette attente utilise `depends_on` avec `service_healthy`, conformément à
https://docs.docker.com/compose/how-tos/startup-order/.

Le compte applicatif MySQL est `expernet`, distinct de root. La base porte le
nom `expernet`, comme le dump fourni. Aucun port MySQL n’est publié sur le PC.
Les comptes et échanges déjà présents dans le MySQL LAMP ne sont pas copiés :
Docker démarre son propre jeu de données. Une reprise des conversations LAMP
nécessite un export/import volontaire, séparé de cette initialisation.

## 4. Tester les comptes et les cours

Les comptes de démonstration actifs utilisent initialement
`Test-local-phrase-2026!`. La double authentification est désactivée dans ce seul
environnement de démonstration (`PORTAL_MFA=false`).

- `student.demo@expernet.re` : planning avec les séances de démonstration.
- `trainer.demo@expernet.re` : formateur.
- `admin.demo@expernet.re` : administration.
- `chat01.demo@expernet.re` et `chat02.demo@expernet.re` : échanges entre deux sessions.

Se connecter dans un navigateur et une fenêtre privée, envoyer un message,
répondre puis se reconnecter : le message doit être conservé. Tester le planning
journée/semaine et le téléchargement du support de démonstration.
Voir également `docs/TEST-DISCUSSIONS.md`.

```bash
sudo docker compose --env-file .env.docker exec app php tests/run.php
sudo docker compose --env-file .env.docker exec app php tests/portal_views.php
sudo docker compose --env-file .env.docker exec app php tests/planning_calendar.php
sudo docker compose --env-file .env.docker exec app php tests/php_readability.php
sudo docker compose --env-file .env.docker exec app php tests/chat_seed.php
TEST_BASE_URL=http://127.0.0.1:8080 python3 tests/http_smoke.py
```

Le test HTTP entre comptes utilise un nettoyage MySQL avec le PHP du poste hôte :
ne pas lancer `tests/chat_accounts_http.py` depuis l’hôte contre Docker avec
une configuration MySQL LAMP. Les deux connexions pointeraient vers des bases
différentes. Utiliser le parcours manuel ci-dessus pour ce premier essai Docker.

## 5. Modifier le code, arrêter et redémarrer

Les sources sont copiées dans l’image pour un lancement reproductible. Après
une modification PHP, CSS ou JS :

```bash
sudo docker compose --env-file .env.docker up -d --build
sudo docker compose --env-file .env.docker logs --tail=100 app database
```

Recharger le navigateur. Pour arrêter puis reprendre, en conservant les volumes :

```bash
sudo docker compose --env-file .env.docker down
sudo docker compose --env-file .env.docker up -d
```

Ne pas ajouter `--volumes` ou `-v` à `down` : cette option supprime les données
persistantes. Changer un mot de passe dans `.env.docker` ne change pas celui d’un
utilisateur MySQL déjà créé : conserver les secrets tant que le volume existe.

## 6. Notifications WebSocket facultatives

Dans `.env.docker`, définir `CHAT_WS_URL=ws://127.0.0.1:8094`, puis :

```bash
sudo docker compose --env-file .env.docker --profile collaboration up -d --build
```

Ouvrir le site via `127.0.0.1` ; les origines 127.0.0.1 et localhost sont autorisées
pour le port web configuré. Le service `chat` attend que le serveur web soit prêt.
Le contenu et les droits restent gérés par l’API HTTP authentifiée.

## 7. Sauvegarder

```bash
sudo docker compose --env-file .env.docker exec -T database sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump --no-tablespaces -u "$MYSQL_USER" --single-transaction --default-character-set=utf8mb4 expernet' > expernet-backup.sql
sudo docker compose --env-file .env.docker exec -T app tar -C /var/www/html/storage -czf - . > expernet-storage-backup.tar.gz
```

Conserver les sauvegardes hors Git, avec les secrets nécessaires au déchiffrement
MFA s’il est activé. Le dump contient les messages et comptes. Les fichiers
privés sont dans l’archive du stockage. Ne pas réimporter `database.sql` pour
restaurer des échanges existants : c’est un jeu de départ contenant des DROP TABLE.

## État de vérification

Vérification après installation du 23 octobre 2026 :

- Docker Compose v5.6.1 détecté et `compose config --quiet` réussi.
- Apache du conteneur répond sur le port 8080 (Apache Debian, HTTP 200).
- 294 pages publiques, 3 API, liens et ressources, erreurs et refus d’écriture : OK.
- 67 requêtes pédagogiques : connexion de six comptes, état de la base MySQL,
  pages des trois rôles, planning journée/semaine, export ICS et documents : OK.
- Les deux participants lisent leur conversation privée, un compte d’une autre
  promotion reçoit un refus 403.

Pour relancer les contrôles pédagogiques sans utiliser la connexion MySQL LAMP :

```bash
python3 tests/docker_http.py
```

Ce test ne crée ni message ni donnée métier. Il nécessite les comptes de
démonstration avec leurs mots de passe initiaux et le MFA désactivé.
L’état détaillé et les journaux du moteur n’ont pas pu être lus par l’agent :
le socket Docker est protégé et sudo exige une authentification interactive.
L’arrêt/redémarrage avec vérification de persistance et le WebSocket facultatif
restent à tester. L’envoi de nouveaux messages n’est pas exercé par ce test
HTTP en lecture seule.
