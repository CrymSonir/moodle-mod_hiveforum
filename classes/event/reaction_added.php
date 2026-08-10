<?php
namespace mod_hiveforum\event;

defined('MOODLE_INTERNAL') || die();

class reaction_added extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'hiveforum_reactions';
    }

    public static function get_name() {
        return get_string('eventreactionadded', 'mod_hiveforum');
    }

    public function get_description() {
        $type = $this->other['reactiontype'] ?? 'unknown';
        return "The user with id '{$this->userid}' reacted with '{$type}' on post '{$this->objectid}'.";
    }
}
