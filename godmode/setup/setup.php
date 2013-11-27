<?php 

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars

global $config;
include_once('include/functions_setup.php');

check_login ();
	
if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access setup");
	require ("general/noaccess.php");
	exit;
}

$is_enterprise = false;
if (file_exists ("enterprise/load_enterprise.php")) {
	$is_enterprise = true;
}
	
/* Tabs list */
print_setup_tabs('setup', $is_enterprise);

$update = (bool) get_parameter ("update");

if ($update) {
	$config["block_size"] = (int) get_parameter ("block_size", 20);
	$config["language_code"] = (string) get_parameter ("language_code", "en_GB");
	$config["sitename"] = (string) get_parameter ("sitename", "Integria IMS");
	$config["fontsize"] = (int) get_parameter ("fontsize", 10);
	$config["incident_reporter"] = (int) get_parameter ("incident_reporter", 0);
	$config["timezone"] = get_parameter ("timezone", "Europe/Madrid");
	$config["api_acl"] = get_parameter ("api_acl", "*");
	$config["api_password"] = get_parameter ("api_password", "");
	$config["site_logo"] = get_parameter ("site_logo", "integria_logo.png");
    $config["header_logo"] = get_parameter ("header_logo", "integria_logo_header.png");
	$config["error_log"] = get_parameter ("error_log", 0);
	$config["flash_charts"] = get_parameter ("flash_charts", 1);
	$config["max_file_size"] = get_parameter ("max_file_size", 1);
	$config["enable_newsletter"] = get_parameter ("enable_newsletter", 0);
	$config["first_day_week"] = get_parameter ("first_day_week", 0);
	$config["access_protocol"] = get_parameter("access_protocol");
	$config["access_port"] = get_parameter("access_port", "");
	$config["access_public"] = get_parameter ("access_public", $_SERVER["SERVER_ADDR"]);

    if ($is_enterprise) {
		$config["enable_pass_policy"] = get_parameter ("enable_pass_policy", 0);
		$config["pass_size"] = get_parameter ("pass_size", 4);
		$config["pass_needs_numbers"] = get_parameter ("pass_needs_numbers", 0);
		$config["pass_needs_symbols"] = get_parameter ("pass_needs_symbols", 0);
		$config["pass_expire"] = get_parameter ("pass_expire", 0);
		$config["first_login"] = get_parameter ("first_login", 1);
		$config["mins_fail_pass"] = get_parameter ("mins_fail_pass", 5);
		$config["number_attempts"] = get_parameter ("number_attempts", 5);
	}
 
    update_config_token ("timezone", $config["timezone"]);	

    //TODO: Change all "process_sqlxxx" for update_config_token in following code:

	update_config_token("language_code", $config["language_code"]);
    update_config_token ("sitename", $config["sitename"]);
    update_config_token ("max_file_size", $config["max_file_size"]);

	process_sql ("DELETE FROM tconfig WHERE token = 'incident_reporter'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('incident_reporter', '".$config["incident_reporter"]."')");
	update_config_token ("api_acl", $config["api_acl"]);
	update_config_token ("api_password", $config["api_password"]);
    update_config_token ("error_log", $config["error_log"]);
    update_config_token ("enable_newsletter", $config["enable_newsletter"]);
    update_config_token ("first_day_week", $config["first_day_week"]);

	update_config_token ("access_protocol", $config["access_protocol"]);
	update_config_token ("access_port", $config["access_port"]);	
	update_config_token ("access_public", $config["access_public"]);

    if ($is_enterprise) {
		update_config_token ("enable_pass_policy", $config["enable_pass_policy"]);
		update_config_token ("pass_size", $config["pass_size"]);
		update_config_token ("pass_needs_numbers", $config["pass_needs_numbers"]);
		update_config_token ("pass_needs_symbols", $config["pass_needs_symbols"]);
		update_config_token ("pass_expire", $config["pass_expire"]);
		update_config_token ("first_login", $config["first_login"]);
		update_config_token ("mins_fail_pass", $config["mins_fail_pass"]);
		update_config_token ("number_attempts", $config["number_attempts"]);
	}
	
	# Update of Integria license 
	$update_manager_installed = get_db_value('value', 'tconfig', 'token', 'update_manager_installed');
	
	if ($update_manager_installed == 1) {
		$license_info_key = get_parameter('license_info_key', '');
		if (empty($license_info_key)) {
			$license_info_key = 'INTEGRIA-FREE';
		}
		
		$sql_update = "UPDATE tupdate_settings SET `value`='$license_info_key'
			WHERE `key`='customer_key'";
		$update_manage_settings_result = process_sql($sql_update);
	}
	
}
// Render SYSTEM language code, not current language.
$table->width = '99%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();

$incident_reporter_options[0] = __('Disabled');
$incident_reporter_options[1] = __('Enabled');

$table->data[0][0] = print_select_from_sql ('SELECT id_language, name FROM tlanguage ORDER BY name',
	'language_code', $config['language_code'], '', '', '', true, false, false,
	__('Language'));

$table->data[0][1] = print_input_text ("sitename", $config["sitename"], '',
	30, 50, true, __('Sitename'));

$error_log_options[0] = __('Disabled');
$error_log_options[1] = __('Enabled');
$table->data[1][0] = print_select ($error_log_options, "error_log", $config["error_log"], '','','',true,0,true, __('Error log'));

$table->data[1][0] .= print_help_tip (__("This errorlog is on /integria.log"), true);

$table->data[1][1] = print_input_text ("timezone", $config["timezone"], '',
	15, 30, true, __('Timezone for integria'));

$table->data[2][0] = print_input_text ("api_acl", $config["api_acl"], '',
	30, 255, true, __('List of IP with access to API'));
	
$table->data[2][0] .= print_help_tip (__("List of IP (separated with commas which can access to the integria API. Use * for any address (INSECURE!)"), true);

$table->data[2][1] = print_input_text ("api_password", $config["api_password"], '',
	30, 255, true, __('API password'));


$table->data[3][0] = print_input_text ("max_file_size", $config["max_file_size"], '',
	10, 255, true, __('Max. Upload file size'));
		
$newsletter_options[0] = __('Disabled');
$newsletter_options[1] = __('Enabled');
$table->data[3][1] = print_select ($newsletter_options, "enable_newsletter", $config["enable_newsletter"], '','','',true,0,true, __('Enable newsletter'));


$table->data[3][1] .= print_help_tip (__("Enable this option to activate the newsletter feature of Integria IMS"), true);

$newsletter_options[0] = __('Disabled');
$newsletter_options[1] = __('Enabled');

$table->data[4][0] = __('License information');
$license_info = get_db_value ('value', 'tupdate_settings', '`key`', 'customer_key');
if ($license_info === false)
	$license_info = '';
$table->data[4][0] = print_input_text ('license_info_key', $license_info, '', 40, 255, true, __('License key'));
$table->data[4][0] .= '&nbsp;<a id="dialog_license_info" title="'.__("License Info").'" href="javascript: show_license_info()">'.print_image('images/lock.png', true, array('class' => 'bot', 'title' => __('License info'))).'</a>';
$table->data[4][0] .= '<div id="dialog_show_license" style="display:none"></div>';	

$days_of_week = get_days_of_week();
$table->data[4][1] = print_select ($days_of_week, "first_day_week", $config["first_day_week"], '','','',true,0,false, __('First day of the week'));

$table->data[5][0] = print_input_text ("url_updatemanager", $config["url_updatemanager"], '',
	60, 255, true, __('URL update manager'));

$table->data[6][0] = print_checkbox ("access_protocol", 1, $config["access_protocol"], true, __('Enable HTTPS access'));

$table->data[6][1] = print_input_text ("access_port", $config["access_port"], '',
	10, 255, true, __('Access port'));
$table->data[6][1] .= print_help_tip (__("Leave blank to use default port (80)"), true);

$table->data[7][0] = print_input_text ("access_public", $config["access_public"],
        '', 30, 50, true, __('Public access to server'));

$table->data[7][0] .= print_help_tip (__("Public IP or name for the server, for example (23.45.67.3 or mydomain.com)"), true);

$table->data[8][0] = print_input_hidden ('update', 1, true);
$table->data[8][0] .= print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"', true);
$table->colspan[8][0] = 2;

echo "<form name='setup' method='post'>";

print_table ($table);
echo '</form>';
?>

<script type="text/javascript" src="include/js/integria.js"></script>

<script type="text/javascript">
$(document).ready (function () {
	$("textarea").TextAreaResizer ();
});
</script>
