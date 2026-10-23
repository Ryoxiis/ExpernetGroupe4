# Refonte Expernet et Campus - contrôle du 22 octobre 2026

Le relevé couvre les deux sites publics : https://expernet.re/ et
https://expernet-campus.re/. Il croise les sitemaps WordPress, les API publiques
pages/articles/formations et les liens HTML internes. La navigation du Campus
est intégrée à la refonte sous `/campus/`. La V2 et la palette Océan sont conservées.

## Pages et contenus repris

- **294 routes locales** : les routes V2 existantes et les pages ajoutées.
- **149 formations continues** et leurs programmes.
- **11 diplômes Campus**, avec objectifs, prérequis, métiers, validation,
  sélection, rythme, rentrée, interlocuteurs et liens vers les documents publiés.
- **28 articles Expernet et 36 articles Campus**, avec leur texte intégral relevé.
- **20 pages éditoriales Campus** : accueil, école, équipe, locaux, partenaires,
  informatique, marketing, Mayotte, actualités Mayotte, FAQ, candidature, contact,
  plan du site, mentions, confidentialité, cookies, confirmations et ancien accueil.
- Les archives, leurs paginations et les deux pages d’atterrissage publiques
  présentes dans le sitemap Campus ont aussi une route dans la refonte.
- Deux pages d’informations pratiques conservent les coordonnées et liens utiles
  complémentaires des pieds de page originaux.

Le relevé possède 271 pages sources canoniques, auxquelles s’ajoutent ces deux
pages pratiques. `couverture-pages.csv` donne la correspondance de chaque URL
réelle visitée. Les redirections du site source sont rattachées à leur destination.
Les pages de thématiques V2 sont conservées même lorsque le site d’origine
redirige désormais son ancienne URL vers le catalogue.

## Vérification du contenu

`data/source-pages.json` contient les blocs de texte importés, tableaux, liens,
images et références de documents. Le rendu ne fait pas confiance au HTML source :
les textes sont échappés et les protocoles des liens sont contrôlés. Les textes
manquants dans les compositions éditoriales V2 sont présentés dans une section
« Les informations en détail ». Les tableaux sont conservés. Les ancres de
navigation relevées sont conservées. Les compteurs Elementor utilisent leur valeur
finale, et non le zéro initial de l’animation.

Le test `php tests/content_coverage.php` vérifie que **23 563 blocs de texte/tableaux**
du relevé et leurs ancres sont présents dans les pages rendues. Ce contrôle porte
sur le relevé public, pas sur une comparaison graphique pixel par pixel avec
WordPress. La hiérarchie visuelle et les gabarits de la refonte remplacent ceux du
site d’origine. Les images officielles sont locales ; les documents PDF conservent
leurs liens officiels et nécessitent une connexion au site source.

## Anomalies constatées sur les sources

- `/marketing-digital/` sur Campus retourne 404 : réorienté vers `/campus/marketing/`.
- `/formation/photoshop-initiation/` retourne 404 : réorienté vers la fiche
  `/formation/adobe-photoshop-initiation/` déjà présente.
- 462 chaînes de modèles JavaScript (`itemDataObject.url` et `{{{data.link}}}`)
  apparaissaient dans le HTML brut : ce ne sont pas des pages publiques et elles
  sont exclues de l’inventaire des pages.
- Le formulaire d’inscription source affiche un avertissement de son extension.
  Ce diagnostic technique n’est pas repris comme contenu éditorial.
- Des marqueurs d’édition TinyMCE vides ont été écartés d’un ancien article.

## Périmètre du prototype

Les formulaires ne transmettent aucune donnée. Les pages Campus de contact et de
candidature proposent les coordonnées de l’équipe et un lien explicite vers le
formulaire officiel. Les pages historiques de remerciement n’annoncent pas qu’une
demande a été réellement envoyée depuis le prototype.
Les vidéos et publications sociales externes restent accessibles par des liens :
les flux tiers ne sont pas répliqués dynamiquement. Les espaces privés et les
contenus non publics ne font pas partie de ce relevé. Les textes historiques,
dates de rentrée, chiffres et mentions correspondent au relevé du 22/10/2026 ;
ils doivent être validés par Expernet avant une mise en production.

## Commandes de contrôle

```sh
php tests/run.php
php tests/content_coverage.php
node tests/frontend.test.js
# Avec le serveur PHP local démarré :
python3 tests/http_smoke.py
```

L’audit HTTP vérifie toutes les routes, ressources et liens locaux, les API,
les réponses 404 et le refus d’écriture du prototype. Aucune initialisation,
configuration, publication ou opération de commit Git n’est effectuée par ce lot.
