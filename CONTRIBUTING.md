# Travailler en binôme

## Avant le premier dépôt

Le dossier ne contient aucun historique Git. Le responsable d’équipe initialise le dépôt, choisit le nom de branche principale et configure le dépôt distant. Aucune de ces opérations n’a été faite automatiquement.

## Cycle conseillé

- Une branche courte par sujet : `feat/catalogue-search`, `fix/mobile-menu`, `docs/setup`.
- Se mettre d’accord sur le contrat avant de modifier à deux le même gabarit.
- Un changement ciblé par pull request ; l’autre personne relit avant fusion.
- Des commits au format Conventional Commits : `feat:`, `fix:`, `refactor:`, `docs:`, `test:`.
- Indiquer dans la PR : problème traité, comportement obtenu, contrôles effectués, limites.
- Garder les données privées et la configuration locale hors du dépôt.

## Définition de terminé

Le lot est limité et ses états vides/erreurs sont gérés. Les contrôles PHP, JavaScript et HTTP pertinents passent. Le rendu est examiné au clavier et sur une largeur étroite. Les modifications de contrat API sont documentées. Le design V2 et la palette Océan sont conservés sauf demande explicite.

## Répartition possible

Une personne travaille sur vues et CSS ; l’autre sur contrôleur, repository et API. Cette répartition est une proposition d’organisation, pas une restriction. Découper les prochains modules pédagogiques par fonctionnalité et faire relire les règles de sécurité par les deux membres.

## Dépendances

Le socle s’exécute sans installation de dépendances. Si vous en ajoutez, justifiez leur utilité, versionnez les fichiers de verrouillage correspondants et documentez la commande d’installation. Ne modifiez pas les sources Bootstrap ; ajoutez les surcharges dans les styles du projet.
