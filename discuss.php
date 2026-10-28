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
 * A discussion thread.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_hiveforum\local\api;
use mod_hiveforum\local\forum;

$d = required_param('d', PARAM_INT);

[$forum, $discussion] = forum::from_discussion_id($d);
require_login($forum->course, true, $forum->cm);
require_capability('mod/hiveforum:view', $forum->context);
$forum->require_can_see_discussion($discussion);

$PAGE->set_url('/mod/hiveforum/discuss.php', ['d' => $discussion->id]);
$PAGE->set_title(format_string($discussion->name) . ' | ' . format_string($forum->instance->name));
$PAGE->set_heading(format_string($forum->course->fullname));
$PAGE->add_body_class('mod-hiveforum');
$PAGE->navbar->add(format_string($discussion->name));
// The discussion title is part of the React header; avoid a duplicate activity header title.
$PAGE->activityheader->disable();

api::mark_read((int) $discussion->id, (int) $USER->id);

echo $OUTPUT->header();
echo $PAGE->get_renderer('mod_hiveforum')->render(new \mod_hiveforum\output\thread_page($forum, $discussion));
echo $OUTPUT->footer();
