# Changelog

All notable changes to Hiveforum are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.0] - 2026-10-28

Moodle version: `2026102800`. Requires Moodle 5.3. Upgrade from 0.2.0 is supported.

### Added

- **Separate and visible groups** (`FEATURE_GROUPS`).
  - Active group selector on the list, the report and the subscriptions page.
  - The target group is chosen when starting a discussion, and the discussion then shows a group badge.
  - "All participants" is reserved for users with `moodle/site:accessallgroups`.
  - In separate groups mode, users only see their own group and discussions addressed to everyone.
  - In visible groups mode, everyone can read everything but only reply in their own group.
  - Group rules apply to the list, counters, search, statistics, tags, thread, web services, notifications, mentions and digests.
- **Subscriptions** modelled on `mod_forum`.
  - Optional, auto, forced and disabled modes, with a site-wide default set by the administrator.
  - Users can subscribe to the whole forum and still follow or ignore an individual discussion.
- **"Notifications" menu** on the list: subscribe to the forum, then choose one notification per post or one daily digest.
- **Daily digest**: scheduled task `send_digests` (5 pm), message provider `digest`.
- **"Subscriptions" page** (`subscribers.php`) for teachers and managers.
  - It shows each participant's subscription status, digest preference and number of followed discussions.
  - Bulk actions: subscribe or unsubscribe the selected users, or everyone.
  - New capability `mod/hiveforum:managesubscriptions`.
- **Turning notifications off**.
  - Site-wide: setting `mod_hiveforum/enablenotifications`.
  - Per activity: "Send notifications" setting.
- **`@user` mentions**.
  - Autocomplete in the editor, usable with the keyboard or the mouse, limited to people who can see the discussion.
  - Each mention is rendered as a link to the user's profile.
  - The mentioned person gets a single notification, even when subscriptions are disabled.
- **Completion rules**: discussions started, replies, posts and accepted answers, each with a minimum count.
- **Engagement report** (`report.php`, capability `mod/hiveforum:viewreports`).
  - Filter by period (7, 30 or 90 days, or all time) and by group.
  - Key figures and a chart of posts per week.
  - Per-participant table, sortable and downloadable (CSV, Excel, ODS…).
- **"Unanswered" filter**, and **sort orders** "Most reactions", "Most 👍" and "Most replies".
- **"Students can start" setting**: discussions and questions, discussions only, questions only, or nothing. Teachers always keep full rights.
- "Engagement report" and "Subscriptions" links in the activity navigation.
- Web services `mod_hiveforum_search_users` and `mod_hiveforum_set_preferences`.
- New tables:
  - `hiveforum_forum_subs` (forum subscriptions);
  - `hiveforum_digests` (digest preference);
  - `hiveforum_mentions` (mentions already notified).
- PHPUnit tests: group isolation, subscriptions, mentions, digests, completion, report, posting restrictions, sort orders and filters.

### Changed

- Following a discussion now depends on the forum's subscription mode. Following can be forced, and a chip says so.
- New discussion and reply notifications go to forum subscribers as well as discussion followers. Authors, mentioned users and digest subscribers are skipped to avoid duplicates.
- Read and reply rights are checked against the discussion's group in `discuss.php`, `post.php` and in every web service that acts on a discussion.
- Backup and restore:
  - new activity fields;
  - the discussion's group, mapped to the new group on restore;
  - forum subscriptions, digest preferences and mentions.
- Privacy API: export (under a "Preferences" subcontext) and deletion of the new data.
- Deleting or resetting the activity also removes the new data.

## [0.2.0] - 2026-10-23

Moodle version: `2026102300`. Full rewrite for Moodle 5.3. Upgrade from 0.1.0 is supported, with data migration.

### Added

- **React + TypeScript interface** built on the Moodle 5.3 ESM stack (`js/esm`, `core/react_autoinit`, `@moodlehq/design-system`).
  - Compiled modules are shipped, with `.dev.js` builds for developer mode.
- **New design**: "honey" accent colour, cards, avatars, badges, Moodle dark mode support, responsive layout.
  - A server-side `<noscript>` rendering is provided when JavaScript is disabled.
- **Discussion list**.
  - Filters (all, unread, followed, open questions, mine) with counters.
  - Instant search in titles and content.
  - Clickable popular tags.
  - Sort orders (activity, newest, most active).
  - "Show more" and "Mark all as read".
  - URL kept in sync, so the state survives reloading, the back button and shared links.
- **New discussion** in an inline panel.
  - Discussion or Question type, title and tags.
  - Markdown editor with automatic draft saving.
  - Link to the full editor (TinyMCE and files).
- **Discussion thread**.
  - Collapsible nested replies.
  - Inline reply under any post.
  - Inline editing and deletion with confirmation.
  - Permalinks (`#p123`).
- **Questions**: accept an answer (only one at a time), shortcut to the accepted answer, mark as resolved or reopen.
- **Live updates**: new replies are checked every 30 seconds while the tab is visible, with an "N new replies" banner.
- **Discussion following**: Moodle notifications (popup, email, mobile app) sent by an ad hoc task.
- **Moderation**: pin a discussion, edit or delete any post, rename and retag a discussion.
- "Teacher" badge and 🔥 streak of consecutive weeks with contributions.
- **11 AJAX web services** (`core_external`).
- Events, Privacy API, and backup and restore with user data.
- PHPUnit tests: API, web services, Privacy, backup and restore.
- **Accessibility**: keyboard navigation, `aria-pressed` and `aria-live`, visible focus, `prefers-reduced-motion`.

### Changed

- Moodle 5.1+ `public/` directory layout. Requires Moodle 5.3.
- All business logic lives in `classes/local` (`forum`, `api`, `exporter`), which replaces `locallib.php`.
- All permission checks live in a single class.
- Optimised queries: no more N+1 queries.

### Removed

- The 0.1.0 AMD modules, replaced by the React components.

## [0.1.0] - 2026-07-29

Moodle version: `2026072900`. Initial alpha release, for Moodle 4.0 and later.

### Added

- "Hiveforum" activity: discussions, replies, tags and unread tracking.
- Reactions through an AJAX web service.
- Weekly contribution streaks.
- English and French language strings.

[0.3.0]: #030---2026-10-28
[0.2.0]: #020---2026-10-23
[0.1.0]: #010---2026-07-29
