<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_connect_activity_task
 */

/**
 * Structure step to restore one connect activity
 */
class restore_rtrecording_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('rtrecording', '/activity/rtrecording');
        $paths[] = new restore_path_element('rtrecording_grade', '/activity/rtrecording/grades/grade');
        if ($userinfo) {
            $paths[] = new restore_path_element('rtrecording_entry', '/activity/rtrecording/entries/entry');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_rtrecording($data) {
        global $DB, $CFG;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->start = $this->apply_date_offset($data->start);
//        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if( $data->autocert ){
            $data->autocert = $this->get_mappingid('certificate', $data->autocert);
        }

        // insert the connect record
        $newitemid = $DB->insert_record('rtrecording', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
        // RT-394 Assign enrolled users to adobe group
        $rtrecording = $DB->get_record('rtrecording', array( 'id' => $newitemid));
        
        require_once( $CFG->dirroot . '/mod/rtrecording/lib.php' );
        $rtrecording->iconsize = 'large';
        $rtrecording->iconpos = 'l';
        $rtrecording->display = preg_replace( "/~$oldid/", "~$newitemid", $rtrecording->display );

        $DB->update_record( 'rtrecording', $rtrecording );
        
        $result = connect_use_sco($rtrecording->id, $rtrecording->url, 'rtrecording', $rtrecording->course);
    }

    protected function process_rtrecording_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->rtrecording_id = $this->get_new_parentid('rtrecording');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('rtrecording_grading', $data);
        $this->set_mapping('rtrecording_grading', $oldid, $newitemid);
    }

    protected function process_rtrecording_entry($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->rtrecording_id = $this->get_new_parentid('rtrecording');
        //$data->gradeid = $this->get_mappingid('rtrecording_grading', $oldid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('rtrecording_entries', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function after_execute() {
        // Add connect related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_rtrecording', 'intro', null);
        // Add force icon related files, matching by item id (connect)
        $this->add_related_files('mod_rtrecording', 'content', null);
    }
}
