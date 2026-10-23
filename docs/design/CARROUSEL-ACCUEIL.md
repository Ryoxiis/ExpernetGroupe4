# Accueil : carrousel et orientation

Le carrousel est placé immédiatement après le hero. Il utilise les images
officielles déjà importées dans le relevé du projet, sans image générée.
Les six photos montrent des événements et des promotions d’Expernet Campus.
Le visuel de présentation générique a été retiré à la demande du propriétaire.

## Provenance des images

- [Une journée d’intégration haute en couleurs 🏝️](https://expernet-campus.re/journee-dintegration-%F0%9F%8F%9D%EF%B8%8F/) : [image originale](https://expernet-campus.re/wp-content/uploads/2024/02/20231124_111846-scaled-e1708932785336.jpg), copie locale `assets/images/campus/058132c6589d7fde39.jpg`.
- [Nos étudiants vice-champions du 24H by Webcup ! 🚀](https://expernet-campus.re/nos-etudiants-vice-champions-du-24h-by-webcup-%F0%9F%9A%80/) : [image originale](https://expernet-campus.re/wp-content/uploads/2024/06/Webcup-2-scaled.jpg), copie locale `assets/images/campus/1192acbcbe8feb15bd.jpg`.
- [Retour sur la Remise de Diplômes ! 🎓](https://expernet-campus.re/retour-sur-la-remise-de-diplomes-%F0%9F%8E%93/) : [image originale](https://expernet-campus.re/wp-content/uploads/2024/01/Design-sans-titre-8.png), copie locale `assets/images/campus/d6fb0064f04e1a3838.png`.

- [Campus à Mayotte](https://expernet-campus.re/campus-a-mayotte/) : [image originale](https://expernet-campus.re/wp-content/uploads/2026/07/diplomes-Expernet-Campus-a-Mayotte.png), copie locale `assets/images/campus/7334288f4f2404a5b6.png`.
- [Campus à Mayotte](https://expernet-campus.re/campus-a-mayotte/) : [image originale](https://expernet-campus.re/wp-content/uploads/2022/11/Rentre-ASR-Mayotte3.jpeg), copie locale `assets/images/campus/1f59dbda95ea21f6aa.jpeg`.
- [Campus à Mayotte](https://expernet-campus.re/campus-a-mayotte/) : [image originale](https://expernet-campus.re/wp-content/uploads/2022/11/Rentre-ASR-Mayotte1.jpeg), copie locale `assets/images/campus/4b4bb8b17095037408.jpeg`.

Les accès web directs ont renvoyé une erreur 502 pendant ce lot. La provenance
est celle du relevé local `data/source-pages.json` ; aucun nouveau téléchargement
ni recherche d’images de remplacement n’a été présenté comme réussi.

## Comportements

- Six photos pleine largeur, texte en surimpression au survol ou au focus.
- Sur appareil tactile, texte directement visible.
- Flèches discrètes à chaque bord, liens vers les articles ou parcours locaux.
- Rotation toutes les trois secondes lorsque la section est visible.
- Pause au survol, à la mise en arrière-plan et lors des interactions clavier.
- Au départ du pointeur, reprise automatique si la préférence de mouvements réduits ne l’interdit pas.
- Au clavier, pause pendant que les commandes sont focalisées.
- Navigation précédente/suivante, choix direct, flèches du clavier et geste horizontal.
- Défilement automatique initial désactivé avec la préférence de mouvements réduits.
- Sans JavaScript, les six articles restent lisibles et les commandes sont masquées.
- Trois entrées sous le carrousel : compétences, diplômes et cours pédagogiques.

## Vérification et livraison

Branche `feat/accueil-carousel`, créée depuis main. Pas de commit ni push.
La fraîcheur distante de main n’a pas été revérifiée. Les modifications déjà
présentes et apparues dans les fichiers partagés pendant le lot sont conservées.
En particulier, les remplacements de tirets dans home.php/layouts/main.php et
les changements globaux de CSS/chat ne font pas partie du carrousel.

Fichiers du lot : `config/home-stories.php`, `app/Views/partials/home-carousel.php`,
`app/Views/pages/home.php` (insertion et troisième accès), `app/Views/layouts/main.php`
(feuille dédiée sur l’accueil et version du script), `public/assets/css/home-stories.css`,
`public/assets/js/main.js`, `public/assets/js/modules/carousel.js`, `tests/carousel.test.js`.

Tests : 890 contrôles PHP, 273 pages/23 565 blocs conservés, 728 références images,
cinq contrôles JavaScript existants et tests des interactions du carrousel : OK.
Le test HTTP a parcouru les 294 pages et les trois API, puis s’est arrêté sur
`/.env` : Apache renvoie 403 alors que le test attend 404. Le fichier reste
inaccessible ; ce refus ne provient pas du carrousel.
Navigateur sur le port 8088 : flèches, pagination, clavier, affichage empilé et
affichage en deux colonnes observés. Préférence de mouvement testée par simulation
unitaire, pas par changement du réglage du navigateur.

Aperçu : http://127.0.0.1:8088/. Les copies LAMP sur expernet.local et les images
Docker doivent être mises à jour pour reprendre les sources. Aucune modification
de ces déploiements nécessitant sudo n’a été effectuée dans ce lot.

Proposition après review : `feat(home): add accessible official-photo carousel and clearer entry points`.

Le bouton pause a été retiré à la demande du propriétaire. Le survol et le
focus clavier interrompent le défilement ; les flèches restent sur les bords.
