# Refactorisation de lisibilité PHP

Responsable : Codex. Branche `refactor/php-lisible`, créée depuis `main`.
Les modifications précédentes, dont le calendrier et les comptes de discussion,
sont conservées dans le répertoire. Aucun commit ou push effectué. Main n’a pas
été récupéré de nouveau pendant ce lot : sa fraîcheur distante n’est pas revérifiée.

## Changements à relire

- `bootstrap/environment.php` : lecture explicite de `.env` avec séparation sur
  le premier signe égal, priorité aux variables exportées et validation des noms.
- `config/app.php`, `public/index.php`, `app/Support/View.php` : caractères
  autorisés et segments de chemin contrôlés sans regex.
- `app/Support/Text.php` : deux petits contrôles partagés de texte ASCII.
- `app/Support/HtmlContent.php` : ancres et caractères invisibles dans les liens.
- `app/Services/PortalAuth.php` : code MFA en six chiffres, condition nommée.
- `app/Services/ChatSocket.php` : jeton nommé et contrôles explicites.
- `app/Services/ChatService.php`, `app/Support/Dates.php` : clés d’envoi et dates
  ISO validées avec les fonctions natives.
- `app/Services/PlanningCalendar.php` : recherche et chevauchements isolés dans
  des méthodes courtes, suppression de la closure qui modifiait des références.
- `bin/seed-chat.php` : section SQL séparée par marqueurs, tables autorisées listées.
- `bin/setup-local-db.php` : mise à jour de `.env` ligne par ligne, sans regex.
- `tests/php_readability.php` et `docs/PHP-SIMPLE.md` : contrôles limites et guide.

Les fonctions déjà directes, vues, requêtes métier, permissions et bibliothèques
externes ne sont pas réécrites systématiquement. Trois regex utiles restent,
avec leurs raisons dans le guide. Aucun schéma SQL ou compte réel modifié.

## Résultats

- Syntaxe PHP dans app/bootstrap/config/bin/public : OK.
- 890 contrôles PHP, 294 routes rendues : OK.
- 36 écrans pédagogiques : OK.
- 5 contrôles de sécurité : OK.
- Contrôles calendrier : OK.
- 40 nouveaux contrôles texte, dates, URL, vues et environnement : OK.
- Couverture : 273 pages et 23 565 blocs conservés.
- HTTP Apache local : 294 pages publiques, 3 API, ressources/liens, erreurs et
  refus des écritures : OK.
- Échange réel entre deux comptes et refus aux non-participants : OK.
- Rechargement des comptes sans changer les mots de passe, permissions ou
  messages : OK.
- `tests/pedagogy_regression.py` : interrompu au téléchargement de la ressource
  1, `guide-architecture.txt`, absente du stockage local. La logique de
  téléchargement n’est pas modifiée par ce lot. Le parcours complet de ce test
  et les tests MySQL isolés/concurrents ne sont donc pas déclarés validés.
- Le mapping Apache du refus CSRF 419 vers 500, déjà observé, reste inchangé.
- Aucun test navigateur visuel nouveau : cette tâche ne modifie pas le design.

## Essayer et déployer

Le port 8088 sert les sources modifiées. Tester la connexion, les échanges,
le calendrier, les filtres du catalogue et les pages de contenu.

Pour mettre à jour la copie Apache de `expernet.local`, exécuter depuis le dossier
projet, dans un terminal utilisateur :

```bash
sudo bash bin/install-lamp.sh
```

Cette opération exige une authentification administrateur interactive. Elle n’a
pas été répétée par l’agent pour ce lot. Les changements restent testables dans
les sources et sur le port 8088.

Examiner uniquement les fichiers de ce lot avant de préparer un commit : plusieurs
changements antérieurs sont encore présents et certains fichiers sont nouveaux
non suivis depuis les lots précédents. Ne pas inclure silencieusement ces lots.
Proposition après validation : `refactor(php): simplify validation and calendar logic`.
