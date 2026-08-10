<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);

$PAGE->set_url('/mod/hiveforum/index.php', ['id' => $courseid]);
$PAGE->set_title(get_string('modulenameplural', 'hiveforum'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'hiveforum'));

$instances = get_all_instances_in_course('hiveforum', $course);

if (empty($instances)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'hiveforum')),
        new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name')];
foreach ($instances as $instance) {
    $link = html_writer::link(
        new moodle_url('/mod/hiveforum/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name)
    );
    $table->data[] = [$link];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
