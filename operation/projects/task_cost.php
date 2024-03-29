<?PHP
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Global variables
require ("include/functions_tasks.php");
include_once ("include/functions_db.php");

global $config;
check_login ();

$id_task = get_parameter ("id_task", -1);
$task_name = get_db_value ("name", "ttask", "id", $id_task);
$id_project = get_parameter ("id_project", -1);

// ACL
if ($id_task == -1){
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task cost without task id");
	no_permission();
}

if ($id_project == -1) {
	$id_project = get_db_value ("id_project", "ttask", "id", $id_task);
}
// ACL
if (! $id_project) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task cost without project id");
	no_permission();
}

// ACL
$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
if (! $task_permission["write"]) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task add cost control without permission");
	no_permission();
}


$operation = get_parameter ("operation", "");

if ($operation == "delete"){
	
	$id_invoice = get_parameter ("id_invoice", "");
	$invoice = get_db_row_sql ("SELECT * FROM tinvoice WHERE id = $id_invoice");
	
	// Do another security check, don't rely on information passed from URL
	
	if (($config["id_user"] = $invoice["id_user"]) OR ($id_task == $invoice["id_task"])){
			// Todo: Delete file from disk
			if ($invoice["id_attachment"] != ""){
				process_sql ("DELETE FROM tattachment WHERE id_attachment = ". $invoice["id_attachment"]);
			}
			process_sql ("DELETE FROM tinvoice WHERE id = $id_invoice");
	}
	
	$operation = "list";
}

if ($operation == "add"){
	
	$filename = get_parameter ('upfile', false);
	$bill_id = get_parameter ("bill_id", "");
	$description = get_parameter ("description", "");
	$amount = (float) get_parameter ("amount", 0);
	$user_id = $config["id_user"];

	if ($filename != ""){
		$file_temp = sys_get_temp_dir()."/$filename";
		$filesize = filesize($file_temp);
		
		// Creating the attach
		$sql = sprintf ('INSERT INTO tattachment (id_usuario, filename, description, size) VALUES ("%s", "%s", "%s", "%s")',
				$user_id, $filename, $description, $filesize);
		$id_attachment = process_sql ($sql, 'insert_id');
		
		// Copy file to directory and change name
		$file_target = $config["homedir"]."/attachment/".$id_attachment."_".$filename;
			
		if (! copy($file_temp, $file_target)) {
			$result_output = "<h3 class=error>".__('File cannot be saved. Please contact Integria administrator about this error')."</h3>";
			$sql = "DELETE FROM tattachment WHERE id_attachment =".$id_attachment;
			process_sql ($sql);
		} else {
			// Delete temporal file
			unlink ($file_temp);
		}
	} else {
		$id_attachment = 0;
	}
	
	// Creating the cost record
	$sql = sprintf ('INSERT INTO tinvoice (description, id_user, id_task,
	bill_id, concept1, amount1, id_attachment) VALUES ("%s", "%s", %d, "%s", "%s", "%s", %d)',
			$description, $user_id, $id_task, $bill_id, 'Task cost', $amount, $id_attachment);//Check
	
	$ret = process_sql ($sql, 'insert_id');
	if ($ret !== false) {
		echo '<h3 class="suc">'.__('Successfully created').'</h3>';
	} else {
		echo '<h3 class="error">'.__('There was a problem creating adding the cost').'</h3>';
	}
	
	$operation = "list";
}

// Show form to create a new cost

if ($operation == "list"){

	echo "<h3>";
	echo __('Cost unit listing')." - $task_name</h3>";
	
	$total = task_cost_invoices ($id_task);
	
	echo "<h4>".__("Total cost for this task"). " : $total</h4>";

	$costs = get_db_all_rows_sql ("SELECT * FROM tinvoice WHERE id_task = $id_task");
	if ($costs === false)
		$costs = array ();
	
	$table->class = 'listing';
	$table->width = '90%';
	$table->data = array ();
	
	$table->head = array ();
	$table->head[0] = __('Description');
	$table->head[1] = __('Amount');
	$table->head[2] = __('Filename');
	$table->head[3] = __('Delete');
	
	foreach ($costs as $cost) {
		$data = array ();
		$data[0] = $cost["description"];
		$data[1] = get_invoice_amount($cost["id"]);// Check
		$id_invoice = $cost["id"];
		
		$filename = get_db_sql ("SELECT filename FROM tattachment WHERE id_attachment = ". $cost["id_attachment"]);
		
		$data[2] = 	"<a href='".$config["base_url"]."/attachment/".$cost["id_attachment"]."_".$filename."'>$filename</a>";
		
		if (($config["id_user"] = $cost["id_user"]) OR (project_manager_check ($id_project))){
			$data[3] = 	"<a href='index.php?sec=projects&sec2=operation/projects/task_cost&id_task=$id_task&id_project=$id_project&operation=delete&id_invoice=$id_invoice '><img src='images/cross.png'></a>";
		}
		
		array_push ($table->data, $data);
	}
	print_table ($table);

}	


if ($operation == ""){

	echo "<h3>";
	echo __('Add cost unit')." - $task_name</A></h3>";
	echo "<div id='upload_control'>";
	
	$action = "index.php?sec=projects&sec2=operation/projects/task_cost&id_task=$id_task&id_project=$id_project";
	
	$table->id = 'cost_form';
	$table->width = '90%';
	$table->class = 'listing';
	$table->size = array ();
	$table->data = array ();
	
	$table->data[0][0] = __('Bill ID');
	$table->data[0][1] = print_input_text ('bill_id', $bill_id, '', 15, 50, true);
	
	$table->data[1][0] = __('Amount');
	$table->data[1][1] = print_input_text ('amount', $amount, '', 10, 20, true);//Check
	
	$table->data[2][0] = __('Description');
	$table->data[2][1] = print_input_text ('description', $description, '', 60, 250, true);
	
	$table->data[3][0] = __('Attach a file');
	$table->data[3][1] = '__UPLOAD_CONTROL__';

	$into_form = print_table ($table, true);
	
	$into_form .= '<div class="button" style="width: '.$table->width.'">';
	if ($operation == "") {
		$into_form .= print_button (__('Add'), "crt", false, '', 'class="sub next"', true);
		$into_form .= print_input_hidden ('operation', "add", true);
		$button_name = "button-crt";
	} else {
		$into_form .= print_input_hidden ('id', $id_profile, true);
		$into_form .= print_input_hidden ('update_profile', 1, true);
		$into_form .= print_button (__('Update'), "upd", false, '', 'class="sub upd"', true);
		$button_name = "button-upd";
	}
	$into_form .= "</div>";	

	print_input_file_progress($action, $into_form, 'id="form-add-file"', 'sub next', $button_name, false, '__UPLOAD_CONTROL__');
	
	echo "</div>";
}
?>
