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

namespace mod_hiveforum\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_hiveforum\local\exporter;
use mod_hiveforum\local\forum;
use mod_hiveforum\local\mentions;

/**
 * Users that can be @mentioned (autocomplete of the composer).
 *
 * Only users who can see the discussion are returned (capability, enrolment and groups).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_users extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'discussionid' => new external_value(PARAM_INT, 'Discussion id, 0 for a new discussion', VALUE_DEFAULT, 0),
            'query' => new external_value(PARAM_TEXT, 'Part of the name', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @param int $discussionid
     * @param string $query
     * @return array
     */
    public static function execute(int $cmid, int $discussionid = 0, string $query = ''): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'discussionid', 'query'));
        $forum = forum::from_cmid($params['cmid']);
        self::validate_context($forum->context);
        $forum->require($forum->can_reply() || $forum->can_start_discussion(), 'reply');

        $discussion = null;
        if ($params['discussionid']) {
            [$owner, $discussion] = forum::from_discussion_id($params['discussionid']);
            $forum->require_can_see_discussion($discussion);
        }

        $exporter = new exporter($forum);
        $users = mentions::search($forum, $discussion, $params['query']);
        $exporter->preload_users(array_map(fn($u) => (int) $u->id, $users));
        return ['users' => array_map(fn($u) => $exporter->user((int) $u->id), $users)];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'users' => new external_multiple_structure(structures::user()),
        ]);
    }
}
