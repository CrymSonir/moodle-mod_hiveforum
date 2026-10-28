# Hiveforum : forum moderne pour Moodle 5.3

Forum de discussion pensé pour l'engagement : réactions, questions avec réponse acceptée, tags,
suivi des non-lus, abonnement aux discussions avec notifications, et séries de contribution
hebdomadaires. L'interface est écrite en **React + TypeScript** avec la nouvelle pile ESM de
Moodle 5.3 (`js/esm`, `react_autoinit`, `@moodlehq/design-system`).

## Installation

1. Copier le dossier dans `<moodle>/public/mod/hiveforum` (Moodle 5.1+ : sous `public/`).
2. *Administration du site > Notifications* pour installer ou mettre à jour la base.
   La mise à jour depuis la 0.1.0 est prise en charge (`db/upgrade.php`), avec reprise des données.
3. Ajouter l'activité « Hiveforum » dans un cours.

Les fichiers compilés `js/esm/build/` sont livrés : rien à compiler pour l'utiliser.

## Fonctionnalités

| | |
|---|---|
| **Liste** | filtres (toutes, non lues, suivies, questions ouvertes, sans réponse, les miennes) avec compteurs, recherche instantanée (titres et contenus), tags populaires cliquables, tri (activité, récentes, plus actives, plus de réactions, plus de 👍, plus de réponses), « Afficher plus », « Tout marquer comme lu », URL synchronisée (rechargement, retour, partage de lien) |
| **Nouvelle discussion** | panneau en ligne : type Discussion / Question, titre, tags (chips), éditeur Markdown ; lien vers l'éditeur complet (TinyMCE + fichiers) |
| **Fil** | message d'ouverture, réponses imbriquées repliables, réponse en ligne sous n'importe quel message, édition en ligne, suppression avec confirmation (et des réponses qui en dépendent), lien permanent `#p123` |
| **Questions** | « Accepter la réponse » (auteur ou modérateur), une seule réponse acceptée, raccourci vers elle sous la question, « Marquer comme résolue » / « Rouvrir » |
| **Réactions** | 👍 ❤️ 💡 avec mise à jour optimiste |
| **Temps réel** | vérification des nouvelles réponses toutes les 30 s quand l'onglet est visible, bandeau « 2 nouvelles réponses » |
| **Groupes** | groupes séparés et visibles : liste, compteurs, recherche, statistiques, fil, web services, notifications et mentions limités à ce que l'utilisateur a le droit de voir ; choix du groupe destinataire à la création ; `moodle/site:accessallgroups` pour « Tous les participants » |
| **Abonnements** | modes facultatif / automatique / imposé / désactivé (comme mod_forum), abonnement au forum + suivi par discussion, menu « Notifications » (abonnement, une notification par message ou résumé quotidien), page « Abonnements » pour les enseignants (abonner / désabonner en masse) |
| **Notifications** | messages Moodle (popup, e-mail, app) par tâche ad hoc ; résumé quotidien par tâche planifiée (17 h) ; interrupteur global `mod_hiveforum/enablenotifications` et réglage par activité |
| **Mentions** | `@` dans l'éditeur → autocomplétion des participants qui peuvent voir la discussion, lien vers le profil, notification unique (même si l'abonnement est désactivé) |
| **Publication** | réglage « Les étudiants peuvent lancer » : discussions et questions / discussions seulement / questions seulement / rien |
| **Achèvement** | règles : discussions lancées, réponses, messages, réponses acceptées |
| **Rapport d'engagement** | par période et par groupe : indicateurs, messages par semaine, tableau triable et exportable (CSV, Excel…) par participant |
| **Gamification** | série 🔥 de semaines consécutives avec contribution (en-tête et à côté du nom), badge « Enseignant » |
| **Modération** | épingler, modifier ou supprimer tout message, renommer et retaguer une discussion |
| **Accessibilité** | navigation clavier, `aria-pressed` / `aria-live`, focus visible, `prefers-reduced-motion`, rendu `<noscript>` côté serveur |
| **Thème** | couleurs du thème (`--bs-*`), mode sombre Moodle (`data-bs-theme="dark"`), responsive |

## Architecture

```
hiveforum/
├── view.php, discuss.php       Pages : montent les composants React (props initiales = 1re page de données)
├── post.php                    Éditeur complet (TinyMCE + fichiers) et réponse sans JavaScript
├── classes/
│   ├── local/forum.php         Activité chargée (instance, cm, contexte) + TOUTES les vérifications de droits
│   ├── local/api.php           Logique métier (lecture/écriture), sans N+1
│   ├── local/exporter.php      Mise en forme des données pour React et pour les web services
│   ├── external/*.php          11 web services AJAX (core_external), structures partagées
│   ├── output/                 forum_page / thread_page (react_component_renderable), renderer, chaînes
│   ├── privacy/provider.php    API Privacy (RGPD) : export, suppression
│   ├── task/notify_followers.php
│   └── event/                  Événements (création, modification, suppression, réaction…)
├── backup/moodle2/             Sauvegarde / restauration (avec données utilisateur)
├── js/esm/src/                 Sources React/TypeScript
│   ├── Forum.tsx, Thread.tsx   Composants racines
│   ├── forum/, thread/         Sous-composants
│   └── common/                 repository (web services), i18n, Composer, ReactionBar, Icon…
├── js/esm/build/               Modules compilés (+ .dev.js utilisés en mode développeur)
├── templates/                  Rendu <noscript>
├── styles.css                  Design (variables Bootstrap du thème + couleur « miel »)
└── tests/                      PHPUnit (API, web services, privacy, backup/restore) + générateur
```

Les composants sont montés par `core/react_autoinit` à partir de
`data-react-component="@moodle/lms/mod_hiveforum/Forum"`. Les chaînes de langue sont envoyées
dans les props pour un affichage immédiat dans la bonne langue.

## Développement

Depuis la racine de Moodle (avec `npm install`) :

```bash
npx grunt esm          # compile js/esm/src -> js/esm/build (tous les composants)
npx grunt esm:watch    # recompilation à la volée
vendor/bin/phpunit --testsuite mod_hiveforum_testsuite
```

En mode développeur (`$CFG->cachejs = false`), Moodle sert les fichiers `.dev.js` avec sourcemaps.
`js/esm/src/swizzle.json` déclare quels composants un thème peut surcharger (`scripts/swizzle.mjs`).

Historique des versions : voir [CHANGELOG.md](CHANGELOG.md).

## Compatibilité

Moodle 5.3 minimum (`$plugin->requires = 2026100500`) : l'interface repose sur la pile React/ESM
introduite dans cette version.

## Pistes pour la suite

- Tests Behat
- Notation des contributions (`FEATURE_GRADE_HAS_GRADE`)
- Heure d'envoi du résumé configurable par l'utilisateur
