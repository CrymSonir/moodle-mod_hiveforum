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

use mod_hiveforum\external\add_post;
use mod_hiveforum\external\get_discussion;
use mod_hiveforum\external\get_discussions;
use mod_hiveforum\external\search_users;
use mod_hiveforum\external\toggle_reaction;

/**
 * Group modes: users only see (and post in) what they are supposed to.
 *
 * Users: a teacher (access all groups), alice in group A, bob in group B, carol in no group.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(forum::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(api::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(mentions::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(subscriptions::class)]
final class groups_test extends \core_external\tests\externallib_testcase {
    /** @var \stdClass */
    private \stdClass $course;
    /** @var \stdClass */
    private \stdClass $teacher;
    /** @var \stdClass */
    private \stdClass $alice;
    /** @var \stdClass */
    private \stdClass $bob;
    /** @var \stdClass */
    private \stdClass $carol;
    /** @var \stdClass */
    private \stdClass $groupa;
    /** @var \stdClass */
    private \stdClass $groupb;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $this->course = $gen->create_course();
        $this->teacher = $gen->create_and_enrol($this->course, 'editingteacher');
        $this->alice = $gen->create_and_enrol($this->course, 'student');
        $this->bob = $gen->create_and_enrol($this->course, 'student');
        $this->carol = $gen->create_and_enrol($this->course, 'student');
        $this->groupa = $gen->create_group(['courseid' => $this->course->id, 'name' => 'A']);
        $this->groupb = $gen->create_group(['courseid' => $this->course->id, 'name' => 'B']);
        $gen->create_group_member(['groupid' => $this->groupa->id, 'userid' => $this->alice->id]);
        $gen->create_group_member(['groupid' => $this->groupb->id, 'userid' => $this->bob->id]);
    }

    /**
     * Create a forum in a group mode.
     *
     * @param int $groupmode
     * @param array $settings
     * @return forum
     */
    private function forum(int $groupmode, array $settings = []): forum {
        $instance = $this->getDataGenerator()->create_module('hiveforum', ['course' => $this->course->id, 'groupmode' => $groupmode]
            + $settings);
        return forum::from_cmid((int) $instance->cmid);
    }

    /**
     * Start a discussion as a user in a group.
     *
     * @param forum $forum
     * @param \stdClass $user
     * @param int $groupid
     * @param string $name
     * @return \stdClass
     */
    private function start(forum $forum, \stdClass $user, int $groupid, string $name): \stdClass {
        $this->setUser($user);
        $forum = forum::from_cmid($forum->get_cmid()); // Fresh object: per-user caches.
        return api::create_discussion(
            $forum,
            (int) $user->id,
            $name,
            "Message of $name",
            FORMAT_MARKDOWN,
            false,
            [],
            null,
            [],
            $groupid
        );
    }

    /**
     * Names of the discussions listed for a user.
     *
     * @param forum $forum
     * @param \stdClass $user
     * @param int $activegroup
     * @return string[]
     */
    private function listed(forum $forum, \stdClass $user, int $activegroup = 0): array {
        $this->setUser($user);
        $forum = forum::from_cmid($forum->get_cmid());
        $names = array_map(fn($d) => $d->name, api::get_discussions(
            $forum,
            (int) $user->id,
            'all',
            '',
            '',
            'recent',
            0,
            50,
            $activegroup
        )['discussions']);
        sort($names);
        return $names;
    }

    public function test_separate_groups_lists_only_allowed_discussions(): void {
        $forum = $this->forum(SEPARATEGROUPS);
        $this->start($forum, $this->alice, (int) $this->groupa->id, 'In A');
        $this->start($forum, $this->bob, (int) $this->groupb->id, 'In B');
        $this->start($forum, $this->teacher, 0, 'For all');

        $this->assertEquals(['For all', 'In A'], $this->listed($forum, $this->alice));
        $this->assertEquals(['For all', 'In B'], $this->listed($forum, $this->bob));
        $this->assertEquals(['For all'], $this->listed($forum, $this->carol));
        $this->assertEquals(['For all', 'In A', 'In B'], $this->listed($forum, $this->teacher));
        $this->assertEquals(['For all', 'In B'], $this->listed($forum, $this->teacher, (int) $this->groupb->id));

        // Counters, stats and unread follow the same rules.
        $this->setUser($this->bob);
        $fresh = forum::from_cmid($forum->get_cmid());
        $this->assertEquals(2, api::get_filter_counts($fresh, (int) $this->bob->id)['all']);
        $this->assertEquals(2, api::get_stats($fresh, (int) $this->bob->id)['discussions']);
        $this->assertEquals(1, api::count_unread($fresh, (int) $this->bob->id));
    }

    public function test_separate_groups_web_services_refuse_other_groups(): void {
        global $DB;
        $forum = $this->forum(SEPARATEGROUPS);
        $ina = $this->start($forum, $this->alice, (int) $this->groupa->id, 'In A');

        $this->setUser($this->bob);
        foreach (
            [
            fn() => get_discussion::execute((int) $ina->id, false),
            fn() => add_post::execute((int) $ina->id, 0, 'Sneaky reply'),
            fn() => toggle_reaction::execute((int) $ina->firstpost, 'like'),
            fn() => search_users::execute($forum->get_cmid(), (int) $ina->id, ''),
            ] as $call
        ) {
            try {
                $call();
                $this->fail('Exception expected');
            } catch (\moodle_exception $e) {
                $this->assertEquals('discussionnotvisible', $e->errorcode);
            }
        }
        $this->assertEquals(1, $DB->count_records('hiveforum_posts', ['discussion' => $ina->id]));

        // The active group asked by a web service cannot widen what the user sees.
        $result = get_discussions::execute($forum->get_cmid(), 'all', '', '', 'recent', 0, (int) $this->groupa->id);
        $this->assertCount(0, $result['discussions']);
    }

    public function test_posting_targets(): void {
        $forum = $this->forum(SEPARATEGROUPS);

        $this->setUser($this->alice);
        $fresh = forum::from_cmid($forum->get_cmid());
        $this->assertEquals([(int) $this->groupa->id], array_keys($fresh->get_post_targets()));
        try {
            api::create_discussion(
                $fresh,
                (int) $this->alice->id,
                'x',
                'y',
                FORMAT_MARKDOWN,
                false,
                [],
                null,
                [],
                (int) $this->groupb->id
            );
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals('cannotpostingroup', $e->errorcode);
        }

        // Without a group, a student cannot start discussions in a group forum.
        $this->setUser($this->carol);
        $this->assertFalse(forum::from_cmid($forum->get_cmid())->can_start_discussion());

        // Teachers can address everybody or any group.
        $this->setUser($this->teacher);
        $targets = forum::from_cmid($forum->get_cmid())->get_post_targets();
        $this->assertEquals([0, (int) $this->groupa->id, (int) $this->groupb->id], array_keys($targets));
    }

    public function test_visible_groups_read_all_but_reply_in_own_group(): void {
        $forum = $this->forum(VISIBLEGROUPS);
        $ina = $this->start($forum, $this->alice, (int) $this->groupa->id, 'In A');
        $all = $this->start($forum, $this->teacher, 0, 'For all');

        $this->assertEquals(['For all', 'In A'], $this->listed($forum, $this->bob));
        $this->setUser($this->bob);
        $fresh = forum::from_cmid($forum->get_cmid());
        $this->assertTrue($fresh->can_see_discussion($ina));
        $this->assertFalse($fresh->can_reply_to($ina));
        $this->assertTrue($fresh->can_reply_to($all));
    }

    public function test_notifications_and_mentions_respect_groups(): void {
        global $DB;
        $forum = $this->forum(SEPARATEGROUPS, ['subscriptionmode' => subscriptions::AUTO]);
        $this->setUser($this->alice);
        $fresh = forum::from_cmid($forum->get_cmid());
        $ina = api::create_discussion(
            $fresh,
            (int) $this->alice->id,
            'In A',
            'Hello @[Bob](user:' . $this->bob->id . ') and @[Teacher](user:' . $this->teacher->id . ')',
            FORMAT_MARKDOWN,
            false,
            [],
            null,
            [],
            (int) $this->groupa->id
        );

        // Bob (other group) is neither a follower nor mentionable; the teacher is both.
        $followers = subscriptions::get_followers($fresh, $ina);
        $this->assertContains((int) $this->teacher->id, $followers);
        $this->assertContains((int) $this->alice->id, $followers);
        $this->assertNotContains((int) $this->bob->id, $followers);
        $this->assertNotContains((int) $this->carol->id, $followers);
        $this->assertEquals(
            [(int) $this->teacher->id],
            array_map('intval', $DB->get_fieldset_select('hiveforum_mentions', 'userid', 'postid = ?', [$ina->firstpost]))
        );

        // The autocomplete does not offer bob either.
        $ids = array_map(fn($u) => (int) $u->id, mentions::search($fresh, $ina, ''));
        $this->assertNotContains((int) $this->bob->id, $ids);
        $this->assertContains((int) $this->teacher->id, $ids);
    }
}
