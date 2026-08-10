<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$d = required_param('d', PARAM_INT); // Discussion id.

$discussion = $DB->get_record('hiveforum_discussions', ['id' => $d], '*', MUST_EXIST);
$hiveforum = $DB->get_record('hiveforum', ['id' => $discussion->hiveforum], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('hiveforum', $hiveforum->id, $hiveforum->course, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/hiveforum:view', $context);

$PAGE->set_url('/mod/hiveforum/discuss.php', ['d' => $d]);
$PAGE->set_title(format_string($discussion->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->js_call_amd('mod_hiveforum/reactions', 'init');

// Mark as read for the current user.
$existing = $DB->get_record('hiveforum_reads', ['discussion' => $d, 'userid' => $USER->id]);
if ($existing) {
    $existing->timeread = time();
    $DB->update_record('hiveforum_reads', $existing);
} else {
    $DB->insert_record('hiveforum_reads', [
        'discussion' => $d,
        'userid' => $USER->id,
        'timeread' => time(),
    ]);
}

$posts = $DB->get_records('hiveforum_posts', ['discussion' => $d], 'timecreated ASC');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($discussion->name));

$renderer = $PAGE->get_renderer('mod_hiveforum');
echo $renderer->render_discussion_thread($discussion, $cm, $posts, $context);

if (has_capability('mod/hiveforum:reply', $context)) {
    echo $renderer->render_reply_form($discussion, $cm);
}

echo $OUTPUT->footer();
