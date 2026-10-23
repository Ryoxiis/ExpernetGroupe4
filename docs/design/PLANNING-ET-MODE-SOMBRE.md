# Planning horizontal et apparence

Lot réalisé par Codex sur `feat/planning-mode-sombre`, depuis `main` à jour
(`d2dd7ec`, identique à `origin/main` au démarrage). Les changements antivirus
et de déploiement déjà présents dans le dossier sont conservés hors de ce lot.

## Direction visuelle

La charte Océan et la typographie Inter locale existantes sont conservées.
Le planning privilégie la lecture des horaires et des lieux, avec des éléments
alignés à gauche. Il ne contient ni nouvelles statistiques ni décoration animée.

En clair : bleu `#007AA8`, encre `#07354A`, orange `#F0A029`, fond `#F4F8FA`,
surface `#DFEEF4`, papier blanc. En sombre : fond `#102530`, papier `#173340`,
surface `#234553`, texte `#E4F0F5`, secondaire `#B2C8D2`, lien `#72C9EC`.
Les surfaces fortes restent bleu nuit pour conserver du texte blanc lisible.

Organisation PC :

```text
Journée / Semaine      Aujourd’hui      Exporter
Date                  Recherche        Afficher
             Période et navigation
Jour          08h   09h   10h   ...   18h
Lundi         [cours du matin] [cours de l’après-midi]
Mardi         [cours du matin] [cours de l’après-midi]
```

Les jours sont en lignes, les heures de gauche à droite. La largeur d’un cours
représente sa durée. Des lignes supplémentaires séparent les cours simultanés.
Les journées sans cours sont plus courtes. Sur mobile (800 px et moins), une liste
par jour remplace l’axe horaire. Les mêmes données et liens de séance sont utilisés.
Les cours très courts ou aux titres longs peuvent défiler dans leur zone pour
rester entièrement consultables, sans agrandir la durée affichée.

## Mode sombre

Le bouton « Mode sombre » est commun au catalogue, à la connexion et au portail.
Son état est exposé avec `aria-pressed`. Sans préférence enregistrée, le thème suit
le système. Le choix explicite persiste localement et se synchronise entre les
onglets de même origine. Le contrôle fonctionne aussi lorsque le stockage est bloqué.
Le script s’exécute avant les feuilles de style pour éviter un éclair de thème clair.
La palette de l’atelier et l’apparence claire/sombre restent indépendantes.
Le logo conserve ses couleurs officielles sur une surface blanche. L’impression
utilise des couleurs claires. Aucune donnée métier n’est stockée pour cette fonction.

## Consignes des skills appliquées

Recherche dans les skills installés ; les consignes explicitement relatives aux
interfaces générées viennent principalement de `frontend-design`. Les autres
sources utiles sont `ui-ux-pro-max` et `responsive-design`.

| Consigne | Application |
| --- | --- |
| Éviter une nouvelle palette générique | Conservation d’Océan ; variante sombre bleue cohérente. |
| Éviter les grilles de cartes identiques pour toute information | Planning structuré par jour et heure ; une seule surface principale. |
| Éviter ombres et dégradés décoratifs | Planning sans ombre ; suppression du dégradé décoratif du chat et des cercles de connexion. Le quadrillage horaire encode des heures. |
| Ne pas numéroter un contenu sans séquence | Retrait des numéros des bénéfices de connexion et des options de l’accueil. |
| Éviter majuscules et espacement excessif des petits titres | Libellés de l’accueil en casse normale ; étiquettes pédagogiques sans capitales forcées. |
| Employer des libellés utiles et compréhensibles | Navigation journée/semaine, recherche, heure Réunion, fiche séance. |
| Ne pas ajouter d’animation gratuite | Aucun mouvement ajouté au planning ou au changement de thème. |
| Garder une typographie cohérente | Police locale existante, aucun chargement externe. |
| Ne pas utiliser la couleur seule | Libellé présentiel/distanciel/entreprise ; date « Aujourd’hui » ; bordure pointillée pour le distanciel. |
| Assurer contraste, clavier et taille des actions | Bouton natif de 44 px minimum, état annoncé, focus visible ; tests de contraste du sombre. |
| Adapter le contenu au mobile | Liste chronologique sans défilement horizontal de la page. |

Ces recommandations ne sont pas des interdictions universelles. Les accents de
titres, visuels et photos de la V2 déjà choisie sont conservés. Le dégradé du
carrousel sert à lire le texte sur les photos et n’est pas supprimé. Les flèches
précédent/suivant du calendrier sont des commandes de navigation utiles.

## Vérifications et essai

- `npm test` : suite existante réussie, dont 890 contrôles PHP, 294 routes et 36 écrans pédagogiques.
- `php tests/planning_calendar.php` : dates, fuseau, recherche, chevauchements, minuit et entrées invalides.
- `node tests/appearance.test.js` : choix système, persistance, changement système,
  synchronisation, stockage bloqué ; contrastes texte/fonds sombres supérieurs à 4,5:1.
- `node --check public/assets/js/theme.js` et `git diff --check`.
- `TEST_BASE_URL=http://127.0.0.1:8088 python3 tests/http_smoke.py` : 294 pages,
  trois API, ressources/liens, 404 et refus des écritures vérifiés.
- Vérification navigateur : connexion, accueil public et planning rendu avec des
  données fictives isolées, en clair/sombre et en liste à 390 px, sans débordement.

Pour tester avec les données du site : ouvrir `http://127.0.0.1:8088/`, basculer
le thème, recharger puis ouvrir la connexion. Après connexion avec un compte de
test existant, consulter « Mon planning », changer de période et de vue, rechercher
un cours et ouvrir sa fiche. Refaire sur mobile et au clavier.

Les permissions et les données du planning ne sont pas modifiées. Les nouveaux
parcours ne font pas l’objet d’un nouvel audit lecteur d’écran ou téléphone physique.
Aucune migration nécessaire. Pas de mise à jour du déploiement LAMP ou Docker.

Fichiers principaux : gabarit planning et `pedagogy.css` ; contrôle partagé
`partials/appearance.php` ; `theme.js` et `appearance.css` ; inclusion dans les deux
layouts. Les surfaces des CSS existants utilisent désormais des couleurs sémantiques.

Relecture : `git diff -- app/Views public/assets/css public/assets/js/theme.js`,
puis les nouveaux fichiers `appearance.css`, `appearance.php`, le test et ce document.
Commit proposé après validation : `feat(ui): add horizontal planning and dark mode`.
Aucun commit ni push réalisé pour ce lot.

## Passe responsive complémentaire

Ajout de `public/assets/css/responsive.css` dans les layouts public et pédagogique,
ainsi que sur l’écran d’erreur. Le fichier regroupe les corrections transversales :

- en-tête mobile en grille, accès pédagogique visible, menu dépliable limité en hauteur ;
- titre du hero adapté, texte de lecture agrandi, cartes du catalogue sur une colonne ;
- champs à 16 px sur mobile et commandes de 44 px, filtres sur toute la largeur ;
- textes longs, images, formulaires et tableaux contenus dans leur zone ;
- fenêtre de discussion bornée par la hauteur disponible, y compris en paysage ;
- raccourci de palette remis dans le flux sur mobile pour ne pas masquer le contenu ;
- légendes du carrousel visibles sur petit écran même avec une souris connectée.

Mesures navigateur réalisées sur les largeurs effectives **320, 375, 768, 844,
1024 et 1440 px**. Le format 844 × 390 vérifie aussi le paysage court.
Sur cinq formats, 35 combinaisons couvrent accueil, catalogue, fiche formation,
Campus, planning public, contact et connexion ; 25 couvrent planning étudiant,
accueil étudiant, résultats, travaux formateur et administration. Aucun débordement
horizontal de la page mesuré. Six gabarits publics et six écrans pédagogiques ont
également été vérifiés à 375 px. Les vues privées de cette vérification utilisent
les vrais gabarits et un jeu fictif isolé, avec plusieurs cours et des états vides.
La fenêtre de discussion a été ouverte en petit écran ; son API n’est pas branchée
sur le serveur d’aperçu. Ces contrôles ne valent pas un test de messagerie réelle.

Les mesures de largeur ne remplacent pas un audit visuel exhaustif des 294 pages.
Pas de nouvel essai sur appareil physique, clavier virtuel iOS/Android ou zoom
navigateur à 200 %. Ces points restent à examiner lors de la recette.

## Publication du 23 octobre 2026

À la demande du propriétaire, les 16 fichiers d’interface de ce lot ont été
publiés sur l’origine Alwaysdata, puis la façade Vercel a été redéployée en production :
https://expernet.vercel.app/ . Déploiement Vercel `dpl_Ao9PuJHwgxbeuU6TqdUZAgBL2a6G`, état READY.
Les fichiers de configuration privés, données et modifications serveur antivirus
préexistantes ne font pas partie de cette copie. Sauvegarde de l’interface précédente
hors Web : `/home/expernet/expernet-ui-backup-20261023-1545.tgz`.

Les 36 écrans pédagogiques se rendent sans avertissement sur PHP distant. Vérification
publique : accueil, connexion, redirection du planning anonyme vers la connexion,
et empreintes identiques pour appearance.css, responsive.css, pedagogy.css et theme.js.
Le bouton sombre a également été actionné dans le navigateur sur le domaine publié.
Le planning authentifié en production reste à essayer avec un compte existant.

Cette publication est une copie ciblée des fichiers locaux, sans commit ni push.
Le serveur possède donc des modifications locales en plus de son commit Git de base.
Conserver ces changements lors d’une prochaine mise à jour par Git.
