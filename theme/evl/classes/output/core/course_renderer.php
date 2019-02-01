<?php

namespace theme_evl\output\core;
defined('MOODLE_INTERNAL') || die();

use moodle_url;

include_once($CFG->dirroot . '/course/renderer.php');

//include($CFG->dirroot .'/course/renderer.php');

/**
 * Course renderer class.
 *
 * @package    theme_noanme
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_renderer extends \theme_boost\output\core\course_renderer {

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
        return 'Hahaha4evl! Nenhum curso disponível'; //$this->coursecat_courses($chelper, $courses, $totalcount);
    }    

    public function frontpage_categories_list() {
        global $CFG;
        require_once($CFG->libdir. '/coursecatlib.php');
        $chelper = new \coursecat_helper();
        $chelper->set_subcat_depth($CFG->maxcategorydepth)->
                set_show_courses(self::COURSECAT_SHOW_COURSES_COUNT)->
                set_categories_display_options(array(
                    'limit' => $CFG->coursesperpage,
                    'viewmoreurl' => new moodle_url('/course/index.php',
                            array('browse' => 'categories', 'page' => 1))
                ))->
                set_attributes(array('class' => 'frontpage-category-names'));
        return $this->coursecat_tree($chelper, coursecat::get(0));
   }
}