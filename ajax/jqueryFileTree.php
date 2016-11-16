<?php
if( !array_key_exists('HTTP_REFERER', $_SERVER) ) exit('No direct script access allowed');

require_once('../../../config.php');
//global $CFG, $SESSION;
require_login();

require_once($CFG->dirroot . '/mod/rtrecording/lib.php');

$scoid = optional_param('dir', null, PARAM_RAW);
$scoid = preg_replace('/\//', '', $scoid );

echo "<ul class='jqueryFileTree'>";

if (!isset($scoid) OR !$scoid) {
    $shortcuts = connect_get_sco_shortcuts();
    if (!is_array($shortcuts)) {
        die($shortcuts);
    }
    foreach ($shortcuts as $sco) {
        if ($sco->type == 'my-meetings') cp_one('direcotyr', 'My-Meetings', $sco->sco_id);
        if ($sco->type == 'user-meetings') cp_one('direcotyr', 'User-Meetings', $sco->sco_id);
        if ($sco->type == 'meetings') cp_one('direcotyr', 'Shared-Meetings', $sco->sco_id);
        if ($sco->type == 'my-content') cp_one('direcotyr', 'My-content', $sco->sco_id);
        if ($sco->type == 'user-content') cp_one('direcotyr', 'User-content', $sco->sco_id);
        if ($sco->type == 'content') cp_one('direcotyr', 'Shared-content', $sco->sco_id);
    }   
}else{
    $scos = connect_get_sco_contents($scoid);
    if (!is_array($scos)) {
        die($scos);
    }
    $first = 1;
    $norecordings = 1;
    foreach ($scos as $sco) {
        $datecreated = $sco->date_created ? userdate(strtotime($sco->date_created), '%a %b %d, %Y %l:%M %p', $USER->timezone) : '';
        $datemod = $sco->date_modified ? userdate(strtotime($sco->date_modified), '%a %b %d, %Y %l:%M %p', $USER->timezone) : '';

        cp_one($sco->type, $sco->name, $sco->sco_id, $sco->icon, $sco->url_path, $datecreated, $datemod);
    }
    if( $norecordings ){
        // no recordings in this folder
        echo "<li>
            <div style='float: left; width: 95%; overflow: hidden;'>No Recordings</div>
            </li>";
    }
}

echo "</ul>";

function cp_one($type, $name, $scoid, $icon = 'folder', $url = '', $datecreated = '', $datemod = '') {
    global $first, $norecordings;

    if ($type == 'meeting') {
        echo "<li class='directory collapsed'><a href='#' style='width:100%;' rel='/" .$scoid. "/'>
                <div style='float: left; width: 95%; overflow:hidden;'>" . $name ." - ( ".$url." )</div>
            </a></li>";
        $norecordings = 0;
    } elseif( $type == 'content' ) {
        if( $icon == 'archive' ){
            if( $first ){
                echo "<li><a style='width:100%;'>
                    <div style='float: left; width: 45%; overflow: hidden;'>Name</div>
                    <div style='float: left; width: 15%; overflow: hidden; padding-left:10px'>Created</div>
                    <div style='float: left; width: 15%; overflow: hidden; padding-left:10px'>Modified</div>
                    <div style='float: left; width: 20%; overflow: hidden; padding-left:10px'>Url</div>
                    </a></li>";
                echo "<li><a style='width:100%;'>
                    <div style='float: left; width: 95%; overflow: hidden;'><hr style='margin: 8px 0;' /></div>
                    </a></li>";
                $first = 0;
            }

            echo "<li class='file ext_mp4'><a href='#' style='width:100%;' rel='" . $url . "'>
                    <div style='float: left; width: 39%; overflow:hidden;'>" . $name ."</div>
                    <div style='float: left; width: 21%; overflow: hidden; padding-left:10px;'>" . $datecreated . "</div>
                    <div style='float: left; width: 21%; overflow: hidden; padding-left:10px;'>" . $datemod . "</div>
                    <div style='float: left; width: 14%; overflow: hidden; padding-left:10px;'>" . $url . "</div>
            </a></li>";
            $norecordings = 0;
        }
    } else {
        echo "<li class='directory collapsed'><a href='#' style='width:100%;' rel='/" .$scoid. "/'>
                <div style='float: left; width: 95%;'>" . $name . "</div>
            </a></li>";
        $norecordings = 0;
    }
}
?>
