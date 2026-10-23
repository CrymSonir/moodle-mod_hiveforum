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
use mod_hiveforum\local\exporter;
use mod_hiveforum\local\forum;

/**
 * Toggle a reaction on a post; returns every counter of the post.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle_reaction extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'postid' => new external_value(PARAM_INT, 'Post id'),
            'reactiontype' => new external_value(PARAM_ALPHA, 'like, love or idea'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $postid
     * @param string $reactiontype
     * @return array
     */
    public static function execute(int $postid, string $reactiontype): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('postid', 'reactiontype'));
        [$forum, , $post] = forum::from_post_id($params['postid']);
        self::validate_context($forum->context);
        $forum->require($forum->can_react(), 'react');

        $result = api::toggle_reaction($forum, $post, (int) $USER->id, $params['reactiontype']);

        return ['reactions' => exporter::reaction_list($result['reactions'], $result['myreactions'])];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'reactions' => structures::reactions(),
        ]);
    }
}
