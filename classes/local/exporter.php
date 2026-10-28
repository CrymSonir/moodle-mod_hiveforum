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
 * Turns records into the arrays consumed by the React front-end.
 *
 * The same shapes are used for the initial props rendered by the page and for every web
 * service response, so the UI never needs two code paths. The matching external
 * structures live in {@see \mod_hiveforum\external\structures}.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class exporter {
    /** @var array<int, array> Exported users, cached per request. */
    private array $users = [];

    /** @var array<int, bool>|null Ids of users who can moderate. */
    private ?array $staff = null;

    /** @var array<int, int> Streaks cache. */
    private array $streaks = [];

    /**
     * Constructor.
     *
     * @param forum $forum
     */
    public function __construct(
        /** @var forum */
        private readonly forum $forum,
    ) {
    }

    /**
     * Preload users (and their streaks) in a single query.
     *
     * @param int[] $userids
     */
    public function preload_users(array $userids): void {
        global $DB, $PAGE;

        $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
        $missing = array_values(array_diff($userids, array_keys($this->users)));
        if (!$missing) {
            return;
        }

        $fields = \core_user\fields::get_picture_fields();
        $records = $DB->get_records_list('user', 'id', $missing, '', implode(',', $fields));
        $this->streaks += api::get_streaks((int) $this->forum->instance->id, $missing);
        $staff = $this->get_staff();

        foreach ($missing as $userid) {
            $user = $records[$userid] ?? null;
            if (!$user) {
                $this->users[$userid] = [
                    'id' => $userid,
                    'fullname' => get_string('deleteduser', 'hiveforum'),
                    'initials' => '?',
                    'avatarurl' => '',
                    'profileurl' => '',
                    'isstaff' => false,
                    'streak' => 0,
                ];
                continue;
            }
            $avatarurl = '';
            if (!empty($user->picture)) {
                $picture = new \user_picture($user);
                $picture->size = 100;
                $avatarurl = $picture->get_url($PAGE)->out(false);
            }
            $this->users[$userid] = [
                'id' => $userid,
                'fullname' => fullname($user, has_capability('moodle/site:viewfullnames', $this->forum->context)),
                'initials' => self::initials($user),
                'avatarurl' => $avatarurl,
                'profileurl' => (new url('/user/view.php', ['id' => $userid, 'course' => $this->forum->course->id]))->out(false),
                'isstaff' => isset($staff[$userid]),
                'streak' => $this->streaks[$userid] ?? 0,
            ];
        }
    }

    /**
     * Export one user.
     *
     * @param int $userid
     * @return array
     */
    public function user(int $userid): array {
        $this->preload_users([$userid]);
        return $this->users[$userid];
    }

    /**
     * Ids of users holding the moderate capability (shown with a "Teacher" badge).
     *
     * @return array<int, bool>
     */
    private function get_staff(): array {
        if ($this->staff === null) {
            $users = get_users_by_capability($this->forum->context, 'mod/hiveforum:moderate', 'u.id');
            $this->staff = array_fill_keys(array_map('intval', array_keys($users)), true);
        }
        return $this->staff;
    }

    /**
     * Two letters initials for the avatar fallback.
     *
     * @param stdClass $user
     * @return string
     */
    public static function initials(stdClass $user): string {
        $first = \core_text::substr(trim((string) ($user->firstname ?? '')), 0, 1);
        $last = \core_text::substr(trim((string) ($user->lastname ?? '')), 0, 1);
        return \core_text::strtoupper($first . $last) ?: '?';
    }

    /**
     * Export a page of discussion summaries.
     *
     * @param stdClass[] $discussions rows returned by api::get_discussions()
     * @return array[]
     */
    public function discussions(array $discussions): array {
        $ids = array_map(fn($d) => (int) $d->id, $discussions);
        $tags = api::get_tags($ids);
        $reactions = api::get_discussion_reaction_totals($ids);
        $participants = api::get_participants($ids);
        $firstposts = api::get_first_posts($discussions);

        $userids = [];
        foreach ($discussions as $d) {
            $userids[] = (int) $d->userid;
            $userids[] = (int) $d->usermodified;
            array_push($userids, ...$participants[(int) $d->id]);
        }
        $this->preload_users($userids);

        $groups = $this->forum->get_groupmode() !== NOGROUPS ? $this->forum->get_all_groups() : [];
        $result = [];
        foreach ($discussions as $d) {
            $id = (int) $d->id;
            $excerpt = '';
            if (isset($firstposts[$id])) {
                $excerpt = self::excerpt($firstposts[$id]->message, (int) $firstposts[$id]->messageformat);
            }
            $replycount = max(0, (int) $d->replycount);
            $result[] = [
                'id' => $id,
                'name' => $this->format_name($d->name),
                'excerpt' => $excerpt,
                'author' => $this->users[(int) $d->userid],
                'lastauthor' => $replycount > 0 && !empty($d->usermodified) ? $this->users[(int) $d->usermodified] : null,
                'participants' => array_map(fn($uid) => $this->users[$uid], $participants[$id]),
                'isquestion' => $d->posttype === 'question',
                'pinned' => !empty($d->pinned),
                'resolved' => !empty($d->resolved),
                'following' => !empty($d->following),
                'unread' => empty($d->timeread) || (int) $d->timeread < (int) $d->timemodified,
                'replycount' => $replycount,
                'reactions' => self::reaction_list($reactions[$id]),
                'tags' => $tags[$id],
                'groupname' => $groups[(int) ($d->groupid ?? 0)] ?? '',
                'timecreated' => (int) $d->timecreated,
                'timemodified' => (int) $d->timemodified,
                'url' => (new url('/mod/hiveforum/discuss.php', ['d' => $id]))->out(false),
            ];
        }
        return $result;
    }

    /**
     * Plain-text excerpt of a message (markup removed, case preserved).
     *
     * @param string $message
     * @param int $format
     * @param int $length
     * @return string
     */
    public static function excerpt(string $message, int $format, int $length = 220): string {
        $message = mentions::to_text($message);
        $html = format_text($message, $format, ['context' => \context_system::instance(), 'filter' => false, 'para' => false]);
        // Keep a space where block elements end, so words do not stick together.
        $text = preg_replace('/<[^>]+>/', ' ', $html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        $text = preg_replace('/\s+([,.)])/u', '$1', $text);
        return shorten_text($text, $length);
    }

    /**
     * Format a discussion name (wiped names get a placeholder).
     *
     * @param string $name
     * @return string
     */
    private function format_name(string $name): string {
        if (trim($name) === '') {
            return get_string('deletedcontent', 'hiveforum');
        }
        return format_string($name, true, ['context' => $this->forum->context]);
    }

    /**
     * Export a full discussion (header data) for the thread view.
     *
     * @param stdClass $discussion
     * @param int $userid
     * @return array
     */
    public function discussion(stdClass $discussion, int $userid): array {
        $forum = $this->forum;
        $tags = api::get_tags([(int) $discussion->id])[(int) $discussion->id];
        $groupid = (int) ($discussion->groupid ?? 0);
        $groupname = $groupid && $forum->get_groupmode() !== NOGROUPS ? ($forum->get_all_groups()[$groupid] ?? '') : '';
        $mode = subscriptions::get_mode($forum);
        return [
            'id' => (int) $discussion->id,
            'name' => $this->format_name($discussion->name),
            'rawname' => $discussion->name,
            'author' => $this->user((int) $discussion->userid),
            'isquestion' => $discussion->posttype === 'question',
            'pinned' => !empty($discussion->pinned),
            'resolved' => !empty($discussion->resolved),
            'following' => subscriptions::is_following($forum, (int) $discussion->id, $userid),
            'tags' => $tags,
            'firstpostid' => (int) $discussion->firstpost,
            'timecreated' => (int) $discussion->timecreated,
            'timemodified' => (int) $discussion->timemodified,
            'canpin' => $forum->can_pin(),
            'canresolve' => $forum->can_resolve($discussion),
            'canfollow' => $forum->can_follow() && subscriptions::can_change_discussion_subscription($forum),
            'followforced' => $mode === subscriptions::FORCED,
            'groupname' => $groupname,
            'canreply' => $forum->can_reply_to($discussion),
            'canrename' => $forum->can_edit_post((object) ['userid' => $discussion->userid]),
        ];
    }

    /**
     * Export posts of a discussion as a flat list (the UI builds the tree).
     *
     * @param stdClass $discussion
     * @param stdClass[] $posts rows from api::get_posts()
     * @return array[]
     */
    public function posts(stdClass $discussion, array $posts): array {
        $this->preload_users(array_map(fn($p) => (int) $p->userid, $posts));
        return array_map(fn($p) => $this->post($discussion, $p), $posts);
    }

    /**
     * Export one post.
     *
     * @param stdClass $discussion
     * @param stdClass $post with ->reactions and ->myreactions (see api::get_posts())
     * @return array
     */
    public function post(stdClass $discussion, stdClass $post): array {
        $forum = $this->forum;
        $context = $forum->context;

        $message = file_rewrite_pluginfile_urls(
            $post->message,
            'pluginfile.php',
            $context->id,
            'mod_hiveforum',
            'post',
            $post->id
        );
        if (trim($post->message) === '') {
            // Content wiped by a privacy request.
            $message = \html_writer::tag('p', get_string('deletedcontent', 'hiveforum'), ['class' => 'hf-wiped']);
        } else {
            $message = mentions::render($message, (int) $post->messageformat, (int) $forum->course->id);
            $message = format_text($message, (int) $post->messageformat, ['context' => $context]);
        }

        $canedit = $forum->can_edit_post($post);
        $isfirst = (int) $post->id === (int) $discussion->firstpost;

        return [
            'id' => (int) $post->id,
            'parentid' => $isfirst ? 0 : (int) $post->parent,
            'author' => $this->user((int) $post->userid),
            'message' => $message,
            // Raw markdown is only sent to people who can edit it inline.
            'rawmessage' => ($canedit && (int) $post->messageformat === (int) FORMAT_MARKDOWN) ? $post->message : '',
            'messageformat' => (int) $post->messageformat,
            'isfirst' => $isfirst,
            'isaccepted' => !empty($post->isaccepted),
            'edited' => (int) $post->timemodified > (int) $post->timecreated + 60,
            'timecreated' => (int) $post->timecreated,
            'timemodified' => (int) $post->timemodified,
            'reactions' => self::reaction_list($post->reactions ?? [], $post->myreactions ?? []),
            'canedit' => $canedit,
            'candelete' => $forum->can_delete_post($post),
            'canaccept' => $discussion->posttype === 'question' && !$isfirst && $forum->can_resolve($discussion),
            'editurl' => (new url('/mod/hiveforum/post.php', ['edit' => $post->id]))->out(false),
        ];
    }

    /**
     * Normalise reaction counters into an ordered list.
     *
     * @param array<string,int> $counts
     * @param array<string,bool> $mine
     * @return array<int, array{type: string, count: int, active: bool}>
     */
    public static function reaction_list(array $counts, array $mine = []): array {
        $list = [];
        foreach (forum::REACTIONS as $type) {
            $list[] = [
                'type' => $type,
                'count' => (int) ($counts[$type] ?? 0),
                'active' => !empty($mine[$type]),
            ];
        }
        return $list;
    }

    /**
     * Permissions and settings needed by the UI.
     *
     * @return array
     */
    public function capabilities(): array {
        $forum = $this->forum;
        $targets = [];
        foreach ($forum->get_post_targets() as $id => $name) {
            $targets[] = ['id' => (int) $id, 'name' => (string) $name];
        }
        return [
            'canstart' => $forum->can_start_discussion(),
            'posttypes' => $forum->get_allowed_post_types(),
            'posttargets' => $forum->get_groupmode() === NOGROUPS ? [] : $targets,
            'groupmode' => $forum->get_groupmode(),
            'canreply' => $forum->can_reply(),
            'canreact' => $forum->can_react(),
            'canmoderate' => $forum->can_moderate(),
            'canpin' => $forum->can_pin(),
            'canfollow' => $forum->can_follow(),
            'allowtags' => !empty($forum->instance->allowtags),
            'allowreactions' => !empty($forum->instance->allowreactions),
            'canviewreports' => has_capability('mod/hiveforum:viewreports', $forum->context),
            'canmanagesubscriptions' => has_capability('mod/hiveforum:managesubscriptions', $forum->context),
        ];
    }

    /**
     * Notification preferences of the current user.
     *
     * @param int $userid
     * @return array
     */
    public function preferences(int $userid): array {
        $forum = $this->forum;
        $mode = subscriptions::get_mode($forum);
        return [
            'subscriptionmode' => $mode,
            'forumsubscribed' => subscriptions::is_forum_subscribed($forum, $userid),
            'canchange' => subscriptions::is_forum_subscription_optional($forum) && $forum->can_follow(),
            'digest' => subscriptions::get_digest($forum, $userid),
            'notificationsenabled' => subscriptions::notifications_enabled($forum),
        ];
    }
}
