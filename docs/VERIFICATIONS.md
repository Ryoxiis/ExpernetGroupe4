# Vérifications de livraison

Effectuées le 22 octobre 2026 dans l’environnement de préparation, avec PHP 8.5.4 et Node 22.22.1.

- Syntaxe des 35 fichiers PHP et des modules JavaScript : contrôlée.
- `php tests/run.php` : 641 contrôles réussis, dont rendu de chaque route, contenu conservé, échappement et refus de traversée de vue.
- `node tests/frontend.test.js` : 5 contrôles réussis sur normalisation, échappement et calcul des contrastes.
- `python3 tests/http_smoke.py` contre le serveur PHP : 211 pages en HTTP 200, trois collections API, présence des ressources et destinations locales, 404 pour les chemins internes et refus des écritures POST.
- Navigateur : accueil V2 affiché, catalogue chargé depuis l’API et recherche Photoshop donnant les deux formations attendues.
- `composer validate --no-check-publish` : manifeste valide ; avertissement de licence non renseignée volontairement, les droits du dépôt restant à décider.

Non effectués : construction Docker, démarrage MySQL, pipeline GitHub distant, revue visuelle individuelle des 211 pages, audit complet de sécurité/accessibilité ou test de charge. Docker n’est pas disponible sur ce poste. Aucun serveur de production ni espace pédagogique authentifié n’est livré.

Les tests HTTP supposent le serveur lancé sur `127.0.0.1:8080`. `TEST_BASE_URL` permet de changer sa destination pour ces contrôles.

## Lot Campus - 22/10/2026

294 routes rendues, 890 contrôles PHP et 5 contrôles JavaScript réussis.
Le contrôle de contenu vérifie 273 fiches du relevé et 23 563 blocs conservés.
Le relevé, les anomalies sources et les limites des formulaires/flux externes
sont détaillés dans `audit/COUVERTURE-EXPERNET-CAMPUS.md`.

## Vérification du portail pédagogique le 22 octobre 2026

Tests locaux sur une instance MySQL 8.4 isolée, base fictive `expernet_qa` :
120 contrôles HTTP initiaux, 15 contrôles MySQL (activation, MFA TOTP, rejeu et
concurrence) et 68 contrôles HTTP de régression sur les trois rôles ont réussi.
Les 294 routes publiques et les trois API publiques restent accessibles.
Les contrôles PHP, JavaScript et de couverture des contenus passent également.

Après interruption de la prévisualisation : serveur PHP relancé, MySQL confirmé
actif, et positionnement du menu pédagogique corrigé pour éviter l'héritage du menu
public sur mobile. La date du tableau de bord utilise désormais un format numérique
français. Les tests de lancement Docker et de déploiement de production n'ont pas
été réalisés. Voir `PEDAGOGIE.md` pour reproduire les tests sans données réelles.

## Refactorisation et préparation Windows du 22 octobre 2026

Les contrôles ont été relancés sur PHP 8.5.7 et Node 24 : 890 contrôles PHP,
294 routes publiques rendues, 273 pages et 23 563 blocs de contenu conservés,
5 contrôles de sécurité avec Sodium chargé explicitement, 5 contrôles JavaScript.
Les 36 écrans du portail (12 pages x 3 rôles) sont rendus sans avertissement et
leurs formulaires POST contiennent le jeton CSRF. Une comparaison temporaire
avec le code initial a également confirmé la même structure, les mêmes attributs
et les mêmes textes pour ces 36 écrans, avec données vides puis remplies.
Les vérifications de syntaxe PHP, JavaScript et PowerShell passent.

Le contrôle jscpd 5.4.2 trouve zéro clone dans les fichiers applicatifs audités
avec un seuil de détection de 5 lignes et 50 tokens. Commande :
`npm run audit:duplicates`. Ce résultat ne signifie pas que chaque expression
courte est unique, ni que les sources éditoriales et historiques doivent être effacées.
28 copies d'images ont été regroupées (10 931 318 octets) ;
`node tests/assets.test.js` vérifie 1 508 références et l'absence de fichiers
images identiques.

Les tests HTTP, d'intégration MySQL et navigateur n'ont pas été revalidés pendant
cette intervention : l'environnement isolé empêchait l'accès aux services locaux
et certains contrôles d'écriture natifs échouaient. Les validations MySQL décrites
plus haut restent l'historique d'une autre intervention, pas des validations de
cette refactorisation. Le lanceur `demarrer-local.cmd` et `docs/LOCAL.md` préparent
la validation sur le poste Windows. Il crée .env, les tables et les comptes au
premier démarrage réussi ; leur initialisation complète reste donc à confirmer.

## Ergonomie du site public - 22 octobre 2026

L'accueil propose deux orientations (formation professionnelle / diplôme), puis
la recherche avant le bloc Campus. Les pages institutionnelles sont regroupées
sous le menu natif « Expernet ». « Calendrier des formations » distingue le
planning public du planning privé. L'accès pédagogique reste hors du menu replié.
Les fiches présentent le résumé avant le programme dans l'ordre HTML ; la grille
conserve le résumé à droite sur ordinateur et en premier sur mobile.

Contrôles : 890 vérifications PHP / 294 routes, conservation des 273 pages et
23 563 blocs, tests JavaScript et images réussis. Onze vérifications DOM avec
jsdom ont validé le menu, Échap, le retour du focus, le clic extérieur et l'ordre
des parcours. Le contrôle jscpd est inchangé (5 lignes / 50 tokens).
Le navigateur de l'assistant ne joint pas le serveur local ; le rendu visuel
ordinateur/mobile et le comportement dans un navigateur réel restent à confirmer.
La palette et les fichiers du logo n'ont pas été modifiés.

## Médias officiels et Inter - 22 octobre 2026

L'inventaire final contient 820 médias retournés par les médiathèques publiques,
dont 606 images. 101 fichiers officiels supplémentaires sont stockés localement ;
les visuels sont associés aux bonnes formations et articles. Deux sous-titres IA
ont été ajoutés ; la couverture conserve 273 pages et 23 565 blocs.
Inter Variable locale remplace Nunito et Georgia. Le logo et les couleurs sont inchangés.
Les contrôles PHP, JavaScript, images et doublons passent. Le contrôle HTTP a été
réalisé cette fois : 294 pages, 3 API, liens et ressources, erreurs et refus d'écriture.
Le rendu Inter et les images ont été contrôlés dans le navigateur à 1280 et 390 px.
Voir `docs/audit/IMAGES-ET-TYPOGRAPHIE.md` pour les périmètres et limites exacts.
