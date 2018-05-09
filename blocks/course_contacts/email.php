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
 * Block Course_Contacts email file.
 *
 * @package    block_course_contacts
 * @author     Mark Ward
 *             2016 Richard Oelmann
 * @copyright  Mark Ward
 * @credits    2016 R. Oelmann
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('email_form.php');
require_login();
$courseid = optional_param('cid', 0, PARAM_INT);
$touid = optional_param('touid', 0, PARAM_INT);
$messages = array();

// Sort out the course.
if ($courseid <= 0) {
    $courseid = $SESSION->block_course_contacts_lastcourse;
}
$course = $DB->get_record('course', array('id' => $courseid));
if (!$course) {
    $messages[] = get_string('no_course', 'block_course_contacts');
}
$SESSION->block_course_contacts_lastcourse = $course->id;
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $courseid);

// Get the email address for our contact.
if ($touid <= 0) {
    $touid = $SESSION->block_course_contacts_lastrecipient;
}
if (!is_enrolled($context, $touid, null, true)) {
    $messages[] = get_string('recipient_not_enrolled', 'block_course_contacts');
}
$mailto = $DB->get_record('user', array('id' => $touid));
$SESSION->block_course_contacts_lastrecipient = $touid;

$modname = get_string('pluginname', 'block_course_contacts');
$header = get_string('sendanemail', 'block_course_contacts');

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->navbar->add($modname);
$PAGE->navbar->add($header);
$PAGE->set_title($modname . ': '. $header);
$PAGE->set_heading($modname . ': '.$header);
$PAGE->set_url('/course/view.php?id='.$courseid);

$form = new simple_email_form(null, array(
    'mailto' => $mailto->email,
    'touid' => $touid,
    'cid' => $course->id
));

if ($form->is_cancelled()) {
    unset($SESSION->block_course_contacts_lastcourse);
    unset($SESSION->block_course_contacts_lastrecipient);
    redirect(new moodle_url('/course/view.php?id='.$courseid));
} else if ($data = $form->get_data()) {
    $email = $data;
    $email->message = $email->message['text'];
    $result = false;
    if ($data->mailto == $mailto->email && $data->cid == $courseid) {
        $result = email_to_user($mailto, $USER, $email->subject, strip_tags($email->message), $email->message);
    } else {
        // debugging($data->mailto.' == '.$mailto->email);
        // debugging($data->cid.' == '.$courseid);
        $messages[] = get_string('invalid_request', 'block_course_contacts');
    }

    if ($result) {
        $messages[] = get_string('email_sent', 'block_course_contacts');
    } else {
        $messages[] = get_string('email_not_sent', 'block_course_contacts');
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($modname);

// Print out messages.
if (count($messages) > 0) {
    echo html_writer::start_tag('div', array('class' => 'cocoemailmsgs'));
    foreach ($messages as $message) {
        echo $OUTPUT->notification($message);
    }
    $url = new moodle_url('/course/view.php?id='.$courseid);
    echo html_writer::link($url, get_string('return_to_course', 'block_course_contacts'), array('id' => 'returnlink'));
    echo html_writer::end_tag('div');
} else {
    if (!$data) {
        $form->display();
    }
}
echo $OUTPUT->footer();
