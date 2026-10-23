# Planning journalier

Le planning affiche uniquement la journée sélectionnée, même depuis un ancien lien `view=week`. Les cours sont classés par heure et présentent le nom du cours, le professeur, les horaires de début et de fin, la durée, le lieu, la promotion et la modalité. Chaque carte ouvre la fiche séance existante.

La navigation propose les jours de la semaine comme sélecteur, un jour précédent/suivant et Aujourd’hui. Le panneau « Choisir une date ou rechercher un cours » contient une date native et une recherche par cours, professeur, salle ou promotion. Les dates et horaires sont calculés en Indian/Reunion à partir des dates UTC existantes. Une séance traversant minuit reste visible sur chaque jour concerné ; les horaires affichés délimitent sa portion dans la journée.

## Direction visuelle

Les skills frontend-design et ui-ux-pro-max ont servi à la composition et à la navigation. Palette Océan conservée : bleu #007AA8 pour les liens, encre #07354A pour la lecture, orange #F0A029 pour le repère du jour, fond #F4F8FA et surface #DFEEF4. Typographie et mode sombre existants conservés. La date est le repère principal ; une colonne horaire accompagne une liste de cours de largeur souple. Les filtres sont repliés pour privilégier les cours, ouverts automatiquement pendant une recherche.

La feuille planning.css reste limitée à cette vue. Aucun positionnement calculé ni style inline n’est nécessaire pour placer les cours ; les séances courtes, simultanées ou portant un titre long restent lisibles. Les contrôles utilisent des liens, un formulaire GET et un élément details natifs, sans dépendance JavaScript.

## Périmètre et vérifications

Branche : `feat/calendrier-journalier`, base `main` / `origin/main` au commit `6f80a6b`, vérifiée après récupération distante le 23 octobre 2026. Travail réalisé par Codex dans le checkout isolé `expernet-planning-jour` pour préserver les changements déjà présents dans `expernet-start`.

Fichiers applicatifs : `app/Services/PlanningCalendar.php`, `app/Views/pedagogy/pages/planning.php`, `public/assets/css/planning.css`. Aucun changement de base, d’autorisation ou d’écriture de séance.

Vérifications réussies : `npm test`, `php tests/planning_calendar.php`, `php tests/planning_day.php`, syntaxe PHP des deux fichiers applicatifs, `npm run audit:duplicates` (aucun doublon), `git diff --check`. Les contrôles ciblés sont intégrés à npm test et à la CI.

Navigateur : aperçu local avec données fictives, navigation vers un jour vide, retour à la journée, recherche par professeur, ouverture native des filtres, mode sombre, largeur 390 px sans débordement horizontal. Ces essais n’ajoutent aucune donnée fictive sur le serveur.

## Review et essai

Consulter le diff avec `git diff -- app/Services/PlanningCalendar.php app/Views/pedagogy/pages/planning.php package.json .github/workflows/checks.yml` et les nouveaux fichiers de styles et de tests. Sur le planning connecté, vérifier les données de séance, le changement de jour, la recherche et l’ouverture de la fiche cours. Aucun test avec lecteur d’écran réel ou téléphone physique réalisé.

Message de commit : `feat(planning): redesign calendar as a single-day agenda`. Commit, push et fusion dans main autorisés par le propriétaire le 23 octobre 2026.

## Publication vérifiée

Les trois fichiers applicatifs ont été publiés sur Alwaysdata le 23 octobre 2026 après sauvegarde ciblée dans `storage/deploy-planning-jour-20261023/backup/`. Le planning Vercel affiche la séance existante « Dev », Sebastien, Niagara, de 10:00 à 11:30. L’ouverture de sa fiche et une journée sans cours ont été vérifiées dans le navigateur connecté. Aucune donnée de séance n’a été modifiée.
