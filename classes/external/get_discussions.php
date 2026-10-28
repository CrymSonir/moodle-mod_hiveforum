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
 * List discussions (filters, search, tag, sort, pagination).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_discussions extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'filter' => new external_value(PARAM_ALPHA, 'all|unread|following|questions|mine', VALUE_DEFAULT, 'all'),
            'search' => new external_value(PARAM_TEXT, 'Search text', VALUE_DEFAULT, ''),
            'tag' => new external_value(PARAM_TAG, 'Tag', VALUE_DEFAULT, ''),
            'sort' => new external_value(PARAM_ALPHA, 'recent|newest|popular', VALUE_DEFAULT, 'recent'),
            'page' => new external_value(PARAM_INT, 'Zero based page', VALUE_DEFAULT, 0),
            'groupid' => new external_value(PARAM_INT, 'Active group, -1 for the current one', VALUE_DEFAULT, -1),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @param string $filter
     * @param string $search
     * @param string $tag
     * @param string $sort
     * @param int $page
     * @param int $groupid
     * @return array
     */
    public static function execute(
        int $cmid,
        string $filter = 'all',
        string $search = '',
        string $tag = '',
        string $sort = 'recent',
        int $page = 0,
        int $groupid = -1
    ): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'filter', 'search', 'tag', 'sort', 'page', 'groupid'));
        $forum = forum::from_cmid($params['cmid']);
        self::validate_context($forum->context);
        require_capability('mod/hiveforum:view', $forum->context);

        $activegroup = $forum->resolve_active_group($params['groupid'] < 0 ? null : $params['groupid']);
        $filter = in_array($params['filter'], api::FILTERS, true) ? $params['filter'] : 'all';
        $sort = in_array($params['sort'], api::SORTS, true) ? $params['sort'] : 'recent';

        $list = api::get_discussions(
            $forum,
            (int) $USER->id,
            $filter,
            $params['search'],
            $params['tag'],
            $sort,
            max(0, $params['page']),
            api::PERPAGE,
            $activegroup
        );
        $exporter = new exporter($forum);

        return [
            'discussions' => $exporter->discussions($list['discussions']),
            'total' => $list['total'],
            'perpage' => api::PERPAGE,
            'counts' => api::get_filter_counts($forum, (int) $USER->id, $activegroup),
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'discussions' => new external_multiple_structure(structures::discussion_summary()),
            'total' => new external_value(PARAM_INT, 'Number of matching discussions'),
            'perpage' => new external_value(PARAM_INT, 'Page size'),
            'counts' => structures::counts(),
        ]);
    }
}
