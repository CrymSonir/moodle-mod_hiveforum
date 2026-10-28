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
 * Engagement report.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_hiveforum\local\forum;
use mod_hiveforum\local\report;

$id = required_param('id', PARAM_INT);
$days = optional_param('period', 30, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$forum = forum::from_cmid($id);
require_login($forum->course, false, $forum->cm);
require_capability('mod/hiveforum:viewreports', $forum->context);
$days = in_array($days, report::PERIODS, true) ? $days : 30;

$url = new moodle_url('/mod/hiveforum/report.php', ['id' => $forum->get_cmid(), 'period' => $days]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('engagementreport', 'hiveforum') . ' | ' . format_string($forum->instance->name));
$PAGE->set_heading(format_string($forum->course->fullname));
$PAGE->add_body_class('mod-hiveforum');
$PAGE->activityheader->disable();

$groupmenu = $forum->get_groupmode() !== NOGROUPS ? groups_print_activity_menu($forum->cm, $url, true) : '';
$groupid = $forum->resolve_active_group();
// In separate groups, users without "access all groups" only see their own groups.
if ($groupid === 0 && $forum->get_groupmode() === SEPARATEGROUPS && !$forum->can_access_all_groups()) {
    throw new moodle_exception('nogroupsaccess', 'hiveforum');
}

$report = new report($forum, $groupid, $days);
$rows = $report->get_rows();

// Table (sortable, downloadable).
$table = new \core_table\flexible_table('mod-hiveforum-report');
$columns = [
    'participant' => get_string('participant', 'hiveforum'),
    'discussions' => get_string('report_discussions', 'hiveforum'),
    'replies' => get_string('report_replies', 'hiveforum'),
    'reactionsgiven' => get_string('report_reactionsgiven', 'hiveforum'),
    'reactionsreceived' => get_string('report_reactionsreceived', 'hiveforum'),
    'accepted' => get_string('report_accepted', 'hiveforum'),
    'streak' => get_string('report_streak', 'hiveforum'),
    'lastpost' => get_string('report_lastpost', 'hiveforum'),
];
$table->define_columns(array_keys($columns));
$table->define_headers(array_values($columns));
$table->define_baseurl($url);
$table->sortable(true, 'replies', SORT_DESC);
$table->is_downloadable(true);
$table->show_download_buttons_at([TABLE_P_BOTTOM]);
$table->set_attribute('class', 'generaltable hf-report-table');
foreach (array_slice(array_keys($columns), 1) as $column) {
    $table->column_class($column, 'text-end');
}
$filename = clean_filename(format_string($forum->instance->name) . '-' . get_string('engagementreport', 'hiveforum'));
$table->is_downloading($download, $filename, get_string('engagementreport', 'hiveforum'));

$table->setup();

// Sort in PHP: the figures are aggregates.
$sorts = $table->get_sort_columns() ?: ['replies' => SORT_DESC];
uasort($rows, function (array $a, array $b) use ($sorts): int {
    foreach ($sorts as $column => $direction) {
        $cmp = $column === 'participant'
            ? strcoll($a['fullname'], $b['fullname'])
            : ($a[$column] ?? 0) <=> ($b[$column] ?? 0);
        if ($cmp !== 0) {
            return $direction === SORT_DESC ? -$cmp : $cmp;
        }
    }
    return strcoll($a['fullname'], $b['fullname']);
});

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('engagementreport', 'hiveforum'));
    if ($groupmenu) {
        echo html_writer::div($groupmenu, 'mb-2');
    }

    // Period selector.
    $periods = [];
    foreach (report::PERIODS as $period) {
        $periods[$period] = $period ? get_string('lastndays', 'hiveforum', $period) : get_string('alltime', 'hiveforum');
    }
    $select = new single_select(new moodle_url($url, ['period' => null]), 'period', $periods, $days, null);
    $select->set_label(get_string('period', 'hiveforum'));
    echo html_writer::div($OUTPUT->render($select), 'mb-3');

    // Key figures.
    $summary = $report->get_summary($rows);
    $tiles = [
        [$summary['discussions'], get_string('report_discussions', 'hiveforum')],
        [$summary['replies'], get_string('report_replies', 'hiveforum')],
        [$summary['active'] . ' / ' . $summary['participants'], get_string('report_active', 'hiveforum')],
        [
            $summary['questions'] ? $summary['resolved'] . ' / ' . $summary['questions'] : '–',
            get_string('report_resolved', 'hiveforum'),
        ],
        [$summary['reactions'], get_string('report_reactionsgiven', 'hiveforum')],
    ];
    echo html_writer::start_div('hf-report-tiles');
    foreach ($tiles as [$value, $label]) {
        echo html_writer::div(
            html_writer::span($value, 'hf-report-tile__value') . html_writer::span($label, 'hf-report-tile__label'),
            'hf-report-tile'
        );
    }
    echo html_writer::end_div();

    // Weekly activity.
    $weekly = $report->get_weekly_activity(array_keys($rows));
    $chart = new \core\chart_bar();
    $chart->set_title(get_string('report_weekly', 'hiveforum'));
    $series = new \core\chart_series(get_string('report_posts', 'hiveforum'), array_values($weekly));
    $series->set_color('#e3a008');
    $chart->add_series($series);
    $chart->set_labels(array_map(fn($t) => userdate($t, get_string('strftimedateshort', 'langconfig')), array_keys($weekly)));
    $chart->set_legend_options(['display' => false]);
    echo html_writer::div($OUTPUT->render($chart), 'hf-report-chart mb-4');

    echo $OUTPUT->heading(get_string('report_participants', 'hiveforum'), 3);
}

foreach ($rows as $row) {
    $name = $table->is_downloading()
        ? $row['fullname']
        : $OUTPUT->user_picture($row['user'], ['courseid' => $forum->course->id, 'size' => 24, 'includefullname' => true]);
    $inactive = $row['posts'] === 0 && $row['reactionsgiven'] === 0;
    $table->add_data([
        $name,
        $row['discussions'],
        $row['replies'],
        $row['reactionsgiven'],
        $row['reactionsreceived'],
        $row['accepted'],
        $table->is_downloading() ? $row['streak'] : ($row['streak'] ? '🔥 ' . $row['streak'] : '0'),
        $row['lastpost'] ? userdate($row['lastpost'], get_string('strftimedatetimeshort', 'langconfig')) : '–',
    ], $inactive && !$table->is_downloading() ? 'hf-report-inactive' : '');
}
$table->finish_output();

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
