# Façade Vercel — Expernet

Adresse publiée : https://expernet.vercel.app/
Origine PHP : https://expernet.alwaysdata.net/
Projet Vercel : `ryoxiis-projects/expernet`.

Ce dossier seul est envoyé à Vercel. Il ne contient aucun PHP, secret, fichier privé ou dump SQL. Les requêtes sont transmises en HTTPS à Alwaysdata ; PHP, MariaDB, sessions et fichiers y restent persistants. La mise en cache du portail reste désactivée par les en-têtes de l’application.

## Déployer la façade

Depuis ce dossier, avec le CLI connecté :

```sh
vercel link --yes --project expernet --scope ryoxiis-projects
node build.mjs
vercel deploy --prebuilt
# Après vérification de la prévisualisation :
vercel deploy --prebuilt --prod
```

`build.mjs` produit le routage explicite au format Build Output API v3 dans `.vercel/output/config.json`. Il n’embarque aucun fichier de l’application. Les fichiers `.vercel/` et `.env*` sont ignorés par Git et exclus des envois de sources. La première publication Vercel est automatiquement affectée à la production ; les publications suivantes utilisent une prévisualisation sauf `--prod`.

## Application Alwaysdata

- Dépôt installé dans `/home/expernet/expernet-app`, depuis `main`, commit applicatif `b69a5f3` inclus via le merge `d2dd7ec`.
- Document root : `/home/expernet/expernet-app/public/` exclusivement.
- PHP 8.4 (8.4.27 lors de l’installation), dépendances Composer installées sans dépendances de développement.
- Base `expernet_portal`, utilisateur `expernet`, hôte `mysql-expernet.alwaysdata.net`, MariaDB 11.8.
- Quatre migrations appliquées avec `php bin/portal.php migrate`, sans import du dump ni reprise de données locales.
- Configuration privée `.env` créée sur le serveur avec permissions 0600 : environnement production, démonstration désactivée, MFA activé, clé applicative aléatoire. Ne jamais afficher ou copier ce fichier dans Git.
- Répertoires de fichiers privés dans `storage/pedagogy` et `storage/chat`, hors document root.
- Paramètres PHP : erreurs masquées, journalisation activée, cookies de session Secure/HttpOnly, fichiers 10 Mo, requêtes POST 12 Mo.
- Administrateur demandé : `etudiant1@expernet.re`. Le secret initial de 24 h est dans `/home/expernet/expernet-admin-access.txt`, permissions privées, hors Web. Il faut le consulter depuis SSH puis effectuer le changement initial dans l’interface ; il n’est pas inclus dans ce dépôt.

## Vérifications et limites

Les 890 contrôles PHP et les 5 contrôles de sécurité sont passés sur PHP 8.4 Alwaysdata. Les contrôles HTTP LAMP et leur variante proxy ont chacun passé 13 vérifications : pages, PHP exécuté, MySQL accessible, cookies/CSRF pour un compte inexistant, API anonyme refusée et fichiers privés inaccessibles.

Le test HTTP public exécuté sur `https://expernet.vercel.app` a également validé les 294 pages, les 3 API, les références aux ressources/liens, la 404 et le refus des écritures publiques. La connexion MySQL chiffrée avec validation du certificat a confirmé les quatre migrations et le compte administrateur au statut `invited`.

Le parcours authentifié complet avec changement de mot de passe et MFA reste à effectuer par le propriétaire. Les tests destructifs ou de création de données de démonstration ne sont pas exécutés sur la production.

Aucun antivirus n’est installé sur le compte : les pièces jointes du chat restent bloquées en production par la protection existante. Il faut un scanner compatible et ses signatures avant de les autoriser ; ne pas désactiver cette protection. WebSocket n’est pas configuré : la messagerie conserve son repli HTTP. Visioconférence, envoi automatique d’e-mails et signature certifiée restent soumis aux limites déjà documentées du projet.

Cette installation gratuite reste soumise aux quotas et à l’usage personnel/non lucratif des offres choisies. Elle ne constitue pas une validation de toutes les exigences d’exploitation d’un véritable service pédagogique.
