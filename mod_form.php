<?php // $Id: mod_form.php
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once("$CFG->dirroot/mod/rtrecording/lib.php");
require_once($CFG->libdir . '/filelib.php');

class mod_rtrecording_mod_form extends moodleform_mod {
    var $_gradings;
    protected $_fmoptions = array(
        // 3 == FILE_EXTERNAL & FILE_INTERNAL
        // These two constant names are defined in repository/lib.php
        'return_types' => 3,
        'accepted_types' => 'images',
        'maxbytes' => 0,
        'maxfiles' => 1
    );

    function definition() {

        global $COURSE, $CFG, $DB, $USER, $PAGE;

        //$PAGE->requires->js('/mod/rtrecording/js/mod_rtrecording.js');
        //$PAGE->requires->js('/mod/rtrecording/js/jQueryFileTree.min.js');
        //$PAGE->requires->js_call_amd('mod_rtrecording/rtrecording', 'init');
        $PAGE->requires->js_init_code('window.browsetitle = "' . get_string( 'browsetitle', 'mod_rtrecording' ) . '";');
        $PAGE->requires->css('/local/connect/css/jQueryFileTree.css');

        $mform =& $this->_form;
        // this hack is needed for different settings of each subtype
        if (!empty($this->_instance)) {
            $new = true;
        } else {
            $new = false;
        }

        $PAGE->requires->string_for_js('notfound', 'rtrecording');
        $PAGE->requires->string_for_js('whensaved', 'rtrecording');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'newurl', '');
        $mform->setType('newurl', (!empty($CFG->formatstringstriptags)) ? PARAM_TEXT : PARAM_CLEAN);
        $mform->addElement('hidden', 'eventid', 0);
        $mform->setType('eventid', PARAM_INT);
        $mform->addElement('hidden', 'scoid', 0);
        $mform->setType('scoid', PARAM_INT);
        $url = optional_param('url', '', PARAM_RAW);
        $name = optional_param('name', '', PARAM_RAW);
        if (is_numeric(substr($url, 0, 1))) $url = 'INVALID';
        if ($url != clean_text($url, PARAM_ALPHAEXT)) $url = 'INVALID';
        if (strpos($url, '/') OR strpos($url, ' ')) $url = 'INVALID';
        if (!empty($url)) $mform->setDefault('newurl', $url);

        $mform->addElement('header', 'general', get_string('modulename', 'rtrecording'));

//-------------------------------------------------------------------------------
        $formgroup = array();
        $formgroup[] =& $mform->createElement('text', 'url', '', array('maxlength' => 255, 'size' => 48, 'class' => 'ignoredirty'));
        $mform->setType('url', (!empty($CFG->formatstringstriptags)) ? PARAM_TEXT : PARAM_CLEAN);
        if (empty($_REQUEST['update'])) {
            $formgroup[] =& $mform->createElement('button', 'browse', get_string('browse', 'rtrecording'), array('data-rtrecording' => 1));
        }
        $mform->addElement('group', 'urlgrp', get_string('url', 'rtrecording'), $formgroup, array(' '), false);
        $mform->setDefault('url', $url);
        if (empty($_REQUEST['update'])) {
            $mform->addRule( 'urlgrp', null, 'required');
            $mform->addGroupRule( 'urlgrp', array(
                'url' => array(
                    array( null, 'required', null, 'client' )
                ),
            ) );
        }

        $mform->addHelpButton('urlgrp', 'url', 'rtrecording');

        if (!empty($_REQUEST['update'])) {
            $mform->hardFreeze('urlgrp');
        }

        $options = array();
        $options[0] = get_string('none');
        for($i=10;$i>0;$i--){
            $perc = $i*10;
            $options[$perc] = $perc.'%';
        }
    
        $goptions = array();
        for ($i = 100; $i >= 1; $i--) {
            $goptions[$i] = $i . '%';
        }

//-------------------------------------------------------------------------------

        $mform->addElement('text', 'name', get_string('rtrecording_name', 'rtrecording'), array(
            'class' => 'ignoredirty',
            'size' => '64',
            'maxlength' => '60',
            'style' => 'width:412px;'
        ));
        $mform->setDefault('name', $name);

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'name', 'rtrecording');
//-------------------------------------------------------------------------------
        // Duration
        $doptions = array();
        $doptions[60 * 15 * 01] = '15 ' . get_string('mins');
        $doptions[60 * 30 * 01] = '30 ' . get_string('mins');
        $doptions[60 * 45 * 01] = '45 ' . get_string('mins');
        $doptions[60 * 60 * 01] = '1 ' . get_string('hour');
        for ($i = 1; $i <= 51; $i++) $doptions[60 * 15 * $i] = GMDATE('H:i', 60 * 15 * $i);

        // Start Date
        $mform->addElement('date_time_selector', 'start', get_string('start', 'rtrecording'), array('optional'=>true));
        $mform->addHelpButton('start', 'start', 'rtrecording');

        $this->standard_intro_elements(false, get_string('summary', 'rtrecording'));
        $mform->addHelpButton('introeditor', 'summary', 'rtrecording');

        if( isset( $CFG->rtrecording_has_vp ) && $CFG->rtrecording_has_vp ){
            $hideplayerdefault = isset( $CFG->rtrecording_hideplayer ) ? $CFG->rtrecording_hideplayer : 0;
            $mform->addElement('checkbox', 'hideplayer', get_string('hideplayer', 'rtrecording'));
            $mform->setDefault('hideplayer', $hideplayerdefault);
            $mform->addHelpButton('hideplayer', 'hideplayer', 'rtrecording');
            $mform->setAdvanced('hideplayer');
        }
//-------------------------------------------------------------------------------
        	
            if (isset($CFG->rtrecording_icondisplay) AND $CFG->rtrecording_icondisplay) {
                $mform->addElement('header', 'disphdr', get_string('disphdr', 'rtrecording'));
                
                $displayoncoursedefault = isset( $CFG->rtrecording_displayoncourse ) ? $CFG->rtrecording_displayoncourse : 1;
                $mform->addElement('checkbox', 'displayoncourse', get_string('displayoncourse', 'rtrecording'));
                $mform->setDefault('displayoncourse', $displayoncoursedefault);
                $mform->addHelpButton('displayoncourse', 'displayoncourse', 'rtrecording');

                $szopt = array();
                //$szopt['none'] = get_string('none');
                $szopt['large'] = get_string('large', 'rtrecording');
                $szopt['medium'] = get_string('medium', 'rtrecording');
                $szopt['small'] = get_string('small', 'rtrecording');
                $szopt['block'] = get_String('block', 'rtrecording');
                $szopt['custom'] = get_String('custom', 'rtrecording');
                $mform->addElement('select', 'iconsize', get_string('iconsize', 'rtrecording'), $szopt);
                $mform->setDefault('iconsize', 'medium');
                $mform->addHelpButton('iconsize', 'iconsize', 'rtrecording');

                $posopt = array();
                $posopt['l'] = get_string('left', 'rtrecording');
                $posopt['c'] = get_string('center', 'rtrecording');
                $mform->addElement('select', 'iconpos', get_string('iconpos', 'rtrecording'), $posopt);
                $mform->addHelpButton('iconpos', 'iconpos', 'rtrecording');
        
                if (isset($CFG->rtrecording_maxviews) AND $CFG->rtrecording_maxviews >= 0) {
                    $vstr = get_string('views', 'rtrecording');
                    $viewopts = array(0 => get_string('disabled', 'rtrecording'), 1 => '1' . get_string('view', 'rtrecording'));
                    for ($i = 2; $i <= 100; $i++) {
                        $viewopts[$i] = $i . $vstr;
                    }
                    $mform->addElement('select', 'maxviews', get_string('maxviews', 'rtrecording'), $viewopts);
                    $mform->setDefault('maxviews', $CFG->rtrecording_maxviews);
                }

                $mform->addElement('checkbox', 'iconsilent', get_string('iconsilent', 'rtrecording'));
                $mform->addHelpButton('iconsilent', 'iconsilent', 'rtrecording');
                $mform->setAdvanced('iconsilent');
                //$mform->addElement('checkbox', 'iconmouse', get_string('iconmouse', 'rtrecording'));
                //$mform->addHelpButton('iconmouse', 'iconmouse', 'rtrecording');
                //$mform->setAdvanced('iconmouse');
                $mform->addElement('htmleditor', 'extrahtml', get_string('extrahtml', 'rtrecording'), array('cols' => '64', 'rows' => '8'));
                $mform->addHelpButton('extrahtml', 'extrahtml', 'rtrecording');
                $mform->setAdvanced('extrahtml');

                $mform->addElement('filemanager', 'forceicon_filemanager', get_string('forceicon', 'rtrecording'), null, $this->_fmoptions);
                $mform->addHelpButton('forceicon_filemanager', 'forceicon_filemanager', 'rtrecording');
                $mform->setAdvanced('forceicon_filemanager');

                $mform->disabledIf('iconpos', 'iconsize', 'eq', 'none');
                $mform->disabledIf('iconsilent', 'iconsize', 'eq', 'none');
                $mform->disabledIf('iconphone', 'iconsize', 'eq', 'none');
                //$mform->disabledIf('iconmouse', 'iconsize', 'eq', 'none');
                $mform->disabledIf('iconguests', 'iconsize', 'eq', 'none');
                $mform->disabledIf('iconnorec', 'iconsize', 'eq', 'none');
                $mform->disabledIf('extrahtml', 'iconsize', 'eq', 'none');
                $mform->disabledIf('forceicon_filemanager', 'iconsize', 'ne', 'custom');
                $mform->disabledIf('iconphone', 'iconsilent', 'checked');
                //$mform->disabledIf('iconmouse', 'iconsilent', 'checked');
                $mform->disabledIf('extrahtml', 'iconsilent', 'checked');
            }

//-------------------------------------------------------------------------------
            $mform->addElement('header', 'grading', get_string('gradinghdr', 'rtrecording'));
            //        $mform->addHelpButton('grading', 'grading', 'rtrecording');

            $dgoptions = array(
                0 => get_string('off', 'rtrecording')
            );
            
            if( isset( $CFG->rtrecording_has_vp ) && $CFG->rtrecording_has_vp ){            
                if (!empty(connect_check_vp_license_active())){
                    $dgoptions[1] = get_string('positiongrading', 'rtrecording');
                    $dgoptions[2] = get_string('durationgrading', 'rtrecording' );
                    if( $CFG->rtrecording_has_vp == 2 ){
                        $dgoptions[3] = get_string('vantagepoint', 'rtrecording' );
                    }
                }            
            }
            $mform->addElement('select', 'detailgrading', get_string("detailgrading", 'rtrecording'), $dgoptions);
            $mform->addHelpButton('detailgrading', "detailgrading", 'rtrecording');
            //$mform->setAdvanced('detailgrading', 'grade');

        if( empty(connect_check_vp_license_active()) ){
            $mform->addElement('static', 'get_vp', get_string('get_vp', 'rtrecording'));
        }else{
            
            /*$formgroup = array($mform->createElement('static', 'position_help_label', ''));
            $mform->addElement('group', 'position_help_group', '', $formgroup, array(' '), false);
            $mform->addHelpButton('position_help_group', "grade_position", 'rtrecording');
 
            $formgroup = array($mform->createElement('static', 'duration_help_label', ''));
            $mform->addElement('group', 'duration_help_group', '', $formgroup, array(' '), false);
            $mform->addHelpButton('duration_help_group', "grade_duration", 'rtrecording');
 
            $formgroup = array($mform->createElement('static', 'vantage_help_label', ''));
            $mform->addElement('group', 'vantage_help_group', '', $formgroup, array(' '), false);
            $mform->addHelpButton('vantage_help_group', "grade_vantage", 'rtrecording');*/


            $formgroup = array();
            $formgroup[] =& $mform->createElement('select', 'threshold[1]', '', $options);
            $mform->setDefault('threshold[1]', 0);
            $mform->disabledIf('threshold[1]', 'detailgrading', 'eq', 0);
            $mform->disabledIf('threshold[1]', 'detailgrading', 'eq', 3);
            $formgroup[] =& $mform->createElement('select', 'grade[1]', '', $goptions);
            $mform->setDefault('grade[1]', 0);
            $mform->disabledIf('grade[1]', 'detailgrading', 'eq', 0);
            $mform->disabledIf('grade[1]', 'detailgrading', 'eq', 3);
            $mform->addElement('group', 'tg1', get_string("tg", 'rtrecording') . ' 1', $formgroup, array(' '), false);
            //$mform->addHelpButton('tg1', "tg", 'rtrecording');
            //$mform->setAdvanced('tg1', 'grade');

            $formgroup = array();
            $formgroup[] =& $mform->createElement('select', 'threshold[2]', '', $options);
            $mform->setDefault('threshold[2]', 0);
            $mform->disabledIf('threshold[2]', 'detailgrading', 'eq', 0);
            $mform->disabledIf('threshold[2]', 'detailgrading', 'eq', 3);
            $formgroup[] =& $mform->createElement('select', 'grade[2]', '', $goptions);
            $mform->setDefault('grade[2]', 0);
            $mform->disabledIf('grade[2]', 'detailgrading', 'eq', 0);
            $mform->disabledIf('grade[2]', 'detailgrading', 'eq', 3);
            $mform->addElement('group', 'tg2', get_string("tg", 'rtrecording') . ' 2', $formgroup, array(' '), false);
            //$mform->addHelpButton('tg2', "tg", 'rtrecording');
            //$mform->setAdvanced('tg2', 'grade');

            $formgroup = array();
            $formgroup[] =& $mform->createElement('select', 'threshold[3]', '', $options);
            $mform->setDefault('threshold[3]', 0);
            $mform->disabledIf('threshold[3]', 'detailgrading', 'eq', 0);
            $mform->disabledIf('threshold[3]', 'detailgrading', 'eq', 3);
            $formgroup[] =& $mform->createElement('select', 'grade[3]', '', $goptions);
            $mform->setDefault('grade[3]', 0);
            $mform->disabledIf('grade[3]', 'detailgrading', 'eq', 0);
            $mform->disabledIf('grade[3]', 'detailgrading', 'eq', 3);
            $mform->addElement('group', 'tg3', get_string("tg", 'rtrecording') . ' 3', $formgroup, array(' '), false);
            //$mform->addHelpButton('tg3', "tg", 'rtrecording');
            //$mform->setAdvanced('tg3', 'grade');

            $vpoptions = array();
            $vpoptions[0] = get_string('none');
            for ($i = 100; $i >= 5; $i -= 5) {
                $vpoptions[$i] = $i . '%';
            }

            $formgroup = array();
            $formgroup[] =& $mform->createElement('select', 'vpthreshold[1]', '', $vpoptions);
            $mform->setDefault('vpthreshold[1]', 0);
            $mform->disabledIf('vpthreshold[1]', 'detailgrading', 'ne', 3);
            $formgroup[] =& $mform->createElement('select', 'vpgrade[1]', '', $goptions);
            $mform->setDefault('vpgrade[1]', 0);
            $mform->disabledIf('vpgrade[1]', 'detailgrading', 'ne', 3);
            $mform->addElement('group', 'tg1vp', get_string("tgvp", 'rtrecording') . ' 1', $formgroup, array(' '), false);
            //$mform->addHelpButton('tg1vp', "tgvp", 'rtrecording');
            //$mform->setAdvanced('tg1', 'grade');

            $formgroup = array();
            $formgroup[] =& $mform->createElement('select', 'vpthreshold[2]', '', $vpoptions);
            $mform->setDefault('vpthreshold[2]', 0);
            $mform->disabledIf('vpthreshold[2]', 'detailgrading', 'ne', 3);
            $formgroup[] =& $mform->createElement('select', 'vpgrade[2]', '', $goptions);
            $mform->setDefault('vpgrade[2]', 0);
            $mform->disabledIf('vpgrade[2]', 'detailgrading', 'ne', 3);
            $mform->addElement('group', 'tg2vp', get_string("tgvp", 'rtrecording') . ' 2', $formgroup, array(' '), false);
            //$mform->addHelpButton('tg2vp', "tgvp", 'rtrecording');
            //$mform->setAdvanced('tg2', 'grade');

            $formgroup = array();
            $formgroup[] =& $mform->createElement('select', 'vpthreshold[3]', '', $vpoptions);
            $mform->setDefault('vpthreshold[3]', 0);
            $mform->disabledIf('vpthreshold[3]', 'detailgrading', 'ne', 3);
            $formgroup[] =& $mform->createElement('select', 'vpgrade[3]', '', $goptions);
            $mform->setDefault('vpgrade[3]', 0);
            $mform->disabledIf('vpgrade[3]', 'detailgrading', 'ne', 3);
            $mform->addElement('group', 'tg3vp', get_string("tgvp", 'rtrecording') . ' 3', $formgroup, array(' '), false);
            //$mform->addHelpButton('tg3vp', "tgvp", 'rtrecording');
            //$mform->setAdvanced('tg3', 'grade');
        }
//-------------------------------------------------------------------------------
        if ( isset($CFG->local_reminders) AND $CFG->local_reminders) {
            require_once($CFG->dirroot . '/local/reminders/lib.php');
            reminders_form($mform, true, true);
        }

//-------------------------------------------------------------------------------


//------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    function definition_after_data() {
        global $CFG, $COURSE, $DB, $USER, $AC;        

        $mform =& $this->_form;
        // this hack is needed for different settings of each subtype
        if (!empty($this->_instance)) {
            $rtrec = $DB->get_record('rtrecording', array('id' => $this->_instance));
            $eventid = $rtrec->eventid;
        }

            $urlgrp = $mform->getElementValue('urlgrp');
            $url = !empty($urlgrp['url']) ? $urlgrp['url'] : '';
            $name = $mform->getElementValue('name');
            if (!empty($url)) {
                if (is_numeric(substr($url, 0, 1))) $url = 'INVALID';
                if ($url != clean_text($url, PARAM_ALPHAEXT)) $url = 'INVALID';
                if (strpos($url, '/') OR strpos($url, ' ')) $url = 'INVALID';
            }

            if (!empty($url) AND $url != 'INVALID') {
                if (!empty($this->_instance)) {
                    $info = connect_get_sco($this->_instance, 0, 'rtrecording');
                } else {
                    $info = connect_get_sco_by_url($url);
                }

                if (isset($info->type)) {

                    $mform->setDefault('urlgrp', $url);

                    //Make URL field uneditable if editing existing activity
                    if (!empty($_REQUEST['update'])) {
                        $element =& $mform->createElement('text', 'url', get_string('url', 'rtrecording'));
                        $mform->setType('url', (!empty($CFG->formatstringstriptags)) ? PARAM_TEXT : PARAM_CLEAN);
                        $mform->insertElementBefore($element, 'urlgrp');
                        $mform->hardFreeze('url');
                        $mform->setDefault('url', $url);

                        $mform->removeElement('urlgrp', true);

                    }

                    $mform->setDefault('name', $info->name);
                    $mform->setDefault('introeditor', array('text' => $info->desc));

                    if (isset($eventid) AND $eventid AND $event = $DB->get_record('event', array('id' => $eventid))) {
                        $mform->setDefault('reminders', 1);
                        reminders_get($event->id, $mform);
                    }
                        
                } else {
                    $last_ac_code = !is_string($info) || $info == 'no-data' ? $info : 'no-access';

                    if ($last_ac_code == 'no-data') {
                        $element =& $mform->createElement('html', '<div class="fitem"><div class="felement fstatic alert alert-info">'
                                . get_string('notfound', 'rtrecording') . ': '
                                . get_string('typelist', 'rtrecording')
                                . get_string('whensaved', 'rtrecording')
                                . '</div></div>', '');
                    } else {
                        $mform->setDefault('url', '');
                        $element =& $mform->createElement('html', '<div class="fitem "><div class="felement fstatic error alert alert-danger">'
                                . get_string('no-access', 'rtrecording') . ': <b>' . $url . '</b>'
                                . '</div></div>', '');
                    }

                    $mform->insertElementBefore($element, 'name');

                }
            } elseif ($url == 'INVALID') {
                $mform->setDefault('url', 'INVALID');
            }

            if (isset($CFG->rtrecording_icondisplay) AND $CFG->rtrecording_icondisplay) {
                if (!empty($this->_instance)) $disp = $DB->get_field('rtrecording', 'display', array('id' => $this->_instance));
                if (!empty($disp)) {
                    preg_match('/data-options="([^"]+)"/', $disp, $matches);
                    if( isset( $matches[1] ) ){
                        $options = explode('~', $matches[1] );
                        $tags = explode('-', strtolower($options[0]));
                        $size = empty($tags[0]) ? 'large' : (($tags[0] == 'large' OR $tags[0] == 'medium' OR $tags[0] == 'small' OR $tags[0] == 'block') ? $tags[0] : 'large');
                        $silent = isset($tags[1]) ? strpos($tags[1], 's') !== false : false;
                        $norec = isset($tags[1]) ? strpos($tags[1], 'a') !== false : false;
                        $phone = isset($tags[1]) ? strpos($tags[1], 'p') !== false : false;
                        $guest = isset($tags[1]) ? strpos($tags[1], 'g') !== false : false;
                        $mouse = isset($tags[1]) ? strpos($tags[1], 'm') !== false : false;
                        $pos = isset($tags[1]) ? strpos($tags[1], 'l') !== false ? 'l' : 'c' : 'l';
                            
                        $xhtml = isset($options[3]) ? $options[3] : ''; 
                        $force = isset($options[4]) ? basename($options[4]) : ''; 
                        $size = empty($force) ? $size : 'custom';

                        $mform->setDefault('iconsize', $size);
                        $mform->setDefault('iconpos', $pos);
                        $mform->setDefault('iconsilent', $silent);
                        $mform->setDefault('iconphone', $phone);
                        //$mform->setDefault('iconmouse', $mouse);
                        $mform->setDefault('iconguests', $guest);
                        $mform->setDefault('iconnorec', $norec);
                        $xhtml = preg_replace( '/%%quote%%/', '"', $xhtml );
                        $mform->setDefault('extrahtml', $xhtml);

                    }

                    $draftitemid = file_get_submitted_draft_itemid('forceicon');
                    file_prepare_draft_area($draftitemid, $this->context->id, 'mod_rtrecording', 'content', 0, $this->_fmoptions);
                    $mform->setDefault('forceicon_filemanager', $draftitemid);
                }
            }
        
        parent::definition_after_data();
    }

    function data_preprocessing(&$data) {
        global $DB;

        parent::data_preprocessing($data);

        if (isset($data['id']) && is_numeric($data['id'])) {
            if ($gradings = $DB->get_records('rtrecording_grading', array('rtrecording_id' => $data['id']), 'threshold desc')) {
                $key = 1;
                foreach ($gradings as $grading) {
                    if ($data['detailgrading'] == 3) {
                        $data['vpthreshold[' . $key . ']'] = $grading->threshold;
                        $data['vpgrade[' . $key . ']'] = $grading->grade;
                    } else {
                        $data['threshold[' . $key . ']'] = $grading->threshold;
                        $data['grade[' . $key . ']'] = $grading->grade;
                    }
                    $key++;
                }
            }
        }
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (count($errors) == 0) {
            return true;
        } else {
            return $errors;
        }
    }
}
