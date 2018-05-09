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

$id = required_param('course', PARAM_INT);

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad');
}

$role_revoked = get_config(null, 'report_ilbenrol_revoked');

if (!$role_revoked) {
    print_error('invalidargorconf');
}

$course = $DB->get_record('course',array('id'=>$id));

if (!$course) {
    print_error('invalidcourseid');
}

$context = context_course::instance($course->id);
$manager = new course_enrolment_manager($PAGE, $course, null, $role_revoked);
$instances = $manager->get_enrolment_instances();
$contextids = $context->get_parent_context_ids(true);

// Prepare select
list($in_instances, $param_instances) = $DB->get_in_or_equal(array_keys($instances), SQL_PARAMS_NAMED);
list($in_contexts, $param_contexts) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
$params = $param_instances + $param_contexts;

$sql = "UPDATE {user_enrolments} SET status = 1 WHERE id in (
SELECT DISTINCT ue.id
FROM {user} u
  JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid {$in_instances})
  JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid {$in_contexts})
WHERE ra.roleid = {$role_revoked})";

$PAGE->set_heading($course->fullname);
$PAGE->set_url('/user/action.php', array('action'=>$action,'id'=>$id));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('title','report_ilbenrol'));
$PAGE->set_heading($course->fullname);

require_login($course);
require_capability('report/ilbenrol:view',$context);

echo $OUTPUT->header();

if ($DB->execute($sql, $params)) {
    echo $OUTPUT->box(get_string('suspendsuccess', 'report_ilbenrol'));
} else {
    echo $OUTPUT->container(get_string('suspenderror', 'report_ilbenrol'), 'errorbox errorboxcontent');
}

echo '<a href="index.php?course='.$course->id.'">'.get_string('return', 'report_ilbenrol').'</a>';
echo $OUTPUT->footer();

