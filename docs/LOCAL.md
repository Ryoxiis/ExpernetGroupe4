# Tester Expernet sur ce poste

Depuis la racine du projet, double-cliquer sur **demarrer-local.cmd**.
Le terminal doit rester ouvert. Ouvrir ensuite http://127.0.0.1:8093/connexion/.

Le lanceur Windows :

1. utilise PHP installé sur le poste et active Sodium pour ce processus si nécessaire ;
2. démarre une instance MySQL propre au projet, sur 127.0.0.1:33306, dans storage/mysql ;
3. crée .env avec des secrets aléatoires si ce fichier n'existe pas ;
4. applique les migrations manquantes ;
5. crée les cinq comptes fictifs seulement si aucun compte n'existe ;
6. affiche leurs accès temporaires et démarre le site.

Les accès temporaires expirent après 24 heures. Au premier accès, choisir un mot
de passe personnel. Les adresses sont admin.demo@expernet.re,
trainer.demo@expernet.re, trainer2.demo@expernet.re, student.demo@expernet.re
et student2.demo@expernet.re. Aucun e-mail n'est envoyé. Conserver les accès
quand ils s'affichent : les démarrages suivants ne recréent pas les comptes.

La démonstration locale désactive le MFA, avec un avertissement visible. Le code
MFA reste présent et peut être testé en passant PORTAL_MFA=true dans .env.
La messagerie utilise le rafraîchissement HTTP ; aucun daemon WebSocket n'est
nécessaire. Les appels exigent une configuration Jitsi séparée et restent indisponibles.

Le lanceur ne modifie pas le service MySQL déjà installé sur le port 3306, ni le
php.ini global. Il conserve .env et les comptes existants. À l'arrêt du serveur,
il demande l'arrêt de l'instance MySQL qu'il a démarrée. Ne pas supprimer
storage/mysql pour relancer : ce dossier contient les données locales.

## Lancement manuel

Avec sa propre instance MySQL : copier .env.example vers .env, renseigner les
paramètres et APP_KEY, puis utiliser :

```powershell
php -d extension=sodium bin/serve-portal.php migrate
php -d extension=sodium bin/serve-portal.php seed-demo
php -d extension=sodium bin/serve-portal.php 8093
```

Ne charger seed-demo que sur une base vide. Si Sodium est déjà activé dans
php.ini, omettre `-d extension=sodium`.

Pour le catalogue public seul, aucune base n'est nécessaire :

```powershell
php -S 127.0.0.1:8080 -t public public/router.php
```

Les dépendances de la messagerie ont été installées avec Composer. Pour les
réinstaller après un clone : `composer install`. Aucun build frontend n'est nécessaire.

## Contrôles

```powershell
php tests/run.php
php tests/content_coverage.php
php -d extension=sodium tests/portal_security.php
php tests/portal_views.php
node tests/frontend.test.js
node tests/assets.test.js
npm run audit:duplicates
```

`audit:duplicates` utilise jscpd 5.4.2 et nécessite Node et un accès npm au premier
lancement. Il contrôle les blocs d'au moins 5 lignes et 50 tokens du code applicatif.
Les migrations historiques, données sources, tests, dépendances et documents ne
sont pas des copies de logique applicative à supprimer automatiquement.

Les tests HTTP et MySQL nécessitent des services réellement accessibles. Ils
n'ont pas pu être validés dans l'environnement isolé de l'assistant : les connexions
vers les services démarrés n'aboutissaient pas et les contrôles d'écriture natifs
refusaient certains fichiers. Le premier démarrage complet doit être vérifié sur
le poste Windows avec le lanceur. Un message rouge et les journaux MySQL dans
storage/mysql permettent de diagnostiquer un échec.
