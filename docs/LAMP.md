# Déployer Expernet sur Linux avec Apache MySQL et PHP

## État et principe

LAMP est utilisé directement : Apache exécute PHP et le portail utilise la base
MySQL persistante indiquée dans `.env`. Aucun processus Node, npm ou serveur PHP
intégré n'est nécessaire. Le catalogue public reste alimenté par ses fichiers JSON.

La racine web est exclusivement `public/`. La configuration, les données et les
fichiers privés restent en dehors. Le chargement de `.env` est commun à Apache,
aux scripts CLI et au serveur de développement.

## Apache local immédiatement utilisable

Avec Apache et mod_php déjà installés sur le PC et MySQL actif :

```bash
cd ~/Documents/cours/ProjetRefonte/expernet-start
php bin/serve-apache.php 8088
```

Ouvrir http://127.0.0.1:8088/ puis http://127.0.0.1:8088/connexion/.
Cet Apache est lancé sous votre utilisateur, limité à la boucle locale et séparé
du service Apache système. Le terminal doit rester ouvert ; Ctrl+C arrête cette
instance, sans arrêter MySQL. Les journaux et le PID sont dans
`storage/apache-local/`, ignoré par Git. La configuration est validable avec :

```bash
php bin/serve-apache.php 8088 --check
```

Les paramètres MySQL locaux sont ceux du `.env` existant, habituellement
`DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=expernet` et un utilisateur dédié.
Ne pas remettre le compte temporaire de test `root` sur le port `33316`.

## Installation dans le service Apache système

Cette étape nécessite le mot de passe administrateur Ubuntu, à saisir uniquement
dans votre terminal. L'assistant ne peut pas saisir ce mot de passe à votre place.

Depuis le dossier du projet :

```bash
sudo bash bin/install-lamp.sh
```

L'installateur :

1. vérifie les outils, extensions PHP et la connexion au MySQL du `.env` ;
2. copie le code exécutable et les dépendances dans `/var/www/expernet`, sans `.git`,
   `.ai`, journaux ou données du serveur MySQL ;
3. crée le `.env` du déploiement uniquement s'il manque, avec accès `root:www-data`
   et permissions `0640`, puis adapte les chemins des fichiers privés ;
4. copie les ressources et pièces jointes existantes sans remplacer les fichiers
   déjà présents, en réservant l'écriture de `storage/` à Apache ;
5. applique les migrations manquantes, sans importer `database.sql` et sans recréer
   les comptes ;
6. installe le VirtualHost `expernet.local`, active les modules requis et conserve
   les autres sites Apache ;
7. contrôle la syntaxe avant de recharger Apache et active Apache/MySQL au démarrage.

Accès après installation :

- http://expernet.local/
- http://expernet.local/connexion/

Le site système est volontairement limité aux accès locaux par `Require local`,
car il s'agit du prototype et de comptes pédagogiques. La configuration actuelle
n'ouvre pas le site au réseau ou à Internet. Un hébergement accessible à d'autres
postes nécessite un nom de domaine/DNS adapté, HTTPS et une configuration d'accès
choisie explicitement, sans exposer les comptes fictifs.

Le code dans `/var/www/expernet` est une copie. Après de nouvelles modifications
validées, relancer l'installateur pour copier la nouvelle version. Il ne supprime
pas les fichiers privés, ne remplace pas un `.env` déjà déployé et ne remet pas la
base à zéro. Vérifier les paramètres du `.env` déployé si la base a changé depuis
la première installation.

Les journaux système sont `/var/log/apache2/expernet-error.log` et
`/var/log/apache2/expernet-access.log`. Le VirtualHost source est disponible dans
`deploy/apache/expernet.conf` avec le marqueur `__PROJECT_ROOT__`.

## Si LAMP n'est pas installé sur un autre Ubuntu

Installer les paquets avant de lancer l'installateur :

```bash
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring php-xml php-curl rsync
```

PHP 8.2+ avec `pdo_mysql`, `mbstring`, `fileinfo` et `sodium` est requis ; le lanceur
local Apache utilise la version de mod_php correspondant au PHP CLI. Vérifier les
extensions effectives du PHP Apache en cas de différence entre CLI et serveur.
Installer les dépendances Composer avant déploiement si `vendor/` manque.

Préparer un compte MySQL dédié et `.env`, puis les migrations :

```bash
php bin/serve-portal.php migrate
```

Créer les comptes de démonstration seulement sur une base vide, ou créer le premier
administrateur selon `PEDAGOGIE.md`. Sur ce PC, la base `expernet` existe déjà :
aucun import de données n'est nécessaire.

## Vérifier l'installation

```bash
TEST_BASE_URL=http://127.0.0.1:8088 python3 tests/lamp_http.py
TEST_BASE_URL=http://127.0.0.1:8088 python3 tests/http_smoke.py
```

Pour le service système, remplacer l'URL par `http://expernet.local`.
Les vérifications LAMP contrôlent Apache, l'exécution effective de PHP, la connexion
MySQL lors d'un refus de connexion attendu, les routes et la protection des fichiers
privés. Elles ne changent ni les comptes ni les données pédagogiques.

Les fonctions non finalisées, dont e-mails et signature électronique officielle,
restent détaillées dans `PEDAGOGIE.md`. Installer Apache ne les active pas.
