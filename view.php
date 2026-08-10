<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT); // Course module id.
$filter = optional_param('filter', 'all', PARAM_ALPHA); // all | unread | following.
$search = optional_param('search', '', PARAM_TEXT);

$cm = get_coursemodule_from_id('hiveforum', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$hiveforum = $DB->get_record('hiveforum', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/hiveforum:view', $context);

$PAGE->set_url('/mod/hiveforum/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($hiveforum->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod-hiveforum');

// Trigger course_module_viewed event + completion.
$event = \mod_hiveforum\event\course_module_viewed::create([
    'objectid' => $hiveforum->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('hiveforum', $hiveforum);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_hiveforum');
$discussions = hiveforum_get_discussion_summaries($hiveforum->id, $USER->id, $filter, $search);

echo $renderer->render_discussion_list($hiveforum, $cm, $discussions, $filter, $search);

echo $OUTPUT->footer();
