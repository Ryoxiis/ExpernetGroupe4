# Images officielles et typographie - contrôle du 22 octobre 2026

Sources publiques consultées : https://expernet.re/ et https://expernet-campus.re/,
avec leurs API WordPress pages, articles, formations et médiathèques.

## Inventaire et intégration

- 253 contenus WordPress publiés comparés aux routes locales : aucun contenu sans
  correspondance locale, y compris les URL comportant des caractères accentués ou emoji.
- Médiathèques parcourues entièrement par pagination : 440 éléments retournés pour
  Expernet et 380 pour Campus, soit 820 médias, dont 606 images et 214 autres médias.
  Les en-têtes des requêtes unitaires annoncent respectivement 453 et 388 éléments ;
  les requêtes paginées, même sans cache, en retournent 440 et 380. Les 21 éléments
  d'écart ne sont donc pas déclarés inspectés ou intégrés.
- 603 aperçus d'images récupérés pour le contrôle, dont 536 images matricielles
  réunies en six planches et 67 fichiers SVG. Trois miniatures officielles renvoient
  404 (fichiers aux noms accentués) ; cela n'a bloqué aucun média sélectionné pour le site.
- 240 images officielles associées aux pages ; 101 fichiers supplémentaires stockés
  localement et dédupliqués par leur contenu. Aucun appel aux sources n'est nécessaire
  pour afficher ces images dans le prototype.
- 143 visuels du catalogue et 5 couvertures d'articles corrigés en fonction de l'image
  mise en avant définie dans les API officielles. Les 11 diplômes Campus disposent
  de leur visuel de formation, plutôt qu'un logo de certification récupéré en premier.
- Galeries nettoyées des placeholders et des vignettes de navigation répétées sur
  d'autres pages. Photos d'équipe et d'étudiants utilisées dans les pages adaptées.
- Deux sous-titres officiels manquants ajoutés aux formations IA. Les différences
  de protocole HTTP/HTTPS dans les mentions légales n'ont pas provoqué de doublons ;
  un marqueur d'édition TinyMCE a été exclu. Aucun programme ou tarif inventé.

Le détail est conservé dans `medias-officiels.json` avec les URL sources,
identifiants, types, dimensions et chemins locaux des images utilisées.
Les PDF restent liés aux sources officielles : la médiathèque n'est pas dupliquée
intégralement dans le site, et les documents anciens ou non utilisés restent inventoriés.

## Typographie

Inter Variable remplace Nunito et les accents Georgia dans le site et le portail.
Le fichier WOFF2 est hébergé localement ; sa licence SIL Open Font License est
conservée dans `public/assets/fonts/INTER-LICENSE.txt`. Les titres sont moins serrés
et les grands titres utilisent une graisse 650. Les couleurs et le logo restent inchangés.
Source de la police : https://github.com/rsms/inter.

## Vérifications

- 890 contrôles PHP et 294 routes rendues.
- 273 pages et 23 565 blocs de texte conservés.
- 36 rendus pédagogiques, tests JavaScript et 728 références d'images vérifiés.
- Aucun fichier image identique en double ; aucun clone de code détecté au seuil
  habituel de 5 lignes / 50 tokens.
- Contrôle HTTP réussi : 294 pages, 3 API, liens et ressources, 404 et refus d'écriture.
  Sous Windows, exécuter Python avec `-X utf8` pour lire les routes françaises.
- Navigateur : Inter chargée, accueil et Campus contrôlés sur ordinateur et mobile,
  absence de débordement horizontal aux largeurs 1280 et 390 px ; visuels des diplômes
  inspectés. Les images hors écran peuvent rester en attente avec le chargement différé.

Les prix, calendriers et informations historiques gardent leur contexte de relevé.
Les formulaires du prototype conservent leurs limites existantes.
