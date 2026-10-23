# Livraison locale LAMP

Intervention du 23 octobre 2026 sur la branche `feat/deploiement-lamp`, créée depuis
`main` après récupération de `origin/main`. Aucun commit ni push. Le fichier
`docs/PEDAGOGIE.md` modifié et `bin/setup-local-db.php` non suivi étaient déjà présents
et ont été conservés sans modification dans ce lot.

## Changements

- `bootstrap/app.php` charge le lecteur `.env` existant pour les requêtes Apache.
- `bin/serve-apache.php` prépare et lance un vrai Apache local avec mod_php,
  uniquement sur `127.0.0.1`, et le MySQL persistant existant.
- `deploy/apache/expernet.conf` définit le VirtualHost, la racine `public/`,
  l'exécution PHP et les restrictions d'accès aux fichiers privés.
- `bin/install-lamp.sh` prépare la copie sous `/var/www/expernet`, les permissions,
  les répertoires privés, les migrations et le service système. Il refuse un
  démarrage sans droits root ou configuration de base accessible.
- `tests/lamp_http.py` vérifie Apache/PHP/MySQL sans modifier les données métier.
- `tests/http_smoke.py` accepte le refus Apache 403 pour `.env`, en plus du 404
  du serveur PHP de développement. L'accès au fichier doit toujours être refusé.
- `docs/LAMP.md` et le README documentent lancement, installation et diagnostic.

## Résultats

Sur le PC : Apache et MySQL système sont actifs ; la base `expernet` contient six
comptes. Aucune base ni aucun compte n'ont été remplacés.

La configuration de l'Apache local passe `Syntax OK`. L'URL
`http://127.0.0.1:8088/connexion/` retourne du HTML exécuté par
`Apache/2.4.66 (Ubuntu)`, et non du code PHP. Les 13 contrôles HTTP LAMP passent.
Les tests PHP, couverture des contenus, 36 écrans pédagogiques, JavaScript et
728 références d'images passent également.

L'installation du VirtualHost système n'a pas été exécutée : `sudo` exige une
saisie interactive du mot de passe Ubuntu. Elle est prête à lancer avec
`sudo bash bin/install-lamp.sh`. Elle doit être testée par le propriétaire avant
validation du lot. Le contrôle de syntaxe shell passe ; cela ne remplace pas une
exécution réelle de l'installateur avec les privilèges système.

Le serveur local n'est pas un service de démarrage automatique ; laisser son
terminal ouvert. L'installation système prévue active le démarrage automatique.
Les configurations fournies sont locales : accès réseau public et HTTPS ne sont
pas configurés.

## Vérification du propriétaire

Ouvrir le site sur le port 8088. Tester la connexion avec un compte existant, le
planning, les ressources et la messagerie, puis installer le service système si
souhaité. Le diff local et les nouveaux fichiers sont à relire avant tout commit.
Proposition de commit après validation :
`feat(lamp): ajouter le lancement Apache et le déploiement système local`.
