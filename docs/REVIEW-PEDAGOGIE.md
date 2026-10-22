# Vérification et stabilisation du portail pédagogique

Date : 22 octobre 2026. Intervention : vérification après interruption de la
prévisualisation. Branche : `fix/pedagogie-stabilisation`, créée depuis le `main`
local. La fraîcheur distante de `main` n'a pas été vérifiée. Aucun commit ni push.
Les modifications Campus déjà présentes dans le dossier sont conservées et ne
sont pas attribuées à cette correction.

## Diagnostic et correction

Le processus PHP utilisé pour la prévisualisation n'était plus disponible après
l'interruption ; l'URL locale ne pouvait plus répondre. MySQL était encore actif.
Le serveur PHP a été relancé sur `127.0.0.1:8093` avec la base fictive isolée.
Cela établit la cause de l'indisponibilité de la prévisualisation ; cela ne permet
pas d'attribuer un éventuel arrêt de l'application Codex elle-même au code du site.

Un défaut visuel distinct a été reproduit : sur petit écran, le menu pédagogique
héritait du positionnement absolu et de l'orientation verticale du menu public.
Dans `public/assets/css/pedagogy.css`, ses position, direction et espacements sont
maintenant explicites. La navigation reste dans son conteneur, avec défilement
horizontal, sans recouvrir le contenu. Le document ne déborde pas horizontalement.
La date de `app/Views/pedagogy/portal.php` utilise un format numérique français.

`bin/serve-portal.php` lit une configuration `.env` sans l'exécuter, vérifie la
base et les tables, puis lance PHP au premier plan. `app/Support/Database.php`
borne le délai de connexion à trois secondes. Voir `PEDAGOGIE.md` pour le lancement
et les limites fonctionnelles du prototype. `.env.example` documente les paramètres
pédagogiques. Aucun secret n'a été ajouté aux fichiers versionnés.

## Vérifications réalisées

- 890 contrôles PHP sur les 294 routes publiques.
- 5 contrôles JavaScript et 5 contrôles de sécurité PHP.
- Couverture : 273 pages de contenu et 23 563 blocs conservés.
- 68 contrôles HTTP de régression sur les comptes fictifs étudiant, formateur et
  administrateur : écrans, fiches séances, documents, calendrier, refus CSRF et déconnexion.
- HTTP public : 294 pages, trois API, ressources et refus des écritures publiques.
- Vérification visuelle : connexion puis tableau de bord étudiant, navigation en
  largeur étroite et absence de recouvrement après correction.
- Les tests initiaux du portail avaient aussi validé 120 contrôles HTTP et 15 contrôles
  MySQL, dont expiration, TOTP, rejeu et déclarations de présence concurrentes.

Commandes reproductibles et précautions pour les bases de test : `PEDAGOGIE.md`.
Nouveau test répétable sur les comptes de démonstration déjà activés :
`TEST_PORTAL_URL=http://127.0.0.1:8093 python3 tests/pedagogy_regression.py`.

## Ce que le propriétaire peut vérifier

1. Ouvrir `http://127.0.0.1:8093/espace-pedagogique/` : l'onglet de démonstration
   étudiant a été reconnecté. En cas de session absente, utiliser la connexion.
2. Réduire la largeur : le menu ne doit pas recouvrir « Bonjour » ni le prochain cours.
3. Ouvrir planning, séance, ressources, travaux et présences : aucune erreur serveur.
4. Consulter le fonctionnement administrateur et formateur avec les comptes fictifs.
5. Pour redémarrer depuis votre terminal, renseigner `.env`, suivre `PEDAGOGIE.md`
   et garder `php bin/serve-portal.php 8093` ouvert. Le port doit être libre.

## Limites et review

La prévisualisation utilise une base de test et le MFA y est explicitement désactivé ;
ce choix ne constitue pas une configuration de production. L'envoi d'e-mails et la
signature électronique officielle ne sont pas réalisés. Les attestations sont des
relevés de démonstration. Docker et le déploiement de production ne sont pas validés.
Les possibilités manquantes d'administration sont détaillées dans `PEDAGOGIE.md`.

Les deux migrations `001_pedagogy.sql` et `002_schedule_lock.sql` ajoutent les tables
privées et le verrou de planification. Ne pas lancer les tests d'initialisation ou
les fixtures contre une base de formation réelle. Le catalogue reste indépendant.

Le diff est disponible localement sur la branche ; les fichiers encore non suivis
font aussi partie du travail à relire et ne sont pas inclus dans `git diff` seul.
Les modifications publiques/Campus d'une autre intervention doivent être séparées
avant toute indexation. Proposition de message pour la correction après review :
`fix(pedagogie): stabiliser la navigation mobile et le lancement local`.
