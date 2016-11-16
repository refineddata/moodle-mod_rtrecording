<?php // $Id: index.php
/**
 * This page lists all the instances of a connect Activity in a particular course
 *
 * @author  Gary Menezes
 * @version $Id: index.php
 * @package connect
 **/
    require_once( "../../config.php" );
    global $CFG, $DB;

    $id = optional_param( 'id', 0, PARAM_INT );
    if ( !$course = $DB->get_record( 'course', array( 'id'=>$id ) ) ) print_error( 'Course ID is incorrect-' . $id );

    require_login( $course );
    $event = \mod_connect\event\course_module_instance_list_viewed::create(array(
    		'context' => context_course::instance($course->id)
    ));
    $event->trigger();
    

    $strtitle       = get_string( "modulenameplural", "rtrecording" );
    $strname        = get_string( 'name' );
    
    $PAGE->set_url( '/mod/rtrecording/index.php', array( 'id'=>$id ) );
    $PAGE->set_pagelayout( 'incourse' );
    $PAGE->set_title( $strtitle );
    $PAGE->set_heading( $course->fullname );
    $PAGE->navbar->add( $strtitle );
    echo $OUTPUT->header();

    if (! $rtrecs = get_all_instances_in_course( "rtconnect", $course ) ) notice( get_string( 'thereareno', 'moodle', $strtitle ), "../../course/view.php?id=$course->id" );

    $table = new html_table();

    foreach ($rtrecs as $rtrec) {
        $table->data[] = array( '<a href="view.php?a=' . $rtrec->id . '">' . $rtrec->name . '</a>' );
    }
    echo '<br />';

    if ( !empty( $table ) ) echo html_writer::table( $table );

    echo $OUTPUT->footer();
?>
