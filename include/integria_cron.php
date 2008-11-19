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

include ("config.php");
require_once ($config["homedir"].'/include/functions_calendar.php');
require_once ($config["homedir"].'/include/functions_groups.php');

$config["id_user"] = 'System';
$now = time ();
$compare_timestamp = date ("Y-m-d H:i:s", $now - $config["notification_period"]);
$human_notification_period = give_human_time ($config["notification_period"]);

/**
 * This function is executed once per day and do several different subtasks
 * like email notify ending tasks or projects, events for this day, etc.
 */
function run_daily_check () {
	$current_date = date ("Y-m-d h:i:s");

	// Create a mark in event log
	process_sql ("INSERT INTO tevent (type, timestamp) VALUES ('DAILY_CHECK', '$current_date') ");

	// Do checks
	run_calendar_check ();
	run_project_check ();
	run_task_check ();
}

/**
 * Checks and notify user by mail if in current day there agenda items 
 */
function run_calendar_check () {
	global $config;

	$now = date ("Y-m-d");
	$events = get_event_date ($now, 1, "_ANY_");

	foreach ($events as $event){
		list ($timestamp, $event_data, $user_event) = split ("\|", $event);
		$user = get_db_row ("tusuario", "id_usuario", $user_event);
		$nombre = $user['nombre_real'];
		$email = $user['direccion'];
		$mail_description = "There is an Integria agenda event planned for today at ($timestamp): \n\n$event_data\n\n";
		integria_sendmail ($email, "[".$config["sitename"]."] Calendar event for today ",  $mail_description );
	}
}

/**
 * Checks and notify user by mail if in current day there are ending projects
 *
 */
function run_project_check () {
	global $config;

	$now = date ("Y-m-d");
	$projects = get_project_end_date ($now, 0, "_ANY_");

	foreach ($projects as $project){
		list ($pname, $idp, $pend, $owner) = split ("\|", $project);
		$user = get_db_row ("tusuario", "id_usuario", $owner);
		$nombre = $user['nombre_real'];
		$email = $user['direccion'];
		$mail_description = "There is an Integria project ending today ($pend): $pname. \n\nUse this link to review the project information:\n";
		$mail_description .= $config["base_url"]. "/index.php?sec=projects&sec2=operation/projects/task&id_project=$idp\n";

		integria_sendmail ($email, "[".$config["sitename"]."] Project ends today ($pname)",  $mail_description );
	}
}


/**
 * Checks and notify user by mail if in current day there are ending tasks
 */
function run_task_check () {
	global $config;

	$now = date ("Y-m-d");
	$baseurl = 
	$tasks = get_task_end_date_by_user ($now);
	
	foreach ($tasks as $task){
		list ($tname, $idt, $tend, $pname, $user) = split ("\|", $task);
		$user_row = get_db_row ("tusuario", "id_usuario", $user);
		$nombre = $user_row['nombre_real'];
		$email = $user_row['direccion'];
		
		$mail_description = "There is a task ending today ($tend) : $pname / $tname \n\nUse this link to review the project information:\n";
		$mail_description .= $config["base_url"]. "/index.php?sec=projects&sec2=operation/projects/task_detail&id_task=$idt&operation=view\n";

		integria_sendmail ($email, "[".$config["sitename"]."] Task ends today ($tname)",  $mail_description );
	}
}



/**
 * Check if daily task has been executed in the last 24 hours.
 */
function check_daily_task () {
	$current_date = date ("Y-m-d");
	$current_date .= " 23:59:59";
	$result = get_db_sql ("SELECT COUNT(id) FROM tevent
		WHERE type = 'DAILY_CHECK'
		AND timestamp < '$current_date'");
	if ($result > 0)
		// Daily check has been executed in the past 24 hours.
		return false;
	return true;
}

/**
 * Check an SLA min response value on an incident and send emails if needed.
 *
 * @param array Incident to check
 */
function check_sla_min ($incident) {
	global $compare_timestamp;
	global $config;
	
	$id_sla = check_incident_sla_min_response ($incident['id_incidencia']);
	if (! $id_sla)
		return false;
	
	/* Check if it was already notified in a specified time interval */
	$sql = sprintf ('SELECT COUNT(id) FROM tevent
		WHERE type = "SLA_MIN_RESPONSE_NOTIFY"
		AND id_item = %d
		AND timestamp > "%s"',
		$incident['id_incidencia'],
		$compare_timestamp);
	$notified = get_db_sql ($sql);
	if ($notified > 0)
		return true;
	
	/* We need to notify via email to the risponsable user */
	$user = get_user ($incident['id_usuario']);
	$group_name = dame_nombre_grupo ($incident['id_grupo']);
	$sla = get_sla ($id_sla);
	$time = give_human_time ($sla['min_response'] * 3600);
	$url = $config["base_url"]."/index.php?sec=incidents&sec2=operation/incidents/incident&id=".$incident['id_incidencia'];
	
	$subject = "Incident #".$incident["id_incidencia"]."[".substr ($incident['titulo'], 0, 20)."...] need inmediate status change (SLA Min.Response Time)";
	$body = 'Hello '.$user['nombre_real'].",\n\n";
	$body .= 'Our SLA policy for incidents of group '.$group_name;
	$body .= ', incidents should be updated in less than a specific time. ';
	$body .= 'For this group this time is configured to be '.$time."\n\n";
	$body .= "Please connect as soon as possible and update status for this incident. ";
	$body .= "Otherwise, you will continue to receive this notifications.\n";
	$body .= " You also could click on following URL: \n\n$url";
	
	integria_sendmail ($user['direccion'], $subject, $body);
	
	insert_event ('SLA_MIN_RESPONSE_NOTIFY', $incident['id_incidencia']);
	
	return true;
}

/**
 * Check an SLA max response value on an incident and send emails if needed.
 *
 * @param array Incident to check
 */
function check_sla_max ($incident) {
	global $compare_timestamp;
	global $config;
	
	$id_sla = check_incident_sla_max_response ($incident['id_incidencia']);
	if (! $id_sla)
		return false;
	
	/* Check if it was already notified in a specified time interval */
	$sql = sprintf ('SELECT COUNT(id) FROM tevent
		WHERE type = "SLA_MAX_RESPONSE_NOTIFY"
		AND id_item = %d
		AND timestamp > "%s"',
		$incident['id_incidencia'],
		$compare_timestamp);
	$notified = get_db_sql ($sql);
	if ($notified > 0)
		return true;
	
	/* We need to notify via email to the risponsable user */
	$user = get_user ($incident['id_usuario']);
	$group_name = dame_nombre_grupo ($incident['id_grupo']);
	$sla = get_sla ($id_sla);
	$time = give_human_time ($sla['max_response'] * 3600);
	$url = $config["base_url"]."/index.php?sec=incidents&sec2=operation/incidents/incident&id=".$incident['id_incidencia'];
	
	$subject = "Incident #".$incident["id_incidencia"]."[".substr ($incident['titulo'], 0, 20)."...] need inmediate status change (SLA Max.Response Time)";
	$body = 'Hello '.$user['nombre_real'].",\n\n";
	$body .= 'Our SLA policy for incidents of group '.$group_name;
	$body .= ', incidents should has a resolution in a maximun period of time. ';
	$body .= 'For this group this period is configured to be '.$time."\n\n";
	$body .= "Please connect as soon as possible and update status for this incident. ";
	$body .= "Otherwise, you will continue to receive this notifications.\n";
	$body .= " You also could click on following URL: \n\n$url";
	
	integria_sendmail ($user['direccion'], $subject, $body);
	
	insert_event ('SLA_MAX_RESPONSE_NOTIFY', $incident['id_incidencia']);
}

$incidents = get_db_all_rows_sql ('SELECT * FROM tincidencia
	WHERE sla_disabled = 0
	AND estado NOT IN (6,7)');
if ($incidents === false)
	$incidents = array ();
foreach ($incidents as $incident) {
	check_sla_min ($incident);
	check_sla_max ($incident);
}

$slas = get_slas (false);
foreach ($slas as $sla) {
	$sql = sprintf ('SELECT id FROM tinventory WHERE id_sla = %d', $sla['id']);
	
	$inventories = get_db_all_rows_sql ($sql);
	if ($inventories === false)
		$inventories = array ();
	
	$noticed_groups = array ();
	foreach ($inventories as $inventory) {
		$sql = sprintf ('SELECT tincidencia.id_incidencia, tincidencia.id_grupo 
			FROM tincidencia, tincident_inventory
			WHERE tincidencia.id_incidencia = tincident_inventory.id_incident
			AND tincident_inventory.id_inventory = %d
			AND affected_sla_id = 0
			AND sla_disabled = 0
			AND estado NOT IN (6,7)', $inventory['id']);
		$opened_incidents = get_db_all_rows_sql ($sql);
		
		if (sizeof ($opened_incidents) <= $sla['max_incidents']) 
			continue;
		
		/* There are too many open incidents */
		foreach ($opened_incidents as $incident) {
			/* Check if it was already notified in a specified time interval */
			$sql = sprintf ('SELECT COUNT(id) FROM tevent
				WHERE type = "SLA_MAX_OPENED_INCIDENTS_NOTIFY"
				AND id_item = %d
				AND timestamp > "%s"',
				$incident['id_grupo'],
				$compare_timestamp);
			$notified = get_db_sql ($sql);
			if ($notified > 0)
				continue;
			
			// Notify by mail for max. incidents opened (ONCE) to this 
			// the group email, if defined, if not, to default user.

			if (! isset ($noticed_groups[$incident['id_grupo']])) {
				$noticed_groups[$incident['id_grupo']] = 1;
				$group_name = dame_nombre_grupo ($incident['id_grupo']);
				$subject = "[".$config['sitename']."] Openened incident limit reached ($group_name)";
				$body = "Opened incidents limit for this group has been exceeded. Please check opened incidentes.\n";
				send_group_email ($incident['id_grupo'], $subject, $body);
				insert_event ('SLA_MAX_OPENED_INCIDENTS_NOTIFY',
					$incident['id_grupo']);
			}
		}
	}
}

// Daily check
if (check_daily_task ())
	run_daily_check ();

?>
