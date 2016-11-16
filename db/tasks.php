<?php
defined('MOODLE_INTERNAL') || die();

$tasks = array(                                                                                                                     
    array(                                                                                                                          
        'classname' => 'mod_rtrecording\task\rtrecording_cron',                                                                            
        'blocking' => 0,                                                                                                            
        'minute' => '*/15',
        'hour' => '*',                                                                                                              
        'day' => '*',                                                                                                               
        'dayofweek' => '*',                                                                                                         
        'month' => '*'                                                                                                              
    )
);
