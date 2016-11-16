<?php
/**
 * connect_callback.php.
 *
 * @author     Dmitriy
 * @since      11/07/14
 */

define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/mod/rtrecording/lib.php');

// This should be accessed by only valid logged in user.
if (!isloggedin() or isguestuser()) {
    die('Invalid access.');
}

$update_from_adobe = optional_param('update_from_adobe', null, PARAM_ALPHANUMEXT);
$rtrecording_id = optional_param('rtrecording_id', null, PARAM_ALPHANUMEXT);
if($rtrecording_id){
    $rtrec = $DB->get_record('rtrecording', array('id' => $rtrecording_id));
}

if( !$rtrec ){
    echo '<div style="text-align:' . $iconalign . ';"><img src="' . $CFG->wwwroot
        . '/mod/rtrecording/images/notfound.gif"/><br/>'
        . get_string('notfound', 'mod_rtrecording')
        . '</div>';
    die;
}

if( $course = $DB->get_record( 'course', array( 'id' => $rtrec->course ) ) ){
	$PAGE->set_context(context_course::instance($course->id));
}else{
	$PAGE->set_context(context_system::instance());
}

if( $update_from_adobe ){
    $sco = connect_get_sco_by_url( $rtrec->url, 1 );
    if( $sco ){
        $rtrec->name = $sco->name;
        $rtrec->intro = $sco->desc;
        $rtrec->duration = $sco->end - $sco->start;
        $rtrec->ac_created = $sco->created;
        $DB->update_record( 'rtrecording', $rtrec );
    }
}

echo rtrecording_create_display( $rtrec );
