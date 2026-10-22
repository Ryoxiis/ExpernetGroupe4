# Design retenu — V2 restaurée, palette Océan

Le propriétaire a préféré la V2 à la proposition V3 et demandé sa restauration.
Conserver cette composition lors du refactoring et des ajouts fonctionnels.

## Références

- Vue d’accueil : app/Views/pages/home.php.
- Structure partagée : app/Views/layouts/main.php et app/Views/partials/.
- Styles : public/assets/css/base.css puis pages.css.
- Palette par défaut : Océan, définie dans pages.css et choisie par theme.js.
- Logos officiels : public/assets/images/logo-officiel-bleu.png et logo-officiel-blanc.png ; ne pas les recolorer ni les redessiner.
- Capture de contrôle : reference-v2.png.

## À préserver

Bleu de lecture #007AA8, bleu nuit #07354A, orange #F0A029, fond #F4F8FA et surface #DFEEF4. Le logo garde ses couleurs de marque. Les autres palettes restent dans l’atelier de comparaison.

Inter locale, titres moins serrés et accents éditoriaux bleus dans la même famille. Navigation blanche collante, bandeau supérieur bleu nuit. Accueil crème en deux colonnes : message à gauche, professionnelle avec ordinateur à droite sur fond arrondi. Recherche et filtres au sein de la section formations, cartes sobres, présentation humaine, formats de parcours, planning bleu nuit, actualités et bandeau de contact.

Conserver les proportions, les espacements, les rayons et les breakpoints existants : 1500, 1100, 800 et 540px. Les styles sont reformattés pour être lisibles, pas réinventés. La V3 avec sa grande accroche « mise à jour », sa navigation en capsule et ses filtres latéraux n’est pas la direction retenue.

Les 149 formations et les 211 routes doivent rester accessibles. Le texte est daté du 22 octobre 2026 ; ne pas inventer de prix, témoignages, statistiques ou disponibilité. Les anomalies de source sont dans docs/audit/.

## Lors d’un changement

Modifier les vues PHP partagées et les modules concernés. Préserver focus visible, navigation clavier, champs libellés, états vides, reprise après erreur et prefers-reduced-motion. Comparer le rendu avant/après aux mêmes dimensions. Exécuter les tests utiles et rendre compte honnêtement des limites.

Typographie révisée le 22 octobre 2026 à la demande du propriétaire : Inter remplace Nunito et Georgia. Le logo et ses lettres ne sont pas modifiés.
