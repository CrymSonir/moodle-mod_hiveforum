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
 * Backup structure for mod_hiveforum.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup structure step.
 */
class backup_hiveforum_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $hiveforum = new backup_nested_element('hiveforum', ['id'], [
            'name', 'intro', 'introformat', 'allowreactions', 'allowtags', 'timecreated', 'timemodified',
        ]);

        $discussions = new backup_nested_element('discussions');
        $discussion = new backup_nested_element('discussion', ['id'], [
            'userid', 'name', 'firstpost', 'pinned', 'resolved', 'posttype', 'usermodified', 'timecreated', 'timemodified',
        ]);

        $tags = new backup_nested_element('tags');
        $tag = new backup_nested_element('tag', ['id'], ['tag']);

        $posts = new backup_nested_element('posts');
        $post = new backup_nested_element('post', ['id'], [
            'parent', 'userid', 'message', 'messageformat', 'isaccepted', 'timecreated', 'timemodified',
        ]);

        $reactions = new backup_nested_element('reactions');
        $reaction = new backup_nested_element('reaction', ['id'], ['userid', 'reactiontype', 'timecreated']);

        $reads = new backup_nested_element('reads');
        $read = new backup_nested_element('read', ['id'], ['userid', 'timeread']);

        $subscriptions = new backup_nested_element('subscriptions');
        $subscription = new backup_nested_element('subscription', ['id'], ['userid', 'timecreated']);

        $streaks = new backup_nested_element('streaks');
        $streak = new backup_nested_element('streak', ['id'], ['userid', 'currentstreak', 'beststreak', 'lastactiveweek']);

        // Tree.
        $hiveforum->add_child($discussions);
        $discussions->add_child($discussion);
        $discussion->add_child($tags);
        $tags->add_child($tag);
        $discussion->add_child($posts);
        $posts->add_child($post);
        $post->add_child($reactions);
        $reactions->add_child($reaction);
        $discussion->add_child($reads);
        $reads->add_child($read);
        $discussion->add_child($subscriptions);
        $subscriptions->add_child($subscription);
        $hiveforum->add_child($streaks);
        $streaks->add_child($streak);

        // Sources.
        $hiveforum->set_source_table('hiveforum', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $discussion->set_source_table('hiveforum_discussions', ['hiveforum' => backup::VAR_PARENTID], 'id ASC');
            $tag->set_source_table('hiveforum_tags', ['discussion' => backup::VAR_PARENTID], 'id ASC');
            // Posts ordered by id so that parents are always restored before their children.
            $post->set_source_table('hiveforum_posts', ['discussion' => backup::VAR_PARENTID], 'id ASC');
            $reaction->set_source_table('hiveforum_reactions', ['postid' => backup::VAR_PARENTID], 'id ASC');
            $read->set_source_table('hiveforum_reads', ['discussion' => backup::VAR_PARENTID]);
            $subscription->set_source_table('hiveforum_subscriptions', ['discussion' => backup::VAR_PARENTID]);
            $streak->set_source_table('hiveforum_streaks', ['hiveforum' => backup::VAR_PARENTID]);
        }

        // Ids annotations.
        $discussion->annotate_ids('user', 'userid');
        $discussion->annotate_ids('user', 'usermodified');
        $post->annotate_ids('user', 'userid');
        $reaction->annotate_ids('user', 'userid');
        $read->annotate_ids('user', 'userid');
        $subscription->annotate_ids('user', 'userid');
        $streak->annotate_ids('user', 'userid');

        // Files.
        $hiveforum->annotate_files('mod_hiveforum', 'intro', null);
        $post->annotate_files('mod_hiveforum', 'post', 'id');

        return $this->prepare_activity_structure($hiveforum);
    }
}
