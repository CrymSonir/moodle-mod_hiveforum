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

/**
 * Discussion list.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_hiveforum\local\api;
use mod_hiveforum\local\forum;

$id = required_param('id', PARAM_INT);
$filter = optional_param('filter', 'all', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);
$tag = optional_param('tag', '', PARAM_TAG);
$sort = optional_param('sort', 'recent', PARAM_ALPHA);

$forum = forum::from_cmid($id);
require_login($forum->course, true, $forum->cm);
require_capability('mod/hiveforum:view', $forum->context);

$filter = in_array($filter, api::FILTERS, true) ? $filter : 'all';
$sort = in_array($sort, api::SORTS, true) ? $sort : 'recent';

$PAGE->set_url('/mod/hiveforum/view.php', ['id' => $forum->get_cmid()]);
$PAGE->set_title(format_string($forum->instance->name));
$PAGE->set_heading(format_string($forum->course->fullname));
$PAGE->add_body_class('mod-hiveforum');

$event = \mod_hiveforum\event\course_module_viewed::create([
    'objectid' => $forum->instance->id,
    'context' => $forum->context,
]);
$event->add_record_snapshot('course_modules', $forum->cm);
$event->add_record_snapshot('course', $forum->course);
$event->add_record_snapshot('hiveforum', $forum->instance);
$event->trigger();

$completion = new completion_info($forum->course);
$completion->set_module_viewed($forum->cm);

echo $OUTPUT->header();
echo $PAGE->get_renderer('mod_hiveforum')->render(
    new \mod_hiveforum\output\forum_page($forum, $filter, $search, $tag, $sort)
);
echo $OUTPUT->footer();
