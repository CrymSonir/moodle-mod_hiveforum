<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Returns whether a given feature is supported.
 */
function hiveforum_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Save a new instance of hiveforum into the database.
 */
function hiveforum_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    return $DB->insert_record('hiveforum', $data);
}

/**
 * Update an instance of hiveforum in the database.
 */
function hiveforum_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('hiveforum', $data);
}

/**
 * Delete an instance of hiveforum, and all related discussions/posts/reactions.
 */
function hiveforum_delete_instance($id) {
    global $DB;

    if (!$hiveforum = $DB->get_record('hiveforum', ['id' => $id])) {
        return false;
    }

    $discussionids = $DB->get_fieldset_select('hiveforum_discussions', 'id', 'hiveforum = ?', [$id]);
    if ($discussionids) {
        [$insql, $params] = $DB->get_in_or_equal($discussionids);
        $postids = $DB->get_fieldset_select('hiveforum_posts', 'id', "discussion $insql", $params);
        if ($postids) {
            [$postinsql, $postparams] = $DB->get_in_or_equal($postids);
            $DB->delete_records_select('hiveforum_reactions', "postid $postinsql", $postparams);
        }
        $DB->delete_records_select('hiveforum_posts', "discussion $insql", $params);
        $DB->delete_records_select('hiveforum_tags', "discussion $insql", $params);
        $DB->delete_records_select('hiveforum_reads', "discussion $insql", $params);
    }
    $DB->delete_records('hiveforum_discussions', ['hiveforum' => $id]);
    $DB->delete_records('hiveforum_streaks', ['hiveforum' => $id]);
    $DB->delete_records('hiveforum', ['id' => $id]);

    return true;
}

/**
 * Returns a small object with summary information about what a user
 * has done with a given particular instance of this module.
 */
function hiveforum_user_outline($course, $user, $mod, $hiveforum) {
    global $DB;

    $count = $DB->count_records('hiveforum_posts', ['userid' => $user->id]);
    $result = new stdClass();
    $result->info = get_string('replies', 'hiveforum') . ': ' . $count;
    $result->time = time();
    return $result;
}

/**
 * File serving - required because we use FEATURE_MOD_INTRO / editor areas.
 */
function hiveforum_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_login($course, true, $cm);

    if ($filearea !== 'post') {
        send_file_not_found();
    }

    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_hiveforum', 'post', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Extends the course navigation with a shortcut, if desired. Left minimal by default.
 */
function hiveforum_extend_navigation($navref, $course, $module, $cm) {
    // Intentionally left blank - default activity link is sufficient.
}
