<?php

namespace theme_evl\output;

use html_writer;
use custom_menu_item;
use custom_menu;
use action_menu_filler;
use action_menu_link_secondary;
use navigation_node;
use action_link;
use stdClass;
use moodle_url;
use action_menu;
use pix_icon;
use theme_config;
use core_text;
use help_icon;
use context_system;

defined('MOODLE_INTERNAL') || die;
/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_moove
 * @copyright  2017 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    
    public function standard_head_html() {
        $output = parent::standard_head_html();
        // Add google analytics code.
        $googleanalyticscode = "<script>
                                    window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};
                                    ga.l=+new Date;ga('create', 'GOOGLE-ANALYTICS-CODE', 'auto');
                                    ga('send', 'pageview');
                                </script>
                                <script async src='https://www.google-analytics.com/analytics.js'></script>";
        $theme = theme_config::load('evl');
        if (!empty($theme->settings->googleanalytics)) {
            $output .= str_replace("GOOGLE-ANALYTICS-CODE", trim($theme->settings->googleanalytics), $googleanalyticscode);
        }
        return $output;
    }

    public function frontpage_available_courses() {
        global $CFG;
        /*require_once($CFG->libdir. '/coursecatlib.php');
        $chelper = new coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->
                set_courses_display_options(array(
                    'recursive' => true,
                    'limit' => $CFG->frontpagecourselimit,
                    'viewmoreurl' => new moodle_url('/course/index.php'),
                    'viewmoretext' => new lang_string('fulllistofcourses')));
        $chelper->set_attributes(array('class' => 'frontpage-course-list-all'));
        $courses = coursecat::get(7)->get_courses($chelper->get_courses_display_options());
        $totalcount = coursecat::get(7)->get_courses_count($chelper->get_courses_display_options());
        if (!$totalcount && !$this->page->user_is_editing() && has_capability('moodle/course:create', context_system::instance())) {
            // Print link to create a new course, for the 1st available category.
            return $this->add_new_course_button();
        }*/
        return 'Hahaha! Nenhum curso disponível'; //$this->coursecat_courses($chelper, $courses, $totalcount);
    }    

}