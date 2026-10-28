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
 * Manage the subscriptions of the participants (teachers and managers).
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_hiveforum\local\forum;
use mod_hiveforum\local\subscriptions;

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$forum = forum::from_cmid($id);
require_login($forum->course, false, $forum->cm);
require_capability('mod/hiveforum:managesubscriptions', $forum->context);

$url = new moodle_url('/mod/hiveforum/subscribers.php', ['id' => $forum->get_cmid()]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('managesubscriptions', 'hiveforum') . ' | ' . format_string($forum->instance->name));
$PAGE->set_heading(format_string($forum->course->fullname));
$PAGE->add_body_class('mod-hiveforum');
$PAGE->activityheader->disable();

$groupmenu = $forum->get_groupmode() !== NOGROUPS ? groups_print_activity_menu($forum->cm, $url, true) : '';
$groupid = $forum->resolve_active_group();
if ($groupid === 0 && $forum->get_groupmode() === SEPARATEGROUPS && !$forum->can_access_all_groups()) {
    throw new moodle_exception('nogroupsaccess', 'hiveforum');
}
$editable = subscriptions::is_forum_subscription_optional($forum);
$userids = subscriptions::get_potential_subscribers($forum, $groupid);

// Bulk actions.
if ($action && $editable) {
    require_sesskey();
    $selected = $action === 'subscribeall' || $action === 'unsubscribeall'
        ? $userids
        : array_intersect(optional_param_array('userids', [], PARAM_INT), $userids);
    $subscribe = in_array($action, ['subscribe', 'subscribeall'], true);
    foreach ($selected as $userid) {
        subscriptions::set_forum_subscription($forum, (int) $userid, $subscribe);
    }
    redirect(
        $url,
        get_string($subscribe ? 'subscribedcount' : 'unsubscribedcount', 'hiveforum', count($selected)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managesubscriptions', 'hiveforum'));
if ($groupmenu) {
    echo html_writer::div($groupmenu, 'mb-3');
}

$mode = subscriptions::get_mode($forum);
$modes = [
    subscriptions::OPTIONAL => 'subscriptionoptional',
    subscriptions::FORCED => 'subscriptionforced',
    subscriptions::AUTO => 'subscriptionauto',
    subscriptions::DISABLED => 'subscriptiondisabled',
];
$settingsurl = new moodle_url('/course/modedit.php', ['update' => $forum->get_cmid(), 'return' => 1]);
$info = get_string('currentmode', 'hiveforum', get_string($modes[$mode], 'hiveforum'));
if (has_capability('moodle/course:manageactivities', $forum->context)) {
    $info .= ' ' . html_writer::link($settingsurl, get_string('changemode', 'hiveforum'));
}
echo $OUTPUT->notification($info . '<br>' . get_string('modeexplain_' . $mode, 'hiveforum'), 'info', false);
if (!subscriptions::notifications_enabled($forum)) {
    echo $OUTPUT->notification(get_string('notificationsoffnotice', 'hiveforum'), 'warning', false);
}

if (!$userids) {
    echo $OUTPUT->notification(get_string('nousers', 'hiveforum'), 'info', false);
    echo $OUTPUT->footer();
    die();
}

$userfields = implode(',', \core_user\fields::get_picture_fields());
$users = $DB->get_records_list('user', 'id', $userids, '', $userfields);
uasort($users, fn($a, $b) => strcoll(fullname($a), fullname($b)));
$states = subscriptions::get_forum_subscription_states($forum, $userids);
$explicit = $DB->get_records_menu('hiveforum_forum_subs', ['hiveforum' => $forum->instance->id], '', 'userid, state');
$digestusers = $DB->get_fieldset_select('hiveforum_digests', 'userid', 'hiveforum = ? AND digest = 1', [$forum->instance->id]);
$digests = array_flip(array_map('intval', $digestusers));
$followed = $DB->get_records_sql_menu(
    "SELECT s.userid, COUNT(1)
       FROM {hiveforum_subscriptions} s
       JOIN {hiveforum_discussions} d ON d.id = s.discussion
      WHERE d.hiveforum = :forumid AND s.state = 1
   GROUP BY s.userid",
    ['forumid' => $forum->instance->id]
);

$subscribedcount = count(array_filter($states));
echo html_writer::tag('p', get_string('subscriberssummary', 'hiveforum', (object) [
    'subscribed' => $subscribedcount,
    'total' => count($userids),
]), ['class' => 'text-muted']);

$table = new html_table();
$table->attributes['class'] = 'generaltable hf-subscribers';
$table->head = [];
if ($editable) {
    $table->head[] = html_writer::checkbox('', 1, false, '', ['id' => 'hf-select-all', 'title' => get_string('selectall')])
        . html_writer::label(get_string('selectall'), 'hf-select-all', false, ['class' => 'visually-hidden']);
}
$table->head = array_merge($table->head, [
    get_string('participant', 'hiveforum'),
    get_string('forumsubscription', 'hiveforum'),
    get_string('dailydigest', 'hiveforum'),
    get_string('followeddiscussions', 'hiveforum'),
]);

foreach ($users as $user) {
    $userid = (int) $user->id;
    $subscribed = $states[$userid];
    $status = html_writer::span(
        get_string($subscribed ? 'subscribed' : 'notsubscribed', 'hiveforum'),
        'badge ' . ($subscribed ? 'text-bg-success' : 'text-bg-secondary')
    );
    if ($mode === subscriptions::AUTO && !isset($explicit[$userid])) {
        $status .= ' ' . html_writer::span(get_string('bydefault', 'hiveforum'), 'text-muted small');
    }
    $row = [];
    if ($editable) {
        $row[] = html_writer::checkbox('userids[]', $userid, false, '', ['class' => 'hf-select-user', 'id' => 'hf-u' . $userid])
            . html_writer::label(fullname($user), 'hf-u' . $userid, false, ['class' => 'visually-hidden']);
    }
    $row[] = $OUTPUT->user_picture($user, ['courseid' => $forum->course->id, 'size' => 24, 'includefullname' => true]);
    $row[] = $status;
    $row[] = isset($digests[$userid]) ? get_string('yes') : get_string('no');
    $row[] = (int) ($followed[$userid] ?? 0);
    $table->data[] = $row;
}

if ($editable) {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false), 'id' => 'hf-subscribers-form']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
}
echo html_writer::table($table);
if ($editable) {
    echo html_writer::start_div('d-flex flex-wrap gap-2 mt-3 mb-4');
    foreach (
        ['subscribe' => 'btn-primary', 'unsubscribe' => 'btn-outline-secondary',
              'subscribeall' => 'btn-outline-primary', 'unsubscribeall' => 'btn-outline-danger'] as $name => $class
    ) {
        echo html_writer::tag(
            'button',
            get_string('action_' . $name, 'hiveforum'),
            ['type' => 'submit', 'name' => 'action', 'value' => $name, 'class' => 'btn ' . $class]
        );
    }
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
    $PAGE->requires->js_amd_inline("
        const all = document.getElementById('hf-select-all');
        all && all.addEventListener('change', () => {
            document.querySelectorAll('.hf-select-user').forEach((box) => { box.checked = all.checked; });
        });
    ");
}

echo $OUTPUT->footer();
