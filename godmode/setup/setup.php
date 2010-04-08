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

check_login ();
	
if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access setup");
	require ("general/noaccess.php");
	exit;
}

$update = (bool) get_parameter ("update");

if ($update) {
	$config["block_size"] = (int) get_parameter ("block_size", 20);
	$config["language_code"] = (string) get_parameter ("language_code", "en");
	$config["no_wu_completion"] = (string) get_parameter ("no_wu_completion", "");
	$config["currency"] = (string) get_parameter ("currency", "€");
	$config["hours_perday"] = (int) get_parameter ("hours_perday", "8");
	$config["sitename"] = (string) get_parameter ("sitename", "Integria IMS");
	$config["limit_size"] = (int) get_parameter ("limit_size");
	$config["autowu_completion"] = (int) get_parameter ("autowu_completion", 0);
	$config["fontsize"] = (int) get_parameter ("fontsize", 10);
	$config["incident_reporter"] = (int) get_parameter ("incident_reporter", 0);
	$config["show_owner_incident"] = (int) get_parameter ("show_owner_incident", 0);
	$config["show_creator_incident"] = (int) get_parameter ("show_creator_incident", 0);
	$config["pwu_defaultime"] = get_parameter ("pwu_defaultime", 4);
	$config["iwu_defaultime"] = get_parameter ("iwu_defaultime", 0.25);
	$config["timezone"] = get_parameter ("timezone", "Europe/Madrid");
	$config["api_acl"] =get_parameter ("api_acl", "*");
	$config["auto_incident_close"] = get_parameter ("auto_incident_close", "72");

	$config["site_logo"] = get_parameter ("site_logo", "integria_logo.png");
    $config["header_logo"] = get_parameter ("header_logo", "integria_logo_header.png");

	update_config_token ("timezone", $config["timezone"]);	

	process_sql ("UPDATE tconfig SET value='".$config["block_size"]."' WHERE token='block_size'");
	process_sql ("UPDATE tconfig SET value='".$config["language_code"]."' WHERE token='language_code'");
	
	process_sql ("UPDATE tconfig SET value='".$config["hours_perday"]."' WHERE token='hours_perday'");
	process_sql ("UPDATE tconfig SET value='".$config["currency"]."' WHERE token='currency'");
	
	process_sql ("UPDATE tconfig SET value='".$config["sitename"]."' WHERE token='sitename'");
	process_sql ("UPDATE tconfig SET value='".$config["limit_size"]."' WHERE token='limit_size'");

	process_sql ("DELETE FROM tconfig WHERE token = 'autowu_completion'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('autowu_completion', '".$config["autowu_completion"]."')");

	process_sql ("DELETE FROM tconfig WHERE token = 'no_wu_completion'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('no_wu_completion', '".$config["no_wu_completion"]."')");

	process_sql ("DELETE FROM tconfig WHERE token = 'fontsize'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('fontsize', '".$config["fontsize"]."')");

	process_sql ("DELETE FROM tconfig WHERE token = 'incident_reporter'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('incident_reporter', '".$config["incident_reporter"]."')");
	
	process_sql ("DELETE FROM tconfig WHERE token = 'show_creator_incident'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('show_creator_incident', '".$config["show_creator_incident"]."')");

	process_sql ("DELETE FROM tconfig WHERE token = 'show_owner_incident'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('show_owner_incident', '".$config["show_owner_incident"]."')");

	update_config_token ("pwu_defaultime", $config["pwu_defaultime"]);
	update_config_token ("iwu_defaultime", $config["iwu_defaultime"]);
	update_config_token ("api_acl", $config["api_acl"]);
    update_config_token ("auto_incident_close", $config["auto_incident_close"]);
    update_config_token ("site_logo", $config["site_logo"]);
    update_config_token ("header_logo", $config["header_logo"]);

}

echo "<h2>".__('General setup')."</h2>";

$table->width = '90%';
$table->class = 'databox';
$table->colspan = array ();
$table->data = array ();

$table->data[0][0] = print_select_from_sql ('SELECT id_language, name FROM tlanguage ORDER BY name',
	'language_code', $config['language_code'], '', '', '', true, false, false,
	__('Language'));

$table->data[0][1] = print_input_text ("no_wu_completion", $config["no_wu_completion"], '',
	20, 500, true, __('No WU completion users'));
$table->data[0][1] .= integria_help ("no_wu_completion", true);

$table->data[1][0] = print_input_text ("block_size", $config["block_size"], '',
	5, 5, true, __('Block size for pagination'));

$table->data[1][1] = print_input_text ("limit_size", $config["limit_size"], '',
	5, 5, true, __('Max. data limit size'));
$table->data[1][1] .= integria_help ("limit_size", true);

$table->data[2][0] = print_input_text ("autowu_completion", $config["autowu_completion"],
	'', 7, 7, true, __('Auto WU Completion (days)'));
$table->data[2][0] .= integria_help ("autowu_completion", true);

$table->data[2][1] = print_input_text ("hours_perday", $config["hours_perday"], '',
	5, 5, true, __('Work hours per day'));
$table->data[2][1] .= integria_help ("hours_perday", true);

$table->data[3][0] = print_input_text ("sitename", $config["sitename"], '',
	30, 50, true, __('Sitename'));
$table->data[3][1] = print_input_text ("currency", $config["currency"], '',
	3, 3, true, __('Currency'));

$table->data[4][0] = print_input_text ("iwu_defaultime", $config["iwu_defaultime"], '',
	5, 5, true, __('Incident WU Default time'));
$table->data[4][1] = print_input_text ("pwu_defaultime", $config["pwu_defaultime"], '',
	5, 5, true, __('Project WU Default time'));

$table->data[5][0] = print_input_text ("fontsize", $config["fontsize"], '',
	3, 5, true, __('Graphics font size'));

$incident_reporter_options[0] = "Disabled";
$incident_reporter_options[1] = "Enabled";

$table->data[5][1] = print_select ($incident_reporter_options, "incident_reporter", $config["incident_reporter"], '','','',true,0,true, "Incident reporter");

$table->data[6][0] = print_select ($incident_reporter_options, "show_owner_incident", $config["show_owner_incident"], '','','',true,0,true, "Show incident owner");

$table->data[6][1] = print_select ($incident_reporter_options, "show_creator_incident", $config["show_creator_incident"], '','','',true,0,true, "Show incident creator");

$table->data[10][0] = print_input_text ("timezone", $config["timezone"], '',
	15, 30, true, __('Timezone for integria'));

$table->data[10][1] = print_input_text ("auto_incident_close", $config["auto_incident_close"], '',
	10, 10, true, __('Auto incident close'));
$table->data[10][1] .= integria_help ("auto_incident_close", true);

$table->data[11][0] = print_input_text ("api_acl", $config["api_acl"], '',
	30, 255, true, __('List of IP with access to API'));


function get_image_files () {
	$base_dir = 'images';
	$files = list_files ($base_dir, ".png", 1, 0);
	
	$retval = array ();
	foreach ($files as $file) {
		$retval[$file] = $file;
	}
	
	return $retval;
}

$imagelist = get_image_files ();
$table->data[12][0] = print_select ($imagelist, 'site_logo', $config["site_logo"], '', '', '',  true, 0, true, "Site logo") ;

$table->data[12][1] = print_select ($imagelist, 'header_logo', $config["header_logo"], '', '', '',  true, 0, true, "Header logo") ;

echo "<form name='setup' method='post'>";

print_table ($table);

echo '<div style="width: '.$table->width.'" class="button">';
print_input_hidden ('update', 1);
print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>

<script type="text/javascript">
$(document).ready (function () {
	$("textarea").TextAreaResizer ();
});
</script>
