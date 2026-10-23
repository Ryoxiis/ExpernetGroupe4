# Calendrier pédagogique

Branche : `feat/planning-calendrier`, depuis `main`. Responsable : Codex.
Les changements précédents présents dans le répertoire sont conservés, sans
commit ni push. Seuls les fichiers ci-dessous appartiennent à ce lot.

## Résultat

- `app/Services/PlanningCalendar.php` : présentation des séances déjà autorisées,
  vues journée et semaine du lundi au dimanche, conversion UTC vers Indian/Reunion,
  navigation, recherche, chevauchements côte à côte et séances traversant minuit.
- `app/Views/pedagogy/pages/planning.php` : calendrier avec accès à la fiche séance,
  date, aujourd’hui, cours, horaires, formateur, lieu et modalité. Conservation
  de l’export ICS et des indisponibilités des formateurs.
- `public/assets/css/pedagogy.css` : grille horaire sur grand écran, agenda par jour
  sous 800 px, palette et composants du portail. Navigation utilisable sans JS.
- `tests/planning_calendar.php` : vérifications des dates, fuseau, recherche,
  chevauchements et passages à minuit.
- `tests/portal_views.php` : reconnaissance du formulaire GET de navigation sans
  assouplir le contrôle CSRF sur les formulaires POST.

Le calendrier ne crée aucun cours et utilise uniquement `state.sessions` déjà
filtré par les droits existants. Aucun changement de schéma ou de permissions.

## Vérifications réalisées

- `php tests/planning_calendar.php` : OK.
- `php tests/portal_views.php` : 36 écrans OK.
- `php tests/run.php` : 890 contrôles et 294 routes OK.
- `node tests/frontend.test.js` : 5 contrôles OK.
- Syntaxe du gabarit PHP et `git diff --check` : OK.
- Navigateur sur Apache local port 8088 : connexion du compte étudiant de démo,
  vue semaine, cours du vendredi et samedi, puis passage à la journée : OK.
  Rendu étroit observé : agenda lisible. Le rendu grand écran n’a pas été
  inspecté visuellement dans cette session.

## Tester et déployer

Aperçu servant directement les sources :
http://127.0.0.1:8088/espace-pedagogique/planning/

Compte existant `student.demo@expernet.re`, mot de passe initial de démonstration
`Test-local-phrase-2026!` s’il n’a pas été changé. Les comptes ajoutés uniquement
pour les discussions n’ont pas nécessairement de séances personnellement affectées.

Vérifier Journée/Semaine, les flèches, Aujourd’hui, une date sans cours,
la recherche et l’ouverture d’une séance. Les filtres s’appliquent avec Afficher.

`expernet.local` utilise une copie des sources dans `/var/www/expernet`, qui n’a
pas encore été mise à jour : sudo exige une authentification interactive.
Depuis la racine du projet, lancer dans le terminal utilisateur :

```bash
sudo bash bin/install-lamp.sh
```

Puis recharger `http://expernet.local/espace-pedagogique/planning/`.
Ce déploiement conserve la base et les conversations existantes.

Le diff local est consultable pour les cinq fichiers de code/test cités ci-dessus.
Proposition de commit après validation du propriétaire :
`feat(planning): add daily and weekly course calendar`
