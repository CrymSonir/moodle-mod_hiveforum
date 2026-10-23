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
use stdClass;

/**
 * The discussion (thread) page, rendered by the React component mod_hiveforum/Thread.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class thread_page implements \core\output\renderable, react_component_renderable {
    /** @var int Seconds between two checks for new replies. */
    public const POLL_INTERVAL = 30;

    /** @var array|null Cached props. */
    private ?array $props = null;

    /**
     * Constructor.
     *
     * @param forum $forum
     * @param stdClass $discussion
     */
    public function __construct(
        /** @var forum */
        private readonly forum $forum,
        /** @var stdClass */
        private readonly stdClass $discussion,
    ) {
    }

    #[\Override]
    public function get_react_component_name(): string {
        return 'mod_hiveforum/Thread';
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
        $this->props = [
            'cmid' => $forum->get_cmid(),
            'forumname' => format_string($forum->instance->name, true, ['context' => $forum->context]),
            'forumurl' => (new url('/mod/hiveforum/view.php', ['id' => $forum->get_cmid()]))->out(false),
            'discussion' => $exporter->discussion($this->discussion, (int) $USER->id),
            'posts' => $exporter->posts($this->discussion, api::get_posts($this->discussion, (int) $USER->id)),
            'capabilities' => $exporter->capabilities(),
            'currentuser' => isloggedin() && !isguestuser() ? $exporter->user((int) $USER->id) : null,
            'pollinterval' => self::POLL_INTERVAL,
            'strings' => strings::get(),
        ];
        return $this->props;
    }
}
