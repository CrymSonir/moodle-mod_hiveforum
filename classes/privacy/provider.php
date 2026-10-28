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

namespace mod_hiveforum\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_hiveforum.
 *
 * When a user's data is deleted, their posts are kept as empty placeholders so that the
 * replies of other people keep their place in the thread (same approach as mod_forum).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /** @var array<string, string> Per-user tables and their user column. */
    private const USER_TABLES = [
        'hiveforum_discussions' => 'userid',
        'hiveforum_posts' => 'userid',
        'hiveforum_reactions' => 'userid',
        'hiveforum_reads' => 'userid',
        'hiveforum_subscriptions' => 'userid',
        'hiveforum_streaks' => 'userid',
        'hiveforum_forum_subs' => 'userid',
        'hiveforum_digests' => 'userid',
        'hiveforum_mentions' => 'userid',
    ];

    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('hiveforum_discussions', [
            'userid' => 'privacy:metadata:hiveforum_discussions:userid',
            'name' => 'privacy:metadata:hiveforum_discussions:name',
            'usermodified' => 'privacy:metadata:hiveforum_discussions:usermodified',
            'timecreated' => 'privacy:metadata:hiveforum_discussions:timecreated',
            'timemodified' => 'privacy:metadata:hiveforum_discussions:timemodified',
        ], 'privacy:metadata:hiveforum_discussions');
        $collection->add_database_table('hiveforum_posts', [
            'userid' => 'privacy:metadata:hiveforum_posts:userid',
            'message' => 'privacy:metadata:hiveforum_posts:message',
            'timecreated' => 'privacy:metadata:hiveforum_posts:timecreated',
            'timemodified' => 'privacy:metadata:hiveforum_posts:timemodified',
        ], 'privacy:metadata:hiveforum_posts');
        $collection->add_database_table('hiveforum_reactions', [
            'userid' => 'privacy:metadata:hiveforum_reactions:userid',
            'reactiontype' => 'privacy:metadata:hiveforum_reactions:reactiontype',
            'timecreated' => 'privacy:metadata:hiveforum_reactions:timecreated',
        ], 'privacy:metadata:hiveforum_reactions');
        $collection->add_database_table('hiveforum_reads', [
            'userid' => 'privacy:metadata:hiveforum_reads:userid',
            'timeread' => 'privacy:metadata:hiveforum_reads:timeread',
        ], 'privacy:metadata:hiveforum_reads');
        $collection->add_database_table('hiveforum_subscriptions', [
            'userid' => 'privacy:metadata:hiveforum_subscriptions:userid',
            'state' => 'privacy:metadata:hiveforum_subscriptions:state',
            'timecreated' => 'privacy:metadata:hiveforum_subscriptions:timecreated',
        ], 'privacy:metadata:hiveforum_subscriptions');
        $collection->add_database_table('hiveforum_forum_subs', [
            'userid' => 'privacy:metadata:hiveforum_forum_subs:userid',
            'state' => 'privacy:metadata:hiveforum_forum_subs:state',
            'timecreated' => 'privacy:metadata:hiveforum_forum_subs:timecreated',
        ], 'privacy:metadata:hiveforum_forum_subs');
        $collection->add_database_table('hiveforum_digests', [
            'userid' => 'privacy:metadata:hiveforum_digests:userid',
            'digest' => 'privacy:metadata:hiveforum_digests:digest',
        ], 'privacy:metadata:hiveforum_digests');
        $collection->add_database_table('hiveforum_mentions', [
            'userid' => 'privacy:metadata:hiveforum_mentions:userid',
            'timecreated' => 'privacy:metadata:hiveforum_mentions:timecreated',
        ], 'privacy:metadata:hiveforum_mentions');
        $collection->add_database_table('hiveforum_streaks', [
            'userid' => 'privacy:metadata:hiveforum_streaks:userid',
            'currentstreak' => 'privacy:metadata:hiveforum_streaks:currentstreak',
            'beststreak' => 'privacy:metadata:hiveforum_streaks:beststreak',
            'lastactiveweek' => 'privacy:metadata:hiveforum_streaks:lastactiveweek',
        ], 'privacy:metadata:hiveforum_streaks');
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:core_message');
        return $collection;
    }

    /**
     * SQL fragment returning the forum id of each row of a per-user table (alias t).
     *
     * @param string $table
     * @return array{0: string, 1: string} [join, forum id expression]
     */
    private static function forum_join(string $table): array {
        return match ($table) {
            'hiveforum_discussions' => ['', 't.hiveforum'],
            'hiveforum_streaks', 'hiveforum_forum_subs', 'hiveforum_digests' => ['', 't.hiveforum'],
            'hiveforum_posts', 'hiveforum_reads', 'hiveforum_subscriptions' =>
                ['JOIN {hiveforum_discussions} jd ON jd.id = t.discussion', 'jd.hiveforum'],
            'hiveforum_reactions', 'hiveforum_mentions' => [
                'JOIN {hiveforum_posts} jp ON jp.id = t.postid JOIN {hiveforum_discussions} jd ON jd.id = jp.discussion',
                'jd.hiveforum',
            ],
        };
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        foreach (self::USER_TABLES as $table => $column) {
            [$join, $forumid] = self::forum_join($table);
            $sql = "SELECT ctx.id
                      FROM {{$table}} t
                           $join
                      JOIN {course_modules} cm ON cm.instance = $forumid
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                      JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                     WHERE t.$column = :userid";
            $contextlist->add_from_sql($sql, [
                'modname' => 'hiveforum', 'contextlevel' => CONTEXT_MODULE, 'userid' => $userid,
            ]);
        }
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('hiveforum', $context->instanceid);
        if (!$cm) {
            return;
        }
        foreach (self::USER_TABLES as $table => $column) {
            [$join, $forumid] = self::forum_join($table);
            $userlist->add_from_sql(
                $column,
                "SELECT t.$column FROM {{$table}} t $join WHERE $forumid = :forumid",
                ['forumid' => $cm->instance]
            );
        }
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('hiveforum', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $forumid = (int) $cm->instance;
            $contextdata = helper::get_context_data($context, $contextlist->get_user());
            helper::export_context_files($context, $contextlist->get_user());
            writer::with_context($context)->export_data([], $contextdata);

            $discussions = $DB->get_records('hiveforum_discussions', ['hiveforum' => $forumid]);
            foreach ($discussions as $discussion) {
                $subcontext = [get_string('discussions', 'hiveforum'), $discussion->id . ' - ' . $discussion->name];
                $posts = $DB->get_records('hiveforum_posts', ['discussion' => $discussion->id, 'userid' => $userid], 'id');
                $reactions = $DB->get_records_sql(
                    "SELECT r.* FROM {hiveforum_reactions} r JOIN {hiveforum_posts} p ON p.id = r.postid
                      WHERE p.discussion = ? AND r.userid = ?",
                    [$discussion->id, $userid]
                );
                $read = $DB->get_record('hiveforum_reads', ['discussion' => $discussion->id, 'userid' => $userid]);
                $follow = $DB->get_record('hiveforum_subscriptions', ['discussion' => $discussion->id, 'userid' => $userid]);

                if ((int) $discussion->userid !== $userid && !$posts && !$reactions && !$read && !$follow) {
                    continue;
                }

                $data = (object) [
                    'name' => format_string($discussion->name, true, ['context' => $context]),
                    'startedbyyou' => transform::yesno((int) $discussion->userid === $userid),
                    'timecreated' => transform::datetime($discussion->timecreated),
                    'following' => $follow ? transform::yesno((bool) $follow->state) : null,
                    'lastread' => $read ? transform::datetime($read->timeread) : null,
                    'reactions' => array_values(array_map(fn($r) => [
                        'post' => $r->postid,
                        'reaction' => $r->reactiontype,
                        'timecreated' => transform::datetime($r->timecreated),
                    ], $reactions)),
                ];
                writer::with_context($context)->export_data($subcontext, $data);

                foreach ($posts as $post) {
                    $postcontext = array_merge($subcontext, [get_string('privacy:post', 'hiveforum', $post->id)]);
                    $message = writer::with_context($context)->rewrite_pluginfile_urls(
                        $postcontext,
                        'mod_hiveforum',
                        'post',
                        $post->id,
                        $post->message
                    );
                    writer::with_context($context)
                        ->export_data($postcontext, (object) [
                            'message' => format_text($message, $post->messageformat, ['context' => $context]),
                            'acceptedanswer' => transform::yesno(!empty($post->isaccepted)),
                            'timecreated' => transform::datetime($post->timecreated),
                            'timemodified' => transform::datetime($post->timemodified),
                        ])
                        ->export_area_files($postcontext, 'mod_hiveforum', 'post', $post->id);
                }
            }

            $streak = $DB->get_record('hiveforum_streaks', ['hiveforum' => $forumid, 'userid' => $userid]);
            if ($streak) {
                writer::with_context($context)->export_data([get_string('privacy:streak', 'hiveforum')], (object) [
                    'currentstreak' => $streak->currentstreak,
                    'beststreak' => $streak->beststreak,
                ]);
            }

            // Notification preferences and mentions.
            $forumsub = $DB->get_record('hiveforum_forum_subs', ['hiveforum' => $forumid, 'userid' => $userid]);
            $digest = $DB->record_exists('hiveforum_digests', ['hiveforum' => $forumid, 'userid' => $userid, 'digest' => 1]);
            $mentions = $DB->get_records_sql(
                "SELECT m.id, m.postid, m.timecreated
                   FROM {hiveforum_mentions} m
                   JOIN {hiveforum_posts} p ON p.id = m.postid
                   JOIN {hiveforum_discussions} d ON d.id = p.discussion
                  WHERE d.hiveforum = ? AND m.userid = ?",
                [$forumid, $userid]
            );
            if ($forumsub || $digest || $mentions) {
                writer::with_context($context)->export_data([get_string('privacy:preferences', 'hiveforum')], (object) [
                    'forumsubscription' => $forumsub ? transform::yesno((bool) $forumsub->state) : null,
                    'dailydigest' => transform::yesno($digest),
                    'mentionedin' => array_values(array_map(fn($m) => [
                        'post' => $m->postid,
                        'timecreated' => transform::datetime($m->timecreated),
                    ], $mentions)),
                ]);
            }
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('hiveforum', $context->instanceid);
        if (!$cm) {
            return;
        }
        $ids = $DB->get_fieldset_select('hiveforum_discussions', 'id', 'hiveforum = ?', [$cm->instance]);
        foreach (array_chunk($ids, 500) as $chunk) {
            \mod_hiveforum\local\api::delete_discussion_data($chunk);
        }
        foreach (['hiveforum_streaks', 'hiveforum_forum_subs', 'hiveforum_digests'] as $table) {
            $DB->delete_records($table, ['hiveforum' => $cm->instance]);
        }
        get_file_storage()->delete_area_files($context->id, 'mod_hiveforum', 'post');
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            self::delete_users_in_context($context, [$userid]);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist) {
        self::delete_users_in_context($userlist->get_context(), $userlist->get_userids());
    }

    /**
     * Delete (or wipe) the data of some users in one context.
     *
     * @param \context $context
     * @param int[] $userids
     */
    private static function delete_users_in_context(\context $context, array $userids): void {
        global $DB;
        if (!$context instanceof \context_module || !$userids) {
            return;
        }
        $cm = get_coursemodule_from_id('hiveforum', $context->instanceid);
        if (!$cm) {
            return;
        }
        $forumid = (int) $cm->instance;
        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = $userparams + ['forumid' => $forumid];
        $indiscussion = "discussion IN (SELECT id FROM {hiveforum_discussions} WHERE hiveforum = :forumid)";

        $DB->delete_records_select(
            'hiveforum_reactions',
            "userid $usersql AND postid IN (SELECT p.id FROM {hiveforum_posts} p
                JOIN {hiveforum_discussions} d ON d.id = p.discussion WHERE d.hiveforum = :forumid)",
            $params
        );
        $DB->delete_records_select('hiveforum_reads', "userid $usersql AND $indiscussion", $params);
        $DB->delete_records_select('hiveforum_subscriptions', "userid $usersql AND $indiscussion", $params);
        $DB->delete_records_select('hiveforum_streaks', "userid $usersql AND hiveforum = :forumid", $params);
        $DB->delete_records_select('hiveforum_forum_subs', "userid $usersql AND hiveforum = :forumid", $params);
        $DB->delete_records_select('hiveforum_digests', "userid $usersql AND hiveforum = :forumid", $params);
        $DB->delete_records_select(
            'hiveforum_mentions',
            "userid $usersql AND postid IN (SELECT p.id FROM {hiveforum_posts} p
                JOIN {hiveforum_discussions} d ON d.id = p.discussion WHERE d.hiveforum = :forumid)",
            $params
        );

        // Wipe the content of posts but keep them so the thread structure stays intact.
        $postids = $DB->get_fieldset_select('hiveforum_posts', 'id', "userid $usersql AND $indiscussion", $params);
        $fs = get_file_storage();
        foreach ($postids as $postid) {
            $fs->delete_area_files($context->id, 'mod_hiveforum', 'post', $postid);
        }
        $DB->set_field_select('hiveforum_posts', 'message', '', "userid $usersql AND $indiscussion", $params);
        $DB->set_field_select('hiveforum_posts', 'messageformat', FORMAT_PLAIN, "userid $usersql AND $indiscussion", $params);
        $DB->set_field_select('hiveforum_posts', 'timemodified', time(), "userid $usersql AND $indiscussion", $params);
        $DB->set_field_select('hiveforum_discussions', 'name', '', "userid $usersql AND hiveforum = :forumid", $params);
    }
}
