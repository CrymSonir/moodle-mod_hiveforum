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

use stdClass;

/**
 * Subscriptions, modelled on mod_forum.
 *
 * Two levels:
 *  - forum subscription: the user follows every discussion (table hiveforum_forum_subs);
 *  - discussion subscription: follow / stop following one discussion (table hiveforum_subscriptions),
 *    which overrides the forum subscription for that discussion.
 *
 * Four modes (setting "subscriptionmode"):
 *  - OPTIONAL: nobody is subscribed to the forum until they choose to;
 *  - FORCED: everyone is subscribed and cannot unsubscribe;
 *  - AUTO: everyone is subscribed but can unsubscribe;
 *  - DISABLED: no subscription at all.
 *
 * A forum_subs row stores an explicit choice (state 1 / 0) that overrides the default of the mode.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class subscriptions {
    /** @var int Users choose to subscribe. */
    public const OPTIONAL = 0;
    /** @var int Everybody is subscribed, no way out. */
    public const FORCED = 1;
    /** @var int Everybody is subscribed by default, can unsubscribe. */
    public const AUTO = 2;
    /** @var int No subscriptions. */
    public const DISABLED = 3;

    /**
     * Subscription mode of a forum.
     *
     * @param forum $forum
     * @return int
     */
    public static function get_mode(forum $forum): int {
        return (int) ($forum->instance->subscriptionmode ?? self::OPTIONAL);
    }

    /**
     * Are notifications sent at all for this forum (site switch and activity setting)?
     *
     * @param forum $forum
     * @return bool
     */
    public static function notifications_enabled(forum $forum): bool {
        return (bool) get_config('mod_hiveforum', 'enablenotifications') && !empty($forum->instance->notifications);
    }

    /**
     * Can users change their own forum subscription?
     *
     * @param forum $forum
     * @return bool
     */
    public static function is_forum_subscription_optional(forum $forum): bool {
        return in_array(self::get_mode($forum), [self::OPTIONAL, self::AUTO], true);
    }

    /**
     * Can users follow / unfollow individual discussions?
     *
     * @param forum $forum
     * @return bool
     */
    public static function can_change_discussion_subscription(forum $forum): bool {
        return in_array(self::get_mode($forum), [self::OPTIONAL, self::AUTO], true);
    }

    /**
     * Is the user subscribed to the whole forum?
     *
     * @param forum $forum
     * @param int $userid
     * @return bool
     */
    public static function is_forum_subscribed(forum $forum, int $userid): bool {
        global $DB;
        $mode = self::get_mode($forum);
        if ($mode === self::DISABLED) {
            return false;
        }
        if ($mode === self::FORCED) {
            return true;
        }
        $state = $DB->get_field('hiveforum_forum_subs', 'state', ['hiveforum' => $forum->instance->id, 'userid' => $userid]);
        if ($state !== false) {
            return (bool) $state;
        }
        return $mode === self::AUTO;
    }

    /**
     * Subscribe / unsubscribe a user to the whole forum.
     *
     * Like mod_forum, changing the forum subscription clears the per-discussion choices
     * that contradict it.
     *
     * @param forum $forum
     * @param int $userid
     * @param bool $subscribed
     */
    public static function set_forum_subscription(forum $forum, int $userid, bool $subscribed): void {
        global $DB;
        if (!self::is_forum_subscription_optional($forum)) {
            throw new \moodle_exception('subscriptionlocked', 'hiveforum');
        }
        $params = ['hiveforum' => $forum->instance->id, 'userid' => $userid];
        $existing = $DB->get_record('hiveforum_forum_subs', $params);
        if ($existing) {
            $DB->set_field('hiveforum_forum_subs', 'state', (int) $subscribed, ['id' => $existing->id]);
        } else {
            $DB->insert_record('hiveforum_forum_subs', $params + ['state' => (int) $subscribed, 'timecreated' => time()]);
        }
        // Remove discussion-level choices that now duplicate or contradict the forum choice.
        $DB->delete_records_select(
            'hiveforum_subscriptions',
            'userid = :userid AND state = :state AND discussion IN (SELECT id FROM {hiveforum_discussions} WHERE hiveforum = :forumid)',
            ['userid' => $userid, 'state' => $subscribed ? 0 : 1, 'forumid' => $forum->instance->id]
        );
    }

    /**
     * Does the user follow a discussion?
     *
     * @param forum $forum
     * @param int $discussionid
     * @param int $userid
     * @return bool
     */
    public static function is_following(forum $forum, int $discussionid, int $userid): bool {
        global $DB;
        $mode = self::get_mode($forum);
        if ($mode === self::DISABLED) {
            return false;
        }
        if ($mode === self::FORCED) {
            return true;
        }
        $state = $DB->get_field('hiveforum_subscriptions', 'state', ['discussion' => $discussionid, 'userid' => $userid]);
        if ($state !== false) {
            return (bool) $state;
        }
        return self::is_forum_subscribed($forum, $userid);
    }

    /**
     * Follow / stop following a discussion. No-op when the mode does not allow choices.
     *
     * @param forum $forum
     * @param int $discussionid
     * @param int $userid
     * @param bool $follow
     */
    public static function set_following(forum $forum, int $discussionid, int $userid, bool $follow): void {
        global $DB;
        if (!self::can_change_discussion_subscription($forum)) {
            return;
        }
        $params = ['discussion' => $discussionid, 'userid' => $userid];
        $existing = $DB->get_record('hiveforum_subscriptions', $params);
        if ($existing) {
            if ((int) $existing->state !== (int) $follow) {
                $DB->set_field('hiveforum_subscriptions', 'state', (int) $follow, ['id' => $existing->id]);
            }
        } else {
            $DB->insert_record('hiveforum_subscriptions', $params + ['state' => (int) $follow, 'timecreated' => time()]);
        }
    }

    /**
     * SQL condition "the user follows the discussion", for a query that LEFT JOINs
     * hiveforum_subscriptions as $alias for that user.
     *
     * @param forum $forum
     * @param int $userid
     * @param string $alias
     * @return array{0: string, 1: array}
     */
    public static function following_sql(forum $forum, int $userid, string $alias = 's'): array {
        static $counter = 0;
        $counter++;
        $mode = self::get_mode($forum);
        if ($mode === self::DISABLED) {
            return ['1 = 0', []];
        }
        if ($mode === self::FORCED) {
            return ['1 = 1', []];
        }
        $param = 'hffsub' . $counter;
        return [
            "($alias.state = 1 OR ($alias.id IS NULL AND 1 = :$param))",
            [$param => self::is_forum_subscribed($forum, $userid) ? 1 : 0],
        ];
    }

    /**
     * Does the user prefer a daily digest?
     *
     * @param forum $forum
     * @param int $userid
     * @return bool
     */
    public static function get_digest(forum $forum, int $userid): bool {
        global $DB;
        return (bool) $DB->get_field('hiveforum_digests', 'digest', ['hiveforum' => $forum->instance->id, 'userid' => $userid]);
    }

    /**
     * Set the digest preference.
     *
     * @param forum $forum
     * @param int $userid
     * @param bool $digest
     */
    public static function set_digest(forum $forum, int $userid, bool $digest): void {
        global $DB;
        $params = ['hiveforum' => $forum->instance->id, 'userid' => $userid];
        if ($digest) {
            if (!$DB->record_exists('hiveforum_digests', $params)) {
                $DB->insert_record('hiveforum_digests', $params + ['digest' => 1]);
            }
        } else {
            $DB->delete_records('hiveforum_digests', $params);
        }
    }

    /**
     * Ids of the users who may read the forum (active enrolment + view capability).
     *
     * @param forum $forum
     * @param int $groupid restrict to a group (0 = everybody)
     * @return int[]
     */
    public static function get_potential_subscribers(forum $forum, int $groupid = 0): array {
        $users = get_enrolled_users($forum->context, 'mod/hiveforum:view', $groupid, 'u.id', null, 0, 0, true);
        return array_map('intval', array_keys($users));
    }

    /**
     * Users following a discussion and allowed to see it (recipients of notifications).
     *
     * @param forum $forum
     * @param stdClass $discussion
     * @return int[]
     */
    public static function get_followers(forum $forum, stdClass $discussion): array {
        global $DB;
        $mode = self::get_mode($forum);
        if ($mode === self::DISABLED) {
            return [];
        }
        $potential = array_flip(self::get_potential_subscribers($forum));

        if ($mode === self::FORCED) {
            $followers = array_keys($potential);
        } else {
            $forumstates = $DB->get_records_menu('hiveforum_forum_subs', ['hiveforum' => $forum->instance->id], '', 'userid, state');
            $discstates = $DB->get_records_menu('hiveforum_subscriptions', ['discussion' => $discussion->id], '', 'userid, state');
            $followers = [];
            $candidates = $mode === self::AUTO ? array_keys($potential) : array_keys($forumstates);
            foreach (array_unique(array_merge($candidates, array_keys($discstates))) as $userid) {
                if (isset($discstates[$userid])) {
                    $follows = (bool) $discstates[$userid];
                } else if (isset($forumstates[$userid])) {
                    $follows = (bool) $forumstates[$userid];
                } else {
                    $follows = $mode === self::AUTO;
                }
                if ($follows) {
                    $followers[] = (int) $userid;
                }
            }
        }

        return array_values(array_filter(
            $followers,
            fn($userid) => isset($potential[$userid]) && $forum->can_see_discussion($discussion, (int) $userid)
        ));
    }

    /**
     * Forum subscription status of several users, for the management page.
     *
     * @param forum $forum
     * @param int[] $userids
     * @return array<int, bool>
     */
    public static function get_forum_subscription_states(forum $forum, array $userids): array {
        global $DB;
        $mode = self::get_mode($forum);
        $result = [];
        $states = $DB->get_records_menu('hiveforum_forum_subs', ['hiveforum' => $forum->instance->id], '', 'userid, state');
        foreach ($userids as $userid) {
            $result[$userid] = match ($mode) {
                self::FORCED => true,
                self::DISABLED => false,
                default => isset($states[$userid]) ? (bool) $states[$userid] : $mode === self::AUTO,
            };
        }
        return $result;
    }
}
