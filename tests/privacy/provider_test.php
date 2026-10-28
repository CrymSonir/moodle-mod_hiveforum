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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_hiveforum\local\api;
use mod_hiveforum\local\forum;
use mod_hiveforum\local\subscriptions;

/**
 * Privacy provider tests.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** @var forum */
    private forum $forum;
    /** @var \stdClass */
    private \stdClass $alice;
    /** @var \stdClass */
    private \stdClass $bob;
    /** @var \stdClass */
    private \stdClass $discussion;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $this->alice = $gen->create_and_enrol($course, 'student');
        $this->bob = $gen->create_and_enrol($course, 'student');
        $cm = $gen->create_module('hiveforum', ['course' => $course->id]);
        $this->forum = forum::from_cmid((int) $cm->cmid);

        $this->setUser($this->alice);
        $this->discussion = api::create_discussion(
            $this->forum,
            (int) $this->alice->id,
            'Alice topic',
            'Alice text',
            FORMAT_MARKDOWN,
            false
        );
        $this->setUser($this->bob);
        $reply = api::add_post($this->forum, $this->discussion, 0, (int) $this->bob->id, 'Bob reply', FORMAT_MARKDOWN);
        api::toggle_reaction($this->forum, $reply, (int) $this->alice->id, 'like');
        // Preferences and a mention of bob.
        subscriptions::set_forum_subscription($this->forum, (int) $this->bob->id, true);
        subscriptions::set_digest($this->forum, (int) $this->bob->id, true);
        $this->setUser($this->alice);
        api::add_post(
            $this->forum,
            $this->discussion,
            0,
            (int) $this->alice->id,
            'Hi @[Bob](user:' . $this->bob->id . ')',
            FORMAT_MARKDOWN
        );
    }

    public function test_metadata(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('mod_hiveforum'));
        $this->assertCount(11, $collection->get_collection());
    }

    public function test_contexts_and_users(): void {
        $contexts = provider::get_contexts_for_userid((int) $this->bob->id)->get_contextids();
        $this->assertEquals([$this->forum->context->id], array_values($contexts));

        $userlist = new userlist($this->forum->context, 'mod_hiveforum');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$this->alice->id, $this->bob->id], $userlist->get_userids());
    }

    public function test_export(): void {
        $contextlist = new approved_contextlist($this->bob, 'mod_hiveforum', [$this->forum->context->id]);
        provider::export_user_data($contextlist);
        $writer = writer::with_context($this->forum->context);
        $this->assertTrue($writer->has_any_data());
        $subcontext = [get_string('discussions', 'hiveforum'), $this->discussion->id . ' - Alice topic'];
        $this->assertNotEmpty($writer->get_data($subcontext));
        $this->assertNotEmpty($writer->get_data([get_string('privacy:preferences', 'hiveforum')]));
    }

    public function test_delete_for_user_wipes_posts_and_keeps_others(): void {
        global $DB;
        $contextlist = new approved_contextlist($this->bob, 'mod_hiveforum', [$this->forum->context->id]);
        provider::delete_data_for_user($contextlist);

        $bobposts = $DB->get_records('hiveforum_posts', ['userid' => $this->bob->id]);
        $this->assertCount(1, $bobposts);
        $this->assertEquals('', reset($bobposts)->message);
        $this->assertFalse($DB->record_exists('hiveforum_subscriptions', ['userid' => $this->bob->id]));
        $this->assertFalse($DB->record_exists('hiveforum_streaks', ['userid' => $this->bob->id]));
        $this->assertFalse($DB->record_exists('hiveforum_forum_subs', ['userid' => $this->bob->id]));
        $this->assertFalse($DB->record_exists('hiveforum_digests', ['userid' => $this->bob->id]));
        $this->assertFalse($DB->record_exists('hiveforum_mentions', ['userid' => $this->bob->id]));
        // Alice keeps her content and her reaction.
        $this->assertEquals('Alice text', $DB->get_field('hiveforum_posts', 'message', ['id' => $this->discussion->firstpost]));
        $this->assertTrue($DB->record_exists('hiveforum_reactions', ['userid' => $this->alice->id]));
    }

    public function test_delete_for_users_and_whole_context(): void {
        global $DB;
        $userlist = new approved_userlist($this->forum->context, 'mod_hiveforum', [$this->alice->id]);
        provider::delete_data_for_users($userlist);
        $this->assertEquals('', $DB->get_field('hiveforum_discussions', 'name', ['id' => $this->discussion->id]));
        $this->assertFalse($DB->record_exists('hiveforum_reactions', ['userid' => $this->alice->id]));

        provider::delete_data_for_all_users_in_context($this->forum->context);
        $this->assertEquals(0, $DB->count_records('hiveforum_posts'));
        $this->assertEquals(0, $DB->count_records('hiveforum_discussions'));
    }
}
