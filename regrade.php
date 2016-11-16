<?php // $Id: connect.php,v 1.00 2008/04/07 09:37:58 terryshane Exp $
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/connectmeeting/lib.php');
require_once($CFG->dirroot . '/mod/certificate/lib.php');
global $CFG, $DB;

$days = optional_param('days', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$shh = optional_param('return', 0, PARAM_RAW);

$all = is_siteadmin();

$shh = urldecode($shh);

if (!isset($days)) $days = 1;
$end = time();
$start = strtotime('-' . $days . ' days');
if (empty($shh)) $shh = false;

require_login();
$context = context_system::instance();

$PAGE->set_url('/mod/rtrecording/regrade.php');
$PAGE->set_context($context);
$PAGE->set_title('Regrading');
$PAGE->set_heading('Regrading');
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add('Regrading', $PAGE->url);

if (!$shh) echo $OUTPUT->header();

if (isset($id) AND $id) $sql = "SELECT * from {$CFG->prefix}connect WHERE course = $id";
else $sql = "SELECT * from {$CFG->prefix}connect WHERE start > $start AND start < $end AND type = 'meeting'";

if ($connects = $DB->get_records_sql($sql)) {
    if (!$shh) echo $OUTPUT->heading('FROM: ' . DATE('Y-m-d', $start) . ' TO: ' . DATE('Y-m-d', $end));
    foreach ($connects as $connect) {
        regrade_one($connect, $shh, $all);
    }
} else print_error('No Meetings in range (defaults to last day)');

// $DB->execute("UPDATE {$CFG->prefix}certificate_issues SET recertdate = UNIX_TIMESTAMP() WHERE recertdate = 0");
if (!$shh) echo $OUTPUT->footer();

if ($shh) redirect($shh);
die;

function regrade_one($connect, $shh, $all = false) {
    global $CFG, $DB;

    if (!$shh) {
        echo 'Regrading ' . $connect->name . '<br/>';
    }
    if ($connect->type == 'meeting') {
        connect_complete_meeting($connect);
    } elseif ($all) {
        connect_regrade_fullquiz($connect, $shh);
    } else {
        regrade_quiz($connect, $shh);
    }
    rebuild_course_cache($connect->course);

    return;
}

function regrade_quiz($connect, $shh) {
    global $CFG, $DB;

    connect_launch($connect->url, $connect->course, true);

    return;
}