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
use mod_hiveforum\local\api;
use mod_hiveforum\local\forum;

/**
 * Delete a post and its replies (the whole discussion for the opening post).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_post extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'postid' => new external_value(PARAM_INT, 'Post id'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $postid
     * @return array
     */
    public static function execute(int $postid): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('postid'));
        [$forum, $discussion, $post] = forum::from_post_id($params['postid']);
        self::validate_context($forum->context);
        $forum->require($forum->can_delete_post($post), 'delete');

        $discussiondeleted = api::delete_post($forum, $discussion, $post);

        return [
            'discussiondeleted' => $discussiondeleted,
            'forumurl' => (new \core\url('/mod/hiveforum/view.php', ['id' => $forum->get_cmid()]))->out(false),
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'discussiondeleted' => new external_value(PARAM_BOOL, 'The whole discussion was deleted'),
            'forumurl' => new external_value(PARAM_URL, 'Forum URL'),
        ]);
    }
}
