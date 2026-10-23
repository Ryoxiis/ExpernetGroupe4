# Lire et modifier le PHP du projet

Le projet utilise du PHP natif. Pas de framework supplémentaire ni de compilation
à lancer pour ces changements.

## Où chercher

| Besoin | Fichiers |
| --- | --- |
| Comprendre l’arrivée d’une requête | `public/index.php` |
| Charger la configuration | `bootstrap/app.php`, `bootstrap/environment.php`, `config/` |
| Choisir une action et une réponse | `app/Controllers/` |
| Comprendre les règles pédagogiques | `app/Services/PedagogyService.php` |
| Connexion et accès aux comptes | `app/Services/PortalAuth.php` |
| Envoyer ou lire une discussion | `app/Services/ChatService.php` |
| Construire le calendrier | `app/Services/PlanningCalendar.php` |
| Lire ou écrire MySQL | `app/Repositories/PedagogyRepository.php` |
| Modifier le HTML | `app/Views/` |

Une action suit généralement ce chemin : contrôleur → service → repository,
puis réponse JSON ou vue HTML. Les droits sont vérifiés côté serveur.

## Fonctions simples utilisées

- `explode('=', $ligne, 2)` sépare une variable et sa valeur, même si la valeur
  contient elle-même un signe égal.
- `str_contains`, `str_starts_with` et `str_ends_with` vérifient une partie de texte.
- `ctype_digit` vérifie que le code MFA ne contient que des chiffres.
- `Text::containsOnly` accepte uniquement les caractères indiqués. Elle utilise
  `strspn`, qui compte les caractères autorisés au début du texte : le compte
  doit être égal à la longueur totale. Une chaîne vide est refusée.
- `Text::hasControlCharacters` repère les caractères invisibles interdits dans
  les chemins ou les URL.
- `Dates::isoTimestamp` lit une date avec son fuseau et vérifie le résultat avec
  `DateTimeImmutable`, sans expression régulière.
- `PlanningCalendar::matchesSearch` et `arrangeOverlaps` séparent la recherche
  et le placement des cours de la construction principale du calendrier.

Exemple de code MFA :

```php
$validCode = strlen($code) === 6 && ctype_digit($code);
if (!$validCode) {
    // Refuser le code, sans supprimer le contrôle d’authentification.
}
```

Les requêtes préparées PDO, transactions, vérifications de rôle, protections
CSRF et hachages de mots de passe restent nécessaires. Les remplacer par une
comparaison dans le navigateur ne simplifierait pas correctement le projet.

## Expressions régulières conservées

Trois usages restent dans le code applicatif :

- `PageController.php` normalise les espaces Unicode des contenus importés.
- `helpers.php` coupe un extrait UTF-8, y compris sans extension mbstring.
- `PedagogyController.php` limite strictement l’adresse WebSocket autorisée dans
  l’en-tête de sécurité CSP.

Les supprimer demanderait davantage de code ou changerait le comportement.
Les expressions présentes dans les outils de test ne sont pas le code de
fonctionnement du site. Les bibliothèques tierces de `vendor/` restent intactes.

## Vérifier une modification

```bash
php tests/run.php
php tests/portal_views.php
php tests/portal_security.php
php tests/planning_calendar.php
php tests/php_readability.php
php tests/content_coverage.php
```

Avec l’Apache local lancé et MySQL disponible :

```bash
TEST_CHAT_URL=http://127.0.0.1:8088 python3 tests/chat_accounts_http.py
php tests/chat_seed.php
TEST_BASE_URL=http://127.0.0.1:8088 python3 tests/http_smoke.py
```
