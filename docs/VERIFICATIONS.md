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

## Lot Campus — 22/10/2026

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
