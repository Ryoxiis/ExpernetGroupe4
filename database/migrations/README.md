# Migrations pédagogiques

Le catalogue public utilise les instantanés JSON. Les fonctionnalités privées du
portail pédagogique utilisent MySQL et les migrations versionnées de ce dossier :

- `001_pedagogy.sql` : comptes, promotions, séances et suivi pédagogique.
- `002_schedule_lock.sql` : verrou utilisé pour les opérations de planification.
- `003_collaboration.sql` : conversations, participants, messages, fichiers,
  réunions, permissions et tickets WebSocket.
- `004_chat_groups.sql` : prise en charge des conversations de groupe.

La commande `php bin/portal.php migrate` applique les migrations absentes de
`schema_migrations`, dans l’ordre. Elle utilise la configuration MySQL du projet.
Sauvegarder une base contenant des données à conserver avant une évolution.

`database.sql` à la racine est un dump d’initialisation avec données fictives et
`DROP TABLE`. Ce n’est pas une migration à relancer sur une base en usage.
La répétition d’une définition de table dans le dump initial et sa migration
n’autorise pas à supprimer l’un des deux : les chemins d’installation diffèrent.
Les nouvelles évolutions doivent maintenir leur cohérence explicitement.
