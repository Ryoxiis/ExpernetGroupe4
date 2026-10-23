# Audit du site Expernet et périmètre de la refonte

Relevé du 22 octobre 2026. Source : https://expernet.re/, plans du site publics et API publique WordPress. Aucune authentification ni interface d’administration utilisée.

## Inventaire

- 219 URL HTML consultées, incluant des routes historiques et des redirections.
- 149 fiches de formation publiées, identifiées dans l’API WordPress.
- 28 articles publiés, avec leur date et leurs principaux médias.
- 9 pages WordPress : accueil, histoire, équipe, devis, planning, actualités, mentions légales, confidentialité et annuaire d’événements.
- 18 thématiques de formation et 3 archives de catégorie.
- 211 pages HTML produites dans la refonte, dont des pages ajoutées pour les couleurs, les contacts et le plan du site.
- 58 sessions futures ou en cours annoncées au moment du relevé, sous réserve de confirmation auprès de l’organisme.

Le recensement concerne le contenu public exposé par ces sources, pas les espaces privés, les brouillons, les anciennes pages non exposées, les pièces jointes en tant que pages autonomes ou le site séparé expernet-campus.re.

## Contenus conservés

Les fiches reprennent les objectifs, prérequis, moyens, méthodes, modalités d’évaluation et programmes textuels disponibles. Les publics, durées, tarifs, sessions et liens PDF sont issus des fiches officielles. Les 149 fiches possèdent des blocs de contenu récupérés.

Les articles gardent leur contenu textuel, leur date, leur image principale et les illustrations intégrées récupérables. Les vidéos sont proposées en liens explicites. Les publications anciennes ne sont pas présentées comme des annonces actuelles.

L’histoire reprend les quatre étapes 2002, 2015, 2017 et 2022. L’équipe reprend les sept personnes et les coordonnées professionnelles publiées. Les pages légales reproduisent le texte source en précisant qu’il décrit le site officiel, sans revendiquer la conformité juridique du futur service.

## Points à corriger dans les données sources

1. **Dates incohérentes** : la fiche Word - Initiation annonce une session du 17 décembre 2026 au 18 novembre 2026. Le relevé brut reste visible sur la fiche avec un avertissement ; cette ligne est exclue du planning principal.
2. **PDF à confirmer** : le lien de la fiche Google Sheets Perfectionnement pointe vers un fichier nommé PROGRAMME-GOOGLE-SHEETS-INITIATION.pdf. Le nom suggère un décalage, à confirmer par Expernet avant de remplacer le document.
3. **Titres de formation dupliqués** : HTML 5 et CSS3 et Méthodes AGILES SCRUM apparaissent chacun sur deux fiches. Les URL et les fiches sont conservées ; une fusion éditoriale exige une vérification des programmes.
4. **Liens historiques** : certains articles renvoient vers /inscription/, /ecole/ et une ancienne URL Copilot absents de l’inventaire courant. Ces liens restent dirigés vers le site officiel au lieu de créer de fausses pages.
5. **Devis** : la page source expose dans son HTML un message de problème du formulaire. La refonte propose un formulaire clair qui prépare un récapitulatif local, sans prétendre réparer le service officiel.
6. **Événements** : la page event-directory utilise un annuaire embarqué sans événements récupérables dans son contenu public. La nouvelle page indique cette limite et renvoie aux publications événementielles.

## Améliorations proposées

- Navigation cohérente sur chaque page et véritable plan du site.
- Catalogue complet, recherche insensible aux accents, filtres thématiques, tri et pagination.
- Fiches lisibles et encadré regroupant durée, tarif et contact.
- Demande de devis préremplie depuis la formation consultée.
- Planning filtrable par mois et recherche par formation ou lieu.
- Logo officiel et quatre palettes comparables, choix conservé dans le navigateur.
- Focus visible, lien d’évitement, libellés explicites, adaptations responsive et respect de la préférence de réduction des animations.

## Vérifications et limites

Validation automatisée de la présence des ressources et des destinations locales sur les 211 pages, ainsi que d’un unique titre H1 par page. Syntaxe des scripts JavaScript vérifiée. Vérification visuelle et interactive de pages représentatives dans le navigateur : palettes, catalogue, recherche, fiche Word et devis prérempli. Toutes les pages n’ont pas fait l’objet d’une revue visuelle individuelle.

Ce livrable est un prototype statique HTML/CSS/JS. Il n’exécute ni PHP ni WordPress. Le formulaire n’envoie aucune donnée et aucune demande n’est enregistrée côté serveur. Les tarifs et calendriers ne sont pas connectés à un système de réservation. Les documents PDF et les vidéos restent sur leurs services d’origine.
