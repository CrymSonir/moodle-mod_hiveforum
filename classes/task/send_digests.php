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
use mod_hiveforum\local\forum;
use mod_hiveforum\local\notifier;
use mod_hiveforum\local\subscriptions;

/**
 * Daily digest: one message per user summarising the new posts in the discussions they follow,
 * for the users who chose "daily digest" in a forum.
 *
 * The window covers everything posted since the previous run, so nothing is lost or sent twice
 * whatever the schedule configured by the administrator.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_digests extends \core\task\scheduled_task {
    /** @var int Maximum number of posts listed per discussion. */
    private const MAX_POSTS_PER_DISCUSSION = 5;

    /**
     * Name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskdigests', 'hiveforum');
    }

    /**
     * Execute.
     */
    public function execute() {
        global $DB;

        $now = \core\di::get(\core\clock::class)->time();
        $since = (int) get_config('mod_hiveforum', 'digestlastrun') ?: $now - DAYSECS;
        set_config('digestlastrun', $now, 'mod_hiveforum');

        if (!get_config('mod_hiveforum', 'enablenotifications')) {
            return;
        }

        $prefs = $DB->get_records('hiveforum_digests', ['digest' => 1], 'hiveforum, userid', 'id, hiveforum, userid');
        $byforum = [];
        foreach ($prefs as $pref) {
            $byforum[(int) $pref->hiveforum][] = (int) $pref->userid;
        }

        $sections = []; // User id => list of HTML/text sections.
        foreach ($byforum as $forumid => $userids) {
            try {
                $forum = forum::from_instance_id($forumid);
            } catch (\moodle_exception $e) {
                continue;
            }
            if (!subscriptions::notifications_enabled($forum)) {
                continue;
            }
            $posts = $DB->get_records_sql(
                "SELECT p.*
                   FROM {hiveforum_posts} p
                   JOIN {hiveforum_discussions} d ON d.id = p.discussion
                  WHERE d.hiveforum = :forumid AND p.timecreated > :since AND p.timecreated <= :now
               ORDER BY p.discussion, p.timecreated",
                ['forumid' => $forumid, 'since' => $since, 'now' => $now]
            );
            if (!$posts) {
                continue;
            }
            $discussionids = array_unique(array_map(fn($p) => (int) $p->discussion, $posts));
            $discussions = $DB->get_records_list('hiveforum_discussions', 'id', $discussionids);

            foreach ($userids as $userid) {
                $user = \core_user::get_user($userid);
                $items = [];
                foreach ($discussions as $discussion) {
                    if (
                        !notifier::can_receive($forum, $discussion, $user)
                            || !subscriptions::is_following($forum, (int) $discussion->id, $userid)
                    ) {
                        continue;
                    }
                    $mine = array_filter($posts, fn($p) => (int) $p->discussion === (int) $discussion->id
                        && (int) $p->userid !== $userid);
                    if ($mine) {
                        $items[] = $this->discussion_section($forum, $discussion, array_values($mine));
                    }
                }
                if ($items) {
                    $name = format_string($forum->instance->name, true, ['context' => $forum->context]);
                    $sections[$userid][] = [
                        'html' => '<h3>' . s($name) . '</h3>' . implode('', array_column($items, 'html')),
                        'text' => "== $name ==\n\n" . implode("\n", array_column($items, 'text')),
                        'count' => array_sum(array_column($items, 'count')),
                    ];
                }
            }
        }

        foreach ($sections as $userid => $parts) {
            $user = \core_user::get_user($userid);
            $count = array_sum(array_column($parts, 'count'));
            $msg = new \core\message\message();
            $msg->component = 'mod_hiveforum';
            $msg->name = 'digest';
            $msg->userfrom = \core_user::get_noreply_user();
            $msg->userto = $user;
            $msg->subject = get_string('digestsubject', 'hiveforum', $count);
            $msg->fullmessage = implode("\n\n", array_column($parts, 'text'));
            $msg->fullmessageformat = FORMAT_PLAIN;
            $msg->fullmessagehtml = implode('', array_column($parts, 'html'));
            $msg->smallmessage = get_string('digestsubject', 'hiveforum', $count);
            $msg->notification = 1;
            message_send($msg);
            mtrace("  Digest sent to user {$userid} ({$count} posts).");
        }
    }

    /**
     * Digest section of one discussion.
     *
     * @param forum $forum
     * @param \stdClass $discussion
     * @param \stdClass[] $posts
     * @return array{html: string, text: string, count: int}
     */
    private function discussion_section(forum $forum, \stdClass $discussion, array $posts): array {
        $name = format_string($discussion->name, true, ['context' => $forum->context]);
        $url = (new url('/mod/hiveforum/discuss.php', ['d' => $discussion->id]))->out(false);
        $html = '<h4><a href="' . s($url) . '">' . s($name) . '</a></h4><ul>';
        $text = "* $name ($url)\n";
        foreach (array_slice($posts, 0, self::MAX_POSTS_PER_DISCUSSION) as $post) {
            $author = \core_user::get_user($post->userid);
            $excerpt = \mod_hiveforum\local\exporter::excerpt($post->message, (int) $post->messageformat, 160);
            $who = $author ? fullname($author) : '';
            $html .= '<li><strong>' . s($who) . '</strong> : ' . s($excerpt) . '</li>';
            $text .= "  - $who : $excerpt\n";
        }
        if (count($posts) > self::MAX_POSTS_PER_DISCUSSION) {
            $more = get_string('digestmore', 'hiveforum', count($posts) - self::MAX_POSTS_PER_DISCUSSION);
            $html .= '<li><a href="' . s($url) . '">' . s($more) . '</a></li>';
            $text .= "  $more\n";
        }
        $html .= '</ul>';
        return ['html' => $html, 'text' => $text, 'count' => count($posts)];
    }
}
