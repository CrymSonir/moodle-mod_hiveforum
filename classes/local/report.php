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

/**
 * Engagement report: per-participant activity, key figures and weekly activity.
 *
 * Every figure is computed with a handful of aggregate queries, whatever the number of participants.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class report {
    /** @var int[] Allowed periods, in days (0 = since the beginning). */
    public const PERIODS = [7, 30, 90, 0];

    /**
     * Constructor.
     *
     * @param forum $forum
     * @param int $groupid participants of this group only (0 = everybody)
     * @param int $days period in days (0 = everything)
     */
    public function __construct(
        /** @var forum */
        private readonly forum $forum,
        /** @var int */
        private readonly int $groupid = 0,
        /** @var int */
        private readonly int $days = 30,
    ) {
    }

    /**
     * Start of the period (timestamp).
     *
     * @return int
     */
    public function get_since(): int {
        return $this->days ? \core\di::get(\core\clock::class)->time() - $this->days * DAYSECS : 0;
    }

    /**
     * Participants (enrolled users able to view the forum), in the group if any.
     *
     * @return \stdClass[] id => user (name fields)
     */
    public function get_participants(): array {
        $fields = 'u.id, ' . implode(', ', array_map(fn($f) => "u.$f", \core_user\fields::get_name_fields()))
            . ', u.picture, u.imagealt, u.email';
        return get_enrolled_users($this->forum->context, 'mod/hiveforum:view', $this->groupid, $fields, 'u.lastname, u.firstname');
    }

    /**
     * Activity of each participant (inactive participants included, with zeros).
     *
     * @return array<int, array> user id => figures
     */
    public function get_rows(): array {
        global $DB;
        $participants = $this->get_participants();
        if (!$participants) {
            return [];
        }
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($participants), SQL_PARAMS_NAMED, 'u');
        $params = $inparams + ['forumid' => $this->forum->instance->id, 'since' => $this->get_since()];

        $posts = $DB->get_records_sql(
            "SELECT p.userid,
                    SUM(CASE WHEN p.parent = 0 THEN 1 ELSE 0 END) AS discussions,
                    SUM(CASE WHEN p.parent <> 0 THEN 1 ELSE 0 END) AS replies,
                    SUM(CASE WHEN p.isaccepted = 1 THEN 1 ELSE 0 END) AS accepted,
                    MAX(p.timecreated) AS lastpost
               FROM {hiveforum_posts} p
               JOIN {hiveforum_discussions} d ON d.id = p.discussion
              WHERE d.hiveforum = :forumid AND p.timecreated >= :since AND p.userid $insql
           GROUP BY p.userid",
            $params
        );
        $given = $DB->get_records_sql_menu(
            "SELECT r.userid, COUNT(1)
               FROM {hiveforum_reactions} r
               JOIN {hiveforum_posts} p ON p.id = r.postid
               JOIN {hiveforum_discussions} d ON d.id = p.discussion
              WHERE d.hiveforum = :forumid AND r.timecreated >= :since AND r.userid $insql
           GROUP BY r.userid",
            $params
        );
        $received = $DB->get_records_sql_menu(
            "SELECT p.userid, COUNT(1)
               FROM {hiveforum_reactions} r
               JOIN {hiveforum_posts} p ON p.id = r.postid
               JOIN {hiveforum_discussions} d ON d.id = p.discussion
              WHERE d.hiveforum = :forumid AND r.timecreated >= :since AND r.userid <> p.userid AND p.userid $insql
           GROUP BY p.userid",
            $params
        );
        $streaks = $DB->get_records_select(
            'hiveforum_streaks',
            "hiveforum = :forumid AND userid $insql",
            $inparams + ['forumid' => $this->forum->instance->id],
            '',
            'userid, currentstreak, beststreak, lastactiveweek'
        );
        $week = api::current_week();

        $rows = [];
        foreach ($participants as $userid => $user) {
            $p = $posts[$userid] ?? null;
            $s = $streaks[$userid] ?? null;
            $rows[$userid] = [
                'user' => $user,
                'fullname' => fullname($user),
                'discussions' => (int) ($p->discussions ?? 0),
                'replies' => (int) ($p->replies ?? 0),
                'posts' => (int) ($p->discussions ?? 0) + (int) ($p->replies ?? 0),
                'reactionsgiven' => (int) ($given[$userid] ?? 0),
                'reactionsreceived' => (int) ($received[$userid] ?? 0),
                'accepted' => (int) ($p->accepted ?? 0),
                'lastpost' => (int) ($p->lastpost ?? 0),
                'streak' => $s && (int) $s->lastactiveweek >= $week - 1 ? (int) $s->currentstreak : 0,
                'beststreak' => (int) ($s->beststreak ?? 0),
            ];
        }
        return $rows;
    }

    /**
     * Key figures for the period.
     *
     * @param array $rows result of get_rows()
     * @return array
     */
    public function get_summary(array $rows): array {
        global $DB;
        [$groupsql, $params] = $this->groupid ? $this->forum->group_sql(0, $this->groupid) : ['1 = 1', []];
        $params += ['forumid' => $this->forum->instance->id, 'since' => $this->get_since()];
        $questions = $DB->get_record_sql(
            "SELECT COUNT(1) AS total, SUM(CASE WHEN d.resolved = 1 THEN 1 ELSE 0 END) AS resolved
               FROM {hiveforum_discussions} d
              WHERE d.hiveforum = :forumid AND d.posttype = 'question' AND d.timecreated >= :since AND $groupsql",
            $params
        );
        $active = count(array_filter($rows, fn($r) => $r['posts'] > 0));
        return [
            'discussions' => array_sum(array_column($rows, 'discussions')),
            'replies' => array_sum(array_column($rows, 'replies')),
            'reactions' => array_sum(array_column($rows, 'reactionsgiven')),
            'active' => $active,
            'participants' => count($rows),
            'questions' => (int) $questions->total,
            'resolved' => (int) $questions->resolved,
        ];
    }

    /**
     * Posts per week over the last weeks (participants of the report only).
     *
     * @param int[] $userids
     * @param int $weeks
     * @return array<int, int> week start timestamp => posts
     */
    public function get_weekly_activity(array $userids, int $weeks = 12): array {
        global $DB;
        $now = \core\di::get(\core\clock::class)->time();
        $start = usergetmidnight($now) - ($weeks - 1) * WEEKSECS - 6 * DAYSECS;
        $result = [];
        for ($i = 0; $i < $weeks; $i++) {
            $result[$start + $i * WEEKSECS] = 0;
        }
        if (!$userids) {
            return $result;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
        $times = $DB->get_fieldset_sql(
            "SELECT p.timecreated
               FROM {hiveforum_posts} p
               JOIN {hiveforum_discussions} d ON d.id = p.discussion
              WHERE d.hiveforum = :forumid AND p.timecreated >= :start AND p.userid $insql",
            $params + ['forumid' => $this->forum->instance->id, 'start' => $start]
        );
        foreach ($times as $time) {
            $index = (int) floor(((int) $time - $start) / WEEKSECS);
            if ($index >= 0 && $index < $weeks) {
                $result[$start + $index * WEEKSECS]++;
            }
        }
        return $result;
    }
}
