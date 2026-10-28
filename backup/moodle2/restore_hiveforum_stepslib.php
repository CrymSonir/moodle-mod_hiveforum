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

/**
 * Restore structure for mod_hiveforum.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore structure step.
 */
class restore_hiveforum_activity_structure_step extends restore_activity_structure_step {
    /**
     * Paths to process.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $paths = [new restore_path_element('hiveforum', '/activity/hiveforum')];
        if ($userinfo) {
            $base = '/activity/hiveforum/discussions/discussion';
            $paths[] = new restore_path_element('hiveforum_discussion', $base);
            $paths[] = new restore_path_element('hiveforum_tag', $base . '/tags/tag');
            $paths[] = new restore_path_element('hiveforum_post', $base . '/posts/post');
            $paths[] = new restore_path_element('hiveforum_reaction', $base . '/posts/post/reactions/reaction');
            $paths[] = new restore_path_element('hiveforum_read', $base . '/reads/read');
            $paths[] = new restore_path_element('hiveforum_subscription', $base . '/subscriptions/subscription');
            $paths[] = new restore_path_element('hiveforum_streak', '/activity/hiveforum/streaks/streak');
            $paths[] = new restore_path_element('hiveforum_mention', $base . '/posts/post/mentions/mention');
            $paths[] = new restore_path_element('hiveforum_forumsub', '/activity/hiveforum/forumsubs/forumsub');
            $paths[] = new restore_path_element('hiveforum_digest', '/activity/hiveforum/digests/digest');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Activity instance.
     *
     * @param array $data
     */
    protected function process_hiveforum($data) {
        global $DB;
        $data = (object) $data;
        $data->course = $this->get_courseid();
        $newid = $DB->insert_record('hiveforum', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * Discussion.
     *
     * @param array $data
     */
    protected function process_hiveforum_discussion($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;
        $data->hiveforum = $this->get_new_parentid('hiveforum');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified, 0);
        $data->groupid = empty($data->groupid) ? 0 : (int) $this->get_mappingid('group', $data->groupid, 0);
        // The first post is remapped in after_execute(), once posts are restored.
        $newid = $DB->insert_record('hiveforum_discussions', $data);
        $this->set_mapping('hiveforum_discussion', $oldid, $newid);
    }

    /**
     * Tag.
     *
     * @param array $data
     */
    protected function process_hiveforum_tag($data) {
        global $DB;
        $data = (object) $data;
        $data->discussion = $this->get_new_parentid('hiveforum_discussion');
        $DB->insert_record('hiveforum_tags', $data);
    }

    /**
     * Post.
     *
     * @param array $data
     */
    protected function process_hiveforum_post($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;
        $data->discussion = $this->get_new_parentid('hiveforum_discussion');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!empty($data->parent)) {
            $data->parent = $this->get_mappingid('hiveforum_post', $data->parent, 0);
        }
        $newid = $DB->insert_record('hiveforum_posts', $data);
        $this->set_mapping('hiveforum_post', $oldid, $newid, true);
    }

    /**
     * Reaction.
     *
     * @param array $data
     */
    protected function process_hiveforum_reaction($data) {
        global $DB;
        $data = (object) $data;
        $data->postid = $this->get_new_parentid('hiveforum_post');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (
            $data->userid && !$DB->record_exists(
                'hiveforum_reactions',
                ['postid' => $data->postid, 'userid' => $data->userid, 'reactiontype' => $data->reactiontype]
            )
        ) {
            $DB->insert_record('hiveforum_reactions', $data);
        }
    }

    /**
     * Read marker.
     *
     * @param array $data
     */
    protected function process_hiveforum_read($data) {
        global $DB;
        $data = (object) $data;
        $data->discussion = $this->get_new_parentid('hiveforum_discussion');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if ($data->userid) {
            $DB->insert_record('hiveforum_reads', $data);
        }
    }

    /**
     * Subscription.
     *
     * @param array $data
     */
    protected function process_hiveforum_subscription($data) {
        global $DB;
        $data = (object) $data;
        $data->discussion = $this->get_new_parentid('hiveforum_discussion');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if ($data->userid) {
            $DB->insert_record('hiveforum_subscriptions', $data);
        }
    }

    /**
     * Streak.
     *
     * @param array $data
     */
    protected function process_hiveforum_streak($data) {
        global $DB;
        $data = (object) $data;
        $data->hiveforum = $this->get_new_parentid('hiveforum');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if ($data->userid) {
            $DB->insert_record('hiveforum_streaks', $data);
        }
    }

    /**
     * Mention.
     *
     * @param array $data
     */
    protected function process_hiveforum_mention($data) {
        global $DB;
        $data = (object) $data;
        $data->postid = $this->get_new_parentid('hiveforum_post');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if ($data->userid) {
            $DB->insert_record('hiveforum_mentions', $data);
        }
    }

    /**
     * Forum subscription.
     *
     * @param array $data
     */
    protected function process_hiveforum_forumsub($data) {
        global $DB;
        $data = (object) $data;
        $data->hiveforum = $this->get_new_parentid('hiveforum');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if ($data->userid) {
            $DB->insert_record('hiveforum_forum_subs', $data);
        }
    }

    /**
     * Digest preference.
     *
     * @param array $data
     */
    protected function process_hiveforum_digest($data) {
        global $DB;
        $data = (object) $data;
        $data->hiveforum = $this->get_new_parentid('hiveforum');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if ($data->userid) {
            $DB->insert_record('hiveforum_digests', $data);
        }
    }

    /**
     * Files and first post remapping.
     */
    protected function after_execute() {
        global $DB;

        $this->add_related_files('mod_hiveforum', 'intro', null);
        $this->add_related_files('mod_hiveforum', 'post', 'hiveforum_post');

        $forumid = $this->get_new_parentid('hiveforum');
        $discussions = $DB->get_records('hiveforum_discussions', ['hiveforum' => $forumid], '', 'id, firstpost');
        foreach ($discussions as $discussion) {
            $newfirst = $this->get_mappingid('hiveforum_post', $discussion->firstpost, 0);
            $DB->set_field('hiveforum_discussions', 'firstpost', $newfirst, ['id' => $discussion->id]);
        }
    }
}
