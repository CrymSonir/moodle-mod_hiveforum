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
 * User mentions in posts.
 *
 * A mention is stored in the message as a token "@[Display name](user:123)" (inserted by the
 * composer's autocomplete). It is turned into a profile link when the post is displayed, and the
 * mentioned users are notified once (table hiveforum_mentions), provided they can see the post.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mentions {
    /** @var string Token pattern: name in group 1, user id in group 2. */
    public const PATTERN = '/@\[([^\]\r\n]{1,100})\]\(user:(\d{1,10})\)/u';

    /** @var int Maximum number of users notified for one post. */
    public const MAX_PER_POST = 20;

    /**
     * Mentioned user ids, in order of appearance.
     *
     * @param string $message
     * @return int[]
     */
    public static function extract_userids(string $message): array {
        preg_match_all(self::PATTERN, $message, $matches);
        return array_values(array_unique(array_map('intval', $matches[2])));
    }

    /**
     * Replace mention tokens by profile links (before format_text).
     *
     * @param string $message
     * @param int $format
     * @param int $courseid
     * @return string
     */
    public static function render(string $message, int $format, int $courseid): string {
        return preg_replace_callback(self::PATTERN, function (array $m) use ($format, $courseid): string {
            $name = trim($m[1]);
            if ($format === (int) FORMAT_PLAIN) {
                return '@' . $name;
            }
            $href = (new url('/user/view.php', ['id' => (int) $m[2], 'course' => $courseid]))->out();
            return '<a class="hf-mention" href="' . $href . '">@' . s($name) . '</a>';
        }, $message);
    }

    /**
     * Replace mention tokens by "@Name" (plain text contexts: excerpts, e-mail subjects).
     *
     * @param string $message
     * @return string
     */
    public static function to_text(string $message): string {
        return preg_replace(self::PATTERN, '@$1', $message);
    }

    /**
     * Record the new mentions of a post and queue their notification.
     *
     * Users who cannot see the discussion (capability, enrolment, group) are ignored, so a mention
     * never leaks a post to someone who should not read it.
     *
     * @param forum $forum
     * @param stdClass $discussion
     * @param stdClass $post
     * @return int[] ids of the users newly mentioned
     */
    public static function process(forum $forum, stdClass $discussion, stdClass $post): array {
        global $DB;

        $ids = array_slice(self::extract_userids($post->message), 0, self::MAX_PER_POST);
        $ids = array_values(array_diff($ids, [(int) $post->userid]));
        if (!$ids) {
            return [];
        }
        $already = $DB->get_fieldset_select('hiveforum_mentions', 'userid', 'postid = ?', [$post->id]);
        $ids = array_values(array_diff($ids, array_map('intval', $already)));
        if (!$ids) {
            return [];
        }

        $allowed = array_flip(subscriptions::get_potential_subscribers($forum));
        $new = [];
        foreach ($ids as $userid) {
            if (isset($allowed[$userid]) && $forum->can_see_discussion($discussion, $userid)) {
                $DB->insert_record('hiveforum_mentions', ['postid' => $post->id, 'userid' => $userid, 'timecreated' => time()]);
                $new[] = $userid;
            }
        }

        if ($new && subscriptions::notifications_enabled($forum)) {
            $task = new \mod_hiveforum\task\notify_mentions();
            $task->set_custom_data(['postid' => (int) $post->id, 'userids' => $new]);
            \core\task\manager::queue_adhoc_task($task);
        }
        return $new;
    }

    /**
     * Users that can be mentioned in a discussion (or in a new one), matching a query.
     *
     * @param forum $forum
     * @param stdClass|null $discussion null for a new discussion (current active group applies)
     * @param string $query part of the name
     * @param int $limit
     * @return stdClass[] user records (picture fields)
     */
    public static function search(forum $forum, ?stdClass $discussion, string $query, int $limit = 8): array {
        global $DB, $USER;

        $query = trim($query);
        [$esql, $params] = get_enrolled_sql($forum->context, 'mod/hiveforum:view', 0, true);
        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $where = 'u.deleted = 0 AND u.id <> :me';
        $params['me'] = $USER->id;
        if ($query !== '') {
            $where .= ' AND (' . $DB->sql_like($fullname, ':q1', false, false)
                . ' OR ' . $DB->sql_like('u.lastname', ':q2', false, false) . ')';
            $params['q1'] = '%' . $DB->sql_like_escape($query) . '%';
            $params['q2'] = $DB->sql_like_escape($query) . '%';
        }
        $fields = implode(', ', array_map(fn($f) => "u.$f", \core_user\fields::get_picture_fields()));
        $sql = "SELECT $fields
                  FROM {user} u
                  JOIN ($esql) je ON je.id = u.id
                 WHERE $where
              ORDER BY u.firstname, u.lastname, u.id";
        // Fetch a few more than needed: some may be filtered out by the group rules.
        $candidates = $DB->get_records_sql($sql, $params, 0, $limit * 4);

        $target = $discussion ?? (object) ['groupid' => $forum->resolve_active_group()];
        $result = [];
        foreach ($candidates as $user) {
            if ($forum->can_see_discussion($target, (int) $user->id)) {
                $result[] = $user;
            }
            if (count($result) >= $limit) {
                break;
            }
        }
        return $result;
    }
}
