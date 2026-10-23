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

use core_external\external_api;

/**
 * Tests for the web services (permissions and return structures).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(get_discussions::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(get_discussion::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(create_discussion::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(add_post::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(update_post::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(delete_post::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(toggle_reaction::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(set_discussion_state::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(toggle_accepted::class)]
final class external_test extends \core_external\tests\externallib_testcase {
    /** @var \stdClass */
    private \stdClass $course;
    /** @var \stdClass */
    private \stdClass $cm;
    /** @var \stdClass */
    private \stdClass $teacher;
    /** @var \stdClass */
    private \stdClass $alice;
    /** @var \stdClass */
    private \stdClass $bob;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $this->course = $gen->create_course();
        $this->teacher = $gen->create_and_enrol($this->course, 'editingteacher');
        $this->alice = $gen->create_and_enrol($this->course, 'student');
        $this->bob = $gen->create_and_enrol($this->course, 'student');
        $this->cm = $gen->create_module('hiveforum', ['course' => $this->course->id]);
    }

    /**
     * Call a web service through the external API and clean the result.
     *
     * @param string $class
     * @param array $args
     * @return array
     */
    private function call(string $class, array $args): array {
        $result = $class::execute(...$args);
        return external_api::clean_returnvalue($class::execute_returns(), $result);
    }

    /**
     * Create a question as alice.
     *
     * @return array
     */
    private function question(): array {
        $this->setUser($this->alice);
        $result = $this->call(create_discussion::class, [(int) $this->cm->cmid, 'Why?', 'Because **markdown**', true, ['tp1']]);
        return $result;
    }

    public function test_full_flow_with_clean_return_values(): void {
        $created = $this->question();
        $this->assertNotEmpty($created['discussionid']);

        $this->setUser($this->bob);
        $reply = $this->call(add_post::class, [$created['discussionid'], 0, 'An answer']);
        $this->assertEquals('An answer', trim(strip_tags($reply['post']['message'])));
        $this->assertTrue($reply['post']['canedit']);
        $this->assertEquals('An answer', $reply['post']['rawmessage']);

        $reaction = $this->call(toggle_reaction::class, [$reply['post']['id'], 'idea']);
        $this->assertEquals(1, $reaction['reactions'][2]['count']);
        $this->assertTrue($reaction['reactions'][2]['active']);

        $list = $this->call(get_discussions::class, [(int) $this->cm->cmid, 'all', '', '', 'recent', 0]);
        $this->assertEquals(1, $list['total']);
        $this->assertEquals(1, $list['discussions'][0]['replycount']);
        $this->assertEquals($this->bob->id, $list['discussions'][0]['lastauthor']['id']);
        $this->assertEquals(1, $list['counts']['questions']);

        $this->setUser($this->alice);
        $accepted = $this->call(toggle_accepted::class, [$reply['post']['id']]);
        $this->assertTrue($accepted['accepted']);
        $this->assertTrue($accepted['resolved']);

        $thread = $this->call(get_discussion::class, [$created['discussionid'], true]);
        $this->assertCount(2, $thread['posts']);
        $this->assertTrue($thread['discussion']['resolved']);
        $this->assertTrue($thread['discussion']['following']);

        $state = $this->call(set_discussion_state::class, [$created['discussionid'], 'following', false]);
        $this->assertFalse($state['discussion']['following']);

        $renamed = $this->call(update_discussion::class, [$created['discussionid'], 'Why, really?', ['tp1', 'tp2']]);
        $this->assertEquals('Why, really?', $renamed['discussion']['name']);
        $this->assertEquals(['tp1', 'tp2'], $renamed['discussion']['tags']);

        $this->assertTrue($this->call(mark_all_read::class, [(int) $this->cm->cmid])['success']);
    }

    public function test_student_cannot_edit_or_delete_others_posts(): void {
        $created = $this->question();
        $thread = $this->call(get_discussion::class, [$created['discussionid'], false]);
        $firstpostid = $thread['posts'][0]['id'];

        $this->setUser($this->bob);
        try {
            update_post::execute($firstpostid, 'hacked');
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals('nopermissions', $e->errorcode);
        }
        try {
            delete_post::execute($firstpostid);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals('nopermissions', $e->errorcode);
        }
        try {
            toggle_accepted::execute($firstpostid);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals('nopermissions', $e->errorcode);
        }
    }

    public function test_student_cannot_pin_but_teacher_can(): void {
        $created = $this->question();
        $this->setUser($this->bob);
        try {
            set_discussion_state::execute($created['discussionid'], 'pinned', true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals('nopermissions', $e->errorcode);
        }

        $this->setUser($this->teacher);
        $result = $this->call(set_discussion_state::class, [$created['discussionid'], 'pinned', true]);
        $this->assertTrue($result['discussion']['pinned']);
    }

    public function test_teacher_can_delete_whole_discussion(): void {
        global $DB;
        $created = $this->question();
        $thread = $this->call(get_discussion::class, [$created['discussionid'], false]);

        $this->setUser($this->teacher);
        $result = $this->call(delete_post::class, [$thread['posts'][0]['id']]);
        $this->assertTrue($result['discussiondeleted']);
        $this->assertEquals(0, $DB->count_records('hiveforum_discussions'));
    }

    public function test_html_posts_must_be_edited_in_full_editor(): void {
        global $DB;
        $created = $this->question();
        $postid = $DB->get_field('hiveforum_discussions', 'firstpost', ['id' => $created['discussionid']]);
        $DB->set_field('hiveforum_posts', 'messageformat', FORMAT_HTML, ['id' => $postid]);

        $this->expectExceptionMessage(get_string('editinfulleditor', 'hiveforum'));
        update_post::execute((int) $postid, 'new');
    }

    public function test_user_outside_course_cannot_read(): void {
        $this->question();
        $stranger = $this->getDataGenerator()->create_user();
        $this->setUser($stranger);
        $this->expectException(\require_login_exception::class);
        get_discussions::execute((int) $this->cm->cmid);
    }

    public function test_messages_are_cleaned(): void {
        $created = $this->question();
        $this->setUser($this->bob);
        $reply = $this->call(add_post::class, [$created['discussionid'], 0, 'Hi <script>alert(1)</script> there']);
        $this->assertStringNotContainsString('<script', $reply['post']['message']);
    }
}
