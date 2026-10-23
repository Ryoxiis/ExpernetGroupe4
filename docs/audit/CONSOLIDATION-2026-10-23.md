# Consolidation du 23 octobre 2026

## Corrections

- Calendrier : suppression des attributs style bloqués par la CSP, ajout d’un nonce et d’un test de rendu hostile.
- Médias : suppression d’un fichier HTML portant une extension PNG et de ses références ; validation des signatures des images.
- Tests : inclusion des contrôles calendrier, composants, sécurité, rétention et brouillons dans la suite principale et la CI.
- Documentation : README, architecture, périmètre, limites, déploiement et évolutions remis en cohérence.
- Revue des ajouts existants : scanner ClamAV privé, purge des discussions et façade Vercel.

## Vérifications réalisées

`npm test` réussit : 890 vérifications PHP, 294 routes, 273 pages sources et
23 565 blocs conservés, vues et calendrier, sécurité, rétention, JavaScript et
712 références images. Les contrôles antivirus passent : 14 cas de flux et
9 cas de file. Le test de configuration Docker et `composer validate` passent
(avertissement de licence non renseignée). Syntaxe : 102 fichiers PHP, 11 JS,
13 Python et 5 shell. Le contrôle HTTP local vérifie 294 pages, 3 API, liens,
ressources, erreurs et refus d’écriture.

Le contrôle standard des doublons (5 lignes, 50 tokens) relève 0 clone sur
109 sources. Le relevé détaillé trouve 73 fragments, soit 364 lignes sur
15 388 (2,3655 %). Il est complété par un
contrôle détaillé (3 lignes, 25 tokens). Ce dernier remonte aussi de courts
fragments de structure, imports et règles CSS : ils ne justifient pas à eux
seuls une abstraction. Les résultats détaillés sont conservés dans les
fichiers de mesures de cet audit.

## Limites de la preuve

Les scénarios MySQL qui écrivent des données ne sont pas relancés : aucune base
de test isolée n’a été autorisée pour cet audit. Aucune migration ni purge n’a
été exécutée sur les données existantes. La CI GitHub doit être constatée après
publication ; les tests locaux ne prouvent pas son résultat. Pas de nouvelle
qualification Windows, lecteur d’écran, appareils physiques ni déploiement.

Le réglage de session PHP est une mitigation partielle de l’alerte CERT ;
la mise à jour vers une version corrigée reste nécessaire. La conservation
des messages pendant 24 heures et les règles institutionnelles de présence
sont à valider. La suppression de fichiers n’est pas annulée par un rollback SQL.

Le dossier de préparation à l’oral et le PDF du binôme restent hors dépôt.

## Intégration de la dernière évolution distante

Avant fusion, main a reçu un agenda journalier utilisant une feuille externe
`planning.css`. Cet agenda est conservé. Il supprime lui aussi les styles inline ;
le test CSP contrôle désormais la feuille externe, le cours rendu et l’échappement.
Le test `planning_day.php` est conservé dans npm et la CI. Les mesures de doublons
ci-dessus correspondent au contrôle précédant cette intégration.
