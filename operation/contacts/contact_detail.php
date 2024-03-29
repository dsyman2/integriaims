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

global $config;

check_login ();

include_once('include/functions_crm.php');

$id = (int) get_parameter ('id');
$id_company = (int) get_parameter ('id_company');

$section_read_permission = check_crm_acl ('company', 'cr');
$section_write_permission = check_crm_acl ('company', 'cw');
$section_manage_permission = check_crm_acl ('company', 'cm');

if (!$section_read_permission && !$section_write_permission && !$section_manage_permission) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to contacts without permission");
	include ("general/noaccess.php");
	exit;
}

if ($id || $id_company) {
	if ($id) {
		$id_company = get_db_value ('id_company', 'tcompany_contact', 'id', $id);
	}
	
	$read_permission = check_crm_acl ('other', 'cr', $config['id_user'], $id_company);
	$write_permission = check_crm_acl ('other', 'cw', $config['id_user'], $id_company);
	$manage_permission = check_crm_acl ('other', 'cm', $config['id_user'], $id_company);
	
	if ((!$read_permission && !$write_permission && !$manage_permission) || $id_company === false) {
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access a contact without permission");
		include ("general/noaccess.php");
		exit;
	}
}

$op = get_parameter("op", "details");

if ($id == 0) {
	echo "<h1>".__('Contact management')."</h1>";
}

if ($id != 0) {
	echo '<ul style="height: 30px;" class="ui-tabs-nav">';

	if ($op == "files")
		echo '<li class="ui-tabs-selected">';
	else   
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=files"><span>'.__("Files").'</span></a></li>';

	if ($op == "inventory")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=inventory"><span>'.__("Inventory").'</span></a></li>';

	if ($op == "incidents")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=incidents"><span>'.__("Tickets").'</span></a></li>';

	if ($op == "activity")
		echo '<li class="ui-tabs-selected">';
	else   
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=activity"><span>'.__("Activity").'</span></a></li>';

	if ($op == "details")
		echo '<li class="ui-tabs-selected">';
	else   
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=details"><span>'.__("Contact details").'</span></a></li>';

	echo '<li class="ui-tabs-title">';
	switch ($op) {
		case "files":
			echo strtoupper(__("Files"));
			break;
		case "activity":
			echo strtoupper(__("Activity"));
			break;
		case "details":
			echo strtoupper(__('Contact details'));
			break;
		case "incidents":
			echo strtoupper(__('Tickets'));
			break;
		case "inventory":
			echo strtoupper(__('Inventory'));
			break;
		default:
			echo strtoupper(__('Details'));
	}

	echo '</li>';

	echo '</ul>';

	$contact = get_db_row ('tcompany_contact', 'id', $id);

	echo '<div class="under_tabs_info">' . sprintf(__('Contact: %s'), $contact['fullname']) . '</div>';
}

switch ($op) {
	case "incidents":
		include("contact_incidents.php");
		break;
	case "inventory":
		include("contact_inventory.php");
		break;
	case "details":
		include("contact_manage.php");
		break;
	case "files":
		include("contact_files.php");
		break;
	case "activity": 
		include("contact_activity.php");
		break;	
	default:
		include("contact_manage.php");
}

if ($id == 0 && !$new_contact) {
	
	$search_text = (string) get_parameter ('search_text');
	$id_company = (int) get_parameter ('id_company', 0);
	
	$where_clause = "WHERE 1=1";
	if ($search_text != "") {
		$where_clause .= " AND (fullname LIKE '%$search_text%' OR email LIKE '%$search_text%' OR phone LIKE '%$search_text%' OR mobile LIKE '%$search_text%') ";
	}

	if ($id_company) {
		$where_clause .= sprintf (' AND id_company = %d', $id_company);
	}
	$search_params = "&search_text=$search_text&id_company=$id_company";

	$table->width = '99%';
	$table->class = 'search-table';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->data = array ();
	$table->data[0][0] = print_input_text ("search_text", $search_text, "", 15, 100, true, __('Search'));
	
	$params = array();
	$params['input_id'] = 'id_company';
	$params['input_name'] = 'id_company';
	$params['input_value'] = $id_company;
	$params['title'] = __('Company');
	$params['return'] = true;
	$table->data[0][1] = print_company_autocomplete_input($params);

	$table->data[0][2] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);
	// Delete new lines from the string
	$where_clause = str_replace(array("\r", "\n"), '', $where_clause);
	$table->data[0][3] = print_button(__('Export to CSV'), '', false, 'window.open(\'include/export_csv.php?export_csv_contacts=1&where_clause=' . str_replace("'", "\'", $where_clause) . '\')', 'class="sub csv"', true);
	echo '<form id="contact_search_form" method="post">';
	print_table ($table);
	echo '</form>';

	$contacts = crm_get_all_contacts ($where_clause);

	$contacts = print_array_pagination ($contacts, "index.php?sec=customers&sec2=operation/contacts/contact_detail&params=$search_params", $offset);

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
		if($section_write_permission || $section_manage_permission) {
			$table->head[3] = __('Delete');
		}
		
		foreach ($contacts as $contact) {
			$data = array ();
			// Name
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/contacts/contact_detail&id=".
				$contact['id']."'>".$contact['fullname']."</a>";
			$data[1] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".$contact['id_company']."'>".get_db_value ('name', 'tcompany', 'id', $contact['id_company'])."</a>";
			$data[2] = $contact['email'];
			if($section_write_permission || $section_manage_permission) {
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

	//Show create button only when contact list is displayed
	if(($section_write_permission || $section_manage_permission) && !$id && !$new_contact) {
		echo '<form method="post" action="index.php?sec=customers&sec2=operation/contacts/contact_detail">';
		echo '<div style="width: '.$table->width.'; text-align: right;">';
		print_submit_button (__('Create'), 'new_btn', false, 'class="sub create"');
		print_input_hidden ('new_contact', 1);
		echo '</div>';
		echo '</form>';
	}
}
?>
