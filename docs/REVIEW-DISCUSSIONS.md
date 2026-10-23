# Comptes de test pour les échanges

Branche : `feat/discussions-demo`, depuis `main`, identique à `origin/main`
lors de la récupération. Aucun commit ou push créé.

## Changements de ce lot

- `database.sql` : tables de collaboration alignées sur les migrations 003/004,
  historique des migrations et section additive de 16 comptes, 2 promotions,
  3 groupes et 6 conversations privées, en plus des 2 fils de promotion.
- `bin/seed-chat.php` : chargement transactionnel de cette seule section, réservé
  à la démonstration hors production, verrou contre les chargements concurrents,
  contrôle de collision des comptes et absence de modification des mots de passe.
- `docs/TEST-DISCUSSIONS.md` : identifiants et parcours pour plusieurs sessions.
- `tests/chat_accounts_http.py` et `tests/chat_seed.php` : échanges HTTP réels,
  confidentialité, antidoublon, refus CSRF et idempotence du chargement.

Aucun message prérempli n’est créé. Les envois utilisent le code de messagerie
existant et sont persistés dans MySQL. La base actuellement utilisée par Apache
a été enrichie, sans réimporter le dump complet. Aucun redéploiement nécessaire
pour voir ces comptes : le code serveur de messagerie n’a pas changé.

## Vérifications

- `php -l bin/seed-chat.php` et `php -l tests/chat_seed.php` : OK.
- `python3 tests/chat_accounts_http.py` sur `http://expernet.local` : OK.
- `php tests/chat_seed.php` après premier chargement : OK, empreinte des lignes
  inchangée dans les comptes, affectations, conversations, participants, messages
  et permissions.
- `git diff --check` : OK.

Limites : import complet en base vierge non exécuté. Le refus CSRF retourne un
code HTTP 500 sur Apache au lieu de 419, avec le message attendu et sans écriture.
Le test vérifie explicitement le refus et l’absence de nouveau message.

## Review et test manuel

Suivre `TEST-DISCUSSIONS.md`, en particulier Lina/Noé dans deux navigateurs ou
une fenêtre privée. Vérifier les réponses, la conservation après reconnexion et
le refus d’accès de Nora aux discussions CDA. Les fenêtres doivent avoir des
sessions indépendantes. La réception HTTP est actualisée toutes les cinq secondes
sans serveur WebSocket.

Le dump complet contient des suppressions de tables : réserver son import à une
initialisation volontaire. Sur une base existante, employer uniquement les
migrations et `seed-chat.php`. Les comptes partagent un mot de passe initial de
test et doivent rester dans l’environnement de démonstration.

Les modifications LAMP déjà présentes et celles de `docs/PEDAGOGIE.md` ne font
pas partie de ce lot et ne doivent pas être incluses dans son éventuel commit.

Proposition de commit après validation :
`feat(chat): add persistent multi-account discussion test setup`
