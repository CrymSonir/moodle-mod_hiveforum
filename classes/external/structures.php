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

namespace mod_hiveforum\external;

use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Return structures shared by the hiveforum web services.
 *
 * They mirror the arrays produced by {@see \mod_hiveforum\local\exporter}.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class structures {
    /**
     * A user.
     *
     * @param int $required VALUE_REQUIRED or VALUE_OPTIONAL
     * @return external_single_structure
     */
    public static function user(int $required = VALUE_REQUIRED): external_single_structure {
        $allownull = $required === VALUE_OPTIONAL ? NULL_ALLOWED : NULL_NOT_ALLOWED;
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'User id'),
            'fullname' => new external_value(PARAM_TEXT, 'Full name'),
            'initials' => new external_value(PARAM_TEXT, 'Initials'),
            'avatarurl' => new external_value(PARAM_URL, 'Profile picture URL, empty when the user has none'),
            'profileurl' => new external_value(PARAM_URL, 'Profile URL'),
            'isstaff' => new external_value(PARAM_BOOL, 'Holds the moderate capability'),
            'streak' => new external_value(PARAM_INT, 'Current weekly contribution streak'),
        ], 'User', $required, null, $allownull);
    }

    /**
     * Reaction counters.
     *
     * @return external_multiple_structure
     */
    public static function reactions(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'type' => new external_value(PARAM_ALPHA, 'Reaction type'),
            'count' => new external_value(PARAM_INT, 'Count'),
            'active' => new external_value(PARAM_BOOL, 'Current user reacted'),
        ]));
    }

    /**
     * A discussion in the list.
     *
     * @return external_single_structure
     */
    public static function discussion_summary(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Discussion id'),
            'name' => new external_value(PARAM_RAW, 'Formatted name'),
            'excerpt' => new external_value(PARAM_RAW, 'Plain text excerpt of the opening post'),
            'author' => self::user(),
            'lastauthor' => self::user(VALUE_OPTIONAL),
            'participants' => new external_multiple_structure(self::user()),
            'isquestion' => new external_value(PARAM_BOOL, 'Is a question'),
            'pinned' => new external_value(PARAM_BOOL, 'Pinned'),
            'resolved' => new external_value(PARAM_BOOL, 'Resolved'),
            'following' => new external_value(PARAM_BOOL, 'Followed by the current user'),
            'unread' => new external_value(PARAM_BOOL, 'Has unread activity'),
            'replycount' => new external_value(PARAM_INT, 'Number of replies'),
            'reactions' => self::reactions(),
            'tags' => new external_multiple_structure(new external_value(PARAM_TAG, 'Tag')),
            'groupname' => new external_value(PARAM_TEXT, 'Group name, empty for all participants'),
            'timecreated' => new external_value(PARAM_INT, 'Time created'),
            'timemodified' => new external_value(PARAM_INT, 'Time of last activity'),
            'url' => new external_value(PARAM_URL, 'Discussion URL'),
        ]);
    }

    /**
     * A discussion header in the thread view.
     *
     * @return external_single_structure
     */
    public static function discussion(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Discussion id'),
            'name' => new external_value(PARAM_RAW, 'Formatted name'),
            'rawname' => new external_value(PARAM_TEXT, 'Raw name for editing'),
            'author' => self::user(),
            'isquestion' => new external_value(PARAM_BOOL, 'Is a question'),
            'pinned' => new external_value(PARAM_BOOL, 'Pinned'),
            'resolved' => new external_value(PARAM_BOOL, 'Resolved'),
            'following' => new external_value(PARAM_BOOL, 'Followed by the current user'),
            'tags' => new external_multiple_structure(new external_value(PARAM_TAG, 'Tag')),
            'firstpostid' => new external_value(PARAM_INT, 'Opening post id'),
            'timecreated' => new external_value(PARAM_INT, 'Time created'),
            'timemodified' => new external_value(PARAM_INT, 'Time of last activity'),
            'canpin' => new external_value(PARAM_BOOL, 'Can pin'),
            'canresolve' => new external_value(PARAM_BOOL, 'Can resolve / accept answers'),
            'canfollow' => new external_value(PARAM_BOOL, 'Can follow / unfollow'),
            'followforced' => new external_value(PARAM_BOOL, 'Subscription is forced'),
            'groupname' => new external_value(PARAM_TEXT, 'Group name, empty for all participants'),
            'canreply' => new external_value(PARAM_BOOL, 'Can reply to this discussion'),
            'canrename' => new external_value(PARAM_BOOL, 'Can rename'),
        ]);
    }

    /**
     * A post.
     *
     * @return external_single_structure
     */
    public static function post(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Post id'),
            'parentid' => new external_value(PARAM_INT, 'Parent post id, 0 for the opening post'),
            'author' => self::user(),
            'message' => new external_value(PARAM_RAW, 'Formatted message (HTML)'),
            'rawmessage' => new external_value(PARAM_RAW, 'Raw markdown, only when the user can edit inline'),
            'messageformat' => new external_value(PARAM_INT, 'Message format'),
            'isfirst' => new external_value(PARAM_BOOL, 'Opening post'),
            'isaccepted' => new external_value(PARAM_BOOL, 'Accepted answer'),
            'edited' => new external_value(PARAM_BOOL, 'Edited after creation'),
            'timecreated' => new external_value(PARAM_INT, 'Time created'),
            'timemodified' => new external_value(PARAM_INT, 'Time modified'),
            'reactions' => self::reactions(),
            'canedit' => new external_value(PARAM_BOOL, 'Can edit'),
            'candelete' => new external_value(PARAM_BOOL, 'Can delete'),
            'canaccept' => new external_value(PARAM_BOOL, 'Can accept as answer'),
            'editurl' => new external_value(PARAM_URL, 'Full editor URL'),
        ]);
    }

    /**
     * Filter counters.
     *
     * @return external_single_structure
     */
    public static function counts(): external_single_structure {
        return new external_single_structure([
            'all' => new external_value(PARAM_INT, 'All'),
            'unread' => new external_value(PARAM_INT, 'Unread'),
            'following' => new external_value(PARAM_INT, 'Following'),
            'questions' => new external_value(PARAM_INT, 'Open questions'),
            'unanswered' => new external_value(PARAM_INT, 'Without reply'),
            'mine' => new external_value(PARAM_INT, 'Started by me'),
        ]);
    }

    /**
     * Notification preferences.
     *
     * @return external_single_structure
     */
    public static function preferences(): external_single_structure {
        return new external_single_structure([
            'subscriptionmode' => new external_value(PARAM_INT, '0 optional, 1 forced, 2 auto, 3 disabled'),
            'forumsubscribed' => new external_value(PARAM_BOOL, 'Subscribed to the whole forum'),
            'canchange' => new external_value(PARAM_BOOL, 'Can change the forum subscription'),
            'digest' => new external_value(PARAM_BOOL, 'Daily digest instead of one notification per post'),
            'notificationsenabled' => new external_value(PARAM_BOOL, 'Notifications are sent'),
        ]);
    }
}
