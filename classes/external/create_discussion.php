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
use mod_hiveforum\local\forum;

/**
 * Start a discussion from the inline composer (markdown).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_discussion extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'name' => new external_value(PARAM_TEXT, 'Title'),
            'message' => new external_value(PARAM_RAW, 'Message (markdown)'),
            'isquestion' => new external_value(PARAM_BOOL, 'Is a question', VALUE_DEFAULT, false),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_TAG, 'Tag'),
                'Tags',
                VALUE_DEFAULT,
                []
            ),
            'groupid' => new external_value(PARAM_INT, 'Target group (0 = all participants, -1 = active group)', VALUE_DEFAULT, -1),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @param string $name
     * @param string $message
     * @param bool $isquestion
     * @param array $tags
     * @param int $groupid
     * @return array
     */
    public static function execute(int $cmid, string $name, string $message, bool $isquestion = false, array $tags = [], int $groupid = -1): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'name', 'message', 'isquestion', 'tags', 'groupid'));
        $forum = forum::from_cmid($params['cmid']);
        self::validate_context($forum->context);
        $forum->require($forum->can_start_discussion(), 'newdiscussion');

        $groupid = $params['groupid'];
        if ($groupid < 0) {
            // Default: the active group if the user can post in it, otherwise their first allowed group.
            $targets = $forum->get_post_targets();
            $active = $forum->resolve_active_group();
            $groupid = array_key_exists($active, $targets) ? $active : (int) array_key_first($targets);
        }

        $discussion = api::create_discussion(
            $forum,
            (int) $USER->id,
            $params['name'],
            $params['message'],
            FORMAT_MARKDOWN,
            $params['isquestion'],
            $params['tags'],
            null,
            [],
            $groupid
        );

        return [
            'discussionid' => (int) $discussion->id,
            'url' => (new \core\url('/mod/hiveforum/discuss.php', ['d' => $discussion->id]))->out(false),
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'discussionid' => new external_value(PARAM_INT, 'New discussion id'),
            'url' => new external_value(PARAM_URL, 'Discussion URL'),
        ]);
    }
}
