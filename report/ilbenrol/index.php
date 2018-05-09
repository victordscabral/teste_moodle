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
 * ILB Enrol Confirmation reports
 *
 * @package    report
 * @subpackage ilbenrol
 * @copyright  2008 Sam Marshall
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require("{$CFG->dirroot}/enrol/locallib.php");
require_once('report_form.php');
require_once("$CFG->dirroot/user/profile/lib.php");

define('ILBENROL_REPORT_PAGE', 25);

// Get course
$id = required_param('course',PARAM_INT);
$course = $DB->get_record('course',array('id'=>$id));

if (!$course) {
    print_error('invalidcourseid');
}

$context = context_course::instance($course->id);

// Sort (default lastname, optionally firstname)
$sort = optional_param('sort','timecreated',PARAM_ALPHA);

// CSV format
$format = optional_param('format','',PARAM_ALPHA);
$excel = $format == 'excelcsv';
$csv = $format == 'csv' || $excel;

// Paging
$start   = optional_param('start', 0, PARAM_INT);
$sifirst = optional_param('sifirst', 'all', PARAM_ALPHA);
$silast  = optional_param('silast', 'all', PARAM_ALPHA);

// User profile fields to filter
$filterfields = explode(',', get_config(null, 'report_ilbenrol_filterfields'));
$filterfields = $DB->get_records_list('user_info_field', 'shortname', $filterfields);

function csv_quote($value) {
    global $excel;
    if ($excel) {
        return textlib::convert('"'.str_replace('"',"'",$value).'"','UTF-8','UTF-16LE');
    } else {
        return '"'.str_replace('"',"'",$value).'"';
    }
}

$url = new moodle_url('/report/ilbenrol/index.php', array('course'=>$id));
if ($sort !== '') {
    $url->param('sort', $sort);
}
if ($format !== '') {
    $url->param('format', $format);
}
if ($start !== 0) {
    $url->param('start', $start);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');

require_login($course);

// Check basic permission
require_capability('report/ilbenrol:view',$context);

// Get group mode
$group = groups_get_course_group($course,true); // Supposed to verify group
if ($group===0 && $course->groupmode==SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups',$context);
}

// Get data for user filtering
$manager = new course_enrolment_manager($PAGE, $course);
$instances = $manager->get_enrolment_instances();
$contextids = $context->get_parent_context_ids(true);

// Roles
$allroles = $manager->get_all_roles();
$role_confirmed = get_config(null, 'report_ilbenrol_confirmed');
$role_revoked   = get_config(null, 'report_ilbenrol_revoked');

if (!$role_confirmed or !$role_revoked) {
    print_error('invalidargorconf');
}

$roles = array($role_confirmed=>$allroles[$role_confirmed], $role_revoked=>$allroles[$role_revoked]);
$mform = new filter_form($course->id, $filterfields, $roles, $instances);

// Generate where clause
$where = array();
$where_params = array();

if ($sifirst !== 'all') {
    $where[] = $DB->sql_like('u.firstname', ':sifirst', false);
    $where_params['sifirst'] = $sifirst.'%';
}

if ($silast !== 'all') {
    $where[] = $DB->sql_like('u.lastname', ':silast', false);
    $where_params['silast'] = $silast.'%';
}

$whereors = array();

if ($formdata = $mform->get_data()) {
    foreach ($filterfields as $field) {
        $shortname = $field->shortname;
        if (array_key_exists($shortname, $formdata)) {
            list($in_options, $param_options) = $DB->get_in_or_equal(array_keys($formdata->$shortname), SQL_PARAMS_NAMED);
            $whereors[] = "(uid.fieldid = {$field->id} AND uid.data {$in_options})";
            $where_params = $where_params + $param_options;
        }
    }

    // Email filter
    if (array_key_exists('email', $formdata) and !empty($formdata->email)) {
        $email_like = $DB->sql_like('u.email', "'%{$formdata->email}%'", false);
        $whereors[] = "($email_like)";
    }
}

// Role filter
if (array_key_exists('role', $formdata)) {
    list($in_roles, $param_roles) = $DB->get_in_or_equal(array_keys($formdata->role), SQL_PARAMS_NAMED);
} else {
    list($in_roles, $param_roles) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);
}

$where[] = "ra.roleid {$in_roles}";
$where_params = $where_params + $param_roles;

// Enrol instance filter
if (array_key_exists('instance', $formdata)) {
    list($in_instances, $param_instances) = $DB->get_in_or_equal(array_keys($formdata->instance), SQL_PARAMS_NAMED);
} else {
    list($in_instances, $param_instances) = $DB->get_in_or_equal(array_keys($instances), SQL_PARAMS_NAMED);
}

list($in_contexts, $param_contexts) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
list($in_fields, $param_fields) = $DB->get_in_or_equal(array_keys($filterfields), SQL_PARAMS_NAMED);
$params = $param_instances + $param_contexts + $param_fields;

// Get data to summary table
$dados = $DB->get_records_sql_menu("SELECT uid.fieldid||'-'||ra.roleid||'-'||uid.data, count(distinct u.id)
                  FROM {user} u
                    JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid {$in_instances})
                    JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid {$in_contexts})
                    LEFT JOIN {user_info_data} uid ON (uid.userid = u.id AND uid.fieldid {$in_fields})
                  GROUP BY uid.fieldid, ra.roleid, uid.data", $params);

// Get data to User List table
$whereorstr = implode(' OR ', $whereors);
if ($whereorstr) {
  $where[] = "({$whereorstr})";
}
$wherestr = implode(' AND ', $where);

$sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, ue.timecreated
FROM {user} u
  JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid {$in_instances})
  JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid {$in_contexts})
  LEFT OUTER JOIN {user_info_data} uid ON (uid.userid = u.id AND uid.fieldid {$in_fields})";

$sql_role_summary = "SELECT ra.roleid, count(distinct ue.id)
FROM {user} u
  JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid {$in_instances})
  JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid {$in_contexts})
  LEFT OUTER JOIN {user_info_data} uid ON (uid.userid = u.id AND uid.fieldid {$in_fields})
 GROUP BY ra.roleid";

$dados_role_summary = $DB->get_records_sql($sql_role_summary, $params);

if ($wherestr) {
    $sql .= " WHERE {$wherestr}";
}

if ($sort == 'timecreated') {
    $sql .= ' ORDER BY ue.timecreated';
} else {
    $sql .= " ORDER BY u.{$sort}";
}

$sql_count = "SELECT COUNT(DISTINCT u.id)
FROM {user} u
  JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid {$in_instances})
  JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid {$in_contexts})
  LEFT OUTER JOIN {user_info_data} uid ON (uid.userid = u.id AND uid.fieldid {$in_fields})";

$total = $DB->count_records_sql($sql_count, $params + $where_params);
$userlist = $DB->get_records_sql($sql, $params + $where_params, $start, ILBENROL_REPORT_PAGE);
 
if ($csv && $userlist) { // Only show CSV if there are some users
    $shortname = format_string($course->shortname, true, array('context' => $context));
    header('Content-Disposition: attachment; filename=ilbenrol.'.
        preg_replace('/[^a-z0-9-]/','_',textlib::strtolower(strip_tags($shortname))).'.csv');
    // Unicode byte-order mark for Excel
    if ($excel) {
        header('Content-Type: text/csv; charset=UTF-16LE');
        print chr(0xFF).chr(0xFE);
        $sep="\t".chr(0);
        $line="\n".chr(0);
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
        $sep=",";
        $line="\n";
    }
} else {
    // Navigation and header
    $PAGE->set_title(get_string('title','report_ilbenrol'));
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
//  $PAGE->requires->js('/report/ilbenrol/textrotate.js');
//  $PAGE->requires->js_function_call('textrotate_init', null, true);

    // Handle groups (if enabled)
    groups_print_course_menu($course,$CFG->wwwroot.'/report/ilbenrol/?course='.$course->id);

    // Print filter form
    echo $OUTPUT->box_start();
    echo '<strong>'.get_string('filter', 'report_ilbenrol').'</strong>';
    $mform->display();
    echo $OUTPUT->box_end();
    echo '<br/>';

    // Print summary tables
    echo $OUTPUT->box_start();
    echo '<strong>'.get_string('summary', 'report_ilbenrol').'</strong>';

    // Role summary table
    $data = array();
    foreach ($dados_role_summary as $role_count) {
        $role = $allroles[$role_count->roleid];
        $data[] = array($role->localname, $role_count->count);
    }
    $table = new html_table();
    $table->align = array('left', 'right');
    $table->head = array(get_string('roles'), get_string('total'));
    $table->data = $data;
    
    echo html_writer::table($table);

    // Print filtered summaries
    $thead = array('');
    $talign = array('left');
    foreach ($roles as $role) {
        $thead[] = $role->localname;
        $talign[] = 'right';
    }

    foreach ($filterfields as $field) {
        $options = explode("\n", $field->param1);
        $data = array();

        foreach($options as $option) {
            $row = array($option);
            foreach ($roles as $role) {
                if (isset($dados["{$field->id}-{$role->id}-{$option}"])) {
                    $row[] = $dados["{$field->id}-{$role->id}-{$option}"];
                } else {
                    $row[] = "";
                }
            }
            $data[] = $row;
        }

        $thead[0]     = $field->name;
        $table        = new html_table();
        $table->align = $talign;
        $table->head  = $thead;
        $table->data  = $data;

        echo html_writer::table($table);
    }
    echo $OUTPUT->box_end();
    echo '<br/>';
}

// If no users in this filter
if (!$userlist) {
    echo $OUTPUT->container(get_string('err_nousers', 'completion'), 'errorbox errorboxcontent');
    echo $OUTPUT->footer();
    exit;
}

// Build link for paging
$link = $CFG->wwwroot.'/report/ilbenrol/?course='.$course->id;
if (strlen($sort)) {
    $link .= '&amp;sort='.$sort;
}

// Add filterform fields

if ($formdata) {
    $formvar = '&_qf__filter_form=1&mform_isexpanded_id_filter=1&sesskey='.sesskey();
    foreach ($formdata as $key=>$value) {
        if (is_array($value)) {
            foreach ($value as $k=>$v) {
                $formvar .= "&{$key}[{$k}]={$v}";
            }
        } else {
            $formvar .= "&{$key}={$value}";
        }
    }
    $link .= $formvar;
}

$link .= '&amp;start=';

// Build the the page by Initial bar
$initials = array('first', 'last');
$alphabet = explode(',', get_string('alphabet', 'langconfig'));

$pagingbar = '';
foreach ($initials as $initial) {
    $var = 'si'.$initial;

    $othervar = $initial == 'first' ? 'silast' : 'sifirst';
    $othervar = $$othervar != 'all' ? "&amp;{$othervar}={$$othervar}" : '';

    $pagingbar .= ' <div class="initialbar '.$initial.'initial">';
    $pagingbar .= get_string($initial.'name').':&nbsp;';

    if ($$var == 'all') {
        $pagingbar .= '<strong>'.get_string('all').'</strong> ';
    }
    else {
        $pagingbar .= "<a href=\"{$link}{$othervar}\">".get_string('all').'</a> ';
    }

    foreach ($alphabet as $letter) {
        if ($$var === $letter) {
            $pagingbar .= '<strong>'.$letter.'</strong> ';
        }
        else {
            $pagingbar .= "<a href=\"$link&amp;$var={$letter}{$othervar}\">$letter</a> ";
        }
    }

    $pagingbar .= '</div>';
}

// Do we need a paging bar?
if ($total > ILBENROL_REPORT_PAGE) {

    // Paging bar
    $pagingbar .= '<div class="paging">';
    $pagingbar .= get_string('page').': ';

    $sistrings = array();
    if ($sifirst != 'all') {
        $sistrings[] =  "sifirst={$sifirst}";
    }
    if ($silast != 'all') {
        $sistrings[] =  "silast={$silast}";
    }
    $sistring = !empty($sistrings) ? '&amp;'.implode('&amp;', $sistrings) : '';

    // Display previous link
    if ($start > 0) {
        $pstart = max($start - ILBENROL_REPORT_PAGE, 0);
        $pagingbar .= "(<a class=\"previous\" href=\"{$link}{$pstart}{$sistring}\">".get_string('previous').'</a>)&nbsp;';
    }

    // Create page links
    $curstart = 0;
    $curpage = 0;
    while ($curstart < $total) {
        $curpage++;

        if ($curstart == $start) {
            $pagingbar .= '&nbsp;'.$curpage.'&nbsp;';
        } else {
            $pagingbar .= "&nbsp;<a href=\"{$link}{$curstart}{$sistring}\">$curpage</a>&nbsp;";
        }

        $curstart += ILBENROL_REPORT_PAGE;
    }

    // Display next link
    $nstart = $start + ILBENROL_REPORT_PAGE;
    if ($nstart < $total) {
        $pagingbar .= "&nbsp;(<a class=\"next\" href=\"{$link}{$nstart}{$sistring}\">".get_string('next').'</a>)';
    }

    $pagingbar .= '</div>';
}

// Okay, let's draw the table of user info,

// Start of table
if (!$csv) {
    print '<br/>'; // ugh
    echo $OUTPUT->box_start();
    print $pagingbar;

    if (!$total) {
        echo $OUTPUT->heading(get_string('nothingtodisplay'));
        echo $OUTPUT->footer();
        exit;
    }

    print '<form action="action.php" method="post" id="participantsform">';
    print '<div id="completion-progress-wrapper" class="no-overflow">';
    print '<table id="completion-progress" class="generaltable flexible boxaligncenter" style="text-align:left"><thead><tr style="vertical-align:top">';

    print '<th scope="col" class="completion-identifyfield">'.get_string('select').'</th>';

    // User heading / sort option
    print '<th scope="col" class="completion-sortchoice">';

    $sistring = "&amp;silast={$silast}&amp;sifirst={$sifirst}";

    if ($sort == 'firstname') {
        print  get_string('firstname')." / <a href=\"./?course={$course->id}&amp;sort=lastname{$sistring}\">".
            get_string('lastname').'</a>';
    } else if ($sort == 'lastname') {
        print "<a href=\"./?course={$course->id}&amp;sort=firstname{$sistring}\">".
            get_string('firstname').'</a> / '.
            get_string('lastname');
    } else {
        print "<a href=\"./?course={$course->id}&amp;sort=firstname{$sistring}\">".
            get_string('firstname')."</a> / <a href=\"./?course={$course->id}&amp;sort=lastname{$sistring}\">".
            get_string('lastname').'</a>';
    }

    print '</th><th scope="col" class="completion-identifyfield">'.get_user_field_name('email').'</th>';
    
    if ($sort == 'timecreated') {
        print '<th scope="col" class="completion-sortchoice">'.
                 get_string('timecreated', 'report_ilbenrol').'</th>';
    } else {
        print '<th scope="col" class="completion-sortchoice">'.
              "<a href=\"./?course={$course->id}&amp;sort=timecreated{$sistring}\">".
                 get_string('timecreated', 'report_ilbenrol').'</a></th>';
    }

    print '<th scope="col" class="completion-sortchoice">'.get_string('userroles', 'report_ilbenrol').'</td>';
} else {
    echo csv_quote(get_user_field_name('fullname'));
    echo $sep . csv_quote(get_user_field_name('email'));
    echo $sep . csv_quote(get_string('timecreated', 'report_ilbenrol'));
    echo $sep . csv_quote(get_string('userroles', 'report_ilbenrol'));
}

// User fields header

foreach ($filterfields as $field) {
    if ($csv) {
        print $sep.csv_quote(strip_tags($field->name)).$sep;
    } else {
        $formattedname = format_string($field->name, true, array('context' => $context));
        print '<th scope="col" class="completion-identifyfield">' . $formattedname . '</th>';
    }
}

if ($csv) {
    print $line;
}

// Row for each user
foreach($userlist as $user) {
    $profile = profile_user_record($user->id);
    $user_roles = $manager->get_user_roles($user->id);
    $display_roles = array();
    foreach ($user_roles as $rid=>$rassignable) {
        $display_roles[] = $allroles[$rid]->localname;
    }
    $display_roles = implode(', ', $display_roles);

    if ($csv) {
        print csv_quote(fullname($user));
        echo $sep . csv_quote($user->email);
        echo $sep . csv_quote(userdate($user->timecreated));
        echo $sep . csv_quote($display_roles);

        foreach ($filterfields as $field) {
            if (array_key_exists($field->shortname, $profile)) {
                $data = $profile->{$field->shortname};
            } else {
                $data = '';
            }
            echo $sep . csv_quote($data);
        }
    } else {
        print '<tr><th><input type="checkbox" class="usercheckbox" name="user['.$user->id.']" /></th>';
        print '<th scope="row"><a href="'.$CFG->wwwroot.'/user/view.php?id='.
            $user->id.'&amp;course='.$course->id.'">'.fullname($user).'</a></th>';
        echo '<td>' . s($user->email) . '</td>';
        echo '<td>' . s(userdate($user->timecreated)) . '</td>';
        echo '<td>' . s($display_roles) . '</td>';

        foreach ($filterfields as $field) {
            if (array_key_exists($field->shortname, $profile)) {
                $data = $profile->{$field->shortname};
            } else {
                $data = '';
            }
            echo '<td>' . s($data) . '</td>';
        }
    }

    if ($csv) {
        print $line;
    } else {
        print '</tr>';
    }
}

if ($csv) {
    exit;
}

print '</tbody></table>';
print '</div>';
print $pagingbar;

// Bulk operations
echo '<br /><div class="buttons">';
echo '<input type="button" id="checkall" value="'.get_string('selectall').'" /> ';
echo '<input type="button" id="checknone" value="'.get_string('deselectall').'" /> ';
$module = array('name'=>'core_user', 'fullpath'=>'/user/module.js');
$PAGE->requires->js_init_call('M.core_user.init_participation', null, false, $module);
$displaylist = array();
$displaylist['confirmenrol'] = get_string('confirmenrol', 'report_ilbenrol');
$displaylist['revokeenrol'] = get_string('revokeenrol', 'report_ilbenrol');
echo $OUTPUT->help_icon('withselectedusers', 'report_ilbenrol');
echo html_writer::tag('label', get_string("withselectedusers"), array('for'=>'formactionid'));
echo html_writer::select($displaylist, 'formaction', '', array(''=>'choosedots'), array('id'=>'formactionid'));
echo '<input type="hidden" name="id" value="'.$course->id.'" />';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';
echo '<noscript style="display:inline">';
echo '<div><input type="submit" value="'.get_string('ok').'" /></div>';
echo '</noscript>';
echo '</div>';
echo '</form>';

print '<ul class="progress-actions">
         <li><a href="index.php?course='.$course->id.'&amp;format=csv">'.
                get_string('csvdownload','completion').'</a></li>
         <li><a href="index.php?course='.$course->id.'&amp;format=excelcsv">'.
                get_string('excelcsvdownload','completion').'</a></li>
         <li><a href="suspend.php?course='.$course->id.'&amp;sesskey='.sesskey().'">'.
                get_string('suspendrevoked', 'report_ilbenrol').'</a></li></ul>';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

