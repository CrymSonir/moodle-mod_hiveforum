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
use core_external\external_single_structure;
use core_external\external_value;
use mod_hiveforum\local\exporter;
use mod_hiveforum\local\forum;
use mod_hiveforum\local\subscriptions;

/**
 * Change the notification preferences of the current user: forum subscription and digest.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_preferences extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'forumsubscribed' => new external_value(PARAM_INT, 'Subscribe to the forum: 1, 0, or -1 to keep', VALUE_DEFAULT, -1),
            'digest' => new external_value(PARAM_INT, 'Daily digest: 1, 0, or -1 to keep', VALUE_DEFAULT, -1),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @param int $forumsubscribed
     * @param int $digest
     * @return array
     */
    public static function execute(int $cmid, int $forumsubscribed = -1, int $digest = -1): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'forumsubscribed', 'digest'));
        $forum = forum::from_cmid($params['cmid']);
        self::validate_context($forum->context);
        $forum->require($forum->can_follow(), 'follow');

        if ($params['forumsubscribed'] >= 0) {
            subscriptions::set_forum_subscription($forum, (int) $USER->id, (bool) $params['forumsubscribed']);
        }
        if ($params['digest'] >= 0) {
            subscriptions::set_digest($forum, (int) $USER->id, (bool) $params['digest']);
        }

        return ['preferences' => (new exporter($forum))->preferences((int) $USER->id)];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'preferences' => structures::preferences(),
        ]);
    }
}
