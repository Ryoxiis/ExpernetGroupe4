# Architecture du socle

## Chemin d’une requête

`public/router.php` sert de routeur au serveur de développement. Il ne laisse passer directement que les assets publics autorisés. Les autres requêtes vont vers `public/index.php`.

Le front controller charge `bootstrap/app.php`, normalise la route, vérifie la méthode et consulte la table `config/routes.json`. Une route de page est rendue par `PageController` avec le `ContentRepository` et `View`. Le layout assemble en-tête, contenu et pied de page. Un nom de vue ne vient jamais directement d’un paramètre utilisateur.

Les contrôleurs coordonnent ; les vues présentent ; le repository lit les données. Les règles du futur portail pédagogique doivent aller dans des services métier, avec des contrôles d’affectation et d’autorisation serveur, pas dans les vues.

## Données et API

`data/content.json` est un instantané éditorial, hors du document root. Les API GET `/api/formations/`, `/api/sessions/` et `/api/palettes/` exposent uniquement les données publiques nécessaires au navigateur. Les programmes complets sont rendus côté serveur. Voir `docs/openapi.yaml`.

Il n’y a pas de duplication par formation : une route contient un slug, le repository retrouve la fiche et le même gabarit la rend. Les archives et les thématiques utilisent aussi des gabarits communs.

## Frontend

Le CSS conserve le rendu V2. `base.css` précède `pages.css`, dont les variables et sélecteurs de palette sont prioritaires. Bootstrap reste isolé dans `vendor/` et n’est pas modifié.

Les modules ES natifs sont chargés depuis `main.js`. Seul `theme.js` est chargé avant les styles pour éviter un flash de mauvaise palette. Il expose une fonction limitée de changement de thème. Les données du catalogue ne sont plus stockées dans des variables globales JavaScript générées.

Chaque module vérifie que sa zone existe avant de s’initialiser. Les chargements réseau ont un état d’erreur et un bouton de reprise. Toute chaîne interpolée dans les cartes est échappée. Les fonctionnalités de recherche et de devis ne doivent pas supprimer les libellés, les compteurs accessibles ou le focus clavier.

## Configuration et sécurité

La configuration vient de variables d’environnement, avec des valeurs locales raisonnables pour les paramètres non secrets. Ne jamais versionner `.env`, des mots de passe, dumps réels ou jetons. `.env.example` ne contient que des exemples.

`public/` est le seul répertoire exposé. Les requêtes POST sont refusées par le prototype. Les vues échappent les textes à l’affichage ; `HtmlContent` reconstruit une petite liste de balises sémantiques et refuse les protocoles actifs. Une erreur serveur est journalisée sans afficher sa trace au visiteur.

Ces précautions ne constituent pas une authentification. Si une fonction d’écriture est développée : validation PHP, requêtes PDO préparées, transactions appropriées, permissions par ressource, protection CSRF selon le mécanisme d’authentification et tests des accès directs. Les règles détaillées sont dans `.ai/rules.md` (contexte local ignoré par Git, à transmettre séparément).

## MySQL

La factory PDO est un point d’extension, pas une base active. Le catalogue utilise le repository JSON sans connexion MySQL. Ne créer que les migrations nécessaires au lot en cours, avec clés étrangères, contraintes, index et données fictives pour les jeux pédagogiques.

## Ajouter une page

1. Ajouter un gabarit dans `app/Views/pages/` ou réutiliser un gabarit existant.
2. Ajouter son chemin canonique, son titre et son type dans `config/routes.json`.
3. Ajouter ses liens dans les composants concernés ; utiliser `url()` et `e()`.
4. Vérifier PHP, interactions et HTTP. Adapter le nombre attendu de routes dans les tests si l’ajout est intentionnel.

Les 211 routes initiales doivent rester accessibles lors d’un refactoring. Un changement de contenu ne doit pas réintroduire les fichiers générés V1/V2 ou les agencements V3 rejetés.

## Déploiement

Le serveur PHP intégré et la configuration Docker servent au développement. Pour la production, prévoir Apache avec `mod_rewrite`, ou Nginx/PHP-FPM configuré avec document root `public/` et redirection vers `index.php`. `.htaccess` est fourni pour Apache ; aucune configuration de production n’a été validée ici.

Le démarrage documenté sert l’application à la racine `/`. `APP_BASE_PATH` existe pour les URL lors d’un déploiement en sous-dossier, dont la réécriture des assets devra aussi être configurée et testée sur le serveur cible.

## Contenus des deux sites publics

`data/source-pages.json` conserve le relevé public complémentaire des deux
domaines. `data/source-map.json` traduit les URL sources et leurs alias vers les
routes locales. `HtmlContent` échappe les textes, rend les tableaux et ancres et
réécrit les liens internes connus. Le contrôleur complète les compositions V2
lorsqu’un bloc source n’y est pas présent. Les nouvelles pages réutilisent
`pages/source.php`, `partials/campus-nav.php` et `partials/source-cards.php`.
Le repository charge une seule fois ces données par requête. Les formations
Campus ne sont pas mélangées avec les formations continues de l’API catalogue.
