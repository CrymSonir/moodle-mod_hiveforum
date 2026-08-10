<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_hiveforum_mod_form extends moodleform_mod {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('modulename', 'hiveforum') . ' - ' . get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'hiveforumsettings', get_string('pluginname', 'hiveforum'));

        $mform->addElement('advcheckbox', 'allowreactions', get_string('hiveforum:react', 'hiveforum'));
        $mform->setDefault('allowreactions', 1);

        $mform->addElement('advcheckbox', 'allowtags', get_string('tags', 'hiveforum'));
        $mform->setDefault('allowtags', 1);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
