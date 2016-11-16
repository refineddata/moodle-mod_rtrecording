<?php // $Id: connectpro.php,v 1.00 2008/04/07 09:37:58 terryshane Exp $

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/rtrecording/lib.php');
global $CFG, $DB, $PAGE, $USER;

$PAGE->set_url('/mod/rtrecording/launch.php');

$acurl = optional_param('acurl', '', PARAM_RAW);
$edit = optional_param('edit', 0, PARAM_INT);
$rtrecording_id = required_param('rtrecording_id', PARAM_INT);
$guest = optional_param('guest', 0, PARAM_INT);
$cm = 0;

$url = str_replace('/', '', $acurl);
if ( !isset( $guest ) || !$guest ) {
	require_login();
}

if ($rtrec = $DB->get_record('rtrecording', array('id' => $rtrecording_id))) {
    if ($course = $DB->get_record('course', array('id' => $rtrec->course))) {
        $context = context_course::instance($course->id);
        $PAGE->set_context($context);
        if ($cm = get_coursemodule_from_instance('rtrecording', $rtrec->id, $course->id)) {
            if ( !isset( $guest ) || !$guest ) {
                require_course_login($course, false, $cm, true, false, true);
                if( isset( $USER->id ) && $USER->id ){
                    connect_group_access( $USER->id, $course->id, true );
                }
            }
        }
    }

    $urlvars = array();
    if( $rtrec->hideplayer ){
        $urlvars[] = 'hideplayer=1';
    }
    if( $rtrec->start ){
        if( time() > $rtrec->start ){
            $offset = ( time() - $rtrec->start ) * 1000;
            $urlvars[] = 'archiveOffset='.$offset;

            $pass = 'ad83ld';
            $method = 'RdsRecordingOffset';
            $urlvars[] = 'satToLoc='.openssl_encrypt( $offset, $method, $pass );
        }else{
            // recording not open yet, redirect to holding page
            redirect( 'recording_not_yet_open.php?rtrecording_id='.$rtrec->id );
        }
    }
    foreach( $urlvars as $key => $vars ){
        $url.= $key ? '&' : '?';
        $url.= $vars;
    }
}

if (!$guest) {
    rtrecording_launch($rtrec->id, $rtrec->course, true, $cm);
}

$launch_url = connect_get_launch_url($url, 'meeting', $edit, '', $guest, 'rtrecording');
if (is_object($launch_url)){
    print_error($launch_url->error);
} else {
    header("Location: " . $launch_url);
}

exit(1);
