# Antivirus privé de démonstration

Le chat conserve sa limite de 10 Mo, ses validations de type/macros et son refus des fichiers non analysés en production. Aucun service externe d’analyse n’est utilisé. Le contenu est transmis depuis Alwaysdata à votre ordinateur par transferts SSH chiffrés, uniquement pour l’analyse par ClamAV.

Cette solution exige que l’ordinateur et le terminal du worker restent actifs. Elle ne remplace pas un scanner disponible en permanence pour un service en production.

## Installation sur Ubuntu/Debian

Depuis votre ordinateur, avec votre mot de passe administrateur local :

```sh
sudo bash bin/install-chat-antivirus.sh
```

Le script installe les paquets officiels `clamav` et `clamav-daemon`, actualise les signatures avec `freshclam` et démarre les services. La configuration précédente de ClamAV est sauvegardée dans `/etc/clamav/clamd.conf.before-expernet`. Il utilise la socket locale `/run/clamav/clamd.ctl` gérée par systemd ; aucun port public n’est nécessaire. Attendre le chargement des signatures avant le démarrage du worker. Freshclam reste actif pour actualiser les signatures et clamd les vérifie périodiquement.

## Démarrer la démonstration

Dans un terminal local, sans sudo :

```sh
bash bin/start-chat-antivirus-demo.sh
```

Saisir le mot de passe SSH du compte Alwaysdata `expernet` lorsqu’il est demandé. Aucun mot de passe n’est enregistré par ces scripts.

Le script :

1. Vérifie que le vrai daemon ClamAV répond localement.
2. Vérifie l’empreinte ED25519 du serveur, préalablement lue dans l’administration Alwaysdata. Si elle change, il s’arrête ; vérifier la nouvelle empreinte dans le panneau avant toute adaptation.
3. Ouvre une connexion SSH réutilisée pour les transferts ordinaires, sans redirection de port.
4. Copie `bin/scan-chat-queue.py` sur le serveur et crée une file privée en 0700 hors de `public/`.
5. Lance le worker local qui récupère chaque fichier temporaire, l’analyse avec ClamAV, puis renvoie uniquement le verdict. Il actualise un signal de présence ; sans ce signal récent, l’envoi est refusé.
6. Vérifie un fichier sain et la signature de test inoffensive EICAR via cette file.
7. Configure le `.env` distant uniquement si le fichier sain est accepté et EICAR rejeté. La configuration précédente est sauvegardée en 0600.
8. Reste actif pour maintenir le worker. Les tâches temporaires expirent après 25 secondes et sont nettoyées.

Les paramètres activés sur Alwaysdata sont :

```dotenv
CHAT_AV_BINARY=/home/expernet/expernet-app/bin/scan-chat-queue.py
CHAT_AV_QUEUE=/home/expernet/expernet-app/storage/antivirus/jobs
CHAT_REQUIRE_SCAN=true
```

`APP_ENV=production` reste inchangé. Le client reçoit `--no-summary CHEMIN`, comme l’exécutable ClamAV attendu par le code existant, et retourne 0 pour un résultat explicitement sain, 1 pour un fichier détecté, 2 pour une erreur. Le protocole INSTREAM transmet le contenu : les chemins privés du serveur ne sont jamais interprétés comme des chemins de l’ordinateur.

## Arrêt et reprise

Ctrl+C arrête le worker et ferme sa connexion. Si l’ordinateur s’éteint, se met en veille ou perd sa connexion, les nouveaux envois sont bloqués. Pour reprendre, relancer le même script.

Si le script s’arrête avant d’annoncer l’activation, le scanner distant n’est pas confirmé opérationnel. La solution n’utilise aucune redirection de port, incompatible avec les restrictions de l’hébergeur.

## Vérification

```sh
npm run test:antivirus
npm test
bash -n bin/install-chat-antivirus.sh bin/start-chat-antivirus-demo.sh
```

La suite dédiée exerce le vrai client CLI avec un daemon simulé sur socket temporaire : transmission exacte, acceptation explicite, détection, réponses invalides/incomplètes, plafond de réponse, délai dépassé, PING et socket absente. Elle peut nécessiter l’autorisation de créer une socket Unix dans un environnement sandboxé.

Les tests locaux réels avec ClamAV ont également confirmé un fichier sain (code 0) et EICAR (code 1). La validation à travers SSH est réalisée par le script avant activation, puis l’envoi d’une pièce jointe dans le chat reste à vérifier dans l’interface avec votre compte.
