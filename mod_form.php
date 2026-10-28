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
 * Activity settings form.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity settings form.
 */
class mod_hiveforum_mod_form extends moodleform_mod {
    /**
     * Definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'postingsettings', get_string('postingsettings', 'hiveforum'));

        $mform->addElement('select', 'studentposting', get_string('studentposting', 'hiveforum'), [
            'all' => get_string('studentposting_all', 'hiveforum'),
            'discussions' => get_string('studentposting_discussions', 'hiveforum'),
            'questions' => get_string('studentposting_questions', 'hiveforum'),
            'none' => get_string('studentposting_none', 'hiveforum'),
        ]);
        $mform->addHelpButton('studentposting', 'studentposting', 'hiveforum');
        $mform->setDefault('studentposting', 'all');

        $mform->addElement('header', 'hiveforumsettings', get_string('engagementsettings', 'hiveforum'));

        $mform->addElement('advcheckbox', 'allowreactions', get_string('allowreactions', 'hiveforum'));
        $mform->addHelpButton('allowreactions', 'allowreactions', 'hiveforum');
        $mform->setDefault('allowreactions', 1);

        $mform->addElement('advcheckbox', 'allowtags', get_string('allowtags', 'hiveforum'));
        $mform->addHelpButton('allowtags', 'allowtags', 'hiveforum');
        $mform->setDefault('allowtags', 1);

        $mform->addElement('header', 'subscriptionsettings', get_string('subscriptionsettings', 'hiveforum'));

        $mform->addElement('select', 'subscriptionmode', get_string('subscriptionmode', 'hiveforum'), [
            \mod_hiveforum\local\subscriptions::OPTIONAL => get_string('subscriptionoptional', 'hiveforum'),
            \mod_hiveforum\local\subscriptions::FORCED => get_string('subscriptionforced', 'hiveforum'),
            \mod_hiveforum\local\subscriptions::AUTO => get_string('subscriptionauto', 'hiveforum'),
            \mod_hiveforum\local\subscriptions::DISABLED => get_string('subscriptiondisabled', 'hiveforum'),
        ]);
        $mform->addHelpButton('subscriptionmode', 'subscriptionmode', 'hiveforum');
        $mform->setDefault('subscriptionmode', (int) get_config('mod_hiveforum', 'defaultsubscriptionmode'));

        $mform->addElement('advcheckbox', 'notifications', get_string('notificationssetting', 'hiveforum'));
        $mform->addHelpButton('notifications', 'notificationssetting', 'hiveforum');
        $mform->setDefault('notifications', 1);
        if (!get_config('mod_hiveforum', 'enablenotifications')) {
            $mform->addElement('static', 'notificationsoff', '', get_string('notificationssitedisabled', 'hiveforum'));
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Custom completion rules (number of discussions, replies, posts, accepted answers).
     *
     * @return string[] element names
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();
        $elements = [];
        foreach (self::completion_rules() as $rule) {
            $enabled = $rule . 'enabled' . $suffix;
            $value = $rule . $suffix;
            $group = [
                $mform->createElement('checkbox', $enabled, '', get_string($rule, 'hiveforum')),
                $mform->createElement('text', $value, '', ['size' => 3]),
            ];
            $mform->setType($value, PARAM_INT);
            $groupname = $rule . 'group' . $suffix;
            $mform->addGroup($group, $groupname, '', ' ', false);
            $mform->hideIf($value, $enabled, 'notchecked');
            $elements[] = $groupname;
        }
        return $elements;
    }

    /**
     * Rule names.
     *
     * @return string[]
     */
    private static function completion_rules(): array {
        return ['completiondiscussions', 'completionreplies', 'completionposts', 'completionaccepted'];
    }

    /**
     * Is a custom completion rule enabled?
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        foreach (self::completion_rules() as $rule) {
            if (!empty($data[$rule . 'enabled' . $suffix]) && !empty($data[$rule . $suffix])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Prepare the checkboxes of the completion rules.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        $suffix = $this->get_suffix();
        foreach (self::completion_rules() as $rule) {
            $defaultvalues[$rule . 'enabled' . $suffix] = !empty($defaultvalues[$rule . $suffix]) ? 1 : 0;
            if (empty($defaultvalues[$rule . $suffix])) {
                $defaultvalues[$rule . $suffix] = 1;
            }
        }
    }

    /**
     * Reset the rules that are not ticked.
     *
     * @param stdClass $data
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix} ?? 0;
            $automatic = !empty($completion) && $completion == COMPLETION_TRACKING_AUTOMATIC;
            foreach (self::completion_rules() as $rule) {
                if (empty($data->{$rule . 'enabled' . $suffix}) || !$automatic) {
                    $data->{$rule . $suffix} = 0;
                }
            }
        }
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $suffix = $this->get_suffix();
        foreach (self::completion_rules() as $rule) {
            if (!empty($data[$rule . 'enabled' . $suffix]) && (int) ($data[$rule . $suffix] ?? 0) < 1) {
                $errors[$rule . 'group' . $suffix] = get_string('completionpositive', 'hiveforum');
            }
        }
        return $errors;
    }
}
