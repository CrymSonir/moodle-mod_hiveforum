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

declare(strict_types=1);

namespace mod_hiveforum\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules: discussions started, replies, posts, accepted answers.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * State of a rule for the user.
     *
     * @param string $rule
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE
     */
    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);

        $forumid = (int) $this->cm->instance;
        $required = (int) $DB->get_field('hiveforum', $rule, ['id' => $forumid], MUST_EXIST);
        $params = ['userid' => $this->userid, 'forumid' => $forumid];
        $base = "SELECT COUNT(1)
                   FROM {hiveforum_posts} p
                   JOIN {hiveforum_discussions} d ON d.id = p.discussion
                  WHERE p.userid = :userid AND d.hiveforum = :forumid";

        $count = match ($rule) {
            'completiondiscussions' => $DB->count_records(
                'hiveforum_discussions',
                ['hiveforum' => $forumid, 'userid' => $this->userid]
            ),
            'completionreplies' => $DB->count_records_sql($base . ' AND p.parent <> 0', $params),
            'completionposts' => $DB->count_records_sql($base, $params),
            'completionaccepted' => $DB->count_records_sql($base . ' AND p.isaccepted = 1', $params),
        };

        return $count >= $required ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Rules defined by the activity.
     *
     * @return string[]
     */
    public static function get_defined_custom_rules(): array {
        return ['completiondiscussions', 'completionreplies', 'completionposts', 'completionaccepted'];
    }

    /**
     * Descriptions of the active rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $rules = $this->cm->customdata['customcompletionrules'] ?? [];
        $descriptions = [];
        foreach (self::get_defined_custom_rules() as $rule) {
            $descriptions[$rule] = get_string('completiondetail:' . substr($rule, 10), 'hiveforum', (int) ($rules[$rule] ?? 0));
        }
        return $descriptions;
    }

    /**
     * Display order.
     *
     * @return string[]
     */
    public function get_sort_order(): array {
        return ['completionview', 'completiondiscussions', 'completionreplies', 'completionposts', 'completionaccepted'];
    }
}
