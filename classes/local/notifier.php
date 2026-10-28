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

namespace mod_hiveforum\local;

use core\url;
use stdClass;

/**
 * Builds and sends the notifications of the forum.
 *
 * Every recipient is re-checked at send time (account active, activity visible to them,
 * view capability, group), so a notification never reveals a post to someone who could not read it.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notifier {
    /**
     * Can this user receive a notification about this discussion?
     *
     * @param forum $forum
     * @param stdClass $discussion
     * @param stdClass|false $user
     * @return bool
     */
    public static function can_receive(forum $forum, stdClass $discussion, $user): bool {
        if (!$user || !empty($user->deleted) || !empty($user->suspended) || isguestuser($user)) {
            return false;
        }
        $cm = get_fast_modinfo($forum->course, $user->id)->get_cm($forum->get_cmid());
        return $cm->uservisible
            && has_capability('mod/hiveforum:view', $forum->context, $user)
            && $forum->can_see_discussion($discussion, (int) $user->id);
    }

    /**
     * Send one notification about a post.
     *
     * @param forum $forum
     * @param stdClass $discussion
     * @param stdClass $post
     * @param stdClass $author
     * @param stdClass $user recipient
     * @param string $kind 'reply', 'discussion' or 'mention'
     */
    public static function send_post(forum $forum, stdClass $discussion, stdClass $post, stdClass $author, stdClass $user, string $kind): void {
        $url = new url('/mod/hiveforum/discuss.php', ['d' => $discussion->id], 'p' . $post->id);
        $discussionname = format_string($discussion->name, true, ['context' => $forum->context]);
        $html = format_text(
            mentions::render($post->message, (int) $post->messageformat, (int) $forum->course->id),
            $post->messageformat,
            ['context' => $forum->context]
        );
        $a = (object) [
            'author' => fullname($author),
            'discussion' => $discussionname,
            'forum' => format_string($forum->instance->name, true, ['context' => $forum->context]),
            'url' => $url->out(false),
        ];
        $prefix = match ($kind) {
            'mention' => 'notifymention',
            'discussion' => 'notifynewdiscussion',
            default => 'notifynewreply',
        };

        $msg = new \core\message\message();
        $msg->component = 'mod_hiveforum';
        $msg->name = $kind === 'mention' ? 'mention' : 'newreply';
        $msg->userfrom = $author;
        $msg->userto = $user;
        $msg->courseid = $forum->course->id;
        $msg->subject = get_string($prefix . 'subject', 'hiveforum', $a);
        $msg->fullmessage = get_string($prefix . 'text', 'hiveforum', $a) . "\n\n" . html_to_text($html);
        $msg->fullmessageformat = FORMAT_PLAIN;
        $msg->fullmessagehtml = '<p>' . get_string($prefix . 'html', 'hiveforum', $a) . '</p>' . $html;
        $msg->smallmessage = get_string($prefix . 'small', 'hiveforum', $a);
        $msg->notification = 1;
        $msg->contexturl = $url->out(false);
        $msg->contexturlname = $discussionname;
        message_send($msg);
    }
}
