# Environnement Docker Expernet

Responsable : Codex. Branche `feat/docker`, créée depuis main puis avancée en
fast-forward au commit existant `1725769` pour conserver les derniers ajouts
pédagogiques. Main n’a pas été modifiée. Les modifications préexistantes de
`docs/PEDAGOGIE.md` et `bin/setup-local-db.php` sont conservées hors de ce lot.
Pas de nouveau commit ni push pour le lot Docker. Pas de récupération distante
nouvelle : la fraîcheur de main n’a pas été revérifiée.

## Changements

- `compose.yaml` : app Apache/PHP, MySQL démarré par défaut, attente du healthcheck,
  volumes de données et stockage, profil optionnel WebSocket, ports loopback.
- `docker/php/` : image PHP 8.4 Apache, extensions, Composer, document root public,
  configuration PHP et exécution des migrations avant démarrage.
- Ancien Dockerfile du chat supprimé : app et chat utilisent la même image.
- `docker/fixtures/guide-architecture.txt` : document de démonstration référencé
  par le dump, présent lors de la création du volume de stockage Docker.
- `.env.docker.example`, `bin/setup-docker.php` : configuration indépendante de
  LAMP et génération de secrets aléatoires, sans remplacement si le fichier existe.
- `.gitignore` / `.dockerignore` : secrets, sauvegardes et contexte privé exclus.
- `bin/install-docker-ubuntu.sh` : installation depuis le dépôt officiel Docker,
  sans suppression de paquets incompatibles ni ajout automatique au groupe docker.
- README et `docs/DOCKER.md` : démarrage, tests, mises à jour et sauvegardes.
- `tests/docker_config.py` : structure et invariants de configuration, génération
  des secrets en répertoire isolé, permissions et idempotence.

L’environnement est local et utilise une nouvelle base. Il ne migre pas les
messages de MySQL LAMP. Aucune donnée locale existante n’a été effacée ou importée.
Le dump ne s’exécute qu’au premier démarrage d’un volume MySQL neuf.

## Vérifications réalisées

- `python3 tests/docker_config.py` : OK, YAML et invariants vérifiés avec PyYAML.
- `php -l bin/setup-docker.php` : OK.
- `bash -n bin/install-docker-ubuntu.sh` / `sh -n docker/php/entrypoint.sh` : OK.
- `php tests/run.php` : 890 contrôles et 294 routes OK.
- `php tests/php_readability.php` : 40 contrôles OK.
- `php tests/planning_calendar.php` : OK.
- `php tests/portal_views.php` : 36 écrans OK.
- `.env.docker` généré sur le poste, ignoré par Git ; deuxième exécution sans
  modification. Le .env LAMP est conservé.
- `git diff --check` : OK.

Docker est absent du poste. L’installation via sudo non interactif a échoué :
`interactive authentication is required`. Le propriétaire doit exécuter le
script dans son terminal pour saisir son mot de passe. Les images ne sont donc
pas construites, `docker compose config` n’est pas exécuté et les conteneurs,
healthchecks, migration initiale, reprise et WebSocket ne sont pas validés en
conditions réelles. Les tests statiques ne remplacent pas ces essais.

## Étape suivante concrète

Depuis la racine du projet :

```bash
sudo bash bin/install-docker-ubuntu.sh
sudo docker compose --env-file .env.docker config --quiet
sudo docker compose --env-file .env.docker up -d --build
sudo docker compose --env-file .env.docker ps
```

Ouvrir http://127.0.0.1:8080/ puis suivre les tests de `docs/DOCKER.md`.
Contrôler les connexions, les messages et leurs permissions, le planning, les
uploads et téléchargements, puis arrêt/redémarrage avec conservation des volumes.
Le test HTTP de discussions existant utilise le PHP et la base du poste pour
son nettoyage : ne pas le lancer contre Docker sans adaptation.

À relire : image limitée à la démonstration locale, MFA désactivé par défaut dans
ce seul environnement, absence de reprise automatique des données LAMP et
importance de conserver les secrets avec leurs volumes existants.

Proposition de commit après essais et accord du propriétaire :
`feat(docker): add isolated Apache PHP and MySQL development environment`


## Mise à jour après installation par le propriétaire — 23 octobre 2026

Docker Compose v5.6.1 est disponible. `docker compose --env-file .env.docker
config --quiet` passe. Apache Debian répond HTTP 200 sur le port 8080.
Le propriétaire a effectué l’installation et le lancement.

Les tests `http_smoke.py` passent sur ce port : 294 pages et 3 API, ressources,
liens et refus des écritures. Nouveau test `tests/docker_http.py` : 67 requêtes
pédagogiques réussies, six connexions, état MySQL, pages par rôle, séances,
calendrier, téléchargement du document et accès privé limité aux participants.
Ce test ne dépend pas de la connexion MySQL hôte et ne crée aucun message.

Les limites d’accès au moteur subsistent : `docker compose ps` et les journaux
sont refusés au compte courant, et sudo exige une authentification interactive.
Le redémarrage avec contrôle de persistance et le service WebSocket optionnel
n’ont pas été testés. Les limites de la vérification initiale ci-dessus restent
un historique ; le démarrage web et les parcours HTTP sont désormais vérifiés.
