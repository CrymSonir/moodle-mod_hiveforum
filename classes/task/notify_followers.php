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

namespace mod_hiveforum\task;

use core\url;

/**
 * Notify the followers of a discussion that a new reply was posted.
 *
 * Runs as an ad-hoc task so posting stays instant whatever the number of followers.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_followers extends \core\task\adhoc_task {
    /**
     * Execute.
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $post = $DB->get_record('hiveforum_posts', ['id' => $data->postid]);
        if (!$post) {
            return; // Deleted in the meantime.
        }
        [$forum, $discussion] = \mod_hiveforum\local\forum::from_discussion_id((int) $post->discussion);
        $author = \core_user::get_user($post->userid);
        if (!$author) {
            return;
        }

        $followers = $DB->get_fieldset_select(
            'hiveforum_subscriptions',
            'userid',
            'discussion = ? AND userid <> ?',
            [$discussion->id, $post->userid]
        );
        if (!$followers) {
            return;
        }

        $url = new url('/mod/hiveforum/discuss.php', ['d' => $discussion->id], 'p' . $post->id);
        $discussionname = format_string($discussion->name, true, ['context' => $forum->context]);
        $forumname = format_string($forum->instance->name, true, ['context' => $forum->context]);
        $message = format_text($post->message, $post->messageformat, ['context' => $forum->context]);

        foreach ($followers as $userid) {
            $user = \core_user::get_user($userid);
            if (!$user || !empty($user->deleted) || !empty($user->suspended)) {
                continue;
            }
            $modinfo = get_fast_modinfo($forum->course, $user->id);
            $cm = $modinfo->get_cm($forum->get_cmid());
            if (!$cm->uservisible || !has_capability('mod/hiveforum:view', $forum->context, $user)) {
                continue;
            }

            $a = (object) [
                'author' => fullname($author),
                'discussion' => $discussionname,
                'forum' => $forumname,
                'url' => $url->out(false),
            ];

            $msg = new \core\message\message();
            $msg->component = 'mod_hiveforum';
            $msg->name = 'newreply';
            $msg->userfrom = $author;
            $msg->userto = $user;
            $msg->courseid = $forum->course->id;
            $msg->subject = get_string('notifynewreplysubject', 'hiveforum', $a);
            $msg->fullmessage = get_string('notifynewreplytext', 'hiveforum', $a) . "\n\n" . html_to_text($message);
            $msg->fullmessageformat = FORMAT_PLAIN;
            $msg->fullmessagehtml = '<p>' . get_string('notifynewreplyhtml', 'hiveforum', $a) . '</p>' . $message;
            $msg->smallmessage = get_string('notifynewreplysmall', 'hiveforum', $a);
            $msg->notification = 1;
            $msg->contexturl = $url->out(false);
            $msg->contexturlname = $discussionname;
            message_send($msg);
        }
    }
}
