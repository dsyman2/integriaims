<?php
// Integria 1.1 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login();

if (give_acl($config["id_user"], 0, "KM")==0) {
	audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to access KB Management");
	require ("general/noaccess.php");
	exit;
}

$color = 0;
$id_user = $config["id_user"];
echo "<h2>".lang_string ("Event history")."</h2>";	

// Pagination
$offset = get_parameter ("offset",0);
$total_events = give_db_sqlfree_field ("SELECT COUNT(id) FROM tevent");
pagination ($total_events, "index.php?sec=godmode&sec2=godmode/setup/event", $offset);

$table->width = '740px';
$table->class = 'listing';
$table->head = array ();
$table->head[0] = __('Type');
$table->head[1] = __('User');
$table->head[2] = __('Extended info');
$table->head[3] = __('Timestamp');
$table->data = array ();

$sql = sprintf ('SELECT * FROM tevent
	ORDER by timestamp
	DESC LIMIT %d, %d',
	$offset, $config["block_size"]);
$events = get_db_all_rows_sql ($sql);
if ($events === false)
	$events = array ();
foreach ($events as $event) {
	$data = array ();
	
	$data[0] = $event["type"];
	$data[1] = $event["id_user"];
	
	switch ($event["type"]) {
	case "SLA_MAX_OPEN_NOTIFY":
	case "SLA_MAX_RESOLUTION_NOTIFY":
	case "SLA_MIN_RESPONSE_NOTIFY":
	case "SLA_MAX_RESPONSE_NOTIFY":
		$data[2] = __('Incident')." :".get_db_value ('titulo', 'tincidencia', 'id_incidencia'. $event["id_item"]);
		break;
	case "SLA_MAX_OPEN_NOTIFY":
		$data[2] = __('Group')." :".get_db_value ('nombre', 'tgrupo', 'id_grupo', $event["id_item"]);
		break;
	default:
		$data[2] = $event["id_item3"];
	}
	$data[3] = $event["timestamp"];
	
	array_push ($table->data, $data);
}
print_table ($table);
?>
