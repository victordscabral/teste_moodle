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
 * Form de inserção de CPF
 *
 * @package    core
 * @subpackage auth
 * @copyright  2006 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

class login_change_cpf_form extends moodleform {

    function definition() {
        global $USER, $CFG;

        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        $mform->addElement('header', 'changecpf', 'Informe o CPF', '');

        // visible elements
        $mform->addElement('static', 'username', get_string('username'), $USER->username);

        //$mform->addElement('password', 'password', get_string('oldpassword'));
        //$mform->addRule('password', get_string('required'), 'required', null, 'client');
        //$mform->setType('password', PARAM_RAW);

        $mform->addElement('text', 'newcpf', 'Seu CPF');
        $mform->addRule('newcpf', get_string('required'), 'required', null, 'client');
        $mform->addRule('newcpf', 'O CPF deve conter apenas números.', 'numeric', null, 'client');     
        $mform->addRule('newcpf', 'O CPF deve conter exatamente 11 dígitos.', 'maxlength', 11, 'client');
        $mform->addRule('newcpf', 'O CPF deve conter exatamente 11 dígitos.', 'minlength', 11, 'client');      
        $mform->setType('newcpf', PARAM_RAW);

        // hidden optional params
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // buttons - o false indica que não pode ser cancelado 
        $this->add_action_buttons(false);

    }

/// perform extra password change validation
    function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);

        // ignore submitted username
	

	if (!user_cpf_validation($USER->id, $data['newcpf'])) {
		$errors['newcpf'] = 'Informe um número de CPF válido!';
	}
        if (!user_cpf_is_available($USER->id, $data['newcpf'])) {
            $errors['newcpf'] = 'Já há um usuário cadastrado no Saberes com este CPF!' . 
		'<br>Por favor, entre em contato com ilbead@senado.leg.br para regularizar ' . 
		'seu cadastro e recuperar acesso ao Saberes.';
        }
        return $errors;
    }
}
