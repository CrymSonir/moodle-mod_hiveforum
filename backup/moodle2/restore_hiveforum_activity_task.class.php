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
 * Restore task for mod_hiveforum.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/hiveforum/backup/moodle2/restore_hiveforum_stepslib.php');

/**
 * Restore task for mod_hiveforum.
 */
class restore_hiveforum_activity_task extends restore_activity_task {
    /**
     * No specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Single structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_hiveforum_activity_structure_step('hiveforum_structure', 'hiveforum.xml'));
    }

    /**
     * Contents to decode.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('hiveforum', ['intro'], 'hiveforum'),
            new restore_decode_content('hiveforum_posts', ['message'], 'hiveforum_post'),
        ];
    }

    /**
     * Decoding rules.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('HIVEFORUMINDEX', '/mod/hiveforum/index.php?id=$1', 'course'),
            new restore_decode_rule('HIVEFORUMVIEWBYID', '/mod/hiveforum/view.php?id=$1', 'course_module'),
            new restore_decode_rule('HIVEFORUMDISCUSSIONVIEW', '/mod/hiveforum/discuss.php?d=$1', 'hiveforum_discussion'),
        ];
    }

    /**
     * Restore log rules (legacy logs are not used).
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Restore log rules for the course (legacy logs are not used).
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
