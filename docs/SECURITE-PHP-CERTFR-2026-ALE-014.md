# PHP : alerte CERTFR-2026-ALE-014

Référence consultée le 23 octobre 2026 :
https://www.cert.cyber.gouv.fr/alerte/CERTFR-2026-ALE-014/

## Modification du projet

`docker/php/php.ini` désactive le suivi de progression des téléversements par
session : `session.upload_progress.enabled=Off`. Ce fichier est copié dans
`/usr/local/etc/php/conf.d/expernet.ini` lors de la construction Docker.
Le VirtualHost LAMP et le lanceur Apache local appliquent aussi la directive
`php_admin_flag session.upload_progress.enabled Off` pour leur moteur mod_php.
Les dépôts de fichiers restent autorisés et leurs contrôles applicatifs conservés.

Cette atténuation, non qualifiée par le CERT-FR, ne réduit la surface d’attaque
que pour CVE-2026-109619. Elle ne remplace pas les correctifs de PHP et ne couvre
pas CVE-2026-109618. Un `ini_set()` dans le code PHP serait trop tardif : le
traitement vulnérable peut se produire avant l’exécution de l’application.

Versions corrigées indiquées par l’alerte pour chaque branche : PHP 8.2.35,
8.3.36, 8.4.27 et 8.5.12, ou versions ultérieures de la même branche maintenue.
Pour des paquets de distribution avec correctifs rétroportés, vérifier également
le bulletin du fournisseur ; le numéro amont seul ne suffit pas à les qualifier.
Le CLI local indique PHP 8.5.4 : sans preuve de rétroportage, sa correction reste
à vérifier et sa mise à jour à effectuer. Ne pas considérer l’environnement comme
sécurisé à la seule lecture du nouveau fichier INI.

## Prise en compte

- Docker : `docker compose --env-file .env.docker build --pull app`, puis
  `docker compose --env-file .env.docker up -d --force-recreate app`.
  Si le service optionnel `chat` est utilisé, le recréer aussi avec cette image.
  Ne pas supprimer les volumes MySQL ou de documents.
- Apache local : arrêter puis relancer `php bin/serve-apache.php 8088`.
- LAMP installé : reporter la directive dans le VirtualHost réellement installé,
  vérifier `sudo apache2ctl configtest`, puis recharger Apache.
- PHP-FPM, si utilisé sur un autre hébergement : ajouter dans le pool actif
  `php_admin_flag[session.upload_progress.enabled] = off`, puis recharger PHP-FPM.
- Alwaysdata : appliquer `session.upload_progress.enabled=Off` dans les paramètres
  PHP du site et vérifier la version PHP sélectionnée et le redémarrage effectif.

Aucun service ni configuration système/hébergée n’a été modifié ou redémarré
par ce lot. La configuration du projet est prête pour ces opérations.
La configuration CLI de `/etc/php/` est distincte de celle du moteur Web.
Contrôler la valeur effective avec le même moteur et les mêmes fichiers INI que
le site ; ne pas publier de page `phpinfo()` exposant la configuration du serveur.

L’alerte propose aussi le blocage ou la supervision des requêtes multipart à
l’entrée du serveur. Aucun blocage global n’est ajouté ici : il empêcherait les
dépôts de documents, et une vérification PHP interviendrait trop tard.
En attente de mise à jour, l’exposition des moteurs vulnérables reste à traiter
au niveau de l’hébergement conformément aux recommandations du CERT-FR.

## Vérifications et revue

`php -c docker/php/php.ini -r 'var_export([ini_get("session.upload_progress.enabled"), ini_get("file_uploads")]);'`
confirme que le suivi est désactivé tandis que les dépôts restent activés.
`php -l bin/serve-apache.php` et `python3 tests/docker_config.py` vérifient le
lanceur et la configuration Docker. Aucun conteneur reconstruit ni essai d’exploitation.

Branche : `fix/php-upload-progress-cert`, depuis `main` après récupération distante.
Fichiers : INI Docker, VirtualHost Apache, lanceur Apache et cette note.
Diff : `git diff -- docker/php/php.ini deploy/apache/expernet.conf bin/serve-apache.php`.
Commit proposé après revue : `fix(security): disable PHP session upload progress`.
Aucun commit, push ou déploiement effectué pour ce lot.
