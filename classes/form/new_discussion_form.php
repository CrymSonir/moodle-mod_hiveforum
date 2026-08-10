<?php
namespace mod_hiveforum\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class new_discussion_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $hiveforumid = $this->_customdata['hiveforumid'];

        $mform->addElement('hidden', 'hiveforum', $hiveforumid);
        $mform->setType('hiveforum', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'isquestion', get_string('question', 'hiveforum'));

        $mform->addElement('editor', 'message', get_string('message', 'moodle'));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', null, 'required', null, 'client');

        $mform->addElement('text', 'tags', get_string('tags', 'hiveforum'));
        $mform->setType('tags', PARAM_TEXT);
        $mform->addHelpButton('tags', 'tags', 'hiveforum');

        $this->add_action_buttons(true, get_string('newdiscussion', 'hiveforum'));
    }
}
