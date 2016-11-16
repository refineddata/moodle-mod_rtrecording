<?php //$Id: upgrade.php

// This file keeps track of upgrades to 
// the connect module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

require_once( $CFG->dirroot.'/mod/rtrecording/connectlib.php' );

function xmldb_rtrecording_upgrade($oldversion = 0) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2015090800) {
        $table = new xmldb_table('rtrecording');
        $field = new xmldb_field('hideplayer', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'unenrol');
        if (!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);
    }

    if ($oldversion < 2015101901) {
        $table = new xmldb_table('rtrecording');
        $field = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'start');
        if (!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);
        $field = new xmldb_field('ac_created', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'duration');
        if (!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);
    }

    return true;
}
