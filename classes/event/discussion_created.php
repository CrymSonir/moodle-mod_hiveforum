<?php
namespace mod_hiveforum\event;

defined('MOODLE_INTERNAL') || die();

class discussion_created extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'hiveforum_discussions';
    }

    public static function get_name() {
        return get_string('eventdiscussioncreated', 'mod_hiveforum');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' created discussion '{$this->objectid}' " .
               "in the hiveforum with course module id '{$this->contextinstanceid}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/hiveforum/view.php', ['id' => $this->contextinstanceid]);
    }
}
