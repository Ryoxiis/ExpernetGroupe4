# Expernet : présentation du projet et évolutions

## Une plateforme commune

Le Groupe 4 propose une application web réunissant le catalogue de formation,
les informations Campus et les activités pédagogiques. L’analyse distingue les
usages de WordPress, GALIA, Teams et des feuilles de présence papier. La cible
remplace ces usages dans une interface commune ; ce prototype ne synchronise
ni GALIA ni Microsoft Teams. Les comptes Microsoft 365 restent indépendants.

L’objectif initial était une proposition accompagnée de design et de code
fonctionnel pour le vendredi 23 octobre 2026, pas la livraison d’un système
institutionnel entièrement exploitable. L’échéance et les critères de la suite
restent à confirmer avec l’enseignant.

## Ce qui a été construit

- Reprise et organisation des contenus des sites Expernet et Expernet Campus :
  149 formations, 11 diplômes, 294 routes, 273 pages sources et 23 565 blocs.
- Architecture PHP native par contrôleurs, services, repositories et vues partagées ;
  catalogue public JSON et portail privé MySQL/PDO.
- Accès pédagogique sans inscription publique : invitation administrative,
  secret temporaire à usage unique, changement initial et MFA TOTP configurable.
- Vues étudiant, formateur et administration ; promotions et affectations ;
  planning personnel, conflits, indisponibilités, recherche et export ICS.
- Ressources privées, devoirs, rendus textuels, brouillons et publication des notes,
  examens, jury, compétences, livret et satisfaction selon les règles du prototype.
- Présences distancielles proposées : fenêtre temporelle, déclaration par l’étudiant,
  validation/correction avec historique et unicité en base.
- Discussions réelles entre comptes autorisés, groupes et canaux, non-lus,
  reprise idempotente, pièces jointes et WebSocket optionnel avec repli HTTP.
- Mode sombre, responsive, calendrier horizontal PC, carrousel de six photos
  officielles et conservation de la palette Océan.
- Lanceurs locaux, LAMP, Docker Compose, migrations et façade Vercel vers Alwaysdata.
- Tests automatisés, audits de doublons et de médias, mesures de couverture et CI.

## Ce que l’on ne doit pas présenter comme terminé

| Sujet | Situation réelle |
| --- | --- |
| CMS | Les contenus sont des instantanés JSON ; aucun back-office éditorial complet. |
| Microsoft | Aucun SSO, création de compte Microsoft, Microsoft Graph ou synchronisation Teams. |
| E-mails | Pas d’envoi automatique des invitations ou formulaires publics. |
| Présences | Déclaration et validation de prototype, pas une signature certifiée. |
| Visioconférence | Intégration Jitsi/JWT possible ; serveur audiovisuel à configurer. |
| Antivirus | Scan obligatoire en production ; worker local privé de démonstration dépendant du poste. |
| Conservation du chat | 24 h dans le prototype ; politique officielle et ordonnanceur à valider. |
| Exploitation | Démonstration publiée ; sauvegardes, restauration, supervision et SLA non qualifiés. |
| Recette | Tests locaux documentés ; Windows, appareils physiques, charge et accessibilité exhaustive à poursuivre. |

La publication Vercel n’implique pas une exécution du PHP par Vercel : les requêtes
sont routées vers l’origine Alwaysdata, où restent PHP, MariaDB, sessions et fichiers.
La protection PHP upload-progress ne remplace pas les correctifs du moteur.

## Suite proposée, à valider

| Priorité | Évolution | Pourquoi | Critère de réussite |
| --- | --- | --- | --- |
| P0 | Moteur PHP corrigé et configuration réellement appliquée | Réduire l’exposition aux failles connues | Version fournisseur vérifiée et valeur INI effective sur le moteur Web |
| P0 | Sauvegarde + restauration répétée | Un volume persistant n’est pas une sauvegarde | Restaurer base et pièces jointes sur un environnement séparé |
| P0 | Antivirus supervisé et permanent | La démonstration dépend d’un ordinateur actif | Service sain, signatures récentes, refus en panne et alertes |
| P0 | Validation des droits et procédures Expernet | Les règles observées sont incomplètes | Matrice de droits approuvée et scénarios métier acceptés |
| P1 | Comptes : récupération, réactivation, retrait d’affectation | Cycle de vie incomplet | Scénarios d’activation/révocation couverts et historisés |
| P1 | Modification/annulation des séances | Répondre aux changements d’emploi du temps | Résolution des conflits sans perte silencieuse des ressources/présences |
| P1 | SMTP et notifications | Éviter l’affichage manuel de secrets et prévenir les usagers | Envoi contrôlé, reprise en panne, aucune fuite de secret |
| P1 | Politique de conservation configurable | Adapter le prototype aux obligations de l’organisme | Durées approuvées, purge supervisée et information des personnes |
| P1 | Accessibilité et mobile réel | Qualifier les parcours au-delà d’une émulation | Tests clavier/lecteur d’écran et appareils réels documentés |
| P2 | Back-office catalogue | Mettre à jour le JSON sans modifier les fichiers à la main | Publication, brouillons, droits et historique éditorial |
| P2 | Abonnement calendrier | Actualiser automatiquement les séances | Flux privé révocable, confidentialité et mises à jour testées |
| P2 | SSO Microsoft ou intégration documentaire | Simplifier l’accès si l’usage le justifie | Consentements, périmètre et désactivation maîtrisés |
| P2 | Charge et architecture multi-instance | Dépasser le prototype mono-instance | Mesures, files de tâches, sessions partagées et verrou distribué si nécessaire |

Ces évolutions ne sont ni déjà réalisées ni promises pour une date arbitraire.
Les choisir avec le binôme, Expernet et l’enseignant, puis les découper en lots testables.
