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

use context_module;
use stdClass;

/**
 * A loaded hiveforum activity: instance record, course module, course and context,
 * plus every permission check used by pages and web services.
 *
 * Centralising the checks here guarantees the React UI (which only hides buttons)
 * and the server (which enforces) never disagree.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class forum {
    /** @var string[] Supported reaction types, in display order. */
    public const REACTIONS = ['like', 'love', 'idea'];

    /** @var array<int, int[]> Group ids per user (request cache). */
    private array $groupcache = [];

    /**
     * Constructor.
     *
     * @param stdClass $instance hiveforum record
     * @param \cm_info|stdClass $cm course module
     * @param stdClass $course course record
     * @param context_module $context module context
     */
    private function __construct(
        /** @var stdClass hiveforum record */
        public readonly stdClass $instance,
        /** @var \cm_info|stdClass course module */
        public readonly object $cm,
        /** @var stdClass course record */
        public readonly stdClass $course,
        /** @var context_module module context */
        public readonly context_module $context,
    ) {
    }

    /**
     * Load from a course module id.
     *
     * @param int $cmid
     * @return self
     */
    public static function from_cmid(int $cmid): self {
        global $DB;
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'hiveforum');
        $instance = $DB->get_record('hiveforum', ['id' => $cm->instance], '*', MUST_EXIST);
        return new self($instance, $cm, $course, context_module::instance($cm->id));
    }

    /**
     * Load from an instance id.
     *
     * @param int $instanceid
     * @return self
     */
    public static function from_instance_id(int $instanceid): self {
        global $DB;
        $instance = $DB->get_record('hiveforum', ['id' => $instanceid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($instance, 'hiveforum');
        return new self($instance, $cm, $course, context_module::instance($cm->id));
    }

    /**
     * Load from a discussion id; returns the forum and the discussion record.
     *
     * @param int $discussionid
     * @return array{0: self, 1: stdClass}
     */
    public static function from_discussion_id(int $discussionid): array {
        global $DB;
        $discussion = $DB->get_record('hiveforum_discussions', ['id' => $discussionid], '*', MUST_EXIST);
        return [self::from_instance_id((int) $discussion->hiveforum), $discussion];
    }

    /**
     * Load from a post id; returns the forum, the discussion and the post records.
     *
     * @param int $postid
     * @return array{0: self, 1: stdClass, 2: stdClass}
     */
    public static function from_post_id(int $postid): array {
        global $DB;
        $post = $DB->get_record('hiveforum_posts', ['id' => $postid], '*', MUST_EXIST);
        [$forum, $discussion] = self::from_discussion_id((int) $post->discussion);
        return [$forum, $discussion, $post];
    }

    /**
     * Course module id.
     *
     * @return int
     */
    public function get_cmid(): int {
        return (int) $this->cm->id;
    }

    /**
     * Ensure the discussion belongs to this forum.
     *
     * @param stdClass $discussion
     */
    public function require_discussion_in_forum(stdClass $discussion): void {
        if ((int) $discussion->hiveforum !== (int) $this->instance->id) {
            throw new \moodle_exception('invaliddiscussionid', 'hiveforum');
        }
    }

    /**
     * Whether the given user is a real (non-guest) logged-in user.
     *
     * @return bool
     */
    private function is_real_user(): bool {
        return isloggedin() && !isguestuser();
    }

    /**
     * Can the current user view the forum?
     *
     * @return bool
     */
    public function can_view(): bool {
        return has_capability('mod/hiveforum:view', $this->context);
    }

    /**
     * Can the current user start a discussion (at least one type, in at least one group)?
     *
     * @return bool
     */
    public function can_start_discussion(): bool {
        return $this->is_real_user()
            && has_capability('mod/hiveforum:startdiscussion', $this->context)
            && $this->get_allowed_post_types()
            && $this->get_post_targets();
    }

    /**
     * Types of discussion the current user may start ('discussion', 'question').
     *
     * Moderators can always start both; other users depend on the "studentposting" setting.
     *
     * @return string[]
     */
    public function get_allowed_post_types(): array {
        if ($this->can_moderate()) {
            return ['discussion', 'question'];
        }
        return match ($this->instance->studentposting ?? 'all') {
            'discussions' => ['discussion'],
            'questions' => ['question'],
            'none' => [],
            default => ['discussion', 'question'],
        };
    }

    /**
     * Can the current user reply (in general, regardless of groups)?
     *
     * @return bool
     */
    public function can_reply(): bool {
        return $this->is_real_user() && has_capability('mod/hiveforum:reply', $this->context);
    }

    /**
     * Can the current user reply to this discussion (groups taken into account)?
     *
     * @param stdClass $discussion
     * @return bool
     */
    public function can_reply_to(stdClass $discussion): bool {
        return $this->can_reply() && $this->can_see_discussion($discussion)
            && ((int) $discussion->groupid === 0 || $this->can_post_in_group((int) $discussion->groupid));
    }

    // Groups.

    /**
     * Effective group mode (NOGROUPS, SEPARATEGROUPS or VISIBLEGROUPS).
     *
     * @return int
     */
    public function get_groupmode(): int {
        return (int) groups_get_activity_groupmode($this->cm, $this->course);
    }

    /**
     * Can the user see and post in every group?
     *
     * @param int|null $userid null for the current user
     * @return bool
     */
    public function can_access_all_groups(?int $userid = null): bool {
        return has_capability('moodle/site:accessallgroups', $this->context, $userid);
    }

    /**
     * Ids of the groups of a user (restricted to the grouping of the activity).
     *
     * @param int $userid
     * @return int[]
     */
    public function get_user_groupids(int $userid): array {
        if (!isset($this->groupcache[$userid])) {
            $groups = groups_get_all_groups($this->course->id, $userid, (int) $this->cm->groupingid, 'g.id');
            $this->groupcache[$userid] = array_map('intval', array_keys($groups));
        }
        return $this->groupcache[$userid];
    }

    /**
     * Groups of the activity (grouping applied), id => name.
     *
     * @return array<int, string>
     */
    public function get_all_groups(): array {
        $groups = groups_get_all_groups($this->course->id, 0, (int) $this->cm->groupingid, 'g.id, g.name');
        $result = [];
        foreach ($groups as $group) {
            $result[(int) $group->id] = format_string($group->name, true, ['context' => $this->context]);
        }
        return $result;
    }

    /**
     * Can the user see a discussion?
     *
     * In separate groups, users without "access all groups" only see the discussions
     * of their own groups and the ones addressed to all participants.
     *
     * @param stdClass $discussion
     * @param int|null $userid null for the current user
     * @return bool
     */
    public function can_see_discussion(stdClass $discussion, ?int $userid = null): bool {
        global $USER;
        $groupid = (int) ($discussion->groupid ?? 0);
        if ($groupid === 0 || $this->get_groupmode() !== SEPARATEGROUPS) {
            return true;
        }
        $userid = $userid ?? (int) $USER->id;
        return $this->can_access_all_groups($userid) || in_array($groupid, $this->get_user_groupids($userid), true);
    }

    /**
     * Throw when the current user cannot see the discussion.
     *
     * @param stdClass $discussion
     */
    public function require_can_see_discussion(stdClass $discussion): void {
        $this->require_discussion_in_forum($discussion);
        if (!$this->can_see_discussion($discussion)) {
            throw new \moodle_exception('discussionnotvisible', 'hiveforum');
        }
    }

    /**
     * Can the user post in a group (0 = all participants)?
     *
     * @param int $groupid
     * @param int|null $userid
     * @return bool
     */
    public function can_post_in_group(int $groupid, ?int $userid = null): bool {
        global $USER;
        if ($this->get_groupmode() === NOGROUPS) {
            return true;
        }
        $userid = $userid ?? (int) $USER->id;
        if ($this->can_access_all_groups($userid)) {
            return $groupid === 0 || array_key_exists($groupid, $this->get_all_groups());
        }
        return $groupid !== 0 && in_array($groupid, $this->get_user_groupids($userid), true);
    }

    /**
     * Groups in which the current user can start a discussion, id => name (0 = all participants).
     *
     * @return array<int, string>
     */
    public function get_post_targets(): array {
        global $USER;
        if ($this->get_groupmode() === NOGROUPS) {
            return [0 => ''];
        }
        $groups = $this->get_all_groups();
        if ($this->can_access_all_groups()) {
            return [0 => get_string('allparticipants')] + $groups;
        }
        $mine = array_flip($this->get_user_groupids((int) $USER->id));
        return array_intersect_key($groups, $mine);
    }

    /**
     * Resolve the active group of the current user.
     *
     * @param int|null $requested group asked for (web services); null reads/updates the session like the group menu
     * @return int 0 for all participants
     */
    public function resolve_active_group(?int $requested = null): int {
        global $USER;
        $mode = $this->get_groupmode();
        if ($mode === NOGROUPS) {
            return 0;
        }
        if ($requested !== null) {
            if ($this->can_access_all_groups() || $mode === VISIBLEGROUPS) {
                if ($requested === 0 || array_key_exists($requested, $this->get_all_groups())) {
                    return $requested;
                }
            } else if (in_array($requested, $this->get_user_groupids((int) $USER->id), true)) {
                return $requested;
            }
        }
        return (int) groups_get_activity_group($this->cm, $requested === null);
    }

    /**
     * SQL restricting discussions (alias d) to what a user may see, optionally to an active group.
     *
     * @param int $userid
     * @param int $activegroup 0 = every group the user can see
     * @param string $alias discussion table alias
     * @return array{0: string, 1: array}
     */
    public function group_sql(int $userid, int $activegroup = 0, string $alias = 'd'): array {
        global $DB;
        static $counter = 0;
        $counter++;
        $mode = $this->get_groupmode();
        if ($mode === NOGROUPS) {
            return ['1 = 1', []];
        }
        if ($activegroup > 0) {
            $param = 'hfactivegroup' . $counter;
            return ["($alias.groupid = :$param OR $alias.groupid = 0)", [$param => $activegroup]];
        }
        if ($mode === VISIBLEGROUPS || $this->can_access_all_groups($userid)) {
            return ['1 = 1', []];
        }
        $groups = $this->get_user_groupids($userid);
        if (!$groups) {
            return ["$alias.groupid = 0", []];
        }
        [$insql, $params] = $DB->get_in_or_equal($groups, SQL_PARAMS_NAMED, 'hfgrp' . $counter . '_');
        return ["($alias.groupid = 0 OR $alias.groupid $insql)", $params];
    }

    /**
     * Can the current user react to posts?
     *
     * @return bool
     */
    public function can_react(): bool {
        return !empty($this->instance->allowreactions)
            && $this->is_real_user()
            && has_capability('mod/hiveforum:react', $this->context);
    }

    /**
     * Can the current user moderate (edit/delete any post, resolve any question)?
     *
     * @return bool
     */
    public function can_moderate(): bool {
        return has_capability('mod/hiveforum:moderate', $this->context);
    }

    /**
     * Can the current user pin discussions?
     *
     * @return bool
     */
    public function can_pin(): bool {
        return has_capability('mod/hiveforum:pindiscussion', $this->context);
    }

    /**
     * Can the current user follow discussions?
     *
     * @return bool
     */
    public function can_follow(): bool {
        return $this->is_real_user() && $this->can_view();
    }

    /**
     * Can the current user edit this post?
     *
     * @param stdClass $post
     * @return bool
     */
    public function can_edit_post(stdClass $post): bool {
        global $USER;
        if ($this->can_moderate()) {
            return true;
        }
        return (int) $post->userid === (int) $USER->id
            && has_capability('mod/hiveforum:editownpost', $this->context);
    }

    /**
     * Can the current user delete this post?
     *
     * @param stdClass $post
     * @return bool
     */
    public function can_delete_post(stdClass $post): bool {
        global $USER;
        if ($this->can_moderate()) {
            return true;
        }
        return (int) $post->userid === (int) $USER->id
            && has_capability('mod/hiveforum:deleteownpost', $this->context);
    }

    /**
     * Can the current user mark the discussion as resolved / accept an answer?
     * The author of a question can, and so can moderators.
     *
     * @param stdClass $discussion
     * @return bool
     */
    public function can_resolve(stdClass $discussion): bool {
        global $USER;
        if ($this->can_moderate()) {
            return true;
        }
        return $this->is_real_user() && (int) $discussion->userid === (int) $USER->id;
    }

    /**
     * Throw if the check fails.
     *
     * @param bool $allowed
     * @param string $action language string identifier in mod_hiveforum describing the action
     */
    public function require(bool $allowed, string $action): void {
        if (!$allowed) {
            throw new \moodle_exception('nopermissions', 'error', '', get_string($action, 'hiveforum'));
        }
    }
}
