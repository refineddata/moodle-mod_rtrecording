<?php  // $Id: view.php
require_once('../../config.php');
global $USER, $SITE, $DB, $OUTPUT;

$rtrecording_id  = optional_param( 'rtrecording_id', 0, PARAM_INT );

if ( !$rtrecording_id ) error( 'Must provide a recording id.' );

$rtrec = $DB->get_record( 'rtrecording', array( 'id' => $rtrecording_id ) );
if( !isset( $rtrec->id ) ) error( 'Invalid recording id' );

require_login();

$PAGE->set_url('/mod/rtrecording/recording_not_yet_open.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_context( context_course::instance($rtrec->course) );

$PAGE->requires->js('/mod/rtrecording/js/jquery.countdown.min.js');
$PAGE->requires->js('/mod/rtrecording/js/not_ready.js');
$PAGE->requires->css('/mod/rtrecording/css/not_ready.css');

$button = $OUTPUT->single_button( new moodle_url( '/course/view.php', array( 'id' => $rtrec->course ) ), get_string( 'backtocourse', 'mod_rtrecording' ) );

$PAGE->set_title( get_string( 'not_ready_title', 'mod_rtrecording' ) );
$PAGE->set_button( $button );

echo $OUTPUT->header();
echo "<div class='countdown-date' style='display:none;'>".date( 'Y/m/d H:i:s T', $rtrec->start )."</div>";
echo "<div class='area-content'>";
echo "<div class='not-ready-message'>";
echo get_string( 'recordingnotready', 'mod_rtrecording' );
echo "</div>";
echo "<div class='ready-message' style='display:none;'>";
echo get_string( 'recordingready', 'mod_rtrecording' );
echo "</div>";
echo "<span class='tab-content'></span>";
echo "<div class='launch-button' style='display:none;'>";
echo $OUTPUT->single_button( new moodle_url( '/mod/rtrecording/launch.php', array( 'acurl' => $rtrec->url, 'guests' => 0, 'rtrecording_id' => $rtrec->id ) ), get_string( 'launch', 'mod_rtrecording' ) );
echo "</div>";
echo "</div>";

echo $OUTPUT->footer();
