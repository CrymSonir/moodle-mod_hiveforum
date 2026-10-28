<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Web services used by the React front-end (and available to the mobile app).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_hiveforum_get_discussions' => [
        'classname' => \mod_hiveforum\external\get_discussions::class,
        'description' => 'List the discussions of a hiveforum.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/hiveforum:view',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_hiveforum_get_discussion' => [
        'classname' => \mod_hiveforum\external\get_discussion::class,
        'description' => 'Get a discussion with its posts.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/hiveforum:view',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_hiveforum_create_discussion' => [
        'classname' => \mod_hiveforum\external\create_discussion::class,
        'description' => 'Start a new discussion.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/hiveforum:startdiscussion',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_hiveforum_update_discussion' => [
        'classname' => \mod_hiveforum\external\update_discussion::class,
        'description' => 'Rename a discussion and update its tags.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_hiveforum_set_discussion_state' => [
        'classname' => \mod_hiveforum\external\set_discussion_state::class,
        'description' => 'Pin, resolve or follow a discussion.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_hiveforum_add_post' => [
        'classname' => \mod_hiveforum\external\add_post::class,
        'description' => 'Reply to a discussion.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/hiveforum:reply',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_hiveforum_update_post' => [
        'classname' => \mod_hiveforum\external\update_post::class,
        'description' => 'Edit a post.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_hiveforum_delete_post' => [
        'classname' => \mod_hiveforum\external\delete_post::class,
        'description' => 'Delete a post and its replies.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_hiveforum_toggle_reaction' => [
        'classname' => \mod_hiveforum\external\toggle_reaction::class,
        'description' => 'Toggle a reaction (like/love/idea) on a post.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/hiveforum:react',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_hiveforum_toggle_accepted' => [
        'classname' => \mod_hiveforum\external\toggle_accepted::class,
        'description' => 'Accept or un-accept a reply as the answer to a question.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_hiveforum_search_users' => [
        'classname' => \mod_hiveforum\external\search_users::class,
        'description' => 'Users that can be mentioned in a discussion.',
        'type' => 'read',
        'ajax' => true,
    ],
    'mod_hiveforum_set_preferences' => [
        'classname' => \mod_hiveforum\external\set_preferences::class,
        'description' => 'Forum subscription and digest preference of the current user.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_hiveforum_mark_all_read' => [
        'classname' => \mod_hiveforum\external\mark_all_read::class,
        'description' => 'Mark every discussion as read.',
        'type' => 'write',
        'ajax' => true,
    ],
];
