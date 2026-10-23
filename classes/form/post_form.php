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

namespace mod_hiveforum\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Full editor form (rich text + files) to start a discussion or edit a post.
 *
 * Custom data:
 *  - mode: 'new' | 'edit'
 *  - withtitle: show the title (new discussion, or editing an opening post)
 *  - allowtags: show the tags field
 *  - editoroptions: editor options
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_form extends \moodleform {
    /**
     * Definition.
     */
    public function definition() {
        $mform = $this->_form;
        $mode = $this->_customdata['mode'];
        $withtitle = !empty($this->_customdata['withtitle']);

        $mform->addElement('hidden', 'hiveforum');
        $mform->setType('hiveforum', PARAM_INT);
        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        if ($withtitle) {
            $mform->addElement('text', 'name', get_string('discussiontitle', 'hiveforum'), ['size' => 60]);
            $mform->setType('name', PARAM_TEXT);
            $mform->addRule('name', null, 'required', null, 'client');
            $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        }

        if ($mode === 'new') {
            $mform->addElement(
                'advcheckbox',
                'isquestion',
                get_string('askquestion', 'hiveforum'),
                get_string('askquestion_desc', 'hiveforum')
            );
        }

        $mform->addElement(
            'editor',
            'message_editor',
            get_string('message', 'hiveforum'),
            null,
            $this->_customdata['editoroptions']
        );
        $mform->setType('message_editor', PARAM_RAW);
        $mform->addRule('message_editor', null, 'required', null, 'client');

        if ($withtitle && !empty($this->_customdata['allowtags'])) {
            $mform->addElement('text', 'tags', get_string('tags', 'hiveforum'), ['size' => 60]);
            $mform->setType('tags', PARAM_TEXT);
            $mform->addHelpButton('tags', 'tags', 'hiveforum');
        }

        $this->add_action_buttons(true, $mode === 'new'
            ? get_string('publishdiscussion', 'hiveforum')
            : get_string('savechanges'));
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
        if (isset($data['name']) && trim($data['name']) === '') {
            $errors['name'] = get_string('errornameempty', 'hiveforum');
        }
        $text = $data['message_editor']['text'] ?? '';
        if (trim(html_to_text($text, 0, false)) === '' && !preg_match('/<img\b/i', $text)) {
            $errors['message_editor'] = get_string('errormessageempty', 'hiveforum');
        }
        return $errors;
    }
}
