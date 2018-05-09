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
 * Change CPF page.
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once('change_cpf_form.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/webservice/lib.php');

$id     = optional_param('id', SITEID, PARAM_INT); // current course
$return = optional_param('return', 0, PARAM_BOOL); // redirect after password change

$systemcontext = context_system::instance();

//HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$PAGE->set_url('/login/change_cpf.php', array('id'=>$id));

$PAGE->set_context($systemcontext);

if ($return) {
    // this redirect prevents security warning because https can not POST to http pages
    if (empty($SESSION->wantsurl)
            or stripos(str_replace('https://', 'http://', $SESSION->wantsurl), str_replace('https://', 'http://', $CFG->wwwroot.'/login/change_cof.php')) === 0) {
        $returnto = "$CFG->wwwroot/user/preferences.php?userid=$USER->id&course=$id";
    } else {
        $returnto = $SESSION->wantsurl;
    }
    unset($SESSION->wantsurl);

    redirect($returnto);
}

$strparticipants = get_string('participants');

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}

// require proper login; guest user can not change cpf
if (!isloggedin() or isguestuser()) {
    if (empty($SESSION->wantsurl)) {
        $SESSION->wantsurl = $CFG->httpswwwroot.'/login/change_cpf.php';
    }
    redirect(get_login_url());
}

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_pagelayout('admin');
$PAGE->set_course($course);

// do not require change own password cap if change forced
if (!get_user_preferences('auth_forcecpfchange', false)) {
    if(!user_can_change_cpf($USER->id))
        redirect($CFG->wwwroot); 
}

// do not allow "Logged in as" users to change any passwords
if (\core\session\manager::is_loggedinas()) {
    print_error('cannotcallscript');
}

// load the appropriate auth plugin
$userauth = get_auth_plugin($USER->auth);


if ($changeurl = $userauth->change_password_url()) {
    // this internal scrip not used
    redirect($changeurl);
}

$mform = new login_change_cpf_form();
$mform->set_data(array('id'=>$course->id));

$navlinks = array();
$navlinks[] = array('name' => $strparticipants, 'link' => "$CFG->wwwroot/user/index.php?id=$course->id", 'type' => 'misc');

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/user/preferences.php?userid='.$USER->id.'&amp;course='.$course->id);
} else if ($data = $mform->get_data()) {

    if (!$userauth->user_update_cpf($USER, $data->newcpf)) {
        print_error('errorpasswordupdate', 'auth');
    }

    // Reset login lockout - we want to prevent any accidental confusion here. 
    login_unlock_account($USER);

    // register success changing password
    unset_user_preference('auth_forcecpfchange', $USER);

    $strpasswordchanged = 'Seu CPF foi cadastrado com sucesso!';

    $fullname = fullname($USER, true);

    $PAGE->set_title($strpasswordchanged);
    $PAGE->set_heading(fullname($USER));
    echo $OUTPUT->header();

    notice($strpasswordchanged, new moodle_url($PAGE->url, array('return'=>1)));

    echo $OUTPUT->footer();
    exit;
}

// make sure we really are on the https page when https login required
$PAGE->verify_https_required();

$strchangepassword = 'Informe seu CPF';

$fullname = fullname($USER, true);

$PAGE->set_title($strchangepassword);
$PAGE->set_heading($fullname);
echo $OUTPUT->header();

if (get_user_preferences('auth_forcecpfchange')) {
    echo $OUTPUT->notification('Você deve cadastrar seu CPF antes de continuar.');
}
$mform->display();
echo $OUTPUT->footer(); 
