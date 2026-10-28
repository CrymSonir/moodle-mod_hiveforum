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

use core_text;
use stdClass;

/**
 * Business logic for hiveforum: every read and write goes through here,
 * whether it comes from a page (view.php, post.php) or a web service.
 *
 * Methods assume permission checks have been done by the caller via {@see forum}.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class api {
    /** @var string[] List filters. */
    public const FILTERS = ['all', 'unread', 'following', 'questions', 'unanswered', 'mine'];

    /** @var string[] List sort orders. */
    public const SORTS = ['recent', 'newest', 'popular', 'reactions', 'likes', 'replies'];

    /** @var int Maximum tags per discussion. */
    public const MAX_TAGS = 5;

    /** @var int Default page size of the discussion list. */
    public const PERPAGE = 20;

    // Reading.

    /**
     * Fetch one page of discussions for the list view.
     *
     * Everything is computed in a fixed number of queries (no N+1), whatever the page size.
     *
     * @param forum $forum
     * @param int $userid
     * @param string $filter one of self::FILTERS
     * @param string $search free text, matches titles and message bodies
     * @param string $tag restrict to a tag ('' for none)
     * @param string $sort one of self::SORTS
     * @param int $page zero-based
     * @param int $perpage
     * @param int $activegroup 0 for every group the user can see
     * @return array{discussions: stdClass[], total: int}
     */
    public static function get_discussions(
        forum $forum,
        int $userid,
        string $filter = 'all',
        string $search = '',
        string $tag = '',
        string $sort = 'recent',
        int $page = 0,
        int $perpage = self::PERPAGE,
        int $activegroup = 0,
    ): array {
        global $DB;

        [$where, $params] = self::build_list_where($forum, $userid, $filter, $search, $tag, $activegroup);
        [$followsql, $followparams] = subscriptions::following_sql($forum, $userid);
        $params['readuser'] = $userid;
        $params['subuser'] = $userid;

        $orderby = match ($sort) {
            'newest' => 'x.timecreated DESC, x.id DESC',
            'popular' => '(x.replycount + x.reactioncount) DESC, x.timemodified DESC',
            'reactions' => 'x.reactioncount DESC, x.timemodified DESC',
            'likes' => 'x.likecount DESC, x.reactioncount DESC, x.timemodified DESC',
            'replies' => 'x.replycount DESC, x.timemodified DESC',
            default => 'x.timemodified DESC, x.id DESC',
        };

        $sql = "SELECT x.*
                  FROM (
                        SELECT d.*,
                               (SELECT COUNT(1) FROM {hiveforum_posts} p WHERE p.discussion = d.id) - 1 AS replycount,
                               (SELECT COUNT(1)
                                  FROM {hiveforum_reactions} r
                                  JOIN {hiveforum_posts} rp ON rp.id = r.postid
                                 WHERE rp.discussion = d.id) AS reactioncount,
                               (SELECT COUNT(1)
                                  FROM {hiveforum_reactions} lr
                                  JOIN {hiveforum_posts} lp ON lp.id = lr.postid
                                 WHERE lp.discussion = d.id AND lr.reactiontype = 'like') AS likecount,
                               rd.timeread,
                               CASE WHEN $followsql THEN 1 ELSE 0 END AS following
                          FROM {hiveforum_discussions} d
                     LEFT JOIN {hiveforum_reads} rd ON rd.discussion = d.id AND rd.userid = :readuser
                     LEFT JOIN {hiveforum_subscriptions} s ON s.discussion = d.id AND s.userid = :subuser
                         WHERE $where
                       ) x
              ORDER BY x.pinned DESC, $orderby";

        $records = $DB->get_records_sql($sql, $params + $followparams, $page * $perpage, $perpage);

        $countsql = "SELECT COUNT(1)
                       FROM {hiveforum_discussions} d
                  LEFT JOIN {hiveforum_reads} rd ON rd.discussion = d.id AND rd.userid = :readuser
                  LEFT JOIN {hiveforum_subscriptions} s ON s.discussion = d.id AND s.userid = :subuser
                      WHERE $where";
        $total = (int) $DB->count_records_sql($countsql, $params);

        return ['discussions' => array_values($records), 'total' => $total];
    }

    /**
     * Build the WHERE clause for the list view.
     *
     * Expects the aliases d (discussions), rd (reads of the user) and s (subscriptions of the user).
     *
     * @param forum $forum
     * @param int $userid
     * @param string $filter
     * @param string $search
     * @param string $tag
     * @param int $activegroup
     * @return array{0: string, 1: array}
     */
    private static function build_list_where(
        forum $forum,
        int $userid,
        string $filter,
        string $search,
        string $tag,
        int $activegroup = 0
    ): array {
        global $DB;

        [$groupsql, $params] = $forum->group_sql($userid, $activegroup);
        $where = ['d.hiveforum = :forumid', $groupsql];
        $params['forumid'] = $forum->instance->id;

        switch ($filter) {
            case 'unread':
                $where[] = '(rd.id IS NULL OR rd.timeread < d.timemodified)';
                break;
            case 'following':
                [$followsql, $followparams] = subscriptions::following_sql($forum, $userid);
                $where[] = $followsql;
                $params += $followparams;
                break;
            case 'questions':
                $where[] = "d.posttype = 'question' AND d.resolved = 0";
                break;
            case 'unanswered':
                $where[] = 'NOT EXISTS (SELECT 1 FROM {hiveforum_posts} ua WHERE ua.discussion = d.id AND ua.parent <> 0)';
                break;
            case 'mine':
                $where[] = 'd.userid = :mine';
                $params['mine'] = $userid;
                break;
        }

        $search = trim($search);
        if ($search !== '') {
            $like1 = $DB->sql_like('d.name', ':search1', false, false);
            $like2 = $DB->sql_like('sp.message', ':search2', false, false);
            $where[] = "($like1 OR EXISTS (SELECT 1 FROM {hiveforum_posts} sp WHERE sp.discussion = d.id AND $like2))";
            $params['search1'] = '%' . $DB->sql_like_escape($search) . '%';
            $params['search2'] = $params['search1'];
        }

        $tag = self::normalise_tag($tag);
        if ($tag !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM {hiveforum_tags} t WHERE t.discussion = d.id AND t.tag = :tag)';
            $params['tag'] = $tag;
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Counters shown next to the filters.
     *
     * @param forum $forum
     * @param int $userid
     * @param int $activegroup
     * @return array{all: int, unread: int, following: int, questions: int, unanswered: int, mine: int}
     */
    public static function get_filter_counts(forum $forum, int $userid, int $activegroup = 0): array {
        global $DB;

        [$groupsql, $params] = $forum->group_sql($userid, $activegroup);
        [$followsql, $followparams] = subscriptions::following_sql($forum, $userid);
        $sql = "SELECT COUNT(1) AS total,
                       SUM(CASE WHEN rd.id IS NULL OR rd.timeread < d.timemodified THEN 1 ELSE 0 END) AS unread,
                       SUM(CASE WHEN $followsql THEN 1 ELSE 0 END) AS following,
                       SUM(CASE WHEN d.posttype = 'question' AND d.resolved = 0 THEN 1 ELSE 0 END) AS questions,
                       SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM {hiveforum_posts} ua
                                                  WHERE ua.discussion = d.id AND ua.parent <> 0)
                                THEN 1 ELSE 0 END) AS unanswered,
                       SUM(CASE WHEN d.userid = :mine THEN 1 ELSE 0 END) AS mine
                  FROM {hiveforum_discussions} d
             LEFT JOIN {hiveforum_reads} rd ON rd.discussion = d.id AND rd.userid = :readuser
             LEFT JOIN {hiveforum_subscriptions} s ON s.discussion = d.id AND s.userid = :subuser
                 WHERE d.hiveforum = :forumid AND $groupsql";
        $row = $DB->get_record_sql($sql, $params + $followparams + [
            'mine' => $userid, 'readuser' => $userid, 'subuser' => $userid, 'forumid' => $forum->instance->id,
        ]);

        return [
            'all' => (int) $row->total,
            'unread' => (int) $row->unread,
            'following' => (int) $row->following,
            'questions' => (int) $row->questions,
            'unanswered' => (int) $row->unanswered,
            'mine' => (int) $row->mine,
        ];
    }

    /**
     * Number of unread discussions (among the ones the user can see) for a user.
     *
     * @param forum $forum
     * @param int $userid
     * @return int
     */
    public static function count_unread(forum $forum, int $userid): int {
        global $DB;
        [$groupsql, $params] = $forum->group_sql($userid);
        $sql = "SELECT COUNT(1)
                  FROM {hiveforum_discussions} d
             LEFT JOIN {hiveforum_reads} rd ON rd.discussion = d.id AND rd.userid = :userid
                 WHERE d.hiveforum = :forumid AND $groupsql AND (rd.id IS NULL OR rd.timeread < d.timemodified)";
        return (int) $DB->count_records_sql($sql, $params + ['userid' => $userid, 'forumid' => $forum->instance->id]);
    }

    /**
     * Statistics for the header (restricted to what the user can see).
     *
     * @param forum $forum
     * @param int $userid
     * @param int $activegroup
     * @return array{discussions: int, posts: int, participants: int}
     */
    public static function get_stats(forum $forum, int $userid, int $activegroup = 0): array {
        global $DB;
        [$groupsql, $params] = $forum->group_sql($userid, $activegroup);
        $sql = "SELECT COUNT(DISTINCT d.id) AS discussions, COUNT(p.id) AS posts, COUNT(DISTINCT p.userid) AS participants
                  FROM {hiveforum_discussions} d
                  JOIN {hiveforum_posts} p ON p.discussion = d.id
                 WHERE d.hiveforum = :forumid AND $groupsql";
        $row = $DB->get_record_sql($sql, $params + ['forumid' => $forum->instance->id]);
        return [
            'discussions' => (int) $row->discussions,
            'posts' => (int) $row->posts,
            'participants' => (int) $row->participants,
        ];
    }

    /**
     * Most used tags of the forum.
     *
     * @param forum $forum
     * @param int $userid
     * @param int $activegroup
     * @param int $limit
     * @return array<int, array{name: string, count: int}>
     */
    public static function get_popular_tags(forum $forum, int $userid, int $activegroup = 0, int $limit = 12): array {
        global $DB;
        [$groupsql, $params] = $forum->group_sql($userid, $activegroup);
        $sql = "SELECT t.tag, COUNT(1) AS cnt
                  FROM {hiveforum_tags} t
                  JOIN {hiveforum_discussions} d ON d.id = t.discussion
                 WHERE d.hiveforum = :forumid AND $groupsql
              GROUP BY t.tag
              ORDER BY cnt DESC, t.tag ASC";
        $rs = $DB->get_recordset_sql($sql, $params + ['forumid' => $forum->instance->id], 0, $limit);
        $tags = [];
        foreach ($rs as $row) {
            $tags[] = ['name' => $row->tag, 'count' => (int) $row->cnt];
        }
        $rs->close();
        return $tags;
    }

    /**
     * Tags of several discussions.
     *
     * @param int[] $discussionids
     * @return array<int, string[]> discussion id => tags
     */
    public static function get_tags(array $discussionids): array {
        global $DB;
        $result = array_fill_keys($discussionids, []);
        if (!$discussionids) {
            return $result;
        }
        [$insql, $params] = $DB->get_in_or_equal($discussionids);
        $rs = $DB->get_recordset_select('hiveforum_tags', "discussion $insql", $params, 'id ASC', 'id, discussion, tag');
        foreach ($rs as $row) {
            $result[(int) $row->discussion][] = $row->tag;
        }
        $rs->close();
        return $result;
    }

    /**
     * Reaction totals per type for several discussions.
     *
     * @param int[] $discussionids
     * @return array<int, array<string, int>> discussion id => [type => count]
     */
    public static function get_discussion_reaction_totals(array $discussionids): array {
        global $DB;
        $result = [];
        foreach ($discussionids as $id) {
            $result[$id] = array_fill_keys(forum::REACTIONS, 0);
        }
        if (!$discussionids) {
            return $result;
        }
        [$insql, $params] = $DB->get_in_or_equal($discussionids);
        $sql = "SELECT p.discussion, r.reactiontype, COUNT(1) AS cnt
                  FROM {hiveforum_reactions} r
                  JOIN {hiveforum_posts} p ON p.id = r.postid
                 WHERE p.discussion $insql
              GROUP BY p.discussion, r.reactiontype";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $row) {
            if (isset($result[(int) $row->discussion][$row->reactiontype])) {
                $result[(int) $row->discussion][$row->reactiontype] = (int) $row->cnt;
            }
        }
        $rs->close();
        return $result;
    }

    /**
     * Distinct participants (most recent first) of several discussions.
     *
     * @param int[] $discussionids
     * @param int $max maximum per discussion
     * @return array<int, int[]> discussion id => user ids
     */
    public static function get_participants(array $discussionids, int $max = 4): array {
        global $DB;
        $result = array_fill_keys($discussionids, []);
        if (!$discussionids) {
            return $result;
        }
        [$insql, $params] = $DB->get_in_or_equal($discussionids);
        $sql = "SELECT p.discussion, p.userid, MAX(p.timecreated) AS lasttime
                  FROM {hiveforum_posts} p
                 WHERE p.discussion $insql
              GROUP BY p.discussion, p.userid
              ORDER BY lasttime DESC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $row) {
            if (count($result[(int) $row->discussion]) < $max) {
                $result[(int) $row->discussion][] = (int) $row->userid;
            }
        }
        $rs->close();
        return $result;
    }

    /**
     * First post (opening message) of several discussions.
     *
     * @param stdClass[] $discussions
     * @return array<int, stdClass> discussion id => post
     */
    public static function get_first_posts(array $discussions): array {
        global $DB;
        $ids = array_filter(array_map(fn($d) => (int) $d->firstpost, $discussions));
        if (!$ids) {
            return [];
        }
        $posts = $DB->get_records_list('hiveforum_posts', 'id', $ids, '', 'id, discussion, message, messageformat');
        $result = [];
        foreach ($posts as $post) {
            $result[(int) $post->discussion] = $post;
        }
        return $result;
    }

    /**
     * All posts of a discussion, oldest first, with reaction data.
     *
     * @param stdClass $discussion
     * @param int $userid current user (for "my reactions")
     * @return stdClass[] posts with ->reactions [type => count] and ->myreactions [type => bool]
     */
    public static function get_posts(stdClass $discussion, int $userid): array {
        global $DB;

        $posts = $DB->get_records('hiveforum_posts', ['discussion' => $discussion->id], 'timecreated ASC, id ASC');
        foreach ($posts as $post) {
            $post->reactions = array_fill_keys(forum::REACTIONS, 0);
            $post->myreactions = array_fill_keys(forum::REACTIONS, false);
        }
        if (!$posts) {
            return [];
        }

        $sql = "SELECT r.id, r.postid, r.reactiontype, r.userid
                  FROM {hiveforum_reactions} r
                  JOIN {hiveforum_posts} p ON p.id = r.postid
                 WHERE p.discussion = :discussion";
        $rs = $DB->get_recordset_sql($sql, ['discussion' => $discussion->id]);
        foreach ($rs as $r) {
            if (!isset($posts[$r->postid]->reactions[$r->reactiontype])) {
                continue;
            }
            $posts[$r->postid]->reactions[$r->reactiontype]++;
            if ((int) $r->userid === $userid) {
                $posts[$r->postid]->myreactions[$r->reactiontype] = true;
            }
        }
        $rs->close();

        return array_values($posts);
    }

    /**
     * Current and best streak of a user in a forum.
     *
     * The current streak is only "alive" if the user contributed this week or last week.
     *
     * @param int $forumid
     * @param int[] $userids
     * @return array<int, int> user id => current streak (weeks)
     */
    public static function get_streaks(int $forumid, array $userids): array {
        global $DB;
        $result = array_fill_keys($userids, 0);
        if (!$userids) {
            return $result;
        }
        $week = self::current_week();
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['forumid'] = $forumid;
        $records = $DB->get_records_select('hiveforum_streaks', "hiveforum = :forumid AND userid $insql", $params);
        foreach ($records as $streak) {
            if ((int) $streak->lastactiveweek >= $week - 1) {
                $result[(int) $streak->userid] = (int) $streak->currentstreak;
            }
        }
        return $result;
    }

    // Writing.

    /**
     * Create a discussion and its opening post.
     *
     * @param forum $forum
     * @param int $userid
     * @param string $name
     * @param string $message
     * @param int $format FORMAT_*
     * @param bool $isquestion
     * @param string[] $tags
     * @param int|null $draftitemid draft area of the full editor, if any
     * @param array $editoroptions
     * @param int $groupid target group (0 = all participants); must be allowed for the user
     * @return stdClass the created discussion (with ->firstpost set)
     */
    public static function create_discussion(
        forum $forum,
        int $userid,
        string $name,
        string $message,
        int $format,
        bool $isquestion,
        array $tags = [],
        ?int $draftitemid = null,
        array $editoroptions = [],
        int $groupid = 0,
    ): stdClass {
        global $DB;

        $name = trim(clean_param($name, PARAM_TEXT));
        if ($name === '') {
            throw new \moodle_exception('errornameempty', 'hiveforum');
        }
        self::require_message($message);
        if (!in_array($isquestion ? 'question' : 'discussion', $forum->get_allowed_post_types(), true)) {
            throw new \moodle_exception('posttypenotallowed', 'hiveforum');
        }
        if (!array_key_exists($groupid, $forum->get_post_targets())) {
            throw new \moodle_exception('cannotpostingroup', 'hiveforum');
        }

        $now = time();
        $transaction = $DB->start_delegated_transaction();

        $discussion = (object) [
            'hiveforum' => $forum->instance->id,
            'userid' => $userid,
            'name' => core_text::substr($name, 0, 255),
            'firstpost' => 0,
            'pinned' => 0,
            'resolved' => 0,
            'posttype' => $isquestion ? 'question' : 'discussion',
            'usermodified' => $userid,
            'groupid' => $forum->get_groupmode() === NOGROUPS ? 0 : $groupid,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $discussion->id = $DB->insert_record('hiveforum_discussions', $discussion);

        $post = (object) [
            'discussion' => $discussion->id,
            'parent' => 0,
            'userid' => $userid,
            'message' => $message,
            'messageformat' => $format,
            'isaccepted' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $post->id = $DB->insert_record('hiveforum_posts', $post);

        if ($draftitemid) {
            $post->message = file_save_draft_area_files(
                $draftitemid,
                $forum->context->id,
                'mod_hiveforum',
                'post',
                $post->id,
                $editoroptions,
                $message
            );
            $DB->set_field('hiveforum_posts', 'message', $post->message, ['id' => $post->id]);
        }

        $discussion->firstpost = $post->id;
        $DB->set_field('hiveforum_discussions', 'firstpost', $post->id, ['id' => $discussion->id]);

        if (!empty($forum->instance->allowtags)) {
            self::set_tags($discussion->id, $tags);
        }

        subscriptions::set_following($forum, (int) $discussion->id, $userid, true);
        self::mark_read((int) $discussion->id, $userid);
        self::bump_streak((int) $forum->instance->id, $userid);
        mentions::process($forum, $discussion, $post);

        $transaction->allow_commit();

        \mod_hiveforum\event\discussion_created::create([
            'objectid' => $discussion->id,
            'context' => $forum->context,
            'other' => ['forumid' => $forum->instance->id],
        ])->trigger();

        self::queue_notification($forum, (int) $post->id);
        self::update_completion($forum, [$userid]);

        return $discussion;
    }

    /**
     * Reply to a discussion.
     *
     * @param forum $forum
     * @param stdClass $discussion
     * @param int $parentid post being replied to (0 means the opening post)
     * @param int $userid
     * @param string $message
     * @param int $format
     * @return stdClass the new post
     */
    public static function add_post(
        forum $forum,
        stdClass $discussion,
        int $parentid,
        int $userid,
        string $message,
        int $format,
    ): stdClass {
        global $DB;

        $forum->require_discussion_in_forum($discussion);
        self::require_message($message);

        if ($parentid) {
            if (!$DB->record_exists('hiveforum_posts', ['id' => $parentid, 'discussion' => $discussion->id])) {
                throw new \moodle_exception('invalidparentpost', 'hiveforum');
            }
        } else {
            $parentid = (int) $discussion->firstpost;
        }

        $now = time();
        $transaction = $DB->start_delegated_transaction();

        $post = (object) [
            'discussion' => $discussion->id,
            'parent' => $parentid,
            'userid' => $userid,
            'message' => $message,
            'messageformat' => $format,
            'isaccepted' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $post->id = $DB->insert_record('hiveforum_posts', $post);

        $DB->update_record('hiveforum_discussions', (object) [
            'id' => $discussion->id,
            'timemodified' => $now,
            'usermodified' => $userid,
        ]);

        subscriptions::set_following($forum, (int) $discussion->id, $userid, true);
        self::mark_read((int) $discussion->id, $userid);
        self::bump_streak((int) $forum->instance->id, $userid);
        mentions::process($forum, $discussion, $post);

        $transaction->allow_commit();

        \mod_hiveforum\event\post_created::create([
            'objectid' => $post->id,
            'context' => $forum->context,
            'other' => ['discussionid' => $discussion->id, 'forumid' => $forum->instance->id],
        ])->trigger();

        self::queue_notification($forum, (int) $post->id);
        self::update_completion($forum, [$userid]);

        return $post;
    }

    /**
     * Update the message of a post.
     *
     * @param forum $forum
     * @param stdClass $discussion
     * @param stdClass $post
     * @param string $message
     * @param int|null $format keep the current format when null
     * @return stdClass updated post
     */
    public static function update_post(forum $forum, stdClass $discussion, stdClass $post, string $message, ?int $format = null): stdClass {
        global $DB;

        $forum->require_discussion_in_forum($discussion);
        self::require_message($message);

        $post->message = $message;
        if ($format !== null) {
            $post->messageformat = $format;
        }
        $post->timemodified = time();
        $DB->update_record('hiveforum_posts', $post);
        // Only the users newly mentioned by this edit are notified.
        mentions::process($forum, $discussion, $post);

        \mod_hiveforum\event\post_updated::create([
            'objectid' => $post->id,
            'context' => $forum->context,
            'other' => ['discussionid' => $discussion->id, 'forumid' => $forum->instance->id],
        ])->trigger();

        return $post;
    }

    /**
     * Rename a discussion / change its tags.
     *
     * @param forum $forum
     * @param stdClass $discussion
     * @param string $name
     * @param string[]|null $tags null leaves the tags untouched
     */
    public static function update_discussion(forum $forum, stdClass $discussion, string $name, ?array $tags = null): void {
        global $DB;
        $forum->require_discussion_in_forum($discussion);
        $name = trim(clean_param($name, PARAM_TEXT));
        if ($name === '') {
            throw new \moodle_exception('errornameempty', 'hiveforum');
        }
        $DB->set_field('hiveforum_discussions', 'name', core_text::substr($name, 0, 255), ['id' => $discussion->id]);
        if ($tags !== null && !empty($forum->instance->allowtags)) {
            self::set_tags((int) $discussion->id, $tags);
        }
    }

    /**
     * Delete a post and all its replies. Deleting the opening post deletes the discussion.
     *
     * @param forum $forum
     * @param stdClass $discussion
     * @param stdClass $post
     * @return bool true when the whole discussion was deleted
     */
    public static function delete_post(forum $forum, stdClass $discussion, stdClass $post): bool {
        global $DB;

        $forum->require_discussion_in_forum($discussion);
        $fs = get_file_storage();

        if ((int) $post->id === (int) $discussion->firstpost || (int) $post->parent === 0) {
            $postids = $DB->get_fieldset_select('hiveforum_posts', 'id', 'discussion = ?', [$discussion->id]);
            $authors = $DB->get_fieldset_select('hiveforum_posts', 'DISTINCT userid', 'discussion = ?', [$discussion->id]);
            $transaction = $DB->start_delegated_transaction();
            self::delete_discussion_data([(int) $discussion->id]);
            $transaction->allow_commit();
            foreach ($postids as $postid) {
                $fs->delete_area_files($forum->context->id, 'mod_hiveforum', 'post', $postid);
            }
            \mod_hiveforum\event\discussion_deleted::create([
                'objectid' => $discussion->id,
                'context' => $forum->context,
                'other' => ['forumid' => $forum->instance->id],
            ])->trigger();
            self::update_completion($forum, array_map('intval', $authors));
            return true;
        }

        // Collect the post and all of its descendants.
        $children = [];
        foreach ($DB->get_records('hiveforum_posts', ['discussion' => $discussion->id], '', 'id, parent') as $p) {
            $children[(int) $p->parent][] = (int) $p->id;
        }
        $todelete = [];
        $stack = [(int) $post->id];
        while ($stack) {
            $id = array_pop($stack);
            $todelete[] = $id;
            foreach ($children[$id] ?? [] as $childid) {
                $stack[] = $childid;
            }
        }

        $transaction = $DB->start_delegated_transaction();
        [$insql, $params] = $DB->get_in_or_equal($todelete);
        $removesanswer = $DB->record_exists_select('hiveforum_posts', "id $insql AND isaccepted = 1", $params);
        $authors = $DB->get_fieldset_select('hiveforum_posts', 'DISTINCT userid', "id $insql", $params);
        $DB->delete_records_select('hiveforum_reactions', "postid $insql", $params);
        $DB->delete_records_select('hiveforum_mentions', "postid $insql", $params);
        $DB->delete_records_select('hiveforum_posts', "id $insql", $params);
        if ($removesanswer) {
            // The accepted answer is gone: the question is open again.
            self::set_resolved($discussion, false);
        }
        $transaction->allow_commit();

        foreach ($todelete as $id) {
            $fs->delete_area_files($forum->context->id, 'mod_hiveforum', 'post', $id);
        }

        \mod_hiveforum\event\post_deleted::create([
            'objectid' => $post->id,
            'context' => $forum->context,
            'other' => ['discussionid' => $discussion->id, 'forumid' => $forum->instance->id],
        ])->trigger();
        self::update_completion($forum, array_map('intval', $authors));

        return false;
    }

    /**
     * Delete discussions and everything attached to them (files excepted).
     *
     * @param int[] $discussionids
     */
    public static function delete_discussion_data(array $discussionids): void {
        global $DB;
        if (!$discussionids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($discussionids);
        foreach (['hiveforum_reactions', 'hiveforum_mentions'] as $table) {
            $DB->delete_records_select($table, "postid IN (SELECT id FROM {hiveforum_posts} WHERE discussion $insql)", $params);
        }
        $DB->delete_records_select('hiveforum_posts', "discussion $insql", $params);
        $DB->delete_records_select('hiveforum_tags', "discussion $insql", $params);
        $DB->delete_records_select('hiveforum_reads', "discussion $insql", $params);
        $DB->delete_records_select('hiveforum_subscriptions', "discussion $insql", $params);
        $DB->delete_records_select('hiveforum_discussions', "id $insql", $params);
    }

    /**
     * Toggle a reaction of a user on a post.
     *
     * @param forum $forum
     * @param stdClass $post
     * @param int $userid
     * @param string $type
     * @return array{reactions: array<string,int>, myreactions: array<string,bool>}
     */
    public static function toggle_reaction(forum $forum, stdClass $post, int $userid, string $type): array {
        global $DB;

        if (!in_array($type, forum::REACTIONS, true)) {
            throw new \invalid_parameter_exception('Unsupported reaction type');
        }

        $existing = $DB->get_record('hiveforum_reactions', [
            'postid' => $post->id, 'userid' => $userid, 'reactiontype' => $type,
        ]);
        if ($existing) {
            $DB->delete_records('hiveforum_reactions', ['id' => $existing->id]);
        } else {
            $DB->insert_record('hiveforum_reactions', [
                'postid' => $post->id, 'userid' => $userid, 'reactiontype' => $type, 'timecreated' => time(),
            ]);
            \mod_hiveforum\event\reaction_added::create([
                'objectid' => $post->id,
                'context' => $forum->context,
                'other' => ['reactiontype' => $type, 'discussionid' => $post->discussion],
            ])->trigger();
        }

        $reactions = array_fill_keys(forum::REACTIONS, 0);
        $mine = array_fill_keys(forum::REACTIONS, false);
        foreach ($DB->get_records('hiveforum_reactions', ['postid' => $post->id], '', 'id, reactiontype, userid') as $r) {
            if (isset($reactions[$r->reactiontype])) {
                $reactions[$r->reactiontype]++;
                if ((int) $r->userid === $userid) {
                    $mine[$r->reactiontype] = true;
                }
            }
        }
        return ['reactions' => $reactions, 'myreactions' => $mine];
    }

    /**
     * Pin or unpin a discussion.
     *
     * @param stdClass $discussion
     * @param bool $value
     */
    public static function set_pinned(stdClass $discussion, bool $value): void {
        global $DB;
        $DB->set_field('hiveforum_discussions', 'pinned', (int) $value, ['id' => $discussion->id]);
        $discussion->pinned = (int) $value;
    }

    /**
     * Mark a discussion as resolved (or not).
     *
     * @param stdClass $discussion
     * @param bool $value
     */
    public static function set_resolved(stdClass $discussion, bool $value): void {
        global $DB;
        $DB->set_field('hiveforum_discussions', 'resolved', (int) $value, ['id' => $discussion->id]);
        $discussion->resolved = (int) $value;
    }

    /**
     * Accept (or un-accept) a reply as the answer of a question. Only one answer can be accepted.
     *
     * @param stdClass $discussion
     * @param stdClass $post
     * @return bool whether the post is now accepted
     */
    public static function toggle_accepted(stdClass $discussion, stdClass $post): bool {
        global $DB;

        if ($discussion->posttype !== 'question') {
            throw new \moodle_exception('notaquestion', 'hiveforum');
        }
        if ((int) $post->discussion !== (int) $discussion->id || (int) $post->id === (int) $discussion->firstpost) {
            throw new \moodle_exception('cannotacceptpost', 'hiveforum');
        }

        $accept = empty($post->isaccepted);
        $previous = $DB->get_fieldset_select('hiveforum_posts', 'userid', 'discussion = ? AND isaccepted = 1', [$discussion->id]);
        $transaction = $DB->start_delegated_transaction();
        $DB->set_field('hiveforum_posts', 'isaccepted', 0, ['discussion' => $discussion->id]);
        if ($accept) {
            $DB->set_field('hiveforum_posts', 'isaccepted', 1, ['id' => $post->id]);
        }
        self::set_resolved($discussion, $accept);
        $transaction->allow_commit();

        $post->isaccepted = (int) $accept;
        if ($forum = forum::from_instance_id((int) $discussion->hiveforum)) {
            self::update_completion($forum, array_merge([(int) $post->userid], array_map('intval', $previous)));
        }
        return $accept;
    }

    /**
     * Mark a discussion as read now.
     *
     * @param int $discussionid
     * @param int $userid
     */
    public static function mark_read(int $discussionid, int $userid): void {
        global $DB;
        if (!$userid || isguestuser($userid)) {
            return;
        }
        $now = time();
        $existing = $DB->get_record('hiveforum_reads', ['discussion' => $discussionid, 'userid' => $userid], 'id');
        if ($existing) {
            $DB->set_field('hiveforum_reads', 'timeread', $now, ['id' => $existing->id]);
        } else {
            $DB->insert_record('hiveforum_reads', ['discussion' => $discussionid, 'userid' => $userid, 'timeread' => $now]);
        }
    }

    /**
     * Mark every discussion of the forum as read.
     *
     * @param forum $forum
     * @param int $userid
     */
    public static function mark_all_read(forum $forum, int $userid): void {
        global $DB;
        [$groupsql, $params] = $forum->group_sql($userid);
        $ids = $DB->get_fieldset_select(
            'hiveforum_discussions',
            'id',
            "hiveforum = :forumid AND $groupsql",
            $params + ['forumid' => $forum->instance->id]
        );
        $transaction = $DB->start_delegated_transaction();
        foreach ($ids as $id) {
            self::mark_read((int) $id, $userid);
        }
        $transaction->allow_commit();
    }

    /**
     * Replace the tags of a discussion.
     *
     * @param int $discussionid
     * @param string[] $tags
     */
    public static function set_tags(int $discussionid, array $tags): void {
        global $DB;
        $DB->delete_records('hiveforum_tags', ['discussion' => $discussionid]);
        foreach (self::normalise_tags($tags) as $tag) {
            $DB->insert_record('hiveforum_tags', ['discussion' => $discussionid, 'tag' => $tag]);
        }
    }

    /**
     * Clean a list of tags: lower case, unique, no '#', at most MAX_TAGS.
     *
     * @param string[] $tags
     * @return string[]
     */
    public static function normalise_tags(array $tags): array {
        $clean = [];
        foreach ($tags as $tag) {
            $tag = self::normalise_tag((string) $tag);
            if ($tag !== '' && !in_array($tag, $clean, true)) {
                $clean[] = $tag;
            }
        }
        return array_slice($clean, 0, self::MAX_TAGS);
    }

    /**
     * Clean one tag.
     *
     * @param string $tag
     * @return string
     */
    public static function normalise_tag(string $tag): string {
        $tag = clean_param(trim(ltrim(trim($tag), '#')), PARAM_TAG);
        return core_text::substr(core_text::strtolower($tag), 0, 50);
    }

    /**
     * Update the weekly contribution streak for a user after they post.
     *
     * @param int $forumid
     * @param int $userid
     */
    public static function bump_streak(int $forumid, int $userid): void {
        global $DB;

        $week = self::current_week();
        $streak = $DB->get_record('hiveforum_streaks', ['hiveforum' => $forumid, 'userid' => $userid]);

        if (!$streak) {
            $DB->insert_record('hiveforum_streaks', (object) [
                'hiveforum' => $forumid,
                'userid' => $userid,
                'currentstreak' => 1,
                'beststreak' => 1,
                'lastactiveweek' => $week,
            ]);
            return;
        }

        // DB values are strings: compare as integers (the previous === comparison always failed).
        $lastweek = (int) $streak->lastactiveweek;
        if ($lastweek === $week) {
            return;
        }
        $streak->currentstreak = ($lastweek === $week - 1) ? ((int) $streak->currentstreak + 1) : 1;
        $streak->beststreak = max((int) $streak->beststreak, (int) $streak->currentstreak);
        $streak->lastactiveweek = $week;
        $DB->update_record('hiveforum_streaks', $streak);
    }

    /**
     * Index of the current week since the epoch.
     *
     * @return int
     */
    public static function current_week(): int {
        return (int) floor(\core\di::get(\core\clock::class)->time() / WEEKSECS);
    }

    /**
     * Queue the notification of a new post (followers and forum subscribers).
     *
     * @param forum $forum
     * @param int $postid
     */
    private static function queue_notification(forum $forum, int $postid): void {
        if (!subscriptions::notifications_enabled($forum) || subscriptions::get_mode($forum) === subscriptions::DISABLED) {
            return;
        }
        $task = new \mod_hiveforum\task\notify_followers();
        $task->set_custom_data(['postid' => $postid]);
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Re-evaluate the custom completion rules for some users.
     *
     * @param forum $forum
     * @param int[] $userids
     */
    public static function update_completion(forum $forum, array $userids): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $instance = $forum->instance;
        $hasrules = !empty($instance->completiondiscussions) || !empty($instance->completionreplies)
            || !empty($instance->completionposts) || !empty($instance->completionaccepted);
        if (!$hasrules) {
            return;
        }
        $completion = new \completion_info($forum->course);
        if (!$completion->is_enabled($forum->cm) || $forum->cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
            return;
        }
        foreach (array_unique($userids) as $userid) {
            $completion->update_state($forum->cm, COMPLETION_UNKNOWN, $userid);
        }
    }

    /**
     * Throw if a message is empty once markup is removed.
     *
     * @param string $message
     */
    private static function require_message(string $message): void {
        if (trim(html_to_text($message, 0, false)) === '' && !preg_match('/<img\b/i', $message)) {
            throw new \moodle_exception('errormessageempty', 'hiveforum');
        }
    }
}
