<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

include_once("include/functions_incidents.php");


$get_incidents_search = get_parameter('get_incidents_search', 0);
$get_incident_name = get_parameter('get_incident_name', 0);
$get_contact_search = get_parameter('get_contact_search',0);
$set_priority = get_parameter('set_priority', 0);
$set_resolution = get_parameter('set_resolution', 0);
$set_status = get_parameter('set_status', 0);
$set_owner = get_parameter('set_owner', 0);
$set_ticket_score = get_parameter('set_ticket_score', 0);
$get_user_info = get_parameter('get_user_info', 0);
$hours_to_dms = get_parameter('hours_to_dms', 0);
$check_incident_childs = get_parameter('check_incident_childs', 0);
$check_custom_search = get_parameter('check_custom_search', 0);
$set_params = get_parameter('set_params', 0);

if ($get_incidents_search) {
	
	$filter = array ();
	$filter['string'] = (string) get_parameter ('search_string');
	$filter['priority'] = (int) get_parameter ('search_priority', -1);
	$filter['id_group'] = (int) get_parameter ('search_id_group', 1);
	$filter['status'] = (int) get_parameter ('search_status', -10);
	$filter['id_product'] = (int) get_parameter ('search_id_product');
	$filter['id_company'] = (int) get_parameter ('search_id_company');
	$filter['id_inventory'] = (int) get_parameter ('search_id_inventory');
	$filter['serial_number'] = (string) get_parameter ('search_serial_number');
	$filter['sla_fired'] = (bool) get_parameter ('search_sla_fired');
	$filter['id_incident_type'] = (int) get_parameter ('search_id_incident_type');
	$filter['id_user'] = (string) get_parameter ('search_id_user', '');
	$filter['id_incident_type'] = (int) get_parameter ('search_id_incident_type');
	$filter['id_user'] = (string) get_parameter ('search_id_user', '');
	$filter['first_date'] = (string) get_parameter ('search_first_date');
	$filter['last_date'] = (string) get_parameter ('search_last_date');	
	$filter['sla_state'] = (string) get_parameter ('search_sla_state');
	$filter['id_task'] = (int) get_parameter ('search_id_task');	
	$filter['left_sla'] = (int) get_parameter ('search_left_sla');
	$filter['right_sla'] = (int) get_parameter ('search_right_left');
	
	$ajax = get_parameter("ajax");
	
	$filter_form = false;
	
	form_search_incident (false, $filter_form);
	
	$no_parents = true;
	incidents_search_result($filter,$ajax, false, false, $no_parents);
}

if ($get_incident_name) {
	$id = get_parameter("id");
	
	$name = get_db_value ("titulo", "tincidencia", "id_incidencia", $id);
	
	echo $name;
}

if ($get_contact_search) {

	include_once("include/functions_crm.php");

	$read = enterprise_hook('crm_check_user_profile', array($config['id_user'], 'cr'));

	if ($read !== ENTERPRISE_NOT_HOOK) {
		$enterprise = true;
	}

	if (!$read) {
		include ("general/noaccess.php");
		exit;
	}
	
	$search_text = (string) get_parameter ('search_text');
	$id_company = (int) get_parameter ('id_company', 0);
	
	//$where_clause = "WHERE 1=1 AND id_company " .get_filter_by_company_accessibility($config["id_user"]);
	$where_clause = "WHERE 1=1";
	if ($search_text != "") {
		$where_clause .= " AND (fullname LIKE '%$search_text%' OR email LIKE '%$search_text%'
					OR phone LIKE '%$search_text%' OR mobile LIKE '%$search_text%') ";
	}

	if ($id_company) {

		$where_clause .= sprintf (' AND id_company = %d', $id_company);
	}
	$params = "&search_text=$search_text&id_company=$id_company";

	$table->width = '99%';
	$table->class = 'search-table';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->data = array ();
	$table->data[0][0] = print_input_text ("search_text", $search_text, "", 15, 100, true, __('Search'));
	
	$companies = crm_get_companies_list("", false, "", true);

	$table->data[0][1] = print_select ($companies, 'id_company', $id_company, '', 'All', 0, true, false, false, __('Company'));
	$table->data[0][2] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);
	echo '<form id="contact_search_form" method="post">';
	print_table ($table);
	echo '</form>';

	$contacts = crm_get_all_contacts ($where_clause);

	if ($read && $enterprise) {
		$contacts = crm_get_user_contacts($config['id_user'], $contacts);
	}

	$contacts = print_array_pagination ($contacts, "index.php?sec=customers&sec2=operation/contacts/contact_detail&params=$params", $offset);

	if ($contacts !== false) {
		unset ($table);
		$table->width = "99%";
		$table->class = "listing";
		$table->data = array ();
		$table->size = array ();
		$table->size[3] = '40px';
		$table->style = array ();
		// $table->style[] = 'font-weight: bold';
		$table->head = array ();
		$table->head[0] = __('Full name');
		$table->head[1] = __('Company');
		$table->head[2] = __('Email');
		if($manage_permission) {
			$table->head[3] = __('Delete');
		}
		
		foreach ($contacts as $contact) {
			$data = array ();
			// Nameif (defined ('AJAX')) {
			$url = "javascript:loadContactEmail(\"".$contact['email']."\");";
			$data[0] = "<a href='".$url."'>".$contact['fullname']."</a>";
			$data[1] = "<a href='".$url."'>".get_db_value ('name', 'tcompany', 'id', $contact['id_company'])."</a>";
			$data[2] = $contact['email'];
			if($manage_permission) {
				$data[3] = '<a href="index.php?sec=customers&
							sec2=operation/contacts/contact_detail&
							delete_contact=1&id='.$contact['id'].'&offset='.$offset.'"
							onClick="if (!confirm(\''.__('Are you sure?').'\'))
							return false;">
							<img src="images/cross.png"></a>';
			}	
			array_push ($table->data, $data);
		}
		print_table ($table);
	}		
}

if ($set_ticket_score) {
	$id_ticket = get_parameter ('id_ticket');
	$score = get_parameter ('score');
	
	$sql = "UPDATE tincidencia SET score = $score WHERE id_incidencia = $id_ticket";
	process_sql ($sql);
}

if ($get_user_info) {

	$id_user = get_parameter('id_user');
	
	$info_user = get_db_row('tusuario', 'id_usuario', $id_user);
	
	$total_tickets_opened = get_db_value_sql("SELECT count(id_incidencia) 
									FROM tincidencia 
									WHERE estado<>7
									AND id_creator='$id_user'");

	echo "<table>";
	echo "<tr>";
	echo "<td>";
		print_image('images/avatars/' . $info_user['avatar'] . '.png', false, false);
	echo "</td>";
	echo "<td vertical-align='middle'>";
			echo $info_user['nombre_real'];
			echo '<br>'.'('.$id_user.')';
	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td align='left'>";
		echo '<b>'.__('Telephone: ').'</b>'.$info_user['telefono'];
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td>";
		echo '<b>'.__('Email: ').'</b>'.$info_user['direccion'];
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td>";
		echo '<b>'.__('Company: ').'</b>'.get_db_value('name', 'tcompany', 'id', $info_user['id_company']);
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td>";
		echo '<b>'.__('Total tickets opened: ').'</b>'.$total_tickets_opened;
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td>";
		echo '<b>'.__('Comments ').'</b>';
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td colspan=2>";
			echo $info_user['comentarios'];
	echo "</td>";
	echo "</tr>";

$user_fields = get_db_all_rows_sql ("SELECT t.label, t2.data 
							FROM tuser_field t, tuser_field_data t2
							WHERE t2.id_user='$id_user'
							AND t.id=t2.id_user_field");
if ($user_fields) {
	foreach ($user_fields as $field) {
		echo "<tr align='left'>";
		echo "<td>";
			echo '<b>'.$field["label"].': '.'</b>'.$field['data'];
		echo "</td>";
	}
}

	echo "</table>";
	return;
}

if ($hours_to_dms) {
	
	$hours = get_parameter('hours');

	$result = incidents_hours_to_dayminseg ($hours);	
	
	echo json_encode($result);
	return;
}

if ($check_incident_childs) {
	
	$id_incident = get_parameter('id_incident');
	
	if ($id_incident == 0) {
		echo false;
		return;
	}
	
	$sql = "SELECT id_incidencia, titulo FROM tincidencia WHERE id_parent=".$id_incident. " AND estado<>7";
	$incident_childs = get_db_all_rows_sql($sql);
	
	if ($incident_childs == false) {
		$incident_childs = array();
	}
	
	if (!empty($incident_childs)) {
		echo "<table>";
		echo "<tr>";
		echo "<td align='left' colspan=2>";
		echo '<b>'. __("Following tickets will be closed: ").'</b>';
		echo "</td>";
		echo "</tr>";

		foreach ($incident_childs as $child) {
			echo "<tr align='left'>";
			echo "<td>";
				echo $child['id_incidencia'].' - '.$child['titulo'];
			echo "</td>";
		}
		echo "</table>";
	} else {
		return false;
	}
	
	return;
}

if ($check_custom_search) {
	$sql = sprintf ('SELECT COUNT(id) FROM tcustom_search
		WHERE id_user = "%s" AND section = "incidents"
		ORDER BY name', $config['id_user']);
	$count_search = get_db_value_sql($sql);
	
	if (!$count_search) {
		$result = __("Ticket reports are based on custom searches. In order to elaborate a report, first it is required to define a customized search that will be used as a base for the report.");
		$result .= integria_help ("custom_search", true);
		$result .= '<br><br><a href="index.php?sec=incidents&sec2=operation/incidents/incident_search">'.__('Go to Custom search').'</a>';

	} else {
		$result = false;
	}
	
	echo json_encode($result);
	return;
}

if ($set_params) {

	$id_ticket = get_parameter('id_ticket');
	$values['prioridad'] = get_parameter ('id_priority');
	$values['resolution'] = get_parameter ('id_resolution');
	$values['estado'] = get_parameter ('id_status');
	$values['id_usuario'] = get_parameter ('id_user');
	$values['actualizacion'] = date('Y:m:d H:i:s');

	$old_incident = get_incident ($id_ticket);
	
	if (!$old_incident['old_status2']) {
		$values['old_status'] = $old_incident["old_status"];
		$values['old_resolution'] = $old_incident["old_resolution"];
		$values['old_status2'] = $estado;
		$values['old_resolution2'] = $resolution;
	} else {
		if (($old_incident['old_status2'] == $values['estado']) && ($old_incident['old_resolution2'] == $values['resolution'])) {
			$values['old_status'] = $old_incident["old_status"];
			$values['old_resolution'] = $old_incident["old_resolution"];
			$values['old_status2'] = $old_incident["old_status2"];
			$values['old_resolution2'] = $old_incident["old_resolution2"];
		} else {
			$values['old_status'] = $old_incident["old_status2"];
			$values['old_resolution'] = $old_incident["old_resolution2"];
			$values['old_status2'] = $estado;
			$values['old_resolution2'] = $resolution;
		}
	}

	$result = db_process_sql_update('tincidencia', $values, array('id_incidencia'=>$id_ticket));
		
	if ($result) {
		
		$email_notify = get_db_value('notify_email', 'tincidencia', 'id_incidencia', $id_ticket);
		$owner = get_db_value('id_usuario', 'tincidencia', 'id_incidencia', $id_ticket);
		
		// Email notify to all people involved in this incident
		if ($email_notify == 1) {
			mail_incident ($id_ticket, $owner, "", 0, 0);
		}
		
		if ($old_incident['prioridad'] != $values['prioridad']) {
			incident_tracking ($id_ticket, INCIDENT_PRIORITY_CHANGED, $values['prioridad']);
		}
		if ($old_incident['resolution'] != $values['resolution']) {
			incident_tracking ($id_ticket, INCIDENT_RESOLUTION_CHANGED, $values['resolution']);
		}
		if ($old_incident['estado'] != $values['estado']) {
			if ($values['estado'] == 7) {
				$values_close['closed_by'] = $config['id_user'];
				$values_close['cierre'] = date('Y:m:d H:i:s');
				db_process_sql_update('tincidencia', $values_close, array('id_incidencia'=>$id_ticket));
			}
			incident_tracking ($id_ticket, INCIDENT_STATUS_CHANGED, $values['estado']);
		}
		if ($old_incident['id_usuario'] != $values['id_usuario']) {
			incident_tracking ($id_ticket, INCIDENT_USER_CHANGED, $values['id_usuario']);
		}
		audit_db ($old_incident['id_usuario'], $config["REMOTE_ADDR"], "Ticket updated", "User ".$config['id_user']." ticket updated #".$id_ticket);
	}
}
?>
