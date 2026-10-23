# Skills frontend installés et application

Installation du 23 octobre 2026 dans `~/.codex/skills/`. Ces outils sont locaux à
la machine : ils ne sont pas inclus dans un clone du dépôt. Codex les découvre
automatiquement au prochain tour. Les instructions ont été lues et appliquées
pendant ce lot.

## Sélection et sources

| Skill | Source | Usage |
| --- | --- | --- |
| ui-ux-pro-max | [nextlevelbuilder](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) | Recommandations UI/UX consultables localement |
| frontend-design | [Anthropic](https://github.com/anthropics/skills/tree/main/skills/frontend-design) | Composition et identité visuelle |
| interaction-design | [wshobson](https://github.com/wshobson/agents/tree/main/plugins/ui-design/skills/interaction-design) | États et retours d’interaction, notamment chat |
| responsive-design | [wshobson](https://github.com/wshobson/agents/tree/main/plugins/ui-design/skills/responsive-design) | Adaptation mobile et cibles tactiles |
| accessibility-compliance | [wshobson](https://github.com/wshobson/agents/tree/main/plugins/ui-design/skills/accessibility-compliance) | Clavier, focus et sémantique |
| emil-design-eng | [Emil Kowalski](https://github.com/emilkowalski/skills) | Finition des composants et mouvement utile |
| animate | [Emil Kowalski](https://github.com/emilkowalski/skills) | Création d’animations ciblées |
| animation-performance | [Emil Kowalski](https://github.com/emilkowalski/skills) | Diagnostic des animations saccadées |
| review-animations | [Emil Kowalski](https://github.com/emilkowalski/skills) | Relecture des animations |
| matt-pocock-prototype | [Matt Pocock](https://github.com/mattpocock/skills/tree/main/skills/engineering/prototype) | Prototypage, installé sous un nom distinct |

La sélection UI provient du [catalogue UI Skills](https://www.ui-skills.com/).
Les skills React Native, Expo et Swift ne correspondent pas au socle PHP et
JavaScript natif de ce projet. Les règles d’interaction et d’accessibilité sont
applicables au chat ; aucune dépendance React n’est nécessaire.

## Utilisation sur Expernet

Partir du composant et du problème observé, puis lire le skill correspondant.
Les règles du projet et la direction V2 Océan restent prioritaires. Éviter
d’empiler plusieurs recommandations contradictoires ou de générer une nouvelle
charte à chaque intervention. Les skills de prototypage et de création
d’animations restent disponibles pour un lot qui en a besoin.

Recherche UI/UX Pro Max utilisée pour ce lot :

```sh
python3 ~/.codex/skills/ui-ux-pro-max/scripts/search.py 'keyboard focus modal' --domain ux -n 3
python3 ~/.codex/skills/ui-ux-pro-max/scripts/search.py 'touch target mobile input' --domain ux -n 3
```

Résultats retenus : focus visible et non masqué par le panneau flottant,
espacement des commandes et adaptation des contrôles tactiles. Le projet retient
44 px pour les boutons du chat comme confort d’utilisation ; ce choix ne doit
pas être présenté comme le seuil universel de conformité WCAG.

## Application du lot

| Avant | Après | Pourquoi |
| --- | --- | --- |
| Focus commun limité aux boutons, liens et inputs | Selects, textareas et summaries couverts | Parcours clavier cohérent |
| Déplacement au survol sur tous les périphériques | Survol animé réservé aux pointeurs précis ; retour à la pression | Éviter les états de survol persistants au toucher |
| Petites commandes du chat | Boutons d’au moins 44 × 44 px et commandes espacées sur mobile | Faciliter le toucher |
| Champs du chat à 14 px | Champs à 16 px sur petit écran | Limiter le zoom automatique mobile |
| Liste recréée sans restauration du focus | Conversation focalisée retrouvée après actualisation | Continuer à naviguer au clavier |
| Historique recréé toutes les cinq secondes | Historique inchangé conservé | Préserver la sélection de texte et limiter les annonces répétées |
| Ouverture sans destination clavier explicite | Focus sur la formation ou le titre de conversation | Rendre l’ouverture prévisible |
| Panneau flottant ouvert après sortie du focus | Fermeture lorsque le clavier quitte le widget | Éviter de masquer le contrôle suivant |
| Label de rédaction uniquement accessible aux lecteurs d’écran | Label visible et bouton « Envoi en cours… » | Clarifier l’action et son état |
| Historique effacé sur erreur de rafraîchissement | Messages déjà reçus conservés | Garder le contexte en cas de coupure |

Pas de nouvelle animation des messages : la consultation fréquente doit rester
immédiate. Les transitions de couleur sont courtes et désactivées avec la
préférence de réduction des mouvements.

## Vérification manuelle

Lancer `npm start`, puis ouvrir une session de test et Discussions. Vérifier :

1. Ouverture du widget, focus sur la formation, choix d’une conversation.
2. Retour à la liste, navigation clavier et focus conservé après cinq secondes.
3. Échap : fermeture et retour au lanceur. Tab en dehors : fermeture du widget.
4. Rédaction avec label visible, état d’envoi et reprise sur erreur réseau.
5. Largeur 390 px : boutons utilisables, champs lisibles, aucun débordement de page.
6. Historique sélectionné : une actualisation sans nouveau message ne le recrée pas.

Les essais de rendu avec données fictives ne valident pas l’envoi réel, MySQL,
WebSocket, les droits ni le rendu du clavier virtuel sur un téléphone physique.

## Résultats de vérification du 23 octobre 2026

- `npm test` : 890 contrôles PHP, 294 routes rendues, 273 pages et 23 565 blocs
  de contenu conservés, 36 écrans pédagogiques, 5 contrôles JavaScript,
  728 références d’images ; réussite.
- `TEST_BASE_URL=http://127.0.0.1:8094 python3 tests/http_smoke.py` : 294 pages,
  trois API, liens, ressources, 404 et refus d’écritures vérifiés.
- Après la dernière correction du focus : `node --check` sur le module chat,
  `node tests/frontend.test.js`, syntaxe PHP du gabarit et `git diff --check` : réussite.
- Navigateur avec API fictive isolée : ouverture, sélection avec focus sur le
  titre, fermeture par Échap avec retour au lanceur, sortie avec Tab sans panneau
  masquant le lien suivant ; réussite. À 390 px, rendu inspecté et conservation
  du focus sur une conversation après plusieurs actualisations vérifiée.
- Pas de test utilisateur externe, lecteur d’écran réel, téléphone physique,
  envoi réel, MySQL ni WebSocket dans ce lot.

Branche : `feat/frontend-skills`, créée depuis `main`, références distantes
récupérées et base identique à `origin/main` au démarrage. Responsable : Codex.
Les modifications déjà présentes dans `docs/PEDAGOGIE.md` et
`bin/setup-local-db.php` ne font pas partie du lot et n’ont pas été modifiées.

Pour relire : `git diff -- AGENTS.md app/Views/pedagogy/chat.php public/assets/css/base.css public/assets/css/chat.css public/assets/js/modules/chat.js`,
puis consulter ce document nouveau. Pour essayer : `npm start`, ouvrir
`http://127.0.0.1:8080/`, vérifier les formulaires publics puis Discussions avec un
compte local de test. Aucun schéma de base ni dépendance applicative modifié.

Message de commit proposé après review :
`fix(frontend): improve chat keyboard access and mobile controls`.
Aucun commit ni push réalisé.
