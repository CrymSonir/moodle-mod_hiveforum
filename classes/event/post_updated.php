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

namespace mod_hiveforum\event;

/**
 * Event post_updated.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_updated extends \core\event\base {
    /**
     * Init.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'hiveforum_posts';
    }

    /**
     * Name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventpostupdated', 'mod_hiveforum');
    }

    /**
     * Description.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' updated the post with id '{$this->objectid}' in the discussion with id '{$this->other['discussionid']}'.";
    }

    /**
     * URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/hiveforum/discuss.php', ['d' => $this->other['discussionid']], 'p' . $this->objectid);
    }

    /**
     * Validate.
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['discussionid'])) {
            throw new \coding_exception('The \'discussionid\' value must be set in other.');
        }
        if (!isset($this->other['forumid'])) {
            throw new \coding_exception('The \'forumid\' value must be set in other.');
        }
    }

    /**
     * Object id mapping for restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'hiveforum_posts', 'restore' => 'hiveforum_post'];
    }

    /**
     * Other mapping for restore.
     *
     * @return array
     */
    public static function get_other_mapping() {
        return [
            'discussionid' => ['db' => 'hiveforum_discussions', 'restore' => 'hiveforum_discussion'],
            'forumid' => ['db' => 'hiveforum', 'restore' => 'hiveforum'],
        ];
    }
}
