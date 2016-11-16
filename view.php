<?php // $Id: view.php
/**
 * This page prints a particular instance of a connect Activity
 *
 * @author  Gary Menezes
 * @version $Id: view.php
 * @package connect
 **/

require_once("../../config.php");
require_once("$CFG->dirroot/mod/rtrecording/lib.php");
global $CFG, $OUTPUT, $PAGE;

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$cid = optional_param('a', 0, PARAM_INT); // connect ID

if (isset($id) AND $id) {
    if (!$cm = $DB->get_record("course_modules", array("id" => $id))) print_error(get_string("moduleiderror", "rtrecording"));
    if (!$course = $DB->get_record("course", array("id" => $cm->course))) print_error(get_string("courseerror", "rtrecording"));
    if (!$rtrec = $DB->get_record("rtrecording", array("id" => $cm->instance))) print_error(get_string("iderror", "rtrecording"));
} else {
    if (!$rtrec = $DB->get_record("rtrecording", array("id" => $cid))) print_error(get_string("iderror", "rtrecording"));
    if (!$course = $DB->get_record("course", array("id" => $rtrec->course))) print_error(get_string("courseerror", "rtrecording"));
    $cm = get_coursemodule_from_instance('rtrecording', $rtrec->id);
}

require_login($course);
$context = context_course::instance($course->id);
$strtitle = get_string('view');

$PAGE->set_url('/mod/rtrecording/view.php?id=' . $id);
$PAGE->set_context($context);
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add($strtitle, $PAGE->url);

$event = \mod_rtrecording\event\course_module_viewed::create(array(
    'objectid' => $cm->instance,
    'context' => context_module::instance($cm->id),
));
$event->add_record_snapshot('course', $course);
// In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
$event->add_record_snapshot('rtrecording', $rtrec);
$event->trigger();


//    $PAGE->requires->jquery();
//	$PAGE->requires->jquery_plugin('qtip');
//	$PAGE->requires->jquery_plugin('qtip-css');

echo $OUTPUT->header();
include($CFG->dirroot . '/filter/connect/scripts/styles.css');

$text = rtrecording_create_display( $rtrec );

echo $text;

echo '<br/><br/><center>' . $OUTPUT->single_button($CFG->wwwroot . '/course/view.php?id=' . $course->id, get_string('returntocourse', 'rtrecording')) . '</center>';
echo $OUTPUT->footer();
