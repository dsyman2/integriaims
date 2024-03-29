<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (!isset($config["id_user"]))
	return;

global $show_projects;
global $show_incidents;
global $show_inventory;
global $show_customers;
global $show_kb;
global $show_file_releases;
global $show_people;
global $show_todo;
global $show_agenda;
global $show_setup;
global $show_wiki;


// PROJECTS
if ($sec == "projects" && give_acl ($config["id_user"], 0, "PR") && $show_projects != MENU_HIDDEN) {
	$id_project = get_parameter ('id_project', -1);
	$id_task = get_parameter ('id_task', -1);
	
	// Get id_task but not id_project
	if (($id_task != -1) AND ($id_project == -1)){
		$id_project = get_db_value ("id_project", "ttask", "id", $id_task);
	}
	
	// ACL Permissions
	$section_permission = get_project_access ($config["id_user"]);
	$manage_any_task = manage_any_task ($config["id_user"]);
	if ($id_project > 0) {
		$project_permission = get_project_access ($config["id_user"], $id_project);
		$manage_any_task_in_project = manage_any_task ($config["id_user"], $id_project);
	}
	if ($id_task > 0) {
		$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
	}
	
	
	echo "<div class='portlet' style='border:padding: 0px; margin: 0px;'>";
	//echo '<a href="javascript:;" onclick="$(\'#projects\').slideToggle (); return false">';
	echo "<h3>".__('Projects')."</h3>";
	//echo "</a>";
	echo "<div id=projects style='padding: 0px; margin: 0px'>";

	echo "<ul class='sidemenu'>";

	// Project overview
	if ($sec2 == "operation/projects/project_overview")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=projects&sec2=operation/projects/project_overview'>".__('Projects overview')."</a></li>";
	
	// Project detail
	if (($sec2 == "operation/projects/project") AND (!isset($_REQUEST["view_disabled"])) )
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=projects&sec2=operation/projects/project'>".__('Projects detail')."</a></li>";

	// Project tree
	if ($sec2 == "operation/projects/user_project_timegraph")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=projects&sec2=operation/projects/user_project_timegraph'>".__('Project timemap')."</a></li>";

	// Project create
	if ($section_permission['write']) {
		if ($sec2 == "operation/projects/project_detail" && $id_project < 0)
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/project_detail&create_project=1'>".__('Create project')."</a></li>";
	}

	if($show_projects != MENU_LIMITED && $show_projects != MENU_MINIMAL) {
		// View disabled projects
		if (($sec2 == "operation/projects/project") AND (isset($_REQUEST["view_disabled"])) )
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/project&view_disabled=1'>".__('Archived projects')."</a></li>";
	}
	
	if ($manage_any_task) {
		// Global user/role/task assigment
		if ($sec2 == "operation/projects/role_user_global")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/role_user_global'>".__('Global assigment')."</a>";
		echo "</li>";
	}
	
	// end of main Project options block
	echo "</ul>";
	echo "</div>";
	echo "</div>";
	
	// Dynamic project sub options menu (PROJECT)
	if ($id_project > 0) {
		echo "<br>";
		
		echo "<div class='portlet'>";
		$project_title = substr(get_db_value ("name", "tproject", "id", $id_project), 0, 25);
		echo '<a href="javascript:;" onclick="$(\'#project\').slideToggle (); return false">';
		echo "<h2>" . __('Project management') . "</h2>";
		echo "</a>";
		echo "<div id=project>";
		echo "<ul class='sidemenu'>";
		// Project detail
		if ($sec2 == "operation/projects/project_detail")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/project_detail&id_project=$id_project'>".__('Project overview')."</a></li>";
		
		if ($manage_any_task_in_project) {
			// Task planning
			if ($sec2 == "operation/projects/task_planning")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_planning&id_project=$id_project'>".__('Task planning')."</a></li>";
		}
		
		// Timegraph d3.js treemap project
		if ($sec2 == "operation/projects/project_timegraph")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/project_timegraph&id_project=$id_project'>".__('Time graph')."</a></li>";
		
		// Project tracking
		if ($sec2 == "operation/projects/project_tracking")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/project_tracking&id_project=$id_project'>".__('Project tracking')."</a></li>";
		
		// Tasks
		$task_number = get_tasks_count_in_project ($id_project);
		if ($task_number > 0) {
			if ($sec2 == "operation/projects/task")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task&id_project=$id_project'>".__('Task list')." ($task_number)</a></li>";
		}
		
		if ($manage_any_task_in_project) {
			// Create task
			if ($sec2 == "operation/projects/task_detail" && $id_task < 0)
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=$id_project&operation=create'>".__('New task')."</a></li>";
		}
		
		// Gantt graph
		if ($sec2 == "operation/projects/gantt")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/gantt&id_project=$id_project'>".__('Gantt graph')."</a></li>";
		
		
		// Milestones
		if ($sec2 == "operation/projects/milestones")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/milestones&id_project=$id_project'>".__('Milestones')."</a></li>";
		
		// PROJECT - People management
		if ($project_permission['manage']) {
			if ($sec2 == "operation/projects/people_manager" && $id_task < 0)
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/people_manager&id_task=-1&id_project=$id_project'>".__('People')."</a></li>";
		}
		
		// Workunits
		$totalhours = get_project_workunit_hours ($id_project);
		$totalwu = get_project_count_workunits ($id_project);
		if ($totalwu > 0){
			if ($sec2 == "operation/projects/task_workunit" && $id_task < 0)
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=$id_project'>".__('Workunits');
			echo " ($totalhours ".__('Hours').")";
			echo "</a></li>";
		}

		// Files
		$numberfiles = give_number_files_project ($id_project);
		if ($numberfiles > 0){
			if ($sec2 == "operation/projects/task_files" && $id_task < 0)
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_files&id_project=$id_project'>".__('Files')." ($numberfiles)";
			echo "</a></li>";
		}

		echo "</ul>";
		echo "</div></div>";
	}

	// Dynamic sub options menu (TASKS)
	if ($id_task > 0) {
		echo "<br>";

		echo "<div class='portlet'>";
		$task_title = substr(get_db_value ("name", "ttask", "id", $id_task), 0, 19);
		echo '<a href="javascript:;" onclick="$(\'#task\').slideToggle (); return false">';
		echo "<h3>".__('Task')." - $task_title </h3>";
		echo '</a>';
		echo "<div id='task'>";
		echo "<ul class='sidemenu'>";

		// Task detail
		if ($sec2 == "operation/projects/task_detail")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=$id_project&id_task=$id_task&operation=view'>".__('Task detail')."</a></li>";

		// Task tracking
		if ($sec2 == "operation/projects/task_tracking")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task_tracking&id_project=$id_project&id_task=$id_task&operation=view'>".__('Task tracking')."</a></li>";

		if ($task_permission['write']) {
			// Add task workunit
			if ($sec2 == "operation/users/user_spare_workunit")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/users/user_spare_workunit&id_project=$id_project&id_task=$id_task'>".__('Add workunit')."</a></li>";

			// Add task file
			if ($sec2 == "operation/projects/task_attach_file")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_attach_file&id_task=$id_task&id_project=$id_project'>".__('Add file')."</a></li>";
			
			$operation = get_parameter ('operation', '');
			// Add task cost
			if ($sec2 == "operation/projects/task_cost" && $operation != "list")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_cost&id_project=$id_project&id_task=$id_task'>".__('Add cost unit')."</a></li>";

			// Vist task costs
			if ($sec2 == "operation/projects/task_cost" && $operation == "list")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_cost&id_project=$id_project&id_task=$id_task&operation=list'>".__('View external costs')."</a></li>";


		}
		
		// Task people_manager
		if ($task_permission['manage']) {
			if ($sec2 == "operation/projects/people_manager" && $id_task > 0)
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/people_manager&id_project=$id_project&id_task=$id_task'>".__('People')."</a></li>";

			// Task email report
			if ($sec2 == "operation/projects/task_emailreport")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_emailreport&id_task=$id_task&id_project=$id_project'>".__('Email report')."</a></li>";

			// Move this task
			if ($sec2 == "operation/projects/task_move")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_move&id_task=$id_task&id_project=$id_project'>".__('Move task')."</a></li>";
		}

		// Workunits
		$totalhours = get_task_workunit_hours ($id_task);
		$totalwu = get_task_workunit_hours ($id_task);
		if ($totalwu > 0){
			if ($sec2 == "operation/projects/task_workunit" && $id_task > 0)
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=$id_project&id_task=$id_task'>".__('Workunits');
			echo " ($totalhours ".__('Hours').")";
			echo "</a></li>";
		}

		// Incidents for this task
		$task_incidents = get_incident_task($id_task);
		if ( $task_incidents > 0){
			$task_incidents_wu = get_incident_task_workunit_hours ($id_task);
			if ($sec2 == "operation/projects/task_incidents")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_incidents&id_project=$id_project&id_task=$id_task'>".__('Tickets');
			echo " ($task_incidents / $task_incidents_wu ".__('Hours').")";
			echo "</a></li>";
		}

		// Files
		$numberfiles = get_number_files_task ($id_task);
		if ($numberfiles > 0){
			if ($sec2 == "operation/projects/task_files"  && $id_task > 0)
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_files&id_project=$id_project&id_task=$id_task'>".__('Files')." ($numberfiles)";
			echo "</a></li>";
		}
		echo "</ul>";
		echo "</div>";
		echo "</div>";
	}
}

// Project group manager
if (give_acl ($config["id_user"], 0, "PM") && $sec == "projects" && $show_projects == MENU_FULL) {
	echo "<div class='portlet'>";
	echo "<h3 class='admin'>".__('Project groups')."</h3>";
	echo "<ul class='sidemenu'>";

	// Building overview
	if ($sec2=="operation/projects/project_group_detail")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=projects&sec2=operation/projects/project_group_detail'>".__('Project groups')."</a></li>";

	echo "</ul>";
	echo "</div>";
}


// Workorders
if ((($sec == "projects" ))&& ( $show_projects != MENU_HIDDEN )) {
	echo "<div class='portlet'>";
	echo "<h3>".__('Workorders')."</h3>";
	echo "<ul class='sidemenu'>";

	// Todo overview
	if (($sec2 == "operation/workorders/wo") && (!isset($_GET["operation"])))
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=projects&sec2=operation/workorders/wo&owner=".$config["id_user"]."'>".__('Workorders')."</a></li>";
	
	if (($sec2 == "operation/workorders/wo")) {
		echo "<li style='margin-left: 15px; font-size: 10px;'>";
		echo "<a href='index.php?sec=projects&sec2=operation/workorders/wo&operation=create'>".__('New WO')."</a>";
		echo "</li>";
	}

	if (dame_admin($config["id_user"])){
		// WO category management
		if (($sec2 == "operation/workorders/wo_category") && (!isset($_GET["operation"])))
			echo "<li id='sidesel'>";
		else 
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/workorders/wo_category'>".__('WO Categories')."</a></li>";

		if (($sec2 == "operation/workorders/wo_category")){
			echo "<li style='margin-left: 15px; font-size: 10px;'>";
			echo "<a href='index.php?sec=projects&sec2=operation/workorders/wo_category&create=1'>".__('New category')."</a>";
			echo "</li>";
		}
	}
	echo "</ul></div>";
}

// INCIDENTS
if ($sec == "incidents" && give_acl ($config['id_user'], 0, "IR") && $show_incidents != MENU_HIDDEN) {
	echo "<div class='portlet'>";
	echo "<h3>".__('Incidents')."</h3>";
	
		echo "<ul class='sidemenu'>";
		$id_incident = get_parameter ('id');

		// Incident overview
		if ($sec2 == "operation/incidents/incident_dashboard")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard'>".__('Tickets overview')."</a></li>";
		
		//~ Add myTickets
		if ($sec2 == "operation/incidents/incident")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&id_myticket=1&search_id_user=".$config['id_user']."'>".__('My tickets')."</a></li>";
		
		if ($sec2 == "operation/incidents/incident")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search'>".__('Search tickets')."</a></li>";

		if (give_acl ($config['id_user'], 0, "IW")) {
			// Incident creation
			if ($sec2 == 'operation/incidents/incident_detail')
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_detail' id='link_create_incident'>".__('Create ticket')."</a></li>";
		}
		
		if (give_acl ($config['id_user'], 0, "IR")) {
			// Incident reports
			if ($sec2 == 'operation/incidents/incident_reports')
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_reports' id='link_incident_report'>".__('Reports')."</a></li>";
		}

		if ($sec2 == 'operation/incidents/incident' || $sec2 == 'operation/incidents/incident_dashboard'
			|| $sec2 == 'operation/incidents/incident_search' || $sec2 == 'operation/incidents/incident_dashboard_detail') {
				
			if ($sec2 == 'operation/incidents/incident_dashboard_detail')
				echo "<li id='sidesel'>";
			else
				echo '<li>';
			echo '<a href="" onclick="return false">'.__('Ticket #').'&nbsp;</a>';
			echo '<form action="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail" method="POST">';
			print_input_text ('id', $id_incident ? $id_incident : '', '', 4, 10);
			echo '</form>';
			echo '</li>';
		}
		echo "</ul></div>";
		
		// Incident type and SLA management only PM
		if (give_acl ($config['id_user'], 0, "IM")) {
			echo "<div class='portlet'>";
			echo "<h3 class='admin'>".__('Ticket types')."</h3>";
			echo "<ul class='sidemenu'>";

		
			if ($sec2=="operation/incidents/types_detail")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=incidents&sec2=operation/incidents/type_detail'>".__('Ticket types')."</a></li>";

			echo "</ul>";
			echo "</div>";	
		
			// SLA Management
			echo "<div class='portlet'>";
			echo "<h3 class='admin'>".__('SLA')."</h3>";
			echo "<ul class='sidemenu'>";

			
			if ($sec2=="operation/slas/sla_detail")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=incidents&sec2=operation/slas/sla_detail'>".__('SLA Management')."</a></li>";

			echo "</ul>";
			echo "</div>";
		}
	
	//Workflow rules
	if (get_admin_user($config['id_user'])) {
		enterprise_include ("operation/sidemenu_workflow_rules.php");
	}
		echo "</div></div>";
	}

// INVENTORY
if ($sec == "inventory" && give_acl ($config['id_user'], 0, "VR") && $show_inventory != MENU_HIDDEN) {
	$id_inventory = (int) get_parameter ('id');
	echo "<div class='portlet'>";
	echo "<h3>".__('Inventory')."</h3>";
	echo "<ul class='sidemenu'>";
	// Incident overview
	if ($sec2 == "operation/inventories/inventory")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory'>".__('Inventory overview')."</a></li>";
	//echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory_search'>".__('Inventory overview')."</a></li>";

	if (give_acl ($config["id_user"], 0, "VW")) {
		// Incident creation
		if ($sec2 == "operation/inventories/inventory_detail")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory_detail'>".__('Create inventory object')."</a></li>";
	}
	
	if ($sec2 == 'operation/inventories/inventory') {
		echo '<li>';
		echo '<a href="" onclick="return false">'.__('Inventory #').'</a>';
		//echo '<form id="goto-inventory-form">';
		echo "<form action='index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id=$id_inventory&check_inventory=1' method='post'>";
		print_input_text ('id', $id_inventory ? $id_inventory : '', '', 3, 10);
		echo '</form>';
		echo '</li>';
	}
	echo "</ul>";

	echo "</div>";

	// Dynamic inventory sub options menu
	echo '<div class="portlet inventory-menu" id="inventory-menu-actions" style="display: none">';
	echo '<h3>'.__('Inventory').' #<span class="id-inventory-menu">';
	if ($id_inventory)
		echo $id_inventory;
	echo '</span></h3>';

	echo "<ul class='sidemenu'>";
	echo '<li>';
	echo '<a id="inventory-create-incident" href="index.php?sec=incidents&sec2=operation/incidents/incident_detail&id_inventory='.$id_inventory.'">'.__('Create ticket').'</a>';

	echo '</li>';

	echo "</ul>";
	echo "</div>";
}

// Customers 
if ($sec == "customers" && give_acl ($config["id_user"], 0, "CR") && $show_customers != MENU_HIDDEN) {
	echo "<div class='portlet'>";


	echo "<h3 class='admin'>".__('Customers')."</h3>";
	echo "<ul class='sidemenu'>";
	
	// Company
	$new_company = (int) get_parameter('new_company');
	
	if (($sec2=="operation/companies/company_detail") AND (!$new_company))
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/companies/company_detail'>".__('Companies')."</a></li>";
	
	if ($sec2 == "operation/companies/company_detail" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) {
		if ($new_company)
			echo "<li id='sidesel' style='margin-left: 15px; font-size: 10px;'>";
		else
			echo "<li style='margin-left: 15px; font-size: 10px;'>";
		echo "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&new_company=1'>".__('New company')."</a>";
		echo "</li>";
	}


	// Company roles
	if (give_acl ($config["id_user"], 0, "CM")) {
		if (($sec2=="operation/companies/company_role") && (!isset($_GET["create"])))
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=customers&sec2=operation/companies/company_role'>".__('Company roles')."</a></li>";
	}

	// Invoices
	$new_invoice = (int) get_parameter('new_invoice');
	
	if (($sec2 == "operation/invoices/invoice_detail" || $sec2 == "operation/invoices/invoices") && !$new_invoice)
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/invoices/invoice_detail'>".__('Invoices')."</a></li>";
	
	// new
	if (($sec2 == "operation/invoices/invoice_detail" || $sec2 == "operation/invoices/invoices") && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) {
		if ($new_invoice)
			echo "<li id='sidesel' style='margin-left: 15px; font-size: 10px;'>";
		else
			echo "<li style='margin-left: 15px; font-size: 10px;'>";
		echo "<a href='index.php?sec=customers&sec2=operation/invoices/invoices&new_invoice=1'>".__('New invoice')."</a>";
		echo "</li>";
	}
	
	
	// Contract overview
	$new_contract = (int) get_parameter('new_contract');
	
	if (($sec2=="operation/contracts/contract_detail") && !$new_contract)
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/contracts/contract_detail'>".__('Contracts')."</a></li>";

	// new
	if ($sec2 == "operation/contracts/contract_detail" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) {
		if ($new_contract)
			echo "<li id='sidesel' style='margin-left: 15px; font-size: 10px;'>";
		else
			echo "<li style='margin-left: 15px; font-size: 10px;'>";
		echo "<a href='index.php?sec=customers&sec2=operation/contracts/contract_detail&new_contract=1'>".__('New contract')."</a>";
		echo "</li>";
	}


	// Contact overview
	$new_contact = (int) get_parameter('new_contact');
	
	if (($sec2=="operation/contacts/contact_detail") && !$new_contact)
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/contacts/contact_detail'>".__('Contacts')."</a></li>";

	if ($sec2 == "operation/contacts/contact_detail" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) {
		if ($new_contact)
			echo "<li id='sidesel' style='margin-left: 15px; font-size: 10px;'>";
		else
			echo "<li style='margin-left: 15px; font-size: 10px;'>";
		echo "<a href='index.php?sec=customers&sec2=operation/contacts/contact_detail&new_contact=1'>".__('New contact')."</a>";
		echo "</li>";
	}


	// Lead management
	$new_lead = (int) get_parameter('new');

	if (($sec2=="operation/leads/lead") AND !$new_lead)
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/leads/lead'>".__('Leads')."</a></li>";

	if ($sec2 == "operation/leads/lead" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) {
		if ($new_lead)
			echo "<li id='sidesel' style='margin-left: 15px; font-size: 10px;'>";
		else
			echo "<li style='margin-left: 15px; font-size: 10px;'>";
		echo "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&new=1'>".__('New lead')."</a>";
		echo "</li>";
	}


	echo "</ul>";
	echo "</div>";
}

// CRM Template Manager
if ($sec == "customers" && give_acl ($config["id_user"], 0, "CM") && $show_customers != MENU_HIDDEN) {
	echo "<div class='portlet'>";
	echo "<h3 class='admin'>".__('CRM Templates')."</h3>";
	echo "<ul class='sidemenu'>";

	// Building overview
	if ($sec2=="operation/leads/template_manager")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/leads/template_manager'>".__('Manage templates')."</a></li>";

	echo "</ul>";
	echo "</div>";
}


// Newsletter


if (($config["enable_newsletter"] == 1) && ($sec == "customers") && (give_acl ($config["id_user"], 0, "CN")) && ($show_customers != MENU_HIDDEN)) {

	echo "<div class='portlet'>";


	echo "<h3 class='admin'>".__('Newsletter')."</h3>";
	echo "<ul class='sidemenu'>";


	if (($sec2=="operation/newsletter/newsletter_definition") AND (!isset($_GET["create"])))
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/newsletter/newsletter_definition'>".__('Newsletters')."</a></li>";

	echo "<li style='margin-left: 15px; font-size: 10px;'>";
	echo "<a href='index.php?sec=customers&sec2=operation/newsletter/newsletter_creation&create=1'>".__('New newsletter')."</a>";
	echo "</li>";
	
	if (($sec2=="operation/newsletter/issue_definition") AND (!isset($_GET["create"])))
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/newsletter/issue_definition'>".__('Issues')."</a></li>";

	echo "<li style='margin-left: 15px; font-size: 10px;'>";
	echo "<a href='index.php?sec=customers&sec2=operation/newsletter/issue_creation&create=1'>".__('New issue')."</a>";
	echo "</li>";
	
	if (($sec2=="operation/newsletter/address_definition") AND (!isset($_GET["create"])))
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/newsletter/address_definition'>".__('Addresses')."</a></li>";

	echo "<li style='margin-left: 15px; font-size: 10px;'>";
	echo "<a href='index.php?sec=customers&sec2=operation/newsletter/address_creation&create=1'>".__('New address')."</a>";
	echo "</li>";
	
	echo "<li style='margin-left: 15px; font-size: 10px;'>";
	echo "<a href='index.php?sec=customers&sec2=operation/newsletter/manage_address&create=1'>".__('Manage address')."</a>";
	echo "</li>";
	
	if (($sec2=="operation/newsletter/queue_manager") AND (!isset($_GET["create"])))
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/newsletter/queue_manager'>".__('Queue control')."</a></li>";
	
	echo "</ul></div>";
		
}

//Print campaign menu
if (($sec == "customers") && (give_acl ($config["id_user"], 0, "VM")) && ($show_customers != MENU_HIDDEN)) {
	echo "<div class='portlet'>";


	echo "<h3 class='admin'>".__('Campaign')."</h3>";
	echo "<ul class='sidemenu'>";


	if ($sec2=="operation/campaign/campaign")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/campaign/campaign'>".__('Campaigns')."</a></li>";
	echo "</ul>";
	echo "</div>";
}

// MANUFACTURER
if ($sec == "inventory" && give_acl ($config["id_user"], 0, "VM") && $show_inventory != MENU_HIDDEN) {
	echo "<div class='portlet'>";
	echo "<h3 class='admin'>".__('Manufacturers')."</h3>";
	echo "<ul class='sidemenu'>";

	// Building overview
	if ($sec2=="operation/manufacturers/manufacturer_detail")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail'>".__('Manufacturer overview')."</a></li>";

	echo "</ul>";
	echo "</div>";
}


// Product types
if ($sec == "inventory" && give_acl($config["id_user"], 0, "PM") && $show_inventory != MENU_HIDDEN) {
	echo "<div class='portlet'>";
	echo "<h3 class='admin'>".__('Inventory objects')."</h3>";
	echo "<ul class='sidemenu'>";

	// Building overview
	if ($sec2=="operation/inventories/manage_objects")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=inventory&sec2=operation/inventories/manage_objects'>".__('Object types')."</a></li>";

	// Building overview
	if ($sec2=="operation/inventories/import_objects")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory_import_objects'>".__('Import objects from CSV')."</a></li>";
	
	echo "</ul>";
	echo "</div>";
}

// KNOWLEDGE BASE (KB)
if ($sec == "kb" && give_acl ($config["id_user"], 0, "KR") && $show_kb != MENU_HIDDEN) {
	echo "<div class='portlet'>";
	echo "<h3>".__('Knowledge Base')."</h3>";
	echo "<ul class='sidemenu'>";

	// KB Browser
	if ($sec2 == "operation/kb/browse")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=kb&sec2=operation/kb/browse'>".__('Browse')."</a></li>";

	if  (give_acl($config["id_user"], 0, "KW")) {
		// KB Add
		if (($sec2 == "operation/kb/browse") AND (isset($_GET["create"])))
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=kb&sec2=operation/kb/browse&create=1'>".__('Create KB item')."</a></li>";
	}

	if  (give_acl($config["id_user"], 0, "KM")) {
		// KB Manage Cat.
		if ($sec2 == "operation/kb/manage_cat")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=kb&sec2=operation/kb/manage_cat'>".__('Manage categories')."</a></li>";
		// KB Product Cat.
		if ($sec2 == "operation/inventories/manage_prod")
				echo "<li id='sidesel'>";
		else
				echo "<li>";
		echo "<a href='index.php?sec=kb&sec2=operation/inventories/manage_prod'>".__('Product types')."</a></li>";
        // KB Manage access
		if ($sec2 == "operation/inventories/manage_perms")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=kb&sec2=operation/kb/manage_perms'>".__('Manage access')."</a></li>";
	}


	echo "</ul>";
	echo "</div>";
}


// Downloads (FR)
if ($sec == "download" && give_acl ($config["id_user"], 0, "FRR") && $show_file_releases != MENU_HIDDEN) {
	echo "<div class='portlet'>";
	echo "<h3>".__('File releases')."</h3>";
	echo "<ul class='sidemenu'>";

	// Browser
	if ($sec2 == "operation/download/browse" AND !isset($_GET["create"]))
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=download&sec2=operation/download/browse&show_types=1'>".__('Browse')."</a></li>";

	if  (give_acl($config["id_user"], 0, "FRW")) {
		// Create / Manage downloads
		if (($sec2 == "operation/download/browse") AND (isset($_GET["create"])))
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=download&sec2=operation/download/browse&create=1'>".__('Create file release')."</a></li>";
	}
	if  (give_acl($config["id_user"], 0, "FRM")) {
		// FR Manage Types
		if ($sec2 == "operation/download/manage_types")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=download&sec2=operation/download/manage_types'>".__('Manage types')."</a></li>";

		// FR Manage Cat.
		if ($sec2 == "operation/download/manage_cat")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=download&sec2=operation/download/manage_cat'>".__('Manage categories')."</a></li>";
		
		// FR Manage access
		if ($sec2 == "operation/download/manage_perms")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=download&sec2=operation/download/manage_perms'>".__('Manage access')."</a></li>";
		
	}


	echo "</ul>";
	echo "</div>";
}


if ($sec == "godmode" && $show_setup != MENU_HIDDEN) {
	echo "<div class='portlet'>";
	echo "<h3>".__('Setup')."</h3>";
	echo "<ul class='sidemenu'>";
	
	// Main Setup
	if ($sec2 == "godmode/setup/setup")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/setup'>".__('Setup')."</a></li>";
	
	// Update Manager
	if ($sec2 == "godmode/setup/update_manager")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/update_manager'>".__('Update')."</a></li>";
	
	// Offline update manager
	if ($sec2 == "godmode/setup/offline_update")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/offline_update'>".__('Offline update')."</a></li>";
	
/* DISABLED UNTIL WE FIX IT

	// Update Manager
	if ($sec2 == "godmode/updatemanager/main")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/updatemanager/main'>".__('Update')."</a></li>";

	// Setup Update Manager
	if ($sec2 == "godmode/updatemanager/settings")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/updatemanager/settings'>".__('Configure updates')."</a></li>";
*/
	// File/Image management
	if ($sec2 == "godmode/setup/filemgr")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/filemgr'>".__('File manager')."</a></li>";

	// Newsboard
	if ($sec2 == "godmode/setup/newsboard")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/newsboard'>".__('News board')."</a></li>";


	// DB manager
	if ($sec2 == "godmode/setup/dbmanager")
                echo "<li id='sidesel'>";
        else
                echo "<li>";
        echo "<a href='index.php?sec=godmode&sec2=godmode/setup/dbmanager'>".__('DB Manager')."</a></li>";


	// Link management
	if ($sec2 == "godmode/setup/links")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/links'>".__('Links')."</a></li>";

	// Event management
	if ($sec2 == "godmode/setup/event")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/event'>".__('System events')."</a></li>";

	// Audit management
	if ($sec2 == "godmode/setup/audit")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/audit'>".__('Audit log')."</a></li>";


	// Log viewer
	if ($sec2 == "godmode/setup/logviewer")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/logviewer'>".__('Error log')."</a></li>";
	
	
	// Pandora FMS translation
	enterprise_include("godmode/sidemenu_translate_setup.php");

	// Pandora FMS Custom Screens
	enterprise_include("godmode/sidemenu_custom_screens_editor.php");

	// Tags management
	if ($sec2 == "godmode/setup/setup_tags")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=godmode&sec2=godmode/setup/setup_tags'>".__('Tags management')."</a></li>";
	
	echo "</ul>";
	echo "</div>";
}

if (($sec == "users") OR ($sec == "user_audit") && $show_people != MENU_HIDDEN) {
	echo "<div class='portlet'>";
	echo "<h3>".__('Myself')."</h3>";
	echo "<ul class='sidemenu'>";
	
	// Edit my user
	if ($sec2 == "operation/users/user_edit")
		if (isset ($_REQUEST['id']) && $_REQUEST['id'] == $config['id_user'])
			echo "<li id='sidesel'>";
		else
			echo "<li>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=users&sec2=operation/users/user_edit&id=".$config['id_user']."'>".__('Edit my user')."</a></li>";

	if (give_acl ($config["id_user"], 0, "PR") && $show_people != MENU_LIMITED && $show_people != MENU_MINIMAL) {
		// Add spare workunit
		if ($sec2 == "operation/users/user_spare_workunit")
		echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/users/user_spare_workunit'>".__('Add spare workunit')."</a></li>";


		$now = date("Y-m-d H:i:s");
		$now_year = date("Y");
		$now_month = date("m");

		// My workunit report
		if ($sec2 == "operation/users/user_workunit_report")
		echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/monthly&month=$now_month&year=$now_year&id=".$config['id_user']."'>".__('Workunit report')."</a></li>";

		// My tasks
		if ($sec2 == "operation/users/user_task_assigment")
		echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/users/user_task_assigment'>".__( "My task assigments")."</a></li>";
		
		echo "</ul>";
	}
	
	echo "</div>";
	
	// PEOPLE REPORTING	
	if  ((give_acl($config["id_user"], 0, "PR") || give_acl($config["id_user"], 0, "IR")) 
			&& $show_people != MENU_LIMITED && $show_people != MENU_MINIMAL) {
		echo "<div class='portlet'>";
		echo "<h3>".__('People reporting')."</h3>";
		echo "<ul class='sidemenu'>";

		// Full report 
		if ($sec2 == "operation/user_report/report_full")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/report_full'>".__('Full report')."</a></li>";

		// Basic report (monthly)
		if ($sec2 == "operation/user_report/report_monthly")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/report_monthly'>".__('Montly report')."</a></li>";

		// Basic report (annual)
		if ($sec2 == "operation/user_report/report_annual")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/report_annual'>".__('Annual report')."</a></li>";
		
		if ($sec2 == "operation/user_report/holidays_calendar")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/holidays_calendar'>".__('Holidays calendar')."</a></li>";
		
		if ($sec2 == "operation/inventories/inventory_reports" || $sec2 == "operation/inventories/inventory_reports_detail")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo '<a href="index.php?sec=users&sec2=operation/inventories/inventory_reports">'.__('Custom reports').'</a>';
		echo '</li>';

		enterprise_hook ('show_programmed_reports', array($sec2));

		echo "</ul></div>";	
	}
	else {
		echo "<div class='portlet'>";
		echo "<h3>".__('People reporting')."</h3>";
		echo "<ul class='sidemenu'>";
		
		if ($sec2 == "operation/inventories/inventory_reports" || $sec2 == "operation/inventories/inventory_reports_detail")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo '<a href="index.php?sec=users&sec2=operation/inventories/inventory_reports">'.__('Custom reports').'</a>';
		echo '</li>';

		enterprise_hook ('show_programmed_reports', array($sec2));
		
		echo "</ul></div>";	
	}

	// PEOPLE MANAGEMENT
	if (give_acl($config["id_user"], 0, "UM") && $show_people != MENU_LIMITED){
		if($show_people != MENU_MINIMAL) {
			echo "<div class='portlet'>";
			echo "<h3>".__('People management')."</h3>";
			echo "<ul class='sidemenu'>";
			
			// Usermanager
			if ($sec2 == "godmode/usuarios/lista_usuarios") 
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=users&sec2=godmode/usuarios/lista_usuarios'>".__('Manage users')."</a>";
			
			if ($sec2 == "godmode/usuarios/lista_usuarios") {
				echo "<li style='margin-left: 15px; font-size: 10px;'>";
				echo "<a href='index.php?sec=users&sec2=godmode/usuarios/configurar_usuarios&alta=1'>".__('Create user')."</a>";
				echo "</li>";
			}

			if ($sec2 == "godmode/usuarios/user_field_list")
				echo "<li id='sidesel'>";
			else
				echo "<li>";   
			echo "<a href='index.php?sec=users&sec2=godmode/usuarios/user_field_list'>".__('Manage user fields')."</a></li>";
			echo "</li>";

			if ($sec2 == "godmode/usuarios/import_from_csv")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=users&sec2=godmode/usuarios/import_from_csv'>".__('Import from CSV')."</a></li>";
			echo "</li>";
			
			// Rolemanager
			if ($sec2 == "godmode/usuarios/role_manager")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=users&sec2=godmode/usuarios/role_manager'>".__('Manage roles')."</a></li>";
			
			// Group manager
			if ($sec2 == "godmode/grupos/lista_grupos")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=users&sec2=godmode/grupos/lista_grupos'>".__('Manage groups')."</a></li>";
			
			if ($sec2 == "godmode/grupos/lista_grupos"){
				echo "<li style='margin-left: 15px; font-size: 10px;'>";
				echo "<a href='index.php?sec=users&sec2=godmode/grupos/configurar_grupo'>".__("Create group")."</a></li>";
			}
		}
		
		enterprise_include ("operation/sidemenu_user_mgmt.php");
		
		if($show_people != MENU_MINIMAL) {
			echo "</ul>";
			echo "</div>";
		}
	}
}

// Wiki
if ($sec == "wiki" && $show_wiki != MENU_HIDDEN)  {
	
	echo "<div class='portlet'>";
	echo "<h3>".__('Wiki')."</h3>";
	echo "<ul class='sidemenu'>";
	
	// Todo overview
	if ($sec2 == "operation/wiki/wiki")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=wiki&sec2=operation/wiki/wiki'>".__('Wiki')."</a></li>";
	echo "</li>";
		$is_enterprise = false;
		if (file_exists ("enterprise/include/functions_wiki.php")) {
			require_once ("enterprise/include/functions_wiki.php");
			$is_enterprise = true;
		}
		
		if (!give_acl ($config['id_user'], $id_grupo, "WW")) {

			//~ $conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
				//~ <li>{plugin:UPLOAD}</li>
				//~ <li>{RECENT_CHANGES}</li>
				//~ <li>{SYNTAX}</li>
				//~ {plugin:SIDEMENU}';
			$conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
				<li>{RECENT_CHANGES}</li>
				<li>{SYNTAX}</li>
				{plugin:SIDEMENU}';
		}
		elseif (!give_acl ($config['id_user'], $id_grupo, "WM")) {

			$conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
				<li>{plugin:UPLOAD}</li>
				<li>{RECENT_CHANGES}</li>
				<li><a href="index.php?sec=wiki&sec2=operation/wiki/wiki&action=syntax">Syntax</a></li>
				{plugin:SIDEMENU}';
		}
		else {
			$translationAdminPages = __('Admin Pages');
			if ($is_enterprise) {
				$conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
					<li>{plugin:ADMINPAGES}</li>
					<li>{plugin:UPLOAD}</li>
					<li>{RECENT_CHANGES}</li>
					<li>{EDIT}</li>
					<li>{HISTORY}</li>
					<li>{READ}</li>
					<li>{WRITE}</li>
					<li><a href="index.php?sec=wiki&sec2=operation/wiki/wiki&action=syntax">Syntax</a></li>
					{plugin:SIDEMENU}';
			} else {
				$conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
				<li>{plugin:ADMINPAGES}</li>
				<li>{plugin:UPLOAD}</li>
				<li>{RECENT_CHANGES}</li>
				<li>{EDIT}</li>
				<li>{HISTORY}</li>
				<li><a href="index.php?sec=wiki&sec2=operation/wiki/wiki&action=syntax">Syntax</a></li>
				{plugin:SIDEMENU}';
			}
		}
		$conf['plugin_dir'] = 'include/wiki/plugins/';
		$conf['self'] = 'index.php?sec=wiki&sec2=operation/wiki/wiki' . '&';
		$conf_var_dir = 'var/';
		if (isset($config['wiki_plugin_dir']))
			$conf_plugin_dir = $config['wiki_plugin_dir'];
		if (isset($config['conf_var_dir']))
			$conf_var_dir = $config['conf_var_dir'];
		$conf['var_dir'] = $conf_var_dir;
	
		require_once("include/wiki/lionwiki_lib.php");	
		ob_start();
		lionwiki_show($conf, false);
		$form_search = ob_get_clean();
	echo $form_search;
	echo "</ul>";
	echo "</div>";
}

// EXTENSIONS
extensions_print_side_menu_subsection($sec, $sec2);

// Calendar box
$month = get_parameter ("month", date ('n'));
$year = get_parameter ("year", date ('y'));

echo '<div class="portlet" style="padding: 0px; margin: 0px;">';
// echo '<a href="javascript:;" onclick="$(\'#calendar_div\').slideToggle (); return false">';
echo '<h3>'.__('Calendar').'</h3>';
echo '<div id="calendar_div">';
echo generate_calendar ($year, $month, array(), 1, NULL, $config["first_day_week"]);
echo '</div>';

echo "<ul class='sidemenu'>";
echo "<li>";
echo '<a href="index.php?sec=agenda&sec2=operation/agenda/agenda">';
echo "<img style='vertical-align:middle' width='20px' src='images/calendar_orange.png'>&nbsp;".__('Full calendar');
echo '</a>';
echo "</li>";

echo "<li>";
if ($sec == 'agenda') {
	echo "<a href='javascript:;' onClick='show_agenda_entry(0, \"\", 0, true)'>
		<img src='images/add.png'>&nbsp;".__('Add entry')."</a>";
} else {
	echo "<a href='javascript:;' onClick='show_agenda_entry(0, \"\", 0, false)'>
		<img style='vertical-align:middle' src='images/add.png'>&nbsp;".__('Add entry')."</a>";
}
echo "</li>";
echo "</ul>";

echo "</div>";
// End of calendar box

// Testing boxes for side menus
$user_row = get_db_row ("tusuario", "id_usuario", $config['id_user']);

$avatar = $user_row["avatar"];
$realname = $user_row["nombre_real"];
$email = $user_row["direccion"];
$description = $user_row["comentarios"];
$userlang = $user_row["lang"];
$telephone = $user_row["telefono"];

$now = date("Y-m-d H:i:s");
$now_year = date("Y");
$now_month = date("m");
$working_month = get_parameter ("working_month", $now_month);
$working_year = get_parameter ("working_year", $now_year);

echo '<div class="portlet">';
//echo '<a href="" onclick="$(\'#userdiv\').slideToggle (); return false">';
echo '<h3>'.__('User info').'</h3>';
//echo '</a>';
echo '<div class="portletBody" id="userdiv">';

echo '<div style="float: left; padding: 7px 7px 0px 0px; ">';
echo '<img src="images/avatars/'.$avatar.'.png" style="height: 30px;" />';
echo '</div>';

echo '<div style="float: left;">';
echo '<a href="index.php?sec=users&sec2=operation/users/user_edit&id='.$config['id_user'].'">';
echo '<strong>'.$config['id_user'].'</strong>';
echo '</a>';
echo '<em style="display: block; margin-top: -2px;">'.$realname.'</em>';
echo '</div>';

echo "<div style='clear:both; margin-bottom: 10px;'></div>";

// Link to workunit calendar (month)
echo '<a href="index.php?sec=users&sec2=operation/user_report/monthly&month='.$now_month.'&year='.$now_year.'&id='.$config['id_user'].'" />';
echo '<img src="images/clock.png" title="'.__('Workunit report').'" /></a>';

if (give_acl ($config["id_user"], 0, "IR")) {
	echo "&nbsp;";
	echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&search_id_user=".$config['id_user']."'>";
	echo '<img src="images/incident.png" title="'.__('My tickets').'"></a>';
}
if (give_acl ($config["id_user"], 0, "PR")) {
	// Link to Work user spare inster
	echo '<a href="index.php?sec=users&sec2=operation/users/user_spare_workunit">';
	echo '<img src="images/award_star_silver_1.png" title="'.__('Workunit').'"></a>';

	// Link to User detailed graph view
	echo '<a href="index.php?sec=users&sec2=operation/user_report/report_full&user_id='.$config['id_user'].'">';
	echo '<img src="images/lightbulb.png" title="'.__('Full graph report').'"></a>';

	// Week Workunit meter
	$begin_week = week_start_day ();
	$begin_week .= " 00:00:00";
	$end_week = date ('Y-m-d H:i:s', strtotime ("$begin_week + 1 week"));
	$total_hours = 5 * $config["hours_perday"];
	$sql = sprintf ('SELECT SUM(duration)
		FROM tworkunit WHERE timestamp > "%s"
		AND timestamp < "%s"
		AND id_user = "%s"',
		$begin_week, $end_week, $config['id_user']);
	$week_hours = get_db_sql ($sql);
	$ratio = $week_hours." ".__('over')." ".$total_hours;
	if ($week_hours < $total_hours)
		echo '<img src="images/exclamation.png" title="'.__('Week workunit time not fully justified').' - '.$ratio.'" />';
	else
		echo '<img src="images/accept.png" title="'.__('Week workunit are fine').' - '.$ratio.'">';
	
	echo "&nbsp;<a href='index.php?sec=projects&sec2=operation/workorders/wo&owner=".$config["id_user"]."'><img src='images/paste_plain.png' title='".__("Work Orders")."' border=0></a>";

}

echo '</div></div>';

// Div for the calendar entry
echo "<div class='dialog ui-dialog-content' id='agenda_entry'></div>";
?>

<script type="text/javascript" src="include/js/agenda.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.slider.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" >
// Form validation
trim_element_on_submit('#text-id');
</script>
