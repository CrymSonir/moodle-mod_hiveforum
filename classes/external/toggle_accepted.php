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
 * Accept (or un-accept) a reply as the answer of a question.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle_accepted extends external_api {
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
        $forum->require_can_see_discussion($discussion);
        $forum->require($forum->can_resolve($discussion), 'acceptanswer');

        $accepted = api::toggle_accepted($discussion, $post);

        return ['accepted' => $accepted, 'resolved' => !empty($discussion->resolved)];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'accepted' => new external_value(PARAM_BOOL, 'Post is now the accepted answer'),
            'resolved' => new external_value(PARAM_BOOL, 'Discussion is resolved'),
        ]);
    }
}
