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

namespace mod_hiveforum\output;

use core\output\react_component_renderable;
use core\output\renderer_base;
use core\url;
use mod_hiveforum\local\api;
use mod_hiveforum\local\exporter;
use mod_hiveforum\local\forum;

/**
 * The discussion list page, rendered by the React component mod_hiveforum/Forum.
 *
 * The first page of results is embedded in the props so the list is displayed
 * instantly, without a round trip.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum_page implements \core\output\renderable, react_component_renderable {
    /** @var array|null Cached props. */
    private ?array $props = null;

    /**
     * Constructor.
     *
     * @param forum $forum
     * @param string $filter
     * @param string $search
     * @param string $tag
     * @param string $sort
     * @param int $activegroup
     */
    public function __construct(
        /** @var forum */
        private readonly forum $forum,
        /** @var string */
        private readonly string $filter = 'all',
        /** @var string */
        private readonly string $search = '',
        /** @var string */
        private readonly string $tag = '',
        /** @var string */
        private readonly string $sort = 'recent',
        /** @var int */
        private readonly int $activegroup = 0,
    ) {
    }

    #[\Override]
    public function get_react_component_name(): string {
        return 'mod_hiveforum/Forum';
    }

    #[\Override]
    public function get_react_component_props(renderer_base $renderer): \stdClass {
        return (object) $this->get_props();
    }

    /**
     * Build (once) the props array.
     *
     * @return array
     */
    public function get_props(): array {
        global $USER;

        if ($this->props !== null) {
            return $this->props;
        }

        $forum = $this->forum;
        $exporter = new exporter($forum);
        $userid = (int) $USER->id;
        $group = $this->activegroup;
        $list = api::get_discussions($forum, $userid, $this->filter, $this->search, $this->tag, $this->sort, 0, api::PERPAGE, $group);
        $realuser = isloggedin() && !isguestuser();

        $this->props = [
            'cmid' => $forum->get_cmid(),
            'forumname' => format_string($forum->instance->name, true, ['context' => $forum->context]),
            'filter' => $this->filter,
            'search' => $this->search,
            'tag' => $this->tag,
            'sort' => $this->sort,
            'groupid' => $group,
            'discussions' => $exporter->discussions($list['discussions']),
            'total' => $list['total'],
            'perpage' => api::PERPAGE,
            'counts' => api::get_filter_counts($forum, $userid, $group),
            'stats' => api::get_stats($forum, $userid, $group),
            'populartags' => $forum->instance->allowtags ? api::get_popular_tags($forum, $userid, $group) : [],
            'capabilities' => $exporter->capabilities(),
            'preferences' => $realuser ? $exporter->preferences($userid) : null,
            'currentuser' => $realuser ? $exporter->user($userid) : null,
            'fulleditorurl' => (new url('/mod/hiveforum/post.php', ['hiveforum' => $forum->instance->id]))->out(false),
            'reporturl' => (new url('/mod/hiveforum/report.php', ['id' => $forum->get_cmid()]))->out(false),
            'subscribersurl' => (new url('/mod/hiveforum/subscribers.php', ['id' => $forum->get_cmid()]))->out(false),
            'strings' => strings::get(),
        ];
        return $this->props;
    }
}
