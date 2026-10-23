# Audit d’architecture et nettoyage des doublons

Audit du contenu du dossier de travail le 23 octobre 2026.
Responsable : Codex. Branche `refactor/nettoyage-doublons`, créée depuis main.
La fraîcheur distante de main n’a pas été revérifiée. Aucun commit ni push créé.
Les nombreux changements antérieurs présents dans le dossier sont conservés.

## Périmètre et méthode

Revue du parcours HTTP, des contrôleurs, services, repositories, vues, modules
frontend, styles, configuration, migrations et scripts de lancement. Mesure
jscpd 5.4.2 sur `app`, `public/assets/js`, `public/assets/css`, `bootstrap`,
`config` et `bin`. Exclusion de fait des dépendances tierces, des secrets et des
données JSON, qui ne sont pas dans ces chemins de code.

Deux seuils ont été employés :

- 5 lignes et 50 tokens, commande existante : aucun gros bloc détecté au départ.
- 3 lignes et 25 tokens : petits blocs répétés, interprétés manuellement avant
  toute modification. Les déclarations de constructeur et les imports peuvent
  être signalés sans justifier une abstraction supplémentaire.

Les « blocs » du rapport ne sont pas des fichiers en double. Certains se
recouvrent. Les lignes dupliquées sont la mesure de cet outil, pas un nombre
exact de lignes que l’on pourrait supprimer sans conséquences.

## Résultats comparables avant/après

| Mesure, seuil 3 lignes / 25 tokens | Avant | Après |
| --- | ---: | ---: |
| Blocs signalés | 94 | 66 |
| Lignes considérées dupliquées | 462 | 318 |
| Proportion de lignes dupliquées | 3,26 % | 2,25 % |
| Tokens dupliqués | 2 968 | 2 032 |

Environ 31 % de réduction des lignes signalées, sans réécriture des règles métier.
Un composant de provenance a été ajouté : 93 sources analysées avant, 94 après.
Les rapports bruts ont été générés dans `/tmp/expernet-duplicates-detailed/`
et `/tmp/expernet-duplicates-after/` ; ils sont temporaires et non versionnés.

Pour reproduire :

```bash
npm run audit:duplicates
npm run audit:duplicates:detailed
```

La seconde commande produit un rapport dans `storage/audit-duplicates`, ignoré
par Git. Elle nécessite Node/npm pour l’audit, pas pour servir le site PHP.

## Nettoyage effectué

1. **En-têtes publics** : huit pages réutilisent `partials/hero.php` au lieu de
   recopier le fil d’Ariane, l’introduction et le titre : contact, devis,
   événements, équipe, histoire, palettes, plan du site et planning public.
2. **Introduction juridique** : `legal-intro.php` réutilise le même hero et
   conserve ses avertissements et son enveloppe de contenu.
3. **Provenance** : six pages utilisent `partials/source-note.php`. L’URL est
   échappée et le texte affiché est inchangé.
4. **Rendu imbriqué** : `View` fournit sa propre instance aux sous-vues, sans
   laisser les données remplacer cette variable interne. Les composants peuvent
   ainsi se réutiliser sans recopier leur HTML ou propager manuellement le renderer.
5. **PDO** : `all()` et `execute()` réutilisent la préparation et l’exécution
   centrales de `PedagogyRepository::query()`, déjà utilisées par `one()`.
6. **CSS** : `.word-digital` et `.serif` partagent leurs quatre déclarations
   typographiques identiques. Les règles propres à chaque sélecteur restent séparées.
7. **Documentation** : architecture et README des migrations corrigés. Ils
   décrivaient encore les comptes/MySQL comme non implémentés, malgré le code existant.
8. **Vérification continue** : test des composants de vues ajouté à `npm test`
   et commande d’audit fin documentée dans `package.json`.

## Répétitions conservées volontairement

- Contrôles d’autorisation des services : des signatures similaires ne signifient
  pas des droits identiques. Le formateur affecté, le propriétaire d’un document,
  le membre d’une promotion et le participant d’une discussion sont distincts.
- Déclarations des dépendances dans les constructeurs et imports : une classe
  de base artificielle ferait perdre en clarté sans apporter de règle commune.
- CSS responsive et palettes : des sélecteurs répétés peuvent être des surcharges
  intentionnelles. Les supprimer aveuglément modifierait le rendu.
- Contenus éditoriaux/juridiques importés : ne pas supprimer un paragraphe parce
  qu’il figure aussi sur une autre page officielle.
- Dump initial et migrations : ces fichiers correspondent à des chemins
  d’installation différents ; les supprimer ne résout pas leur cohérence.
- Réponses HTTP proches mais distinctes : fichiers privés, exports ICS et API
  JSON possèdent des en-têtes et contrôles différents.

L’objectif n’est pas d’atteindre zéro répétition au prix d’un code plus difficile
à comprendre. Les 66 petits blocs restants sont conservés pour ces raisons.

## Analyse d’architecture

Le découpage principal est cohérent : front controller, coordination HTTP,
services métier, repositories et vues. Le catalogue JSON et le portail MySQL
ont des responsabilités différentes. Les grands programmes par formation ne
sont pas copiés dans autant de fichiers PHP : ils partagent leurs gabarits.

Points de vigilance qui restent des travaux distincts :

- `PedagogyService::state()` prépare beaucoup de données en un seul appel.
  Un découpage par écran pourra être utile si les promotions et données augmentent.
- Les téléchargements pédagogiques et ceux du chat n’ont pas les mêmes règles
  de taille, formats et contrôle antivirus. Les fusionner requiert une politique
  produit explicite, pas seulement un nettoyage syntaxique.
- Le routage public et la distribution des actions privées sont séparés. Leur
  consolidation doit préserver les méthodes HTTP, statuts et contrôles actuels.
- Les évolutions LAMP, Docker, PHP et frontend existent sur plusieurs branches,
  avec de nombreux changements locaux. Un assemblage final doit être revu et
  testé ; le présent audit ne déclare pas main prêt à livrer.

Aucun nouveau framework, aucune base supplémentaire et aucune migration SQL
n’ont été introduits par ce nettoyage.

## Tests et preuves

- Capture avant modifications des titres, paragraphes, liens, images et
  formulaires rendus pour les 294 routes, puis comparaison après nettoyage :
  **identiques**. Les espaces de mise en forme HTML sont normalisés ; ce contrôle
  ne prétend pas comparer pixel par pixel le CSS.
- `php tests/run.php` : **890 contrôles PHP et 294 routes réussis**.
- `php tests/content_coverage.php` : **273 pages et 23 565 blocs conservés**.
- `php tests/portal_views.php` : **36 écrans pédagogiques réussis**.
- `php tests/portal_security.php` : **5 contrôles réussis**.
- `php tests/view_components.php` : rendu imbriqué, échappement, protection des
  variables internes et fermeture du tampon après exception : **OK**.
- `node tests/frontend.test.js` et `node tests/carousel.test.js` : **OK**.
- `node tests/assets.test.js` : **728 références valides, aucune image identique**.
- MySQL local réel : appels `all`, `one`, `execute` vérifiés avec paramètres
  préparés et requêtes SELECT uniquement. **Aucune donnée modifiée**.
- Syntaxe des fichiers PHP impactés et diff ciblé : **OK**.

Les scénarios MySQL complets de mutations, concurrence, le redémarrage Docker et
le rendu pixel par pixel ne sont pas déclarés testés. Les changements ne touchent
pas leurs règles métier ou configuration de déploiement.

## Review et livraison

Aperçu des sources sur http://127.0.0.1:8088/. Tester contact, devis, histoire,
confidentialité et planning public, puis vérifier le carrousel et le catalogue.
Les copies Apache/Docker déjà déployées ne sont pas mises à jour par ce lot.

Fichiers de ce lot : `app/Support/View.php`, `app/Repositories/PedagogyRepository.php`,
les huit vues publiques citées, les vues juridiques (note de source uniquement),
`partials/legal-intro.php`, le nouveau `partials/source-note.php`, la règle commune
CSS, `package.json`, `tests/view_components.php` et les documents d’architecture.
Plusieurs de ces fichiers contiennent aussi des modifications antérieures :
examiner les portions du diff avant toute inclusion dans un commit.

Proposition après review :
`refactor(core): reuse shared page components and prepared query execution`
