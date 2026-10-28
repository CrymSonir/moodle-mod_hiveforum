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
 * Tests for the business logic.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(api::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(forum::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(exporter::class)]
final class api_test extends \advanced_testcase {
    /** @var \stdClass */
    private \stdClass $course;
    /** @var forum */
    private forum $forum;
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
        $instance = $gen->create_module('hiveforum', ['course' => $this->course->id]);
        $this->forum = forum::from_cmid((int) $instance->cmid);
    }

    /**
     * Shortcut to start a discussion as a user.
     *
     * @param \stdClass $user
     * @param string $name
     * @param bool $question
     * @param array $tags
     * @return \stdClass
     */
    private function start(\stdClass $user, string $name = 'Hello', bool $question = false, array $tags = []): \stdClass {
        $this->setUser($user);
        return api::create_discussion($this->forum, (int) $user->id, $name, 'Some **text**', FORMAT_MARKDOWN, $question, $tags);
    }

    public function test_create_discussion_sets_first_post_follow_read_and_streak(): void {
        global $DB;
        $d = $this->start($this->alice, 'My question', true, ['#TP1', 'tp1', 'Graphs ']);

        $record = $DB->get_record('hiveforum_discussions', ['id' => $d->id]);
        $this->assertEquals('question', $record->posttype);
        $this->assertNotEmpty($record->firstpost);
        $this->assertEquals($this->alice->id, $record->usermodified);
        $this->assertTrue(subscriptions::is_following($this->forum, (int) $d->id, (int) $this->alice->id));
        $this->assertEquals(['tp1', 'graphs'], api::get_tags([(int) $d->id])[(int) $d->id]);
        $this->assertEquals(0, api::count_unread($this->forum, (int) $this->alice->id));
        $this->assertEquals(1, api::count_unread($this->forum, (int) $this->bob->id));
        $this->assertEquals(1, $DB->get_field('hiveforum_streaks', 'currentstreak', ['userid' => $this->alice->id]));
    }

    public function test_create_discussion_requires_title_and_message(): void {
        $this->setUser($this->alice);
        $this->expectException(\moodle_exception::class);
        api::create_discussion($this->forum, (int) $this->alice->id, '   ', 'x', FORMAT_MARKDOWN, false);
    }

    public function test_empty_message_rejected(): void {
        $this->setUser($this->alice);
        $this->expectException(\moodle_exception::class);
        api::create_discussion($this->forum, (int) $this->alice->id, 'Title', '<p> </p>', FORMAT_HTML, false);
    }

    public function test_add_post_updates_discussion_and_unread_state(): void {
        global $DB;
        $d = $this->start($this->alice);
        $this->setUser($this->bob);
        $this->waitForSecond();
        $post = api::add_post($this->forum, $d, 0, (int) $this->bob->id, 'Reply', FORMAT_MARKDOWN);

        $this->assertEquals($d->firstpost, $post->parent);
        $record = $DB->get_record('hiveforum_discussions', ['id' => $d->id]);
        $this->assertEquals($this->bob->id, $record->usermodified);
        $this->assertEquals(1, api::count_unread($this->forum, (int) $this->alice->id));
        $this->assertTrue(subscriptions::is_following($this->forum, (int) $d->id, (int) $this->bob->id));

        // The followers are notified through an ad-hoc task.
        // One task for the new discussion (forum subscribers), one for the reply.
        $this->assertCount(2, \core\task\manager::get_adhoc_tasks(\mod_hiveforum\task\notify_followers::class));
    }

    public function test_add_post_rejects_parent_from_another_discussion(): void {
        $d1 = $this->start($this->alice, 'One');
        $d2 = $this->start($this->alice, 'Two');
        $this->expectException(\moodle_exception::class);
        api::add_post($this->forum, $d1, (int) $d2->firstpost, (int) $this->alice->id, 'x', FORMAT_MARKDOWN);
    }

    public function test_filters_search_and_tags(): void {
        $this->start($this->alice, 'Alpha discussion', false, ['maths']);
        $q = $this->start($this->bob, 'Beta question', true, ['physics']);
        $this->setUser($this->bob);
        api::add_post($this->forum, $q, 0, (int) $this->bob->id, 'Contains the word zebra', FORMAT_MARKDOWN);

        $uid = (int) $this->alice->id;
        $this->assertEquals(2, api::get_discussions($this->forum, $uid)['total']);
        $this->assertEquals(1, api::get_discussions($this->forum, $uid, 'questions')['total']);
        $this->assertEquals(1, api::get_discussions($this->forum, $uid, 'mine')['total']);
        $this->assertEquals(1, api::get_discussions($this->forum, $uid, 'unread')['total']);
        $this->assertEquals(1, api::get_discussions($this->forum, $uid, 'following')['total']);
        $this->assertEquals(1, api::get_discussions($this->forum, $uid, 'all', 'zebra')['total']);
        $this->assertEquals(1, api::get_discussions($this->forum, $uid, 'all', 'ALPHA')['total']);
        $this->assertEquals(1, api::get_discussions($this->forum, $uid, 'all', '', '#Physics')['total']);
        $this->assertEquals('Beta question', api::get_discussions($this->forum, $uid, 'all', '', '', 'popular')['discussions'][0]->name);

        $counts = api::get_filter_counts($this->forum, $uid);
        $this->assertEquals(['all' => 2, 'unread' => 1, 'following' => 1, 'questions' => 1, 'unanswered' => 1, 'mine' => 1], $counts);

        api::mark_all_read($this->forum, $uid);
        $this->assertEquals(0, api::count_unread($this->forum, $uid));
    }

    public function test_pinned_first(): void {
        $old = $this->start($this->alice, 'Old');
        $this->waitForSecond();
        $this->start($this->alice, 'New');
        api::set_pinned($old, true);
        $list = api::get_discussions($this->forum, (int) $this->alice->id);
        $this->assertEquals('Old', $list['discussions'][0]->name);
    }

    public function test_delete_reply_deletes_descendants_and_reactions(): void {
        global $DB;
        $d = $this->start($this->alice);
        $this->setUser($this->bob);
        $reply = api::add_post($this->forum, $d, 0, (int) $this->bob->id, 'reply', FORMAT_MARKDOWN);
        $child = api::add_post($this->forum, $d, (int) $reply->id, (int) $this->alice->id, 'child', FORMAT_MARKDOWN);
        $other = api::add_post($this->forum, $d, 0, (int) $this->alice->id, 'other', FORMAT_MARKDOWN);
        api::toggle_reaction($this->forum, $child, (int) $this->bob->id, 'like');

        $this->assertFalse(api::delete_post($this->forum, $d, $reply));
        $this->assertFalse($DB->record_exists('hiveforum_posts', ['id' => $child->id]));
        $this->assertTrue($DB->record_exists('hiveforum_posts', ['id' => $other->id]));
        $this->assertEquals(0, $DB->count_records('hiveforum_reactions'));
    }

    public function test_delete_first_post_deletes_discussion(): void {
        global $DB;
        $d = $this->start($this->alice);
        api::add_post($this->forum, $d, 0, (int) $this->bob->id, 'reply', FORMAT_MARKDOWN);
        $first = $DB->get_record('hiveforum_posts', ['id' => $d->firstpost]);
        $this->assertTrue(api::delete_post($this->forum, $d, $first));
        $this->assertEquals(0, $DB->count_records('hiveforum_discussions'));
        $this->assertEquals(0, $DB->count_records('hiveforum_posts'));
        $this->assertEquals(0, $DB->count_records('hiveforum_subscriptions'));
        $this->assertEquals(0, $DB->count_records('hiveforum_tags'));
    }

    public function test_toggle_reaction(): void {
        $d = $this->start($this->alice);
        $post = (object) ['id' => $d->firstpost, 'discussion' => $d->id];
        $result = api::toggle_reaction($this->forum, $post, (int) $this->bob->id, 'love');
        $this->assertEquals(1, $result['reactions']['love']);
        $this->assertTrue($result['myreactions']['love']);
        $result = api::toggle_reaction($this->forum, $post, (int) $this->bob->id, 'love');
        $this->assertEquals(0, $result['reactions']['love']);

        $this->expectException(\invalid_parameter_exception::class);
        api::toggle_reaction($this->forum, $post, (int) $this->bob->id, 'angry');
    }

    public function test_accepted_answer_is_unique_and_resolves(): void {
        global $DB;
        $q = $this->start($this->alice, 'Q', true);
        $a1 = api::add_post($this->forum, $q, 0, (int) $this->bob->id, 'a1', FORMAT_MARKDOWN);
        $a2 = api::add_post($this->forum, $q, 0, (int) $this->teacher->id, 'a2', FORMAT_MARKDOWN);

        $this->assertTrue(api::toggle_accepted($q, $a1));
        $this->assertTrue(api::toggle_accepted($q, $a2));
        $this->assertEquals(1, $DB->count_records('hiveforum_posts', ['discussion' => $q->id, 'isaccepted' => 1]));
        $this->assertEquals(1, $DB->get_field('hiveforum_discussions', 'resolved', ['id' => $q->id]));

        // Deleting the accepted answer reopens the question.
        api::delete_post($this->forum, $q, $DB->get_record('hiveforum_posts', ['id' => $a2->id]));
        $this->assertEquals(0, $DB->get_field('hiveforum_discussions', 'resolved', ['id' => $q->id]));
    }

    public function test_cannot_accept_on_regular_discussion(): void {
        $d = $this->start($this->alice);
        $reply = api::add_post($this->forum, $d, 0, (int) $this->bob->id, 'r', FORMAT_MARKDOWN);
        $this->expectException(\moodle_exception::class);
        api::toggle_accepted($d, $reply);
    }

    public function test_streaks_count_consecutive_weeks(): void {
        global $DB;
        $clock = $this->mock_clock_with_frozen(WEEKSECS * 3000);
        $forumid = (int) $this->forum->instance->id;
        $userid = (int) $this->alice->id;

        api::bump_streak($forumid, $userid);
        api::bump_streak($forumid, $userid); // Same week: no change.
        $clock->bump(WEEKSECS);
        api::bump_streak($forumid, $userid);
        $clock->bump(WEEKSECS);
        api::bump_streak($forumid, $userid);
        $streak = $DB->get_record('hiveforum_streaks', ['userid' => $userid]);
        $this->assertEquals(3, $streak->currentstreak);
        $this->assertEquals(3, $streak->beststreak);
        $this->assertEquals([$userid => 3], api::get_streaks($forumid, [$userid]));

        // A missed week resets the streak but keeps the best one.
        $clock->bump(WEEKSECS * 3);
        $this->assertEquals([$userid => 0], api::get_streaks($forumid, [$userid]));
        api::bump_streak($forumid, $userid);
        $streak = $DB->get_record('hiveforum_streaks', ['userid' => $userid]);
        $this->assertEquals(1, $streak->currentstreak);
        $this->assertEquals(3, $streak->beststreak);
    }

    public function test_permissions(): void {
        $d = $this->start($this->alice);
        $post = (object) ['userid' => $this->alice->id];

        $this->setUser($this->bob);
        $this->assertFalse($this->forum->can_edit_post($post));
        $this->assertFalse($this->forum->can_delete_post($post));
        $this->assertFalse($this->forum->can_pin());
        $this->assertFalse($this->forum->can_resolve($d));
        $this->assertTrue($this->forum->can_reply());

        $this->setUser($this->alice);
        $this->assertTrue($this->forum->can_edit_post($post));
        $this->assertTrue($this->forum->can_resolve($d));

        $this->setUser($this->teacher);
        $this->assertTrue($this->forum->can_edit_post($post));
        $this->assertTrue($this->forum->can_pin());

        $this->setGuestUser();
        $this->assertFalse($this->forum->can_reply());
        $this->assertFalse($this->forum->can_react());
    }

    public function test_reactions_disabled_by_setting(): void {
        global $DB;
        $DB->set_field('hiveforum', 'allowreactions', 0, ['id' => $this->forum->instance->id]);
        $forum = forum::from_cmid($this->forum->get_cmid());
        $this->setUser($this->alice);
        $this->assertFalse($forum->can_react());
    }

    public function test_exporter_shapes_and_excerpt(): void {
        $this->start($this->alice, 'Hello <b>world</b>', false, ['x']);
        $this->setUser($this->bob);
        $list = api::get_discussions($this->forum, (int) $this->bob->id);
        $exported = (new exporter($this->forum))->discussions($list['discussions']);
        $this->assertCount(1, $exported);
        $this->assertEquals('Some text', $exported[0]['excerpt']);
        $this->assertTrue($exported[0]['unread']);
        $this->assertEquals(0, $exported[0]['replycount']);
        $this->assertNull($exported[0]['lastauthor']);
        $this->assertEquals(['x'], $exported[0]['tags']);
        $this->assertStringNotContainsString('<b>', $exported[0]['name']);
    }
}
