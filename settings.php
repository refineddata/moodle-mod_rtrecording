<?php //$Id: settings.php,v 1.1.2.2 2007/12/19 17:38:41 skodak Exp $
//$settings = new admin_settingpage( 'mod_connect', get_string( 'settings', 'mod_connect' ) );
// Warning message if local_refined_services not installed or missing username or password
$message = '';
if (!$DB->get_record('config_plugins', array('plugin' => 'local_refinedservices', 'name' => 'version'))) {
    $message .= get_string('localrefinedservicesnotinstalled', 'rtrecording') . '<br />';
}
$rs_plugin_link = new moodle_url('/admin/settings.php?section=local_refinedservices');
if (empty($CFG->connect_service_username)) {
    $message .= get_string('connectserviceusernamenotgiven', 'rtrecording', array('url' => $rs_plugin_link->out())) . '<br />';
}

if (empty($CFG->connect_service_password)) {
    $message .= get_string('connectservicepasswordnotgiven', 'rtrecording', array('url' => $rs_plugin_link->out())) . '<br />';
}

if (!empty($message)) {
    $caption = html_writer::tag('div', $message, array('class' => 'notifyproblem'));
    $setting = new admin_setting_heading('refined_services_warning', $caption, '<strong>' . get_string('connectsettingsrequirement', 'rtrecording') . '</strong>');
    $settings->add($setting);
}

if( !function_exists( 'is_sitesuperadmin' ) || is_sitesuperadmin() ){
    $settings->add(new admin_setting_configselect('rtrecording_has_vp', get_string('has_vp', 'rtrecording'),
                        null, 0, array( 'No', get_string('has_vp_part', 'rtrecording' ), get_string('has_vp_full', 'rtrecording' ))));
}

if ( $hassiteconfig && !empty($CFG->connect_service_username) && !empty($CFG->connect_service_password) ) {

    $setting = new admin_setting_configcheckbox('refinedservices_debug', get_string('refinedservices_debug', 'rtrecording'),
        null, 0); 
    $setting->set_updatedcallback('rtrecording_update_config');
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox('rtrecording_icondisplay', get_string('icondisplay', 'rtrecording'),
            get_string('configicondisplay', 'rtrecording'), 1);
    $setting->set_updatedcallback('rtrecording_update_config');
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox('rtrecording_displayoncourse', get_string('displayoncourse', 'rtrecording'),
            get_string('configdisplayoncourse', 'rtrecording'), 1);
    $setting->set_updatedcallback('rtrecording_update_config');
    $settings->add($setting);
}

$settings->add(new admin_setting_configtext( 'rtrecording_popup_height', get_string( 'popup_height', 'mod_rtrecording' ), get_string( 'popup_height_hint', 'mod_rtrecording'), '800' ) );
$settings->add(new admin_setting_configtext( 'rtrecording_popup_width', get_string( 'popup_width', 'mod_rtrecording' ), get_string( 'popup_width_hint', 'mod_rtrecording'), '800' ) );

// Logo file setting.
$name = 'mod_rtrecording/rtrecording_icon';
$title = get_string('rtrecordingicon', 'rtrecording');
$description = get_string('rtrecordingicondesc', 'rtrecording');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'rtrecording_icon');
//$setting->set_updatedcallback('theme_reset_all_caches');
$settings->add($setting);

if ( $hassiteconfig && !empty($CFG->connect_service_username) && !empty($CFG->connect_service_password) ) {
    $setting = new admin_setting_configtext('rtrecording_maxviews', get_string('cfgmaxviews', 'rtrecording'),
        get_string('configmaxviews', 'rtrecording'), -1, PARAM_INT);
    $setting->set_updatedcallback('rtrecording_update_config');
    $settings->add($setting);

    if( isset( $CFG->rtrecording_has_vp ) && $CFG->rtrecording_has_vp ){
        $setting = new admin_setting_configcheckbox('rtrecording_hideplayer', get_string('hideplayer', 'rtrecording'),
            null, 0);
        $setting->set_updatedcallback('rtrecording_update_config');
        $settings->add($setting);
    }
}
if (isset($CFG->local_reminders)) {
    $settings->add(new admin_setting_configtext('local_reminders', get_string('rtrecording_reminders', 'mod_rtrecording'),
        get_string('rtrecording_reminders_desc', 'mod_rtrecording'), 3, PARAM_INT));
}

if (!function_exists('rtrecording_update_config')) {
    function rtrecording_update_config() {
        global $CFG;
        //die('rtrecording_update_config');
        $params = array();
        foreach ($CFG as $name => $value) {
            if (preg_match('/rtrecording_/', $name) || $name == 'refinedservices_debug') {
                $params[] = array('name' => $name, 'value' => $value);
            }
        }
        //var_dump($params);
        //die('rtrecording_update_config');
        if (!empty($params)) {
            require_once($CFG->dirroot . '/mod/rtrecording/connectlib.php');
            $connect = _connect_get_instance();
            return $connect->connect_call('setconfig', $params);
        }
    }
}
