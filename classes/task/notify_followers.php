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

use mod_hiveforum\local\forum;
use mod_hiveforum\local\notifier;
use mod_hiveforum\local\subscriptions;

/**
 * Notify the followers of a discussion (and the forum subscribers) of a new post.
 *
 * Users who chose the daily digest are skipped (they get it in the digest), and so are the
 * users mentioned in the post (they receive the mention notification instead).
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

        $post = $DB->get_record('hiveforum_posts', ['id' => $this->get_custom_data()->postid]);
        if (!$post) {
            return; // Deleted in the meantime.
        }
        [$forum, $discussion] = forum::from_discussion_id((int) $post->discussion);
        if (!subscriptions::notifications_enabled($forum)) {
            return;
        }
        $author = \core_user::get_user($post->userid);
        if (!$author) {
            return;
        }

        $skip = array_map('intval', $DB->get_fieldset_select('hiveforum_mentions', 'userid', 'postid = ?', [$post->id]));
        $skip = array_merge($skip, array_map('intval', $DB->get_fieldset_select(
            'hiveforum_digests',
            'userid',
            'hiveforum = ? AND digest = 1',
            [$forum->instance->id]
        )));
        $skip[] = (int) $post->userid;

        $kind = (int) $post->id === (int) $discussion->firstpost ? 'discussion' : 'reply';
        foreach (array_diff(subscriptions::get_followers($forum, $discussion), $skip) as $userid) {
            $user = \core_user::get_user($userid);
            if (notifier::can_receive($forum, $discussion, $user)) {
                notifier::send_post($forum, $discussion, $post, $author, $user, $kind);
            }
        }
    }
}
