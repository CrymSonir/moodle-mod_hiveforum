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
 * Core callbacks for mod_hiveforum.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns whether a given feature is supported.
 *
 * @param string $feature FEATURE_xx constant
 * @return mixed
 */
function hiveforum_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION_HAS_RULES => false,
        FEATURE_GRADE_HAS_GRADE => false,
        // Groups are not implemented yet: do not advertise them.
        FEATURE_GROUPS => false,
        FEATURE_GROUPINGS => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_COLLABORATION,
        FEATURE_MOD_OTHERPURPOSE => MOD_PURPOSE_COMMUNICATION,
        default => null,
    };
}

/**
 * Save a new instance of hiveforum into the database.
 *
 * @param stdClass $data Form data
 * @param mod_hiveforum_mod_form|null $mform
 * @return int new instance id
 */
function hiveforum_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    return $DB->insert_record('hiveforum', $data);
}

/**
 * Update an instance of hiveforum in the database.
 *
 * @param stdClass $data Form data
 * @param mod_hiveforum_mod_form|null $mform
 * @return bool
 */
function hiveforum_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('hiveforum', $data);
}

/**
 * Delete an instance of hiveforum, and all related data.
 *
 * @param int $id Instance id
 * @return bool
 */
function hiveforum_delete_instance($id) {
    global $DB;

    if (!$hiveforum = $DB->get_record('hiveforum', ['id' => $id])) {
        return false;
    }

    $discussionids = $DB->get_fieldset_select('hiveforum_discussions', 'id', 'hiveforum = ?', [$id]);
    foreach (array_chunk($discussionids, 500) as $chunk) {
        \mod_hiveforum\local\api::delete_discussion_data($chunk);
    }
    $DB->delete_records('hiveforum_streaks', ['hiveforum' => $id]);
    $DB->delete_records('hiveforum', ['id' => $id]);

    // Files are removed by the core when the context is deleted.
    return true;
}

/**
 * Returns a small object with summary information about what a user has done.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param cm_info|stdClass $mod
 * @param stdClass $hiveforum
 * @return stdClass|null
 */
function hiveforum_user_outline($course, $user, $mod, $hiveforum) {
    global $DB;

    $sql = "SELECT COUNT(p.id) AS cnt, MAX(p.timecreated) AS lasttime
              FROM {hiveforum_posts} p
              JOIN {hiveforum_discussions} d ON d.id = p.discussion
             WHERE d.hiveforum = :forum AND p.userid = :userid";
    $stats = $DB->get_record_sql($sql, ['forum' => $hiveforum->id, 'userid' => $user->id]);
    if (empty($stats->cnt)) {
        return null;
    }

    $result = new stdClass();
    $result->info = get_string('postscount', 'hiveforum', (int) $stats->cnt);
    $result->time = (int) $stats->lasttime;
    return $result;
}

/**
 * Serves files attached to posts (images inserted with the full editor).
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool|void false if file not found
 */
function hiveforum_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/hiveforum:view', $context);

    if ($filearea !== 'post') {
        return false;
    }

    $itemid = (int) array_shift($args);

    // Make sure the post belongs to this activity.
    $sql = "SELECT p.id
              FROM {hiveforum_posts} p
              JOIN {hiveforum_discussions} d ON d.id = p.discussion
             WHERE p.id = :postid AND d.hiveforum = :forum";
    if (!$DB->record_exists_sql($sql, ['postid' => $itemid, 'forum' => $cm->instance])) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_hiveforum', 'post', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules.
 *
 * @param cm_info|stdClass $cm
 * @return array
 */
function mod_hiveforum_get_completion_active_rule_descriptions($cm) {
    return [];
}

/**
 * Data to display on the course page for the activity (unread badge for the current user).
 *
 * @param cm_info $cm
 */
function hiveforum_cm_info_view(cm_info $cm) {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return;
    }
    $unread = \mod_hiveforum\local\api::count_unread((int) $cm->instance, (int) $USER->id);
    if ($unread > 0) {
        $cm->set_after_link(html_writer::span(
            get_string('unreadcount', 'hiveforum', $unread),
            'badge rounded-pill text-bg-primary ms-2'
        ));
    }
}

/**
 * Reset course data.
 *
 * @param stdClass $data
 * @return array status
 */
function hiveforum_reset_userdata($data) {
    global $DB;

    $status = [];
    if (empty($data->reset_hiveforum_all)) {
        return $status;
    }

    $forumids = $DB->get_fieldset_select('hiveforum', 'id', 'course = ?', [$data->courseid]);
    foreach ($forumids as $forumid) {
        $discussionids = $DB->get_fieldset_select('hiveforum_discussions', 'id', 'hiveforum = ?', [$forumid]);
        foreach (array_chunk($discussionids, 500) as $chunk) {
            \mod_hiveforum\local\api::delete_discussion_data($chunk);
        }
        $DB->delete_records('hiveforum_streaks', ['hiveforum' => $forumid]);
        $cm = get_coursemodule_from_instance('hiveforum', $forumid, $data->courseid);
        if ($cm) {
            get_file_storage()->delete_area_files(context_module::instance($cm->id)->id, 'mod_hiveforum', 'post');
        }
    }

    $status[] = [
        'component' => get_string('modulenameplural', 'hiveforum'),
        'item' => get_string('resetall', 'hiveforum'),
        'error' => false,
    ];
    return $status;
}

/**
 * Course reset form elements.
 *
 * @param MoodleQuickForm $mform
 */
function hiveforum_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'hiveforumheader', get_string('modulenameplural', 'hiveforum'));
    $mform->addElement('advcheckbox', 'reset_hiveforum_all', get_string('resetall', 'hiveforum'));
}

/**
 * Course reset form defaults.
 *
 * @param stdClass $course
 * @return array
 */
function hiveforum_reset_course_form_defaults($course) {
    return ['reset_hiveforum_all' => 1];
}
