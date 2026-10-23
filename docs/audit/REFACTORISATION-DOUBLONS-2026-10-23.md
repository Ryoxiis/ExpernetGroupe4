# Audit et refactorisation des duplications — 23 octobre 2026

## A. Bilan initial

Base : branche locale `main`, état de travail initial propre ; travail sur `refactor/audit-duplications`. La fraîcheur de la branche par rapport au dépôt distant n’a pas été vérifiée. Aucun push n’est effectué.

Le rapport présent a été examiné, puis `npm run audit:duplicates:detailed` a été relancé avant modification. Il confirme les 68 détections annoncées : 337 lignes dupliquées sur 14 645 lignes, soit 2,30 %.

| Langage | Détections | Lignes dupliquées | Taux du langage |
|---|---:|---:|---:|
| PHP | 57 | 289 | 4,75 % |
| CSS | 9 | 37 | 0,67 % |
| JavaScript | 2 | 11 | 0,81 % |
| Autres | 0 | 0 | 0 % |

L’[inventaire des 68 blocs](INVENTAIRE-DOUBLONS-2026-10-23.md) donne les deux emplacements, la catégorie, la priorité et la décision pour chaque détection. Les [mesures JSON](MESURES-DOUBLONS-2026-10-23.json) conservent les statistiques avant/après ; le rapport détaillé final reste dans `storage/audit-duplicates/jscpd-report.json`, ignoré par Git.

Zones examinées : services de messagerie et pédagogie, authentification et socket, vues publiques et privées, CSS et lanceurs. Les imports, constructeurs, frontières de blocs et chaînes normalisées par le détecteur expliquent une partie des rapprochements.

## B. Modifications réalisées

| Fichiers | Problème et solution | Intérêt et risque résiduel |
|---|---|---|
| `app/Views/partials/source.php`, `source-note.php` | Composants strictement identiques. `source-note` reste canonique et `source` délègue ; quatre appels historiques et six appels au nom canonique vérifiés. | Une seule définition du HTML ; aucun fichier supprimé. Test d’égalité exacte du rendu et d’échappement d’une URL hostile. |
| `public/assets/js/modules/chat.js` | Deux écritures identiques du brouillon deviennent `saveDraft()`, appelé avant changement de conversation et lors de la saisie. | Clé par utilisateur/conversation et gestion facultative du stockage conservées. Tests des handlers réels avec frontières DOM/stockage simulées ; pas de parcours navigateur authentifié réel. |
| `app/Services/PedagogyService.php` | `createAssignment` et `upload` réutilisent `authorizedCohort` déjà existant. | Même ordre rôle → validation de l’identifiant → promotion/affectation ; validations spécifiques suivantes conservées. Tests PDO simulés ; intégration MySQL non exécutée. |
| `app/Support/Database.php`, `bin/serve-apache.php`, `bin/serve-portal.php` | Les deux requêtes de disponibilité sont regroupées dans `Database::checkPortalSchema(PDO)`. Variables de retour inutilisées supprimées. | Une définition des tables minimales nécessaires. Connexion, exceptions, diagnostics propres, délégation migrate/seed et démarrage respectifs conservés. Ordre SQL et propagation d’exception vérifiés avec PDO simulé ; démarrages LAMP/Docker réels non validés. |
| `public/assets/css/chat.css` | Deux règles adjacentes avec exactement `display: block` et `width: 100%` regroupent leurs sélecteurs. | Même contexte responsive, spécificité, valeurs et ordre par rapport aux autres règles. Aucun autre style fusionné. Pas de comparaison visuelle navigateur. |
| `.github/workflows/checks.yml`, `package.json`, `tests/` | Ajout de l’audit standard après setup PHP/Node ; nouveaux tests branchés dans `npm test` et CI. | Seuils inchangés : 5 lignes, 50 tokens, seuil 0. Pas de nouvelle dépendance applicative. Le runner doit pouvoir télécharger jscpd via npm. CI distante non exécutée. |

Aucun changement de route, migration, contenu, secret, transaction, contrôle CSRF, requête préparée ou règle d’authentification. `ChatService`, `PortalAuth` et `ChatSocket` restent inchangés : les détections de ces deux derniers portent surtout sur les en-têtes et constructeurs. Dans ChatService, conserver les vérifications avant et dans la transaction est nécessaire, notamment pour l’idempotence concurrente et le nettoyage des fichiers.

## C. Bilan final

Même version de jscpd, mêmes chemins et mêmes seuils détaillés avant/après.

| Indicateur | Avant | Après |
|---|---:|---:|
| Doublons PHP | 57 | 55 |
| Doublons CSS | 9 | 8 |
| Doublons JavaScript | 2 | 1 |
| Doublons totaux | 68 | 64 |
| Lignes dupliquées | 337 | 315 |
| Lignes analysées | 14 645 | 14 636 |
| Duplication globale | 2,30 % | 2,15 % |

L’audit standard final réussit : **0 clone à 5 lignes/50 tokens**, seuil 0 inchangé. Le détaillé reste un diagnostic à 3 lignes/25 tokens. Le premier passage standard après extraction des requêtes détectait encore le préambule des lanceurs ; la suppression des variables inutilisées a réduit ce préambule, sans masquer de fichier ni changer la configuration.

La baisse nette de quatre détections ne compte pas directement les factorisations : les frontières des clones changent. Le détaillé rapproche encore les préambules des lanceurs alors que leurs requêtes sont désormais centralisées.

Répétitions conservées : autorisations spécifiques à chaque action, contrôle d’idempotence répété à deux moments, verrou de planning conservé avant l’accès à la promotion, petites structures HTML, coordonnées éditoriales, variantes de palettes, libellés de tableaux et grilles CSS de composants indépendants. Elles sont justifiées bloc par bloc dans l’inventaire. L’objectif reste la maintenance sans abstraction disproportionnée.

## D. Vérifications réellement exécutées

Réussites :

- `npm test` avant et après : 890 contrôles PHP et 294 routes rendues ; 273 pages sources et 23 565 blocs conservés ; 36 écrans pédagogiques ; composants/échappement ; contrôles JS ; 728 références d’images.
- Nouveaux tests `php tests/refactoring.php` et `node tests/chat_drafts.test.js`, également intégrés à `npm test` : ordre des contrôles rôle/promotion/affectation, disponibilité SQL et erreurs, saisie/changement/restauration/effacement de brouillon, absence de conversation, exceptions de stockage.
- `php tests/portal_security.php` : 5 contrôles (TOTP, chiffrement et CSRF).
- Syntaxe de tous les PHP dans `app bootstrap config public tests bin` et `node --check public/assets/js/modules/chat.js`.
- `TEST_BASE_URL=http://127.0.0.1:8097 python3 tests/http_smoke.py` contre un serveur PHP local : 294 pages, 3 API, ressources/liens, 404 et refus des écritures.
- `python3 tests/docker_config.py` : structure Compose, réseau, volumes, exclusions et génération isolée de configuration ; aucun conteneur exécuté par ce test.
- Les deux commandes npm d’audit finales et `git diff --check`.

Incidents résolus : accès npm et HTTP local refusés dans le sandbox ; audits et HTTP réexécutés avec les permissions nécessaires. Le mode npm hors ligne n’avait pas les métadonnées du registre en cache. Un audit standard intermédiaire échouait sur un clone des lanceurs, corrigé avant le passage final.

Non exécuté / limites :

- Accès au socket Docker refusé, y compris après tentative hors sandbox. Pas de build ni de `up`; aucun volume supprimé et aucune réinitialisation de base.
- Tests MySQL `tests/chat_mysql.php`, `tests/pedagogy_mysql.php` et parcours authentifiés HTTP non exécutés : pas de base QA isolée préparée. Les doubles PDO ne prouvent pas le comportement transactionnel/concurrent réel.
- Apache/LAMP réel, WebSocket et matrice des rôles en navigateur non exécutés ; pas de validation responsive/visuelle réelle. HTML du composant de provenance vérifié par égalité exacte ; structure des vues couverte par les tests existants.
- Version PHP locale : **8.5.4**, pas 8.4. Compatibilité 8.4 attendue (aucune nouvelle syntaxe incompatible), à confirmer par CI/Docker.
- GitHub Actions distant non exécuté. Aucune régression observée dans les contrôles exécutés ; les périmètres non testés restent à vérifier.

## E. Git et reprise

Branche dédiée `refactor/audit-duplications` depuis la `main` locale. La mission autorise explicitement la création de commits ; message du commit de livraison : `refactor: mutualise shared code and audit duplicates in CI`. Aucun push sur main ni vers le dépôt distant.

Pour relire : `git show --stat` puis `git show`. Pour revérifier : `npm test`, `php tests/portal_security.php`, `npm run audit:duplicates`, `npm run audit:duplicates:detailed`. Pour le public : démarrer `php -S 127.0.0.1:8097 -t public public/router.php`, puis lancer la commande HTTP ci-dessus. Pour les droits réels, utiliser une base dédiée suffixée `_qa`, `APP_ENV=test` et les procédures QA existantes ; ne pas utiliser les données persistantes pour ces tests.
