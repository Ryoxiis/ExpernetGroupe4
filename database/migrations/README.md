# Migrations

Aucune migration métier à ce stade : le catalogue public est un instantané JSON.
Créer les tables et les migrations versionnées seulement pour la fonctionnalité
prise en charge. Ne pas inventer ici le schéma du portail pédagogique complet.

Les futures requêtes utilisent PDO, des paramètres préparés et les contraintes
appropriées. `App\Support\Database` fournit une connexion optionnelle, inutilisée
par le prototype actuel. Aucun compte ou mot de passe réel n'est livré.
