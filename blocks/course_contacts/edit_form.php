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
 * Block Course_Contacts edit_form file.
 *
 * @package    block_course_contacts
 * @author     Mark Ward
 *             2016 Richard Oelmann
 * @copyright  Mark Ward
 * @credits    2016 R. Oelmann
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_course_contacts_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // First section configures which contact methods should be displayed.
        $mform->addElement('header', 'configheader', get_string('method', 'block_course_contacts'));

        $mform->addElement('selectyesno', 'config_email', get_string('email', 'block_course_contacts'));
        $mform->setDefault('config_email', 1);
        $mform->setType('config_email', PARAM_INTEGER);

        $mform->addElement('selectyesno', 'config_message', get_string('message', 'block_course_contacts'));
        $mform->setDefault('config_message', 1);
        $mform->setType('config_message', PARAM_INTEGER);

        $mform->addElement('selectyesno', 'config_phone', get_string('phone', 'block_course_contacts'));
        $mform->setDefault('config_phone', 0);
        $mform->setType('config_phone', PARAM_INTEGER);

        // Second section gives options of how to display contacts.
        $mform->addElement('header', 'configheader', get_string('display', 'block_course_contacts'));

        $sortby = array(0 => get_string('alphabetical', 'block_course_contacts'),
                        1 => get_string('recentlyactive', 'block_course_contacts'),
                        2 => get_string('dateenrolled', 'block_course_contacts'));
        $mform->addElement('select', 'config_sortby', get_string('sortby', 'block_course_contacts'), $sortby);
        $mform->setDefault('config_sortby', 0);

        $mform->addElement('selectyesno', 'config_inherit', get_string('inherit', 'block_course_contacts'));
        $mform->setDefault('config_inherit', 0);
        $mform->setType('config_inherit', PARAM_INTEGER);
        $mform->setAdvanced('config_inherit');

        // Third section builds a list of the roles available within this context for selection.
        $mform->addElement('header', 'configheader', get_string('roles', 'block_course_contacts'));

        $roles = array_reverse(get_default_enrol_roles($this->block->context, null), true);
        foreach ($roles as $key => $role) {
            $mform->addElement('selectyesno', 'config_role_'.$key, $role);
            $mform->setDefault('config_role_'.$key, 0);
            if ($key = 3) {
                $mform->setDefault('config_role_'.$key, 1);
            }
            $mform->setType('config_role_'.$key, PARAM_INTEGER);
        }
    }
}
