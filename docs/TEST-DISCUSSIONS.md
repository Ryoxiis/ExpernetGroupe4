# Tester les échanges entre plusieurs comptes

La messagerie utilise réellement MySQL : les messages envoyés restent enregistrés
et sont consultables par leurs destinataires après reconnexion. Aucun message
artificiel n’est ajouté par le nouveau jeu de comptes.

## Sur ce PC

Ouvrir http://expernet.local/connexion/ dans un navigateur et dans un second
navigateur ou une fenêtre privée, pour conserver deux sessions indépendantes.

Mot de passe initial commun aux nouveaux comptes : `Test-local-phrase-2026!`.
Ces comptes sont uniquement destinés à l’environnement de démonstration.
Ils ne modifient pas les comptes Microsoft 365.

| Adresse | Personne | Rôle | Promotion |
| --- | --- | --- | --- |
| chat01.demo@expernet.re | Lina Démo | Étudiante | CDA Horizon |
| chat02.demo@expernet.re | Noé Démo | Étudiant | CDA Horizon |
| chat03.demo@expernet.re | Maya Démo | Étudiante | CDA Horizon |
| chat04.demo@expernet.re | Eli Démo | Étudiant | CDA Horizon |
| chat05.demo@expernet.re | Inès Démo | Étudiante | CDA Horizon |
| chat06.demo@expernet.re | Sacha Démo | Étudiant | CDA Horizon |
| chat07.demo@expernet.re | Jade Démo | Étudiante | CDA Horizon |
| chat08.demo@expernet.re | Milo Démo | Étudiant | CDA Horizon |
| chat09.demo@expernet.re | Nora Démo | Étudiante | Réseaux Azur |
| chat10.demo@expernet.re | Adam Démo | Étudiant | Réseaux Azur |
| chat11.demo@expernet.re | Zoé Démo | Étudiante | Réseaux Azur |
| chat12.demo@expernet.re | Léo Démo | Étudiant | Réseaux Azur |
| chat13.demo@expernet.re | Alice Formatrice Démo | Formatrice | CDA Horizon |
| chat14.demo@expernet.re | Hugo Formateur Démo | Formateur | CDA Horizon |
| chat15.demo@expernet.re | Emma Formatrice Démo | Formatrice | Réseaux Azur |
| chat16.demo@expernet.re | Robin Admin Démo | Administration | Les deux |

1. Se connecter avec Lina dans la première fenêtre, Noé dans la seconde.
2. Ouvrir **Les échanges**, puis leur conversation privée. Sinon, rechercher
   « Noé » parmi les contacts de la promotion CDA et ouvrir la discussion.
3. Envoyer un message depuis Lina, puis répondre depuis Noé.
4. Actualiser ou se reconnecter : les messages sont conservés.
5. Tester également le fil de promotion et les groupes de projet.
6. Nora, d’une autre promotion, ne doit pas voir la discussion privée Lina/Noé.
   L’administrateur n’a pas non plus accès à cette discussion privée.

Sans serveur WebSocket configuré, l’interface vérifie les nouveaux messages toutes
les cinq secondes. Aucun Node/npm n’est nécessaire pour ces échanges.

## Charger sur une autre installation de test

Depuis la racine du projet, avec MySQL configuré dans `.env` et
`PORTAL_DEMO=true`, hors `APP_ENV=production` :

```bash
php bin/portal.php migrate
php bin/seed-chat.php
```

La commande lit uniquement la section délimitée du fichier racine `database.sql`.
Elle conserve les messages, les mots de passe et les comptes déjà présents.
Elle peut être relancée sans dupliquer les comptes ou les conversations.
Elle ne réactive pas un compte désactivé et ne contourne pas les permissions
administratives existantes. Un mot de passe personnalisé reste inchangé.

Le fichier `database.sql` complet reste un script de création initiale contenant
des `DROP TABLE` : **ne pas le réimporter dans une base à conserver**.
Les tables de discussion et les migrations 003/004 y sont désormais incluses.
Les nouveaux comptes sont actifs pour faciliter le test, sans inscription publique.

## Vérification automatique locale

```bash
python3 tests/chat_accounts_http.py
```

Ce test utilise quatre sessions HTTP indépendantes sur `expernet.local`, envoie
un message et une réponse, vérifie leur réception et les refus d’accès, puis
supprime uniquement les deux messages créés par cette exécution. Il suppose
les nouveaux comptes inchangés et le MFA de démonstration désactivé.
Pour une autre adresse locale, définir `TEST_CHAT_URL`.

Après un premier chargement, `php tests/chat_seed.php` vérifie qu’une relance
ne change ni les comptes, ni leurs mots de passe, ni les messages ou permissions.

## État vérifié le 23 octobre 2026

Les échanges HTTP ont été vérifiés sur l’Apache de `expernet.local` : connexion
avec quatre comptes, recherche, envoi, réponse, lecture depuis l’autre session,
antidoublon et refus des lectures privées aux non-participants. Le rechargement
sans modification des lignes existantes a également été vérifié.

Un détail préexistant reste à corriger dans la configuration HTTP : un formulaire
sans jeton CSRF est bien refusé et aucun message n’est créé, mais Apache renvoie
500 au lieu du code applicatif 419, avec le message « Votre formulaire a expiré ».
Ce comportement ne bloque pas les échanges normaux avec le jeton de la page.
L’import complet du dump dans une nouvelle base n’a pas été exécuté lors de ce lot.
