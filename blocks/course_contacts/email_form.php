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
 * Block Course_Contacts email_form file.
 *
 * @package    block_course_contacts
 * @author     Mark Ward
 *             2016 Richard Oelmann
 * @copyright  Mark Ward
 * @credits    2016 R. Oelmann
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');
class simple_email_form extends moodleform {
    public function definition() {
        global $CFG, $USER, $COURSE, $OUTPUT;

        $mform =& $this->_form;

        $mailto = $this->_customdata['mailto'];
        $touid = $this->_customdata['touid'];

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        $mform->addElement('header', 'simpleemail_topsection',
        get_string('sendanemail', 'block_course_contacts'));

        $mform->addElement('hidden', 'mailto', $mailto);
        $mform->addElement('hidden', 'cid', $COURSE->id);
        $mform->addElement('static', 'emailinfo', '',
            str_replace('{recipient}', strtolower($mailto),
            get_string('emailinfo', 'block_course_contacts')));
        $mform->addElement('html', '<br />');
        $mform->addElement('static', 'from', get_string('from', 'block_course_contacts'), strtolower($USER->email));
        $mform->addElement('static', 'to', get_string('to', 'block_course_contacts'), strtolower($mailto));

        $mform->addElement('text', 'subject', get_string('subject', 'block_course_contacts'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->setDefault('subject', $COURSE->fullname);
        $mform->addRule('subject', null, 'required');

        $mform->addElement('editor', 'message', get_string('message', 'block_course_contacts'));
        $mform->addRule('message', null, 'required');

        if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey) && $CFG->block_co_co_recaptcha) {
            $mform->addElement('recaptcha', 'recaptcha_element', get_string('recaptcha', 'auth'));
        }

        $buttons = array();
        $buttons[] =& $mform->createElement('submit', 'send', get_string('send', 'block_course_contacts'));
        $buttons[] =& $mform->createElement('cancel', 'cancel', get_string('cancel'));

        $mform->addGroup($buttons, 'buttons', '', array(' '), false);
    }

    public function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);
        if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey) && $CFG->block_co_co_recaptcha) {
            $recaptchaelement = $this->_form->getElement('recaptcha_element');
            if (!empty($this->_form->_submitValues['recaptcha_challenge_field'])) {
                $challengefield = $this->_form->_submitValues['recaptcha_challenge_field'];
                $responsefield = $this->_form->_submitValues['recaptcha_response_field'];
                if (true !== ($result = $recaptchaelement->verify($challengefield, $responsefield))) {
                    $errors['recaptcha'] = $result;
                }
            } else {
                $errors['recaptcha'] = get_string('missingrecaptchachallengefield');
            }
        }
        return $errors;
    }

}
