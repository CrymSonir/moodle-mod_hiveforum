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
 * Full editor (rich text + files) and no-JavaScript fallbacks.
 *
 * Day to day posting happens inline in the React UI through web services;
 * this page handles:
 *  - ?hiveforum=ID   start a discussion with the full editor
 *  - ?edit=POSTID    edit a post with the full editor (any format)
 *  - POST discussion=ID&message=...  reply without JavaScript
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use mod_hiveforum\local\api;
use mod_hiveforum\local\forum;

$hiveforumid = optional_param('hiveforum', 0, PARAM_INT);
$editid = optional_param('edit', 0, PARAM_INT);
$discussionid = optional_param('discussion', 0, PARAM_INT);

// No-JavaScript reply.
if ($discussionid) {
    [$forum, $discussion] = forum::from_discussion_id($discussionid);
    require_login($forum->course, false, $forum->cm);
    require_sesskey();
    $forum->require($forum->can_reply(), 'reply');

    $message = required_param('message', PARAM_RAW);
    $post = api::add_post(
        $forum,
        $discussion,
        optional_param('parent', 0, PARAM_INT),
        (int) $USER->id,
        $message,
        FORMAT_MARKDOWN
    );
    redirect(new moodle_url('/mod/hiveforum/discuss.php', ['d' => $discussion->id], 'p' . $post->id));
}

if ($editid) {
    [$forum, $discussion, $post] = forum::from_post_id($editid);
} else if ($hiveforumid) {
    $forum = forum::from_instance_id($hiveforumid);
    $discussion = $post = null;
} else {
    throw new moodle_exception('invalidparameter', 'debug');
}

require_login($forum->course, false, $forum->cm);
$context = $forum->context;

$editoroptions = [
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $forum->course->maxbytes,
    'context' => $context,
    'subdirs' => 0,
    'trusttext' => false,
];

if ($post) {
    $forum->require($forum->can_edit_post($post), 'edit');
    $isfirst = (int) $post->id === (int) $discussion->firstpost;
    $returnurl = new moodle_url('/mod/hiveforum/discuss.php', ['d' => $discussion->id], 'p' . $post->id);
    $PAGE->set_url('/mod/hiveforum/post.php', ['edit' => $post->id]);
    $title = get_string('editpost', 'hiveforum');
} else {
    $forum->require($forum->can_start_discussion(), 'newdiscussion');
    $isfirst = true;
    $returnurl = new moodle_url('/mod/hiveforum/view.php', ['id' => $forum->get_cmid()]);
    $PAGE->set_url('/mod/hiveforum/post.php', ['hiveforum' => $forum->instance->id]);
    $title = get_string('newdiscussion', 'hiveforum');
}

$PAGE->set_title($title . ' | ' . format_string($forum->instance->name));
$PAGE->set_heading(format_string($forum->course->fullname));
$PAGE->add_body_class('mod-hiveforum');
$PAGE->activityheader->disable();

$mform = new \mod_hiveforum\form\post_form(null, [
    'mode' => $post ? 'edit' : 'new',
    'withtitle' => $isfirst,
    'allowtags' => !empty($forum->instance->allowtags),
    'editoroptions' => $editoroptions,
]);

// Prepare the current data.
$data = new stdClass();
$data->hiveforum = $forum->instance->id;
$data->edit = $post->id ?? 0;
if ($post) {
    $data->id = $post->id;
    $data->message = $post->message;
    $data->messageformat = $post->messageformat;
    if ($isfirst) {
        $data->name = $discussion->name;
        $data->tags = implode(', ', api::get_tags([(int) $discussion->id])[(int) $discussion->id]);
    }
} else {
    $data->id = null;
    $data->message = '';
    $data->messageformat = editors_get_preferred_format();
}
$data = file_prepare_standard_editor($data, 'message', $editoroptions, $context, 'mod_hiveforum', 'post', $data->id);
$mform->set_data($data);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($formdata = $mform->get_data()) {
    $tags = isset($formdata->tags) ? explode(',', $formdata->tags) : [];

    if ($post) {
        $formdata->id = $post->id;
        $formdata = file_postupdate_standard_editor(
            $formdata,
            'message',
            $editoroptions,
            $context,
            'mod_hiveforum',
            'post',
            $post->id
        );
        api::update_post($forum, $discussion, $post, $formdata->message, (int) $formdata->messageformat);
        if ($isfirst && isset($formdata->name)) {
            api::update_discussion($forum, $discussion, $formdata->name, isset($formdata->tags) ? $tags : null);
        }
        redirect($returnurl, get_string('postupdated', 'hiveforum'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    $editor = $formdata->message_editor;
    $newdiscussion = api::create_discussion(
        $forum,
        (int) $USER->id,
        $formdata->name,
        $editor['text'],
        (int) $editor['format'],
        !empty($formdata->isquestion),
        $tags,
        (int) $editor['itemid'],
        $editoroptions
    );
    redirect(new moodle_url('/mod/hiveforum/discuss.php', ['d' => $newdiscussion->id]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
