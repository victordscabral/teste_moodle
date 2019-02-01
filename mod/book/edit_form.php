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
 * Chapter edit form
 *
 * @package    mod_book
 * @copyright  2004-2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class book_chapter_edit_form extends moodleform {

    function definition() {
        $val = 1;

        global $CFG;

        $chapter = $this->_customdata['chapter'];
        $options = $this->_customdata['options'];
        
        $content = $this->_customdata['content'];
        $content2 = $this->_customdata['content2'];

        // Disabled subchapter option when editing first node.
        $disabledmsg = null;
        if ($chapter->pagenum == 1) {
            $disabledmsg = get_string('subchapternotice', 'book');
        }

        $mform = $this->_form;

        if (!empty($chapter->id)) {
            $mform->addElement('header', 'general', get_string('editingchapter', 'mod_book'));            
        } else {
            $mform->addElement('header', 'general', get_string('addafter', 'mod_book'));
        }

        $mform->addElement('text', 'title', get_string('chaptertitle', 'mod_book'), array('size'=>'30'));
        $mform->setType('title', PARAM_RAW);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'subchapter', get_string('subchapter', 'mod_book'), $disabledmsg);

        $mform->addElement('editor', 'content_editor', get_string('content', 'mod_book'), null, $options);
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addRule('content_editor', get_string('required'), 'required', null, 'client');

        if (core_tag_tag::is_enabled('mod_book', 'book_chapters')) {
            $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
        }
        $mform->addElement('tags', 'tags', get_string('tags'),
            array('itemtype' => 'book_chapters', 'component' => 'mod_book'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'pagenum');
        $mform->setType('pagenum', PARAM_INT);

        $this->add_action_buttons(true);

        // set the defaults
        $this->set_data($chapter);


        // header da seção de criar novas abas
        $mform->addElement('header', 'addtab', 'Adicionar abas');

        $repeatarray = array();
        $repeatarray[] = $mform->createElement('text', 'tabtitle', 'Título da aba');
        $repeatarray[] = $mform->createElement('text', 'tabtitlecont', 'Título dentro da caixa da aba');
        // $repeatarray[] = $mform->createElement('textarea', 'tabcont', 'Conteúdo da aba', 'rows="15" cols="50"');
        $repeatarray[] = $mform->createElement('editor', 'tabcont', 'Conteúdo da aba');
 
        $repeatno = 2;
 
        $repeateloptions = array();
        // $repeateloptions['tabcont']['type'] = PARAM_RAW;

 
        $mform->setType('tabtitle', PARAM_CLEANHTML);
        $mform->setType('tabcont', PARAM_CLEANHTML);

        $qtde = $this->repeat_elements($repeatarray, $repeatno,
                    $repeateloptions, 'option_repeats', 'option_add_fields', 1, "Adicionar mais uma tab", true);

        $mform->registerNoSubmitButton('addotags');
        $otagsgrp = array();
        $otagsgrp[] =& $mform->createElement('submit', 'addotags', 'Gerar código HTML');
        $mform->addGroup($otagsgrp, 'otagsgrp', 'Adicionar código', array(' '), false);

        $mform->addElement('textarea', 'introduction', 'Código HTML resultante', 'wrap="virtual" rows="20" cols="100"' );

        $val = '<script type="text/javascript">
                    var content='.json_encode($content).';
            //<![CDATA[
                jQuery(document).ready(function() {
                    console.log(content);
                    jQuery("#id_introduction").html(content);
                });
            //]]>
            </script>';

        $mform->addElement('static', null, '', $val);

        $mform->setType('introduction', PARAM_RAW);
        

        //$mform->setDefault('introduction', $content);
        //$mform->setDefault('introduction', array('text' => $content, 'format' => FORMAT_HTML));

        // HEADER DA CRIACAO DE CARROUSEL
        $mform->addElement('header', 'addtab', 'Adicionar Sanfonas');

        $repeatarray2 = array();
        $repeatarray2[] = $mform->createElement('text', 'sanfonatitle', 'Título da aba da sanfona');
        $repeatarray2[] = $mform->createElement('editor', 'sanfonacont', 'Conteúdo da Sanfona', 'rows="15" cols="50"');
        // $repeatarray[] = $mform->createElement('editor', 'tabcont', 'Conteúdo da aba');
 
        $repeatno2 = 2;
 
        $repeateloptions2 = array();
        // $repeateloptions['tabcont']['type'] = PARAM_RAW;

 
        $mform->setType('sanfonatitle', PARAM_CLEANHTML);
        $mform->setType('sanfonacont', PARAM_CLEANHTML);

        $qtde2 = $this->repeat_elements($repeatarray2, $repeatno2,
                    $repeateloptions2, 'option_repeats', 'option_add_fields', 1, "Adicionar mais uma sanfona", true);

        $mform->registerNoSubmitButton('addCarrousel');
        $otagsgrp2 = array();
        $otagsgrp2[] =& $mform->createElement('submit', 'addCarrousel', 'Gerar código HTML');
        $mform->addGroup($otagsgrp2, 'otagsgrp', 'Adicionar código', array(' '), false);

        $mform->addElement('textarea', 'introduction2', 'Código HTML resultante', 'wrap="virtual" rows="20" cols="100"');  

        $val2 = '<script type="text/javascript">
                    var content2='.json_encode($content2).';
            //<![CDATA[
                jQuery(document).ready(function() {                    
                    console.log(content2);
                    jQuery("#id_introduction2").html(content2);
                });
            //]]>
            </script>';

        $mform->addElement('static', null, '', $val2);
    }

    function get_submit_value($elementname) {
        $mform = $this->_form;
        return $mform->getSubmitValue($elementname);
    }
    
    function definition_after_data(){
        $mform = $this->_form;
        $pagenum = $mform->getElement('pagenum');
        if ($pagenum->getValue() == 1) {
            $mform->hardFreeze('subchapter');
        }
    }
}
