<?php

// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2011 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars

check_login ();

global $config;

$option = get_parameter("option", "search");

$filter = array ();
$filter['string'] = (string) get_parameter ('search_string');
$filter['priority'] = (int) get_parameter ('search_priority', -1);
$filter['id_group'] = (int) get_parameter ('search_id_group', 1);
$filter['status'] = (int) get_parameter ('search_status', -10);
$filter['id_product'] = (int) get_parameter ('search_id_product');
$filter['id_company'] = (int) get_parameter ('search_id_company');
$filter['id_inventory'] = (int) get_parameter ('id_inventory');
$filter['serial_number'] = (string) get_parameter ('search_serial_number');
$filter['sla_fired'] = (bool) get_parameter ('search_sla_fired');
$filter['id_incident_type'] = (int) get_parameter ('search_id_incident_type');
$filter['id_user'] = (string) get_parameter ('search_id_user', '');
$filter['id_creator'] = (string) get_parameter ('search_creator', '');
$filter['editor'] = (string) get_parameter ('search_editor', '');
$filter['closed_by'] = (string) get_parameter ('search_closed_by', '');
$filters['first_date'] = (string) get_parameter ('first_date', '');
$filters['last_date'] = (string) get_parameter ('last_date', '');
$filter['order_by'] = get_parameter ('search_order_by', '');
$filter['first_date'] = get_parameter('search_first_date', '');
$filter['last_date'] = get_parameter('search_last_date', '');

$type_fields = array();

if ($filter['id_incident_type']) {
	$sql = sprintf("SELECT *
					FROM tincident_type_field
					WHERE id_incident_type = %d", $filter["id_incident_type"]);
	
} else {
	$sql = sprintf("SELECT DISTINCT (global_id)
				FROM tincident_type_field
				WHERE global_id != 0");

	$global_fields = get_db_all_rows_sql($sql);

	if (!$global_fields) {
		$global_fields = array();
	}

	$aux = array();
	foreach ($global_fields as $g) {
		$aux[] = $g["global_id"];
	}

	$clause = "(".implode(",",$aux).")";

	$sql = sprintf("SELECT *
				FROM tincident_type_field 
				WHERE id IN %s", $clause);
}

$config['mysql_result_type'] = MYSQL_ASSOC;
$type_fields = process_sql($sql);

if ($type_fields) {
	foreach ($type_fields as $type_field) {
		$filter['type_field_'.$type_field['id']] = (string) get_parameter ('search_type_field_'.$type_field['id']);
	}
}

switch ($option) {
	case "search":
		include("incident_search_logic.php");
		break;
	case "stats":
		include("incident_statistics.php");
		break;
	case 'graph':
		include("incident_graph.php");
		break;
	default:
		break;
}
echo "</div>";

?>

<script>

//Configure some actions to send forms stats
$(document).ready(function () {
	$("#search_form_submit").click(function (event) {
		event.preventDefault();
		$("#search_form").submit();
	});
	
	$("#html_report_submit").click(function (event) {
		event.preventDefault();
		$("#html_report_form").submit();
	});
	
	$("#pdf_report_submit").click(function (event) {
		event.preventDefault();
		$("#pdf_report_form").submit();
	});
	
});


</script>
