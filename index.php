<?php

/**
 * This page lists all the instances of rtrecording in a particular course
 *
 * @package    mod
 * @subpackage rtrecording
 * @copyright  Elvis Li <elvis.li@refineddata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);           // Course Module ID

// Ensure that the course specified is valid
if (!$course = $DB->get_record('course', array('id'=> $id))) {
    print_error('Course ID is incorrect');
}

// Requires a login
require_course_login($course);

// Declare variables
$currentsection = "";
$printsection = "";
$timenow = time();

// Strings used multiple times
$strrtrecordings = get_string('modulenameplural', 'rtrecording');
$strname  = get_string("name");
$strsectionname = get_string('sectionname', 'format_'.$course->format);

// Print the header
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/rtrecording/index.php', array('id'=>$course->id));
$PAGE->navbar->add($strrtrecordings);
$PAGE->set_title($strrtrecordings);
$PAGE->set_heading($course->fullname);

// Get the rtrecordings, if there are none display a notice
if (!$rtrecordings = get_all_instances_in_course('rtrecording', $course)) {
    echo $OUTPUT->header();
    notice(get_string('nortrecordings', 'rtrecording'), "$CFG->wwwroot/course/view.php?id=$course->id");
    echo $OUTPUT->footer();
    exit();
}

$table = new html_table();

$table->head  = array ($strname);

foreach ($rtrecordings as $rtrecording) {
    if (!$rtrecording->visible) {
        // Show dimmed if the mod is hidden
        $link = html_writer::tag('a', $rtrecording->name, array('class' => 'dimmed',
            'href' => $CFG->wwwroot . '/mod/rtrecording/view.php?id=' . $rtrecording->coursemodule));
    } else {
        // Show normal if the mod is visible
        $link = html_writer::tag('a', $rtrecording->name, array('class' => 'dimmed',
            'href' => $CFG->wwwroot . '/mod/rtrecording/view.php?id=' . $rtrecording->coursemodule));
    }

    $table->data[] = array ($link );

}

echo $OUTPUT->header();
echo '<br />';
echo html_writer::table($table);
echo $OUTPUT->footer();