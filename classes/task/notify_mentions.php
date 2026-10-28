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
 * Notify the users mentioned (@user) in a post. Mentions are always immediate (never in the digest).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_mentions extends \core\task\adhoc_task {
    /**
     * Execute.
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $post = $DB->get_record('hiveforum_posts', ['id' => $data->postid]);
        if (!$post) {
            return;
        }
        [$forum, $discussion] = forum::from_discussion_id((int) $post->discussion);
        $author = \core_user::get_user($post->userid);
        if (!$author || !subscriptions::notifications_enabled($forum)) {
            return;
        }
        foreach ((array) $data->userids as $userid) {
            $user = \core_user::get_user((int) $userid);
            if ((int) $userid !== (int) $post->userid && notifier::can_receive($forum, $discussion, $user)) {
                notifier::send_post($forum, $discussion, $post, $author, $user, 'mention');
            }
        }
    }
}
