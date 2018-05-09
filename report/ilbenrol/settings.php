<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $choices = $DB->get_records_menu('user_info_field', array('datatype'=>'menu'), 'name', 'shortname, name');
    $roles = role_fix_names(get_all_roles(), context_system::instance(), ROLENAME_ORIGINAL);
    $rolechoices = array();
    foreach ($roles as $role) {
        $rolechoices[$role->id] = $role->localname;
    }
    $settings->add(new admin_setting_configmulticheckbox('report_ilbenrol_filterfields', get_string('filterfields', 'report_ilbenrol'), get_string('filterfieldsdescription', 'report_ilbenrol'), '', $choices));

    $settings->add(new admin_setting_configselect('report_ilbenrol_confirmed', get_string('confirmed', 'report_ilbenrol'), get_string('confirmeddescription', 'report_ilbenrol'), '', $rolechoices));
    $settings->add(new admin_setting_configselect('report_ilbenrol_revoked', get_string('revoked', 'report_ilbenrol'), get_string('revokeddescription', 'report_ilbenrol'), '', $rolechoices));
}
