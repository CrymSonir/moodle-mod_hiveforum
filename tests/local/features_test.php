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

use mod_hiveforum\completion\custom_completion;
use mod_hiveforum\external\set_preferences;

/**
 * Subscriptions, mentions, digests, completion rules, report and posting restrictions.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(subscriptions::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(mentions::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(report::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(custom_completion::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_hiveforum\task\send_digests::class)]
final class features_test extends \core_external\tests\externallib_testcase {
    /** @var \stdClass */
    private \stdClass $course;
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
        $this->course = $gen->create_course(['enablecompletion' => 1]);
        $this->teacher = $gen->create_and_enrol($this->course, 'editingteacher');
        $this->alice = $gen->create_and_enrol($this->course, 'student');
        $this->bob = $gen->create_and_enrol($this->course, 'student');
    }

    /**
     * Create a forum.
     *
     * @param array $settings
     * @return forum
     */
    private function forum(array $settings = []): forum {
        $instance = $this->getDataGenerator()->create_module('hiveforum', ['course' => $this->course->id] + $settings);
        return forum::from_cmid((int) $instance->cmid);
    }

    /**
     * Start a discussion as a user.
     *
     * @param forum $forum
     * @param \stdClass $user
     * @param string $message
     * @param bool $question
     * @return \stdClass
     */
    private function start(forum $forum, \stdClass $user, string $message = 'Hello', bool $question = false): \stdClass {
        $this->setUser($user);
        return api::create_discussion(
            forum::from_cmid($forum->get_cmid()),
            (int) $user->id,
            'Topic',
            $message,
            FORMAT_MARKDOWN,
            $question
        );
    }

    public function test_subscription_modes(): void {
        $optional = $this->forum(['subscriptionmode' => subscriptions::OPTIONAL]);
        $auto = $this->forum(['subscriptionmode' => subscriptions::AUTO]);
        $forced = $this->forum(['subscriptionmode' => subscriptions::FORCED]);
        $disabled = $this->forum(['subscriptionmode' => subscriptions::DISABLED]);
        $uid = (int) $this->alice->id;

        $this->assertFalse(subscriptions::is_forum_subscribed($optional, $uid));
        $this->assertTrue(subscriptions::is_forum_subscribed($auto, $uid));
        $this->assertTrue(subscriptions::is_forum_subscribed($forced, $uid));
        $this->assertFalse(subscriptions::is_forum_subscribed($disabled, $uid));

        // Auto: users may opt out; optional: opt in.
        subscriptions::set_forum_subscription($auto, $uid, false);
        $this->assertFalse(subscriptions::is_forum_subscribed($auto, $uid));
        subscriptions::set_forum_subscription($optional, $uid, true);
        $this->assertTrue(subscriptions::is_forum_subscribed($optional, $uid));

        foreach ([$forced, $disabled] as $locked) {
            try {
                subscriptions::set_forum_subscription($locked, $uid, true);
                $this->fail('Exception expected');
            } catch (\moodle_exception $e) {
                $this->assertEquals('subscriptionlocked', $e->errorcode);
            }
        }
    }

    public function test_discussion_override_and_followers(): void {
        $forum = $this->forum(['subscriptionmode' => subscriptions::AUTO]);
        $d = $this->start($forum, $this->teacher);
        $alice = (int) $this->alice->id;
        $bob = (int) $this->bob->id;

        $this->assertTrue(subscriptions::is_following($forum, (int) $d->id, $alice));
        subscriptions::set_following($forum, (int) $d->id, $alice, false);
        $this->assertFalse(subscriptions::is_following($forum, (int) $d->id, $alice));

        $followers = subscriptions::get_followers($forum, $d);
        $this->assertNotContains($alice, $followers);
        $this->assertContains($bob, $followers);

        // The "following" filter of the list uses the same rules.
        $this->setUser($this->alice);
        $this->assertCount(0, api::get_discussions($forum, $alice, 'following')['discussions']);
        $this->setUser($this->bob);
        $this->assertCount(1, api::get_discussions($forum, $bob, 'following')['discussions']);

        // Unsubscribing from the forum forgets the discussion-level choices.
        subscriptions::set_following($forum, (int) $d->id, $bob, true);
        subscriptions::set_forum_subscription($forum, $bob, false);
        $this->assertFalse(subscriptions::is_following($forum, (int) $d->id, $bob));
    }

    public function test_set_preferences_web_service(): void {
        $forum = $this->forum(['subscriptionmode' => subscriptions::OPTIONAL]);
        $this->setUser($this->alice);
        $result = set_preferences::execute($forum->get_cmid(), 1, 1);
        $result = \core_external\external_api::clean_returnvalue(set_preferences::execute_returns(), $result);
        $this->assertTrue($result['preferences']['forumsubscribed']);
        $this->assertTrue($result['preferences']['digest']);
        $this->assertTrue(subscriptions::get_digest($forum, (int) $this->alice->id));
    }

    public function test_mentions(): void {
        global $DB;
        $this->assertEquals([12, 5], mentions::extract_userids('Hi @[A](user:12), @[B](user:5) and @[A](user:12)'));
        $this->assertEquals('Hi @Ann!', mentions::to_text('Hi @[Ann](user:3)!'));
        $html = mentions::render('Hi @[<b>Ann</b>](user:3)', (int) FORMAT_HTML, 7);
        $this->assertStringContainsString('class="hf-mention"', $html);
        $this->assertStringNotContainsString('<b>', $html);

        $forum = $this->forum(['subscriptionmode' => subscriptions::DISABLED]);
        $stranger = $this->getDataGenerator()->create_user();
        $d = $this->start(
            $forum,
            $this->alice,
            "Hey @[Bob](user:{$this->bob->id}), @[X](user:{$stranger->id}) and myself @[A](user:{$this->alice->id})"
        );

        // Only bob is recorded: strangers and the author are ignored.
        $this->assertEquals([(int) $this->bob->id], array_map(
            'intval',
            $DB->get_fieldset_select('hiveforum_mentions', 'userid', 'postid = ?', [$d->firstpost])
        ));
        $this->assertCount(1, \core\task\manager::get_adhoc_tasks(\mod_hiveforum\task\notify_mentions::class));

        // Editing the post does not notify bob twice.
        $post = $DB->get_record('hiveforum_posts', ['id' => $d->firstpost]);
        mentions::process($forum, $d, $post);
        $this->assertEquals(1, $DB->count_records('hiveforum_mentions', ['postid' => $d->firstpost]));

        // Mentions are sent even when subscriptions are disabled.
        $sink = $this->redirectMessages();
        $this->runAdhocTasks(\mod_hiveforum\task\notify_mentions::class);
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('mention', $messages[0]->eventtype);
        $this->assertEquals($this->bob->id, $messages[0]->useridto);

        // Autocomplete.
        $this->setUser($this->alice);
        $found = array_map(fn($u) => (int) $u->id, mentions::search($forum, $d, $this->bob->firstname));
        $this->assertContains((int) $this->bob->id, $found);
        $this->assertNotContains((int) $stranger->id, $found);
    }

    public function test_global_notification_switch(): void {
        $forum = $this->forum(['subscriptionmode' => subscriptions::FORCED]);
        $this->assertTrue(subscriptions::notifications_enabled($forum));
        set_config('enablenotifications', 0, 'mod_hiveforum');
        $this->assertFalse(subscriptions::notifications_enabled($forum));

        $d = $this->start($forum, $this->alice);
        $sink = $this->redirectMessages();
        $this->runAdhocTasks();
        $this->assertCount(0, $sink->get_messages());
        $this->assertNotEmpty($d->id);

        $off = $this->forum(['notifications' => 0]);
        set_config('enablenotifications', 1, 'mod_hiveforum');
        $this->assertFalse(subscriptions::notifications_enabled($off));
    }

    public function test_digest(): void {
        $forum = $this->forum(['subscriptionmode' => subscriptions::AUTO]);
        subscriptions::set_digest($forum, (int) $this->bob->id, true);
        set_config('digestlastrun', time() - HOURSECS, 'mod_hiveforum');
        $d = $this->start($forum, $this->alice, 'First');
        api::add_post($forum, $d, (int) $d->firstpost, (int) $this->alice->id, 'Second', (int) FORMAT_MARKDOWN);

        // Immediate notifications: the teacher, not bob (who chose the digest), nor alice (author).
        $sink = $this->redirectMessages();
        $this->runAdhocTasks();
        $recipients = array_unique(array_map(fn($m) => (int) $m->useridto, $sink->get_messages()));
        $this->assertEquals([(int) $this->teacher->id], array_values($recipients));
        $sink->clear();

        $this->waitForSecond();
        ob_start();
        (new \mod_hiveforum\task\send_digests())->execute();
        ob_end_clean();
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('digest', $messages[0]->eventtype);
        $this->assertEquals($this->bob->id, $messages[0]->useridto);
        $this->assertStringContainsString('Second', $messages[0]->fullmessagehtml);
    }

    public function test_completion_rules(): void {
        $forum = $this->forum([
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionposts' => 2,
            'completionaccepted' => 1,
        ]);
        $cm = get_fast_modinfo($this->course)->get_cm($forum->get_cmid());
        $completion = new custom_completion($cm, (int) $this->alice->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionposts'));

        $q = $this->start($forum, $this->teacher, 'Question?', true);
        $this->setUser($this->alice);
        $reply = api::add_post($forum, $q, (int) $q->firstpost, (int) $this->alice->id, 'Answer', (int) FORMAT_MARKDOWN);
        api::add_post($forum, $q, (int) $q->firstpost, (int) $this->alice->id, 'More', (int) FORMAT_MARKDOWN);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_state('completionposts'));
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionaccepted'));

        $this->setUser($this->teacher);
        global $DB;
        api::toggle_accepted($DB->get_record('hiveforum_discussions', ['id' => $q->id]), $reply);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_state('completionaccepted'));
        $this->assertArrayHasKey('completionposts', $completion->get_custom_rule_descriptions());

        // The course completion API sees it too.
        $info = new \completion_info($this->course);
        $this->assertEquals(COMPLETION_COMPLETE, $info->get_data($cm, false, (int) $this->alice->id)->completionstate);
    }

    public function test_report(): void {
        $forum = $this->forum();
        $d = $this->start($forum, $this->alice);
        $this->setUser($this->bob);
        $reply = api::add_post($forum, $d, (int) $d->firstpost, (int) $this->bob->id, 'Reply', (int) FORMAT_MARKDOWN);
        $this->setUser($this->alice);
        api::toggle_reaction($forum, $reply, (int) $this->alice->id, 'like');

        $report = new report($forum, 0, 30);
        $rows = $report->get_rows();
        $this->assertCount(3, $rows);
        $this->assertEquals(1, $rows[$this->alice->id]['discussions']);
        $this->assertEquals(1, $rows[$this->alice->id]['reactionsgiven']);
        $this->assertEquals(1, $rows[$this->bob->id]['replies']);
        $this->assertEquals(1, $rows[$this->bob->id]['reactionsreceived']);
        $this->assertEquals(0, $rows[$this->teacher->id]['posts']);
        $summary = $report->get_summary($rows);
        $this->assertEquals(1, $summary['discussions']);
        $this->assertEquals(1, $summary['replies']);
        $this->assertEquals(1, $summary['reactions']);
        $this->assertEquals(2, $summary['active']);
    }

    public function test_student_posting_setting(): void {
        $forum = $this->forum(['studentposting' => 'questions']);
        $this->setUser($this->alice);
        $fresh = forum::from_cmid($forum->get_cmid());
        $this->assertEquals(['question'], $fresh->get_allowed_post_types());
        try {
            api::create_discussion($fresh, (int) $this->alice->id, 'T', 'M', (int) FORMAT_MARKDOWN, false);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals('posttypenotallowed', $e->errorcode);
        }
        $this->assertNotEmpty(api::create_discussion($fresh, (int) $this->alice->id, 'T', 'M', (int) FORMAT_MARKDOWN, true));

        $none = $this->forum(['studentposting' => 'none']);
        $this->assertFalse(forum::from_cmid($none->get_cmid())->can_start_discussion());
        $this->setUser($this->teacher);
        $this->assertTrue(forum::from_cmid($none->get_cmid())->can_start_discussion());
    }

    public function test_sorts_and_unanswered_filter(): void {
        global $DB;
        $forum = $this->forum();
        $busy = $this->start($forum, $this->alice, 'Busy', true);
        $this->waitForSecond();
        $quiet = $this->start($forum, $this->alice, 'Quiet', true);
        $this->setUser($this->bob);
        $reply = api::add_post($forum, $busy, (int) $busy->firstpost, (int) $this->bob->id, 'R', (int) FORMAT_MARKDOWN);
        $this->setUser($this->alice);
        api::toggle_reaction($forum, $reply, (int) $this->alice->id, 'like');
        $this->setUser($this->teacher);
        api::toggle_reaction(
            $forum,
            $DB->get_record('hiveforum_posts', ['id' => $quiet->firstpost]),
            (int) $this->teacher->id,
            'love'
        );

        $order = fn(string $sort) => array_map(
            fn($d) => (int) $d->id,
            api::get_discussions($forum, (int) $this->teacher->id, 'all', '', '', $sort)['discussions']
        );
        $this->assertEquals([(int) $busy->id, (int) $quiet->id], $order('replies'));
        $this->assertEquals([(int) $busy->id, (int) $quiet->id], $order('likes'));
        $this->assertEquals([(int) $quiet->id, (int) $busy->id], $order('newest'));

        $unanswered = api::get_discussions($forum, (int) $this->teacher->id, 'unanswered')['discussions'];
        $this->assertEquals([(int) $quiet->id], array_map(fn($d) => (int) $d->id, $unanswered));
        $this->assertEquals(1, api::get_filter_counts($forum, (int) $this->teacher->id)['unanswered']);
    }
}
