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
     * Can the current user start a discussion?
     *
     * @return bool
     */
    public function can_start_discussion(): bool {
        return $this->is_real_user() && has_capability('mod/hiveforum:startdiscussion', $this->context);
    }

    /**
     * Can the current user reply?
     *
     * @return bool
     */
    public function can_reply(): bool {
        return $this->is_real_user() && has_capability('mod/hiveforum:reply', $this->context);
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
