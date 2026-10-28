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

namespace mod_hiveforum\backup;

use mod_hiveforum\local\api;
use mod_hiveforum\local\forum;
use mod_hiveforum\local\subscriptions;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Backup and restore (duplication with user data).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class backup_restore_test extends \advanced_testcase {
    public function test_backup_and_restore_course_with_user_data(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $group = $gen->create_group(['courseid' => $course->id, 'name' => 'Team']);
        $gen->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
        $cm = $gen->create_module('hiveforum', ['course' => $course->id, 'groupmode' => SEPARATEGROUPS,
            'studentposting' => 'questions', 'subscriptionmode' => subscriptions::AUTO, 'completionposts' => 3]);
        $forum = forum::from_cmid((int) $cm->cmid);

        $q = api::create_discussion(
            $forum,
            (int) $student->id,
            'Question',
            'Body @[Teacher](user:' . $teacher->id . ')',
            FORMAT_MARKDOWN,
            true,
            ['tag1'],
            null,
            [],
            (int) $group->id
        );
        subscriptions::set_forum_subscription($forum, (int) $student->id, false);
        subscriptions::set_digest($forum, (int) $student->id, true);
        $a = api::add_post($forum, $q, 0, (int) $USER->id, 'Answer', FORMAT_MARKDOWN);
        $child = api::add_post($forum, $q, (int) $a->id, (int) $student->id, 'Thanks', FORMAT_MARKDOWN);
        api::toggle_reaction($forum, $a, (int) $student->id, 'love');
        api::toggle_accepted($q, $a);

        // Backup the course.
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->execute_plan();
        $file = $bc->get_results()['backup_destination'];
        $bc->destroy();
        $path = make_backup_temp_directory('hfbackup');
        $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $path);

        // Restore into a new course.
        $newcourseid = \restore_dbops::create_new_course('Restored', 'RESTORED', $course->category);
        $rc = new \restore_controller(
            'hfbackup',
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $rc->get_plan()->get_setting('users')->set_value(true);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $newforum = $DB->get_record('hiveforum', ['course' => $newcourseid], '*', MUST_EXIST);
        $newd = $DB->get_record('hiveforum_discussions', ['hiveforum' => $newforum->id], '*', MUST_EXIST);
        $posts = $DB->get_records('hiveforum_posts', ['discussion' => $newd->id], 'id ASC');
        $this->assertCount(3, $posts);
        [$first, $answer, $thanks] = array_values($posts);
        $this->assertEquals($first->id, $newd->firstpost);
        $this->assertEquals($first->id, $answer->parent);
        $this->assertEquals($answer->id, $thanks->parent);
        $this->assertEquals(1, $answer->isaccepted);
        $this->assertEquals(1, $newd->resolved);
        $this->assertEquals(['tag1'], api::get_tags([(int) $newd->id])[(int) $newd->id]);
        $this->assertTrue($DB->record_exists('hiveforum_reactions', ['postid' => $answer->id, 'reactiontype' => 'love']));
        $this->assertTrue($DB->record_exists('hiveforum_subscriptions', ['discussion' => $newd->id, 'userid' => $student->id]));

        // Settings, group of the discussion, preferences and mentions.
        $this->assertEquals('questions', $newforum->studentposting);
        $this->assertEquals(subscriptions::AUTO, $newforum->subscriptionmode);
        $this->assertEquals(3, $newforum->completionposts);
        $newgroup = $DB->get_record('groups', ['courseid' => $newcourseid, 'name' => 'Team'], '*', MUST_EXIST);
        $this->assertEquals($newgroup->id, $newd->groupid);
        $this->assertTrue($DB->record_exists(
            'hiveforum_forum_subs',
            ['hiveforum' => $newforum->id, 'userid' => $student->id, 'state' => 0]
        ));
        $this->assertTrue($DB->record_exists(
            'hiveforum_digests',
            ['hiveforum' => $newforum->id, 'userid' => $student->id, 'digest' => 1]
        ));
        $this->assertTrue($DB->record_exists('hiveforum_mentions', ['postid' => $first->id, 'userid' => $teacher->id]));
    }
}
