# Hiveforum — plugin de forum moderne pour Moodle

Forum de discussion avec réactions, tags, suivi des non-lus et gamification légère (streaks de contribution), pensé pour remplacer `mod_forum` sur des cours où l'engagement est prioritaire.

## Installation

1. Copier le dossier `hiveforum` dans `<moodleroot>/mod/`
2. Se connecter en tant qu'admin et aller sur **Administration du site > Notifications** pour lancer l'installation de la base de données (crée les tables listées dans `db/install.xml`)
3. Ajouter l'activité "Hiveforum" depuis n'importe quel cours

## Structure du plugin

```
hiveforum/
├── version.php              Métadonnées du plugin (composant, version, dépendances)
├── lib.php                  Callbacks core Moodle (add/update/delete_instance, supports, pluginfile)
├── locallib.php             Logique métier (requêtes discussions, streaks)
├── mod_form.php             Formulaire de configuration de l'activité
├── view.php                 Page liste des discussions
├── discuss.php               Page fil de discussion (thread)
├── post.php                 Création de discussion / réponse
├── index.php                Liste des instances dans un cours
├── styles.css                Design des cartes/badges/réactions
├── db/
│   ├── install.xml          Schéma des tables (discussions, posts, réactions, tags, reads, streaks)
│   ├── access.php            Capacités (permissions)
│   ├── services.php          Déclaration du web service AJAX de réactions
│   └── upgrade.php
├── lang/en, lang/fr          Chaînes de langue
├── classes/
│   ├── event/                Événements (vue, création discussion/post, réaction)
│   ├── external/             API externe toggle_reaction (AJAX)
│   ├── form/                 Formulaire de nouvelle discussion
│   └── output/renderer.php   Construction du contexte + rendu Mustache
├── templates/                discussion_list, discussion_card, discussion_thread, reply_form
└── amd/src/reactions.js      Toggle de réaction sans rechargement de page
```

## Ce qui est fonctionnel dans ce squelette

- Création/édition/suppression d'instance, formulaire d'activité
- Liste de discussions : filtres (toutes/non lu/suivies), recherche, badges (question/résolu/épinglé), tags, compteurs de réaction
- Fil de discussion avec réponses et réactions AJAX (like/love/idea) sans rechargement
- Création de discussion (avec tags et flag "question")
- Réponse à une discussion
- Suivi des lectures par utilisateur (non-lu)
- Streaks de contribution hebdomadaires (`hiveforum_bump_streak`)
- Permissions granulaires (`mod/hiveforum:*`)
- Design moderne en CSS (cartes, badges, pastilles), dark mode basique

## Ce qu'il reste à faire avant une mise en prod

- **Backup/restore** (`backup/moodle2/`) — nécessaire pour dupliquer/sauvegarder des cours
- **Éditeur riche** (Atto/TinyMCE) avec drag & drop d'images sur le formulaire de réponse rapide (actuellement `<textarea>` simple)
- **Notifications** (mentions @utilisateur, digest d'activité) via le message system de Moodle
- **Badges affichés dans l'UI** (actuellement seul le calcul du streak existe côté serveur)
- **Privacy API** (`classes/privacy/provider.php`) — obligatoire pour la conformité RGPD avant publication sur le plugins directory Moodle
- **Tests PHPUnit/Behat**
- **Build AMD** : le JS est en ES module source (`amd/src/`), il faut le compiler (`grunt amd`) avant prod
- Vue mobile affinée + pagination/infinite scroll sur la liste de discussions

## Notes techniques

- Compatible Moodle 4.0+ (`$plugin->requires = 2022041900`)
- Suit les conventions officielles `mod_*` (voir https://moodledev.io/docs/apis/plugintypes/mod)
- Les tables sont préfixées `hiveforum_*` pour éviter tout conflit avec `mod_forum`
