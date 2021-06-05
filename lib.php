<?php // $Id: lib.php
/**
 * Library of functions and constants for module connect
 *
 * @author  Gary Menezes
 * @version $Id: lib.php
 * @package connect
 **/

require_once($CFG->dirroot . '/mod/rtrecording/connectlib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');

global $PAGE;
//$PAGE->requires->js('/mod/rtrecording/js/mod_rtrecording_coursepage.js');

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted connect record
 **/
function rtrecording_add_instance($rtrec) {
    global $CFG, $USER, $COURSE, $DB;
    require_once($CFG->libdir . '/gdlib.php');

    $cmid = $rtrec->coursemodule;

    $rtrec->timemodified = time();
    if (empty($rtrec->url) and !empty($rtrec->newurl)) {
        $rtrec->url = $rtrec->newurl;
    }
    
    $rtrec->url = preg_replace( '/\//', '', $rtrec->url ); // if someone tries to save with slashes, get ride of it

    $rtrec->display = '';
    $rtrec->complete = 0;
    
    if( !isset( $rtrec->displayoncourse ) ) $rtrec->displayoncourse = 0;

    $sco = connect_get_sco_by_url( $rtrec->url, 1 );
    $rtrec->duration = $sco->end - $sco->start;
    $rtrec->ac_created = $sco->created;

    //insert instance
    if ($rtrec->id = $DB->insert_record("rtrecording", $rtrec)) {
        // Update display to include ID and save custom file if needed
        $rtrec = rtrecording_set_forceicon($rtrec);
        $display = rtrecording_translate_display($rtrec);
        if ($display != $rtrec->display) {
            $DB->set_field('rtrecording', 'display', $display, array('id' => $rtrec->id));
            $rtrec->display = $display;
        }

        // Save the grading
        $DB->delete_records('rtrecording_grading', array('rtrecording_id' => $rtrec->id));
        if (isset($rtrec->detailgrading) && $rtrec->detailgrading) {
            for ($i = 1; $i < 4; $i++) {

                $grading = new stdClass;
                $grading->rtrecording_id = $rtrec->id;
                if ($rtrec->detailgrading == 3) {
                    $grading->threshold = $rtrec->vpthreshold[$i];
                    $grading->grade = $rtrec->vpgrade[$i];
                } else {
                    $grading->threshold = $rtrec->threshold[$i];
                    $grading->grade = $rtrec->grade[$i];
                }
                if (!$DB->insert_record('rtrecording_grading', $grading, false)) {
                    return "Could not save rtrecording grading.";
                }
            }
        }

        if (isset($rtrec->reminders) && $rtrec->reminders) {
            $event = new stdClass();
            $event->name = $rtrec->name;
            $event->description = isset($rtrec->intro) ? $rtrec->intro : '';
            $event->format = 1;
            $event->courseid = $rtrec->course;
            $event->modulename = 'rtrecording';
            $event->instance = $rtrec->id;
            $event->eventtype = 'course';
            $event->timestart = $rtrec->start;
            $event->timeduration = $rtrec->duration;
            $event->uuid = '';
            $event->visible = 1;
            $event->acurl = $rtrec->url;
            $event->timemodified = time();

            if ($event->id = $DB->insert_record('event', $event)) {
                $DB->set_field('rtrecording', 'eventid', $event->id, array('id' => $rtrec->id));
                $rtrec->eventid = $event->id;
                if (isset($CFG->local_reminders) AND $CFG->local_reminders) {
                    require_once($CFG->dirroot . '/local/reminders/lib.php');
                    reminders_update($event->id, $rtrec);
                }
            }
        }

        if (!empty($COURSE)) $course = $COURSE;
        else $course = $DB->get_record('course', 'id', $rtrec->course);
        $result = connect_use_sco($rtrec->id, $rtrec->url, 'rtrecording', $course->id);
        if (!$result) {
            return false;
        }

    }

    //create grade item for locking
    $entry = new stdClass;
    $entry->grade = 0;
    $entry->userid = $USER->id;
    rtrecording_gradebook_update($rtrec, $entry);

    return $rtrec->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function rtrecording_update_instance($rtrec) {
    global $CFG, $DB;

    $rtrec->timemodified = time();

    if (!isset($rtrec->detailgrading)) {
        $rtrec->detailgrading = 0;
    }

    if (isset($rtrec->iconsize) && $rtrec->iconsize == 'custom') {
        $rtrec = rtrecording_set_forceicon($rtrec);
    } else {
        $rtrec->forceicon = '';
    }
    $rtrec->display = rtrecording_translate_display($rtrec);
    $rtrec->complete = 0;
    
    $rtrec->url = preg_replace( '/\//', '', $rtrec->url ); // if someone tries to save with slashes, get ride of it
    
    if( !isset( $rtrec->displayoncourse ) ) $rtrec->displayoncourse = 0;
    
    $sco = connect_get_sco_by_url( $rtrec->url, 1 );
    $rtrec->duration = $sco->end - $sco->start;
    $rtrec->ac_created = $sco->created;

    //update instance
    if (!$DB->update_record("rtrecording", $rtrec)) {
        return false;
    }

    // Save the grading
    $DB->delete_records('rtrecording_grading', array('rtrecording_id' => $rtrec->id));
    if (isset($rtrec->detailgrading) && $rtrec->detailgrading) {
        for ($i = 1; $i < 4; $i++) {
            $grading = new stdClass;
            $grading->rtrecording_id = $rtrec->id;
            if ($rtrec->detailgrading == 3) {
                $grading->threshold = $rtrec->vpthreshold[$i];
                $grading->grade = $rtrec->vpgrade[$i];
            } else {
                $grading->threshold = $rtrec->threshold[$i];
                $grading->grade = $rtrec->grade[$i];
            }
            $grading->timemodified = time();
            if (!$DB->insert_record('rtrecording_grading', $grading, false)) {
                return false;
            }
        }
    }

    if (isset($rtrec->reminders) && $rtrec->reminders) {
        if (isset($rtrec->eventid) AND $rtrec->eventid){
        	$event = $DB->get_record('event', array('id' => $rtrec->eventid));
        }else{
        	$event = new stdClass();
        }

        $event->name = $rtrec->name;
        $event->description = isset($rtrec->intro) ? $rtrec->intro : '';
        $event->format = 1;
        $event->courseid = $rtrec->course;
        $event->modulename = 'rtrecording';
        $event->instance = $rtrec->id;
        $event->timestart = $rtrec->start;
        $event->timeduration = $rtrec->duration;
        $event->visible = 1;
        $event->uuid = '';
        $event->sequence = 1;
        $event->acurl = $rtrec->url;
        $event->timemodified = time();

        if (isset($event->id) AND $event->id) $DB->update_record('event', $event);
        else $event->id = $DB->insert_record('event', $event);

        if (isset($event->id) AND $event->id) {
            if ($rtrec->eventid != $event->id) $DB->set_field('rtrecording', 'eventid', $event->id, array('id' => $rtrec->id));

            if (isset($CFG->local_reminders) AND $CFG->local_reminders) {
                $DB->delete_records('reminders', array('event' => $event->id));
                require_once($CFG->dirroot . '/local/reminders/lib.php');
                reminders_update($event->id, $rtrec);
            }
        }
    } elseif (isset($rtrec->eventid) AND $rtrec->eventid) {
        $DB->delete_records('reminders', array('event' => $rtrec->eventid));
        $DB->delete_records('event', array('id' => $rtrec->eventid));
    }

    //create grade item for locking
    global $USER;
    $entry = new stdClass;
    $entry->grade = 0; 
    $entry->userid = $USER->id;
    rtrecording_gradebook_update($rtrec, $entry);

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function rtrecording_delete_instance($id) {
    global $DB;

    if (!$rtrec = $DB->get_record('rtrecording', array('id' => $id))) {
        return false;
    }

    // Delete area files (must be done before deleting the instance)
    $cm = get_coursemodule_from_instance('rtrecording', $id);
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_rtrecording');

    // Delete dependent records
    if (isset($rtrec->eventid) AND $rtrec->eventid) $DB->delete_records('reminders', array('event' => $rtrec->eventid));
    if (isset($rtrec->eventid) AND $rtrec->eventid) $DB->delete_records('event', array('id' => $rtrec->eventid));

    // Delete connect records
    $DB->delete_records("rtrecording_grading", array("rtrecording_id" => $id));
    $DB->delete_records("rtrecording_entries", array("rtrecording_id" => $id));
    $DB->delete_records("rtrecording", array('id' => $id));

    return true;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 **/
function rtrecording_user_outline($course, $user, $mod, $rtrec) {
    global $DB;

    if ($grade = $DB->get_record('rtrecording_entries', array('userid' => $user->id, 'rtrecording_id' => $rtrec->id))) {

        $result = new stdClass;
        if ((float)$grade->grade) {
            $result->info = get_string('grade') . ':&nbsp;' . $grade->grade;
        }
        $result->time = $grade->timemodified;
        return $result;
    }
    return NULL;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 **/
function rtrecording_user_complete($course, $user, $mod, $rtrec) {
    global $DB;

    if ($grade = $DB->get_record('rtrecording_entries', array('userid' => $user->id, 'rtrecording_id' => $rtrec->id))) {
        echo get_string('grade') . ': ' . $grade->grade;
        echo ' - ' . userdate($grade->timemodified) . '<br />';
    } else {
        print_string('nogrades', 'rtrecording');
    }

    return true;
}

/**
 * Loads the Types of connect Activities
 *
 * $returns array of object types
 **/
/*function rtrecording_get_types() {
    global $CFG;
    $types = array();
    
    $type = new stdClass();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "connect&amp;type=rtrecording";
    $type->typestr = get_string("modulename", 'rtrecording');
    $types["rtrecording"] = $type;

    return $types;
}
*/

/**
 * Runs each time cron runs.
 *  Updates meeting completion and recurring meetings.
 *  Gets and processes entries who's recheck time has elapsed.
 *
 * @return boolean
 **/
function rtrecording_cron_task() {
    echo '+++++ rtrecording_cron'."\n";
    global $CFG, $DB;
    $now = time();

    //Entries Every 15min
    if (!$entries = $DB->get_records_sql("SELECT * FROM {rtrecording_entries} WHERE rechecks > 0 AND rechecktime < $now")) return true;
    
    foreach ($entries as $entry) {
        echo '+++++' . $entry->id . "\n";
        rtrecording_do_grade_entry( $entry );
    }
    return true;
}

function rtrecording_do_grade_entry( $entry ){
    global $DB;

   if (!$rtrec = $DB->get_record("rtrecording", array("id" => $entry->rtrecording_id))) return;
   if (!$user = $DB->get_record("user", array("id" => $entry->userid))) return;

   $entry->timemodified = time();
   $entry->rechecks--;
   $entry->rechecktime = time() + $rtrec->loopdelay;
   if ($entry->rechecks < 0) $entry->rechecktime = 0;

   $oldgrade = isset( $entry->grade ) ? $entry->grade : 0;

   if (!rtrecording_grade_entry($user->id, $rtrec, $entry)) return;

   $DB->update_record('rtrecording_entries', $entry);

   if ($entry->grade == 100 AND $cm = get_coursemodule_from_instance('rtrecording', $rtrec->id)) {
       // Mark Users Complete
       if ($cmcomp = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cm->id, 'userid' => $user->id))) {
           $cmcomp->completionstate = 1;
           $cmcomp->viewed = 1;
           $cmcomp->timemodified = time();
           $DB->update_record('course_modules_completion', $cmcomp);
       } else {
           $cmcomp = new stdClass;
           $cmcomp->coursemoduleid = $cm->id;
           $cmcomp->userid = $user->id;
           $cmcomp->completionstate = 1;
           $cmcomp->viewed = 1;
           $cmcomp->timemodified = time();
           $DB->insert_record('course_modules_completion', $cmcomp);
       }
       rebuild_course_cache($rtrec->course);
   } 
}

function rtrecording_process_options(&$rtrec) {
    return true;
}

function rtrecording_install() {
    return true;
}

function rtrecording_get_view_actions() {
    return array('launch', 'view all');
}

function rtrecording_get_post_actions() {
    return array('');
}

function rtrecording_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return false;

        default:
            return null;
    }
}

function rtrecording_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    return $DB->record_exists('rtrecording_entries', array('rtrecording_id' => $cm->instance, 'userid' => $userid));
}

function rtrecording_cm_info_dynamic($mod) {
    global $DB, $USER;

    if (!$mod->available) return;

    $rtrec = $DB->get_record('rtrecording', array('id' => $mod->instance));
    if (!empty($rtrec->display) && $rtrec->displayoncourse) {
        $mod->set_content(rtrecording_create_display( $rtrec ));
        // If set_no_view_link is TRUE - it's not showing on Activity Report (https://app.liquidplanner.com/space/73723/projects/show/9961959)
        if( method_exists( $mod, 'rt_set_no_view_link' ) ){
            $mod->rt_set_no_view_link();
        }
    }
    return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other connect functions go here.  Each of them must have a name that
/// starts with connect_

/**
 * Called from /filters/connect/launch.php each time connect is launched.
 * Works out if it is an activity, and if so, updates the grade or sets up cron to.
 *
 * @param string $acurl The unique connect url for the resource
 * @param boolean $fullupdate Whethr all information should be updated even if max grade reached
 **/
function rtrecording_launch($rtrecording_id, $courseid = 1, $regrade = false, $cm = 0) {
    global $CFG, $USER, $DB, $PAGE;

    if (!$rtrec = $DB->get_record('rtrecording', array('id' => $rtrecording_id), '*', IGNORE_MULTIPLE)) {
        return;
    }

    $acurl = $rtrec->url;

    if (!$entry = $DB->get_record('rtrecording_entries', array('userid' => $USER->id, 'rtrecording_id' => $rtrec->id))) {
        $entry = new stdClass;
        $entry->rtrecording_id = $rtrec->id;
        $entry->userid = $USER->id;
        $entry->type = 'rtrecording';
        $entry->views = 0;
    }

    if (!is_siteadmin() AND isset($CFG->rtrecording_maxviews) AND $CFG->rtrecording_maxviews >= 0 AND isset($rtrec->maxviews) AND $rtrec->maxviews > 0 AND $rtrec->maxviews <= $entry->views) {
        $PAGE->set_url('/');
        notice(get_string('overmaxviews', 'rtrecording'), $CFG->wwwroot . '/course/view.php?id=' . $rtrec->course);
    }

    $entry->timemodified = time();
    $entry->views++;
    
    $oldgrade = isset( $entry->grade ) ? $entry->grade : 0;

    // Without detail grading, just set the grade to 100 and return
    if (!$rtrec->detailgrading) {
        $entry->grade = 100;
        rtrecording_gradebook_update($rtrec, $entry);
    } elseif (!isset($entry->grade) OR $entry->grade < 100) {
        rtrecording_grade_entry($USER->id, $rtrec, $entry);
    }
    
    $entry->rechecks = $entry->grade == 100 ? 0 : $rtrec->loops;
    $entry->rechecktime = $entry->grade == 100 ? 0 : time() + $rtrec->initdelay;

    if (!isset($entry->id)) {
        $DB->insert_record('rtrecording_entries', $entry);
    } else {
        $DB->update_record('rtrecording_entries', $entry);
    }

    if ($cm) {
        $course = $DB->get_record('course', array('id' => $courseid));
        //error_log('+++ $course' . json_encode($course));
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm)) {
            if ( $cm->completiongradeitemnumber == null and $cm->completionview == 1){
                $completion->set_module_viewed($cm);
            }
        }
    }

    if ($cm) {
        $description = "RTRecording ID: $entry->rtrecording_id";
        $action = 'rtrecording';
    	
        $event = \mod_rtrecording\event\rtrecording_launch::create(array(
            'objectid' => $rtrec->id,
            'other' => array('acurl' => $acurl, 'description' => "$action - $rtrec->name ( $acurl ) - $description")
        ));
        $event->trigger();
    }
}

/**
 * returns updated entry record based on grading
 * called from launch and cron
 *
 * @param char $url Custom URL of Adobe connect Resource
 * @param char $userid Login acp_login (Adobe connect Username)
 * @param object $connect Original connect record
 * @param object $entry Original entry record
 **/
function rtrecording_grade_entry($userid, $rtrec, &$entry) {
    global $CFG, $DB;

    $threshold = 0;

    $connect_instance = _connect_get_instance();
    $params = array(
        'external_connect_id' => $rtrec->id,
        'external_user_id'    => $userid,
        'start'               => $rtrec->start,
        'type'                => 'rtrecording'
    );    

    if( $rtrec->detailgrading == 1 ){
        $result =  $connect_instance->connect_call('vp-get-position', $params);
        $threshold = $result;
    }elseif( $rtrec->detailgrading == 2 ){
        $result =  $connect_instance->connect_call('vp-get-time', $params);
        $threshold = $result;
    }elseif( $rtrec->detailgrading == 3 ){
        $result =  $connect_instance->connect_call('vp-get-score', $params);
        $threshold = $result;
    }

    if ( $threshold && $specs = $DB->get_field_sql("SELECT MAX(grade) AS grade FROM {rtrecording_grading} WHERE rtrecording_id = $rtrec->id AND threshold <= $threshold AND threshold > 0")) {
        $grade = (int)$specs;
    } elseif ($specs = $DB->get_field_sql("SELECT MAX(grade) AS grade FROM {rtrecording_grading} WHERE rtrecording_id = {$rtrec->id} AND threshold > 0")) {
        $grade = 0;
    } else $grade = (int)$threshold;

    if (!isset($entry->grade) OR $entry->grade < $grade) {
        $entry->grade = $grade;
        rtrecording_gradebook_update($rtrec, $entry);
    }

    if ($grade == 100) {
        $entry->rechecks = 0;
        $entry->rechecktime = 0;
    }

    return true;
}

function rtrecording_gradebook_update($rtrec, $entry) {
    if( function_exists( 'local_connect_gradebook_update' ) ){
        return local_connect_gradebook_update( $rtrec, $entry, 'rtrecording' );
    }else{
        return false;
    }
}

function rtrecording_translate_display($rtrec, $forviewpage = 0) {

    global $CFG;

    if (empty($rtrec->url)) return '';
    if( !$forviewpage && ( empty($rtrec->iconsize) OR $rtrec->iconsize == 'none' ) ) return ''; 
    $flags = '-';

    if (!empty($rtrec->iconpos) AND $rtrec->iconpos) $flags .= $rtrec->iconpos;
    if (!empty($rtrec->iconsilent) AND $rtrec->iconsilent) $flags .= 's';
    if (!empty($rtrec->iconphone) AND $rtrec->iconphone) $flags .= 'p';
    //if (!empty($rtrec->iconmouse) AND $rtrec->iconmouse) $flags .= 'm';
    if (!empty($rtrec->iconguests) AND $rtrec->iconguests) $flags .= 'g';
    if (!empty($rtrec->iconnorec) AND $rtrec->iconnorec) $flags .= 'a';
    if (empty($rtrec->iconsize)) $rtrec->iconsize = '';

    $start = ''; //TODO - get start and end from Restrict Access area
    $end = ''; 
    $extrahtml = empty($rtrec->extrahtml) ? '' : $rtrec->extrahtml;

    if( !isset( $rtrec->iconsize ) )$rtrec->iconsize = 'large';
    $options = $rtrec->iconsize . $flags . '~' . $start . '~' . $end . '~' . $extrahtml . '~' . $rtrec->forceicon . '~' . $rtrec->id;

    $display = '<div class="rtrecording_display_block" ';
    $display.= 'data-courseid="' . $rtrec->course . '" ';
    $display.= 'data-acurl="' . $rtrec->url . '" ';
    $display.= 'data-rtrecording_id="' . $rtrec->id . '" ';
    $display.= 'data-options="' . preg_replace( '/"/', '%%quote%%', $options ) . '" >';
    $display.= '<div id="id_ajax_spin" class="rt-loading-image"></div>';
    $display.= '</div>';

    
    return $display;
}

function rtrecording_create_display( $rtrec ){
    global $USER, $CFG, $PAGE, $DB, $OUTPUT;
   
    if( !$rtrec ){
        echo '<div style="text-align:center;"><img src="' . $CFG->wwwroot
            . '/mod/rtrecording/images/notfound.gif"/><br/>'
            . get_string('notfound', 'mod_rtrecording')
            . '</div>';
        return;
    }
    
    if( !$rtrec->display ){
        $rtrec = rtrecording_set_forceicon($rtrec);
        $rtrec->display = rtrecording_translate_display( $rtrec, 1 );
        $DB->update_record( 'rtrecording', $rtrec );
    }
    preg_match('/data-options="([^"]+)"/', $rtrec->display, $matches);
    if( isset( $matches[1] ) ){
        $element = explode('~', $matches[1] );
    }

    $sizes = array(
        "medium" => "_md",
        "med" => "_md",
        "md" => "_md",
        "_md" => "_md",
        "small" => "_sm",
        "sml" => "_sm",
        "sm" => "_sm",
        "_sm" => "_sm",
        "block" => "_sm",
        "sidebar" => "_sm"
    );
    $breaks = array("_md" => "<br/>", "_sm" => "<br/>");

    $iconsize = '';
    $iconalign = 'center';
    $silent = false;
    $telephony = true;
    $mouseovers = true;
    $allowguests = false;
    $viewlimit = '';

    if (isset($element[0])) {
        $iconopts = explode("-", strtolower($element[0]));
        $iconsize = empty($iconopts[0]) ? '' : $iconopts[0];
        if (isset($iconopts[1])) {
            $silent = strpos($iconopts[1], 's') !== false; // no text output
            $allowguests = strpos($iconopts[1], 'g') !== false; // allow guest user access
            $mouseovers = strpos($iconopts[1], 'm') === false; // no mouseover
            if (strpos($iconopts[1], 'l') !== false) $iconalign = 'left';
            elseif (strpos($iconopts[1], 'r') !== false) $iconalign = 'right';
        }
    }
    $startdate = empty($element[1]) ? '' : $element[1];
    $extra_html = empty($element[3]) ? '' : $element[3];
    $extra_html = preg_replace( '/%%quote%%/', '"', $extra_html );
    $force_icon = empty($element[4]) ? '' : $element[4];
    $grouping = '';

    if ($rtrec->maxviews) {
        if (!$views = $DB->get_field('rtrecording_entries', 'views', array('rtrecording_id' => $rtrec->id, 'userid' => $USER->id))) $views = 0;
        $viewlimit = get_string('viewlimit', 'mod_rtrecording') . $views . '/' . $rtrec->maxviews . '<br/>';
    }

    // Check for grouping
    $grouping = '';
    $mod = get_coursemodule_from_instance('rtrecording', $rtrec->id, $rtrec->course);
    if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', context_course::instance($mod->course))) {
        $groupings = groups_get_all_groupings($mod->course);
        $textclasses = isset( $textclasses ) ? $textclasses : '';
        $grouping = html_writer::tag('span', '('.format_string($groupings[$mod->groupingid]->name).')',
                array('class' => 'groupinglabel '.$textclasses));
    }

    // Custom icon from activity settings
    if (!empty($force_icon)) {
        // get the custom icon file url
        // TODO consider storing file name in display so as not to fetch it from the database here
        if ($cm = get_coursemodule_from_instance('rtrecording', $rtrec->id, $rtrec->course, false)) {
            $context = context_module::instance($cm->id);
            $fs = get_file_storage();
            if ($files = $fs->get_area_files($context->id, 'mod_rtrecording', 'content', 0, 'sortorder', false)) {
                $iconfile = reset($files);

                $filename = $iconfile->get_filename();
                $path = "/$context->id/mod_rtrecording/content/0";
                $iconurl = moodle_url::make_file_url('/pluginfile.php', "$path/$filename");
                $iconsize = '';
                $icondiv = 'force_icon';
            }
        }

        // Custom icon from editor has the url in the force icon but no connect id
    } else if (!empty($force_icon)) {
        $iconurl = $force_icon;
        $iconsize = '';
        $icondiv = 'force_icon';
    }

    // No custom icon, see if there is a custom default for this type
    if (empty($iconurl)) {
        $iconsize = isset($sizes[$iconsize]) ? $sizes[$iconsize] : '';

        $context = context_system::instance();
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($context->id, 'mod_rtrecording', 'rtrecording_icon', 0, 'sortorder', false)) {
            $iconfile = reset($files);

            $filename = $iconfile->get_filename();
            $path = "/$context->id/mod_rtrecording/rtrecording_icon/0";
            $iconurl = moodle_url::make_file_url('/pluginfile.php', "$path/$filename");
            $icondiv = $icontype . '_icon' . $iconsize;

            if ($iconsize == '_md') {
                $iconforcewidth = 120;
            } elseif ($iconsize == '_sm') {
                $iconforcewidth = 60;
            } else {
                $iconforcewidth = 180;
            }

        }
    }    

    // No custom icon so just display the default icon
    if (empty($iconurl)) {
        $iconsize = isset($sizes[$iconsize]) ? $sizes[$iconsize] : '';
        $iconurl = new moodle_url("/mod/rtrecording/images/archive$iconsize.jpg");
        $icondiv = 'archive_icon' . $iconsize;
    }

    $strtime = '';

    if (isset($USER->timezone)){
        $timezone = $USER->timezone;
    } else {
        $timezone = $CFG->timezone;
    }
    if( $rtrec->start ){
        $strtime .= 'Scheduled Rebroadcast: '.userdate($rtrec->start, '%a %b %d, %Y, %I:%M%p', $timezone);
        $strtime.='<br />';
    }

    $strtime .= 'Originally Recorded On: '.userdate($rtrec->ac_created, '%a %b %d, %Y', $timezone);
    $strtime.='<br />';

    $init = $rtrec->duration;
    $hours = floor($init / 3600);
    $minutes = floor(($init / 60) % 60);
    $seconds = $init % 60;
    $strtime .= 'Duration: '. ($hours ? "$hours hours, " : '') . ($hours || $minutes ? "$minutes minutes, " : '') . "$seconds seconds";
    $strtime.='<br />';

    if (!$silent) {
        $font = '<font>';
        if ($iconsize == '_sm') {
            $font = '<font size="1">';
        }
        $instancename = html_writer::tag('span', $rtrec->name, array('class' => 'instancename')) . '<br/>';
        $aftertext = $font . $instancename . $strtime . $viewlimit . $grouping . $extra_html . '</font>';
    } else {
        $aftertext = $extra_html;
    }

    $linktarget = '_blank';
    $link = $CFG->wwwroot . '/mod/rtrecording/launch.php?acurl=' . $rtrec->url . '&guests=' . ($allowguests ? 1 : 0) . '&rtrecording_id=' . $rtrec->id;

    $overtext = '';
    if ($mouseovers || is_siteadmin($USER)) {
        $overtext = '<div align="right"><br /><br /><br />';
        //$overtext .= '<div align="left"><a href="' . $link . '" target="'.$linktarget.'" >';
        //$overtext .= '<b>' . get_string('launch_recording', 'mod_rtrecording') . '</a></b><br/>';

        if (!empty($rtrec->intro)) {
            $search = '/\[\[user#([^\]]+)\]\]/is';
            $rtrec->intro = preg_replace_callback($search, 'mod_rtrecording_user_callback', $rtrec->intro);
            $overtext .= str_replace("\n", "<br />", $rtrec->intro) . '<br/>';
        }

        if (($PAGE->context) && !empty($PAGE->context->id) && $PAGE->user_allowed_editing() && !empty($USER->editing) && empty(strstr($PAGE->url, 'launch')) && empty(strstr($PAGE->url, 'modedit')) && empty(strstr($PAGE->url, 'rest'))) {
            // for Moodle 3.3 onwards
            if (method_exists($OUTPUT, 'image_url')){
                $edit_icon = $OUTPUT->image_url('/t/edit');
                $return_icon = $OUTPUT->image_url('/i/return');
                $groups_icon = $OUTPUT->image_url('/t/groups');
                $calendar_icon = $OUTPUT->image_url('/t/calendar');
            } else {
                $edit_icon = $OUTPUT->pix_url('/t/edit');
                $return_icon = $OUTPUT->pix_url('/i/return');
                $groups_icon = $OUTPUT->pix_url('/t/groups');
                $calendar_icon = $OUTPUT->pix_url('/t/calendar');
            }

            $overtext .= '<a href="' . $link . '&edit=' . $rtrec->id . '" target="'.$linktarget.'" >';
            //$overtext .= '<img src="' . $CFG->wwwroot . '/mod/rtrecording/images/adobe.gif" border="0" align="middle"> ';
            //$overtext .= get_string('launch_edit', 'mod_rtrecording') . '</a><br/>';
            $overtext .= "<img src='" . $edit_icon . "' class='iconsmall' title='" . get_string('launch_edit', 'mod_rtrecording')  ."' />". "</a>";
            
            $overtext .= '<a href="#" id="rtrecording-update-from-adobe" data-rtrecordingid="'.$rtrec->id.'">';
            //$overtext .= '<img src="' . $CFG->wwwroot . '/mod/rtrecording/images/adobe.gif" border="0" align="middle"> ';
            //$overtext .= get_string('update_from_adobe', 'mod_rtrecording') . '</a><br/>';
            $overtext .= "<img src='" . $return_icon . "' class='iconsmall' title='" . get_string('update_from_adobe', 'connectquiz')  ."' />". "</a>";
        }
        $overtext .= '</div>';
    }

    $height = (isset($CFG->rtrecording_popup_height) ? 'height=' . $CFG->rtrecording_popup_height . ',' : '100');
    $width = (isset($CFG->rtrecording_popup_width) ? 'width=' . $CFG->rtrecording_popup_width . ',' : '100');

    $font = '';
    if ($iconsize == '_sm') $font = '<font size="1">';

    $onclick = $link;
    $onclick = str_replace("'", "\'", htmlspecialchars($link));
    $onclick = str_replace('"', '\"', $onclick);
    if( $linktarget == '_self' ){
        $onclick = "window.location.href='$onclick'";
    }else{
        $onclick = ' onclick="return window.open(' . "'" . $onclick . "' , 'connect', '{$height}{$width}menubar=0,location=0,scrollbars=0,resizable=1' , 0);" . '"';
    }

    $iconwidth = (isset($iconforcewidth)) ? "width=\"$iconforcewidth\" " : "";
    $iconheight = (isset($iconforceheight)) ? "height=\"$iconforceheight\" " : "";

    if( isset( $CFG->rtrecording_has_vp ) && $CFG->rtrecording_has_vp && !connect_check_vp_license_active() ){ // VP is expired
        $aftertext = '<div style="color:red;">Vantage Point Auto Record access is expired, grades will not be reported. Contact <a href="mailto:support@refineddata.com">support@refineddata.com</a>.</div><br />'.$aftertext;
    }

    $display = '<div id="recordingcontent'.$rtrec->id.'" style="text-align: '.$iconalign.'; width: 100%;">
        <div class="rtrecording-course-icon-'.$iconalign.'" id="'.$icondiv.'">
            <a href="'.$link.'" 
                '. ($mouseovers || is_siteadmin($USER) ? 'class="rtrecording_tooltip"' : '').'
                style="display: inline-block;" target="'.$linktarget.'">
                <img src="'.$iconurl.'" border="0"/>
            </a>

        </div>
        <div class="rtrecording-course-aftertext-'.$iconalign.'">
        '.$aftertext.'
        </div>
        <div class="rtrecording_popup" style="display: block;">
                '.$overtext.'
            </div>
    </div>';

    return $display;
}

function mod_rtrecording_user_callback($link) {
    global $CFG, $USER, $PAGE;
    $disallowed = array('password', 'aclogin', 'ackey');

    $PAGE->set_cacheable(false);
    // don't show any content to users who are not logged in using an authenticated account
    if (!isloggedin()) return;

    if (!isset($USER->{$link[1]}) || in_array($link[1], $disallowed)) return;

    return $USER->{$link[1]};
}

function rtrecording_set_forceicon($rtrec) {
    if( function_exists( 'local_connect_set_forceicon' ) ){
        return local_connect_set_forceicon( $rtrec, 'rtrecording' );
    }else{
        return false;
    }
}

/**
 * Serves the resource files.
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function rtrecording_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if( function_exists( 'local_connect_pluginfile' ) ){
        return local_connect_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options, 'rtrecording' );
    }else{
        return false;
    }
}

