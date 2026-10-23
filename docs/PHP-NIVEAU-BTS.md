# Lire le PHP du projet

Le projet conserve PHP 8.2, des classes simples et PDO. Pour les prochaines
modifications, privilégier les variables explicites, `if`, `switch` et `foreach`.
Déclarer les attributs de classe avant le constructeur, puis les initialiser avec
`$this->attribut = $parametre`. Éviter de cumuler plusieurs transformations de
tableaux sur une même ligne.

## Parcours d'une requête

1. `public/index.php` lit l'adresse et choisit la page ou l'API.
2. Le contrôleur reçoit la demande et appelle les traitements nécessaires.
3. Le service vérifie les droits et applique les règles métier.
4. Le repository lit les fichiers JSON ou exécute les requêtes PDO.
5. La vue affiche le HTML. `e()` protège le texte affiché.

Les tableaux associatifs contiennent les données : `$user['id']` correspond
à l'identifiant de l'utilisateur. `foreach` parcourt ces tableaux. Une méthode
s'appelle avec `$objet->methode()` ; `$this` désigne l'objet courant.

Une fonction anonyme passée à `transaction()` regroupe des écritures : elles
sont validées ensemble ou annulées si une exception survient. Les fonctions de
comparaison passées à `usort()` servent à trier un tableau. Ces mécanismes restent
utiles et ne doivent pas être remplacés par du code qui supprime les protections.

Conserver les requêtes préparées, les vérifications de droits, les jetons CSRF,
le hachage des mots de passe et l'échappement HTML. Le fonctionnement du MFA,
des signatures JWT et des WebSockets demande des notions supplémentaires ;
la simplification de l'écriture ne supprime pas ces fonctionnalités.

## Vérifier localement

Depuis le dossier du projet :

```sh
php tests/run.php
php tests/content_coverage.php
php tests/portal_security.php
php tests/portal_views.php
php -S 127.0.0.1:8080 -t public public/router.php
```

Ouvrir `http://127.0.0.1:8080/`, parcourir le catalogue puis la connexion.
Les écritures pédagogiques et les discussions nécessitent aussi une base MySQL
configurée : les tests de rendu ne valident pas ces échanges avec la base.
