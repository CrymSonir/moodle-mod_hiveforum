<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

global $DB, $USER, $PAGE, $OUTPUT, $CFG;

require_once($CFG->libdir . '/formslib.php');

$hiveforumid = optional_param('hiveforum', 0, PARAM_INT);   // New discussion.
$discussionid = optional_param('discussion', 0, PARAM_INT); // Reply.
$editid = optional_param('edit', 0, PARAM_INT);             // Edit post.
$deleteid = optional_param('delete', 0, PARAM_INT);         // Delete post.

if ($deleteid) {
    require_sesskey();
    $post = $DB->get_record('hiveforum_posts', ['id' => $deleteid], '*', MUST_EXIST);
    $discussion = $DB->get_record('hiveforum_discussions', ['id' => $post->discussion], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('hiveforum', $discussion->hiveforum, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    require_login($cm->course, true, $cm);

    $canmoderate = has_capability('mod/hiveforum:moderate', $context);
    $isauthor = ($post->userid == $USER->id);

    if ($isauthor) {
        require_capability('mod/hiveforum:deleteownpost', $context);
    } else if (!$canmoderate) {
        throw new \moodle_exception('nopermissions', 'error', '', get_string('delete', 'hiveforum'));
    }

    // If it's the first post of a discussion, maybe we should delete the whole discussion?
    // For now, let's just delete the post. If it's the first post, the discussion might look empty.
    $DB->delete_records('hiveforum_posts', ['id' => $post->id]);

    redirect(new moodle_url('/mod/hiveforum/discuss.php', ['d' => $discussion->id]), get_string('postdeleted', 'hiveforum'));
}

if ($editid) {
    $post = $DB->get_record('hiveforum_posts', ['id' => $editid], '*', MUST_EXIST);
    $discussion = $DB->get_record('hiveforum_discussions', ['id' => $post->discussion], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('hiveforum', $discussion->hiveforum, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    require_login($cm->course, true, $cm);

    $canmoderate = has_capability('mod/hiveforum:moderate', $context);
    $isauthor = ($post->userid == $USER->id);

    if ($isauthor) {
        require_capability('mod/hiveforum:editownpost', $context);
    } else if (!$canmoderate) {
        throw new \moodle_exception('nopermissions', 'error', '', get_string('edit', 'hiveforum'));
    }

    if ($message = optional_param('message', '', PARAM_CLEANHTML)) {
        require_sesskey();
        $post->message = $message;
        $post->timemodified = time();
        $DB->update_record('hiveforum_posts', $post);
        redirect(new moodle_url('/mod/hiveforum/discuss.php', ['d' => $discussion->id]), get_string('postupdated', 'hiveforum'));
    }

    // If no message, we could show an edit form, but for now we'll handle it via JS (inline).
    // Or redirect back if someone calls this directly without message.
    redirect(new moodle_url('/mod/hiveforum/discuss.php', ['d' => $discussion->id]));
}

if ($discussionid) {
    require_sesskey();

    $discussion = $DB->get_record('hiveforum_discussions', ['id' => $discussionid], '*', MUST_EXIST);
    $hiveforum = $DB->get_record('hiveforum', ['id' => $discussion->hiveforum], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('hiveforum', $hiveforum->id, $hiveforum->course, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    require_capability('mod/hiveforum:reply', $context);

    $parent = optional_param('parent', 0, PARAM_INT);
    $message = required_param('message', PARAM_CLEANHTML);

    $post = new stdClass();
    $post->discussion = $discussion->id;
    $post->parent = $parent;
    $post->userid = $USER->id;
    $post->message = $message;
    $post->messageformat = FORMAT_HTML;
    $post->timecreated = time();
    $post->timemodified = $post->timecreated;
    $post->id = $DB->insert_record('hiveforum_posts', $post);

    $discussion->timemodified = time();
    $DB->update_record('hiveforum_discussions', $discussion);

    hiveforum_bump_streak($hiveforum->id, $USER->id);

    $event = \mod_hiveforum\event\post_created::create([
        'objectid' => $post->id,
        'context' => $context,
    ]);
    $event->trigger();

    redirect(new moodle_url('/mod/hiveforum/discuss.php', ['d' => $discussion->id]));
}

if ($hiveforumid) {
    $hiveforum = $DB->get_record('hiveforum', ['id' => $hiveforumid], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('hiveforum', $hiveforum->id, $hiveforum->course, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    require_capability('mod/hiveforum:startdiscussion', $context);

    $PAGE->set_url('/mod/hiveforum/post.php', ['hiveforum' => $hiveforumid]);
    $PAGE->set_title(get_string('newdiscussion', 'hiveforum'));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_context($context);

    $mform = new \mod_hiveforum\form\new_discussion_form(null, ['hiveforumid' => $hiveforumid]);

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/hiveforum/view.php', ['id' => $cm->id]));
    } else if ($data = $mform->get_data()) {
        require_sesskey();

        $discussion = new stdClass();
        $discussion->hiveforum = $hiveforum->id;
        $discussion->userid = $USER->id;
        $discussion->name = $data->name;
        $discussion->posttype = !empty($data->isquestion) ? 'question' : 'discussion';
        $discussion->pinned = 0;
        $discussion->resolved = 0;
        $discussion->timecreated = time();
        $discussion->timemodified = $discussion->timecreated;
        $discussion->id = $DB->insert_record('hiveforum_discussions', $discussion);

        $post = new stdClass();
        $post->discussion = $discussion->id;
        $post->parent = 0;
        $post->userid = $USER->id;
        $post->message = $data->message['text'] ?? $data->message;
        $post->messageformat = FORMAT_HTML;
        $post->timecreated = time();
        $post->timemodified = $post->timecreated;
        $post->id = $DB->insert_record('hiveforum_posts', $post);

        $DB->update_record('hiveforum_discussions', (object) [
            'id' => $discussion->id,
            'firstpost' => $post->id,
        ]);

        if (!empty($data->tags)) {
            foreach (array_filter(array_map('trim', explode(',', $data->tags))) as $tag) {
                $DB->insert_record('hiveforum_tags', ['discussion' => $discussion->id, 'tag' => $tag]);
            }
        }

        hiveforum_bump_streak($hiveforum->id, $USER->id);

        $event = \mod_hiveforum\event\discussion_created::create([
            'objectid' => $discussion->id,
            'context' => $context,
        ]);
        $event->trigger();

        redirect(new moodle_url('/mod/hiveforum/discuss.php', ['d' => $discussion->id]));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('newdiscussion', 'hiveforum'));
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

throw new moodle_exception('invalidparameter', 'error');
