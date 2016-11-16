<?php
namespace mod_rtrecording\task;

class rtrecording_cron extends \core\task\scheduled_task {      
    public function get_name() {
        // Shown in admin screens
        return get_string('rtrecordingcron', 'mod_rtrecording');
    }
                                                                     
    public function execute() { 
        global $CFG;
        mtrace('++ Connect Cron Task: start');
        require_once($CFG->dirroot . '/mod/rtrecording/lib.php');
        rtrecording_cron_task();
        mtrace('++ Connect Cron Task: end');
    }                                                                                                                               
} 
