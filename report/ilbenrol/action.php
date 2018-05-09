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
 * Wrapper script redirecting user operations to correct destination.
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package user
 */

require_once("../../config.php");
require("{$CFG->dirroot}/enrol/locallib.php");

$action = required_param('formaction', PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$users = optional_param_array('user', array(), PARAM_INT);
$returnto = required_param('returnto', PARAM_TEXT);

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad');
}

if (count($users) == 0) {
    print_error('invalidformdata', '', $returnto);
}

$role_confirmed = get_config(null, 'report_ilbenrol_confirmed');
$role_revoked   = get_config(null, 'report_ilbenrol_revoked');

if (!$role_confirmed or !$role_revoked) {
    print_error('invalidargorconf');
}

$course = $DB->get_record('course',array('id'=>$id));

if (!$course) {
    print_error('invalidcourseid');
}

require_login($course);

$context = context_course::instance($course->id);
$manager = new course_enrolment_manager($PAGE, $course);
$roles = $manager->get_all_roles();
$PAGE->set_heading($course->fullname);
$PAGE->set_url('/user/action.php', array('action'=>$action,'id'=>$id));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('title','report_ilbenrol'));
$PAGE->set_heading($course->fullname);

require_login($course);
require_capability('report/ilbenrol:view',$context);

if (!array_key_exists($role_confirmed, $roles) or !array_key_exists($role_revoked, $roles)) {
    print_error('invalidargorconf');
}

echo $OUTPUT->header();

if ($action === 'confirmenrol') {
    $from_role = $roles[$role_revoked];
    $to_role = $roles[$role_confirmed];
} else if ($action === 'revokeenrol') {
    $from_role = $roles[$role_confirmed];
    $to_role = $roles[$role_revoked];
} else {
    print_error('unknownuseraction');
}

echo "<ul>";
foreach ($users as $userid=>$nothing) {
    $user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
    echo '<li>' . get_string('changeduser', 'report_ilbenrol', array('user'=>fullname($user), 'from'=>$from_role->localname, 'to'=>$to_role->localname)) . '</li>';
    $manager->assign_role_to_user($to_role->id, $user->id);
    $manager->unassign_role_from_user($user->id, $from_role->id);
}

echo '</ul><br/>';

echo "<a href=\"{$returnto}\">".get_string('return', 'report_ilbenrol').'</a>';
echo $OUTPUT->footer();
