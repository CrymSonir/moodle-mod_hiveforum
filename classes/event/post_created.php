<?php
namespace mod_hiveforum\event;

defined('MOODLE_INTERNAL') || die();

class post_created extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'hiveforum_posts';
    }

    public static function get_name() {
        return get_string('eventpostcreated', 'mod_hiveforum');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' created post '{$this->objectid}'.";
    }
}
