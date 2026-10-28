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
use mod_hiveforum\local\api;
use mod_hiveforum\local\exporter;
use mod_hiveforum\local\forum;

/**
 * Fetch a discussion with all its posts (used for live refresh of the thread).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_discussion extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'discussionid' => new external_value(PARAM_INT, 'Discussion id'),
            'markread' => new external_value(PARAM_BOOL, 'Mark the discussion as read', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $discussionid
     * @param bool $markread
     * @return array
     */
    public static function execute(int $discussionid, bool $markread = true): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('discussionid', 'markread'));
        [$forum, $discussion] = forum::from_discussion_id($params['discussionid']);
        self::validate_context($forum->context);
        require_capability('mod/hiveforum:view', $forum->context);
        $forum->require_can_see_discussion($discussion);

        if ($params['markread']) {
            api::mark_read((int) $discussion->id, (int) $USER->id);
        }

        $exporter = new exporter($forum);
        return [
            'discussion' => $exporter->discussion($discussion, (int) $USER->id),
            'posts' => $exporter->posts($discussion, api::get_posts($discussion, (int) $USER->id)),
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'discussion' => structures::discussion(),
            'posts' => new external_multiple_structure(structures::post()),
        ]);
    }
}
