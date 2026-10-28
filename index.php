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
 * List of hiveforums in a course.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_course_login($course);
$context = context_course::instance($course->id);

\mod_hiveforum\event\course_module_instance_list_viewed::create(['context' => $context])->trigger();

$PAGE->set_url('/mod/hiveforum/index.php', ['id' => $courseid]);
$PAGE->set_title(get_string('modulenameplural', 'hiveforum'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('modulenameplural', 'hiveforum'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'hiveforum'));

$instances = get_all_instances_in_course('hiveforum', $course);
if (empty($instances)) {
    notice(
        get_string('thereareno', 'moodle', get_string('modulenameplural', 'hiveforum')),
        new moodle_url('/course/view.php', ['id' => $course->id])
    );
}

$table = new html_table();
$table->head = [get_string('name'), get_string('discussions', 'hiveforum'), get_string('unread', 'hiveforum')];
$table->attributes['class'] = 'generaltable mod_index';

foreach ($instances as $instance) {
    $discussions = $DB->count_records('hiveforum_discussions', ['hiveforum' => $instance->id]);
    $unread = isguestuser() ? 0 : \mod_hiveforum\local\api::count_unread(
        \mod_hiveforum\local\forum::from_instance_id((int) $instance->id),
        (int) $USER->id
    );
    $link = html_writer::link(
        new moodle_url('/mod/hiveforum/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name),
        ['class' => $instance->visible ? '' : 'dimmed']
    );
    $table->data[] = [$link, $discussions, $unread ? html_writer::span($unread, 'badge text-bg-primary') : '0'];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
