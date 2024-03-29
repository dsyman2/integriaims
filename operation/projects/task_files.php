<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars

global $config;

check_login ();

$id_user = $_SESSION['id_usuario'];
$id_project = get_parameter ("id_project", -1);
$id_task = get_parameter ("id_task", -1);
$operation = get_parameter ("operation", "");

// ACL
if ($id_project == -1) {
	// Doesn't have access to this page
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to task files without project");
	no_permission();
}
$project_access = get_project_access ($config["id_user"], $id_project);
if (! $project_access["read"]) {
	// Doesn't have access to this page
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task files without permission");
	no_permission();
}
if ($id_task > 0) {
	$task_access = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
	if (! $task_access["read"]) {
		// Doesn't have access to this page
		audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task files without permission");
		no_permission();
	}
}

// Get names
$project_name = get_db_value ("name", "tproject", "id", $id_project);
if ($id_task != -1)
	$task_name = get_db_value ("name", "ttask", "id", $id_task);
else
	$task_name = "";


// -----------
// Upload file
// -----------
if ($operation == "attachfile") {
	
	// ACL
	$task_access = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
	if (!$task_access["write"]) {
		// Doesn't have access to this page
		audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation", "Trying to attach a file to a task without permission");
		no_permission();
	}
	
	$filename = get_parameter ('upfile', false);
	$filename_real = safe_output($filename);
	$filename_safe = str_replace (" ", "_", $filename_real);

	if ((bool)$filename){ //if file
		if (isset($_POST["file_description"]))
			$description = $_POST["file_description"];
		else
			$description = "No description available";
			
		// Insert into database
		$file_temp = sys_get_temp_dir()."/$filename_real";
		$filesize = filesize($file_temp);
		
		$sql = " INSERT INTO tattachment (id_task, id_usuario, filename, description, size ) VALUES (".$id_task.", '".$id_user." ','".$filename_safe."','".$description."',".$filesize.") ";
		$id_attachment = process_sql ($sql, 'insert_id');
		//project_tracking ( $id_inc, $id_usuario, 3);
		$result_output = "<h3 class='suc'>".__('File added')."</h3>";
		// Copy file to directory and change name
		$file_target = $config["homedir"]."/attachment/".$id_attachment."_".$filename_safe;
		
		if (! copy($file_temp, $file_target)) {
				$result_output = "<h3 class=error>".__('File cannot be saved. Please contact Integria administrator about this error')."</h3>";
			$sql = "DELETE FROM tattachment WHERE id_attachment =".$id_attachment;
			process_sql ($sql);
		} else {
			// Delete temporal file
			unlink ($file_temp);
		}
	}
}

// -----------
// Delete file
// -----------
if ($operation == "delete") {
	
	// ACL
	$task_access = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
	if (!$task_access["write"]) {
		// Doesn't have access to this page
		audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation", "Trying to delete a file ofy a task without permission");
		no_permission();
	}
	
	$file_id = get_parameter ("file", "");
	$file_row = get_db_row ("tattachment", "id_attachment", $file_id);
	$nombre_archivo = $config["homedir"]."/attachment/".$file_id."_".$file_row["filename"];
	unlink ($nombre_archivo);
	get_db_sql ("DELETE FROM tattachment WHERE id_attachment = $file_id");
	$result_output = "<h3 class='suc'>".__('File deleted')."</h3>";
}

// Specific task
if ($id_task != -1){ 
	$sql = "SELECT * FROM tattachment WHERE id_task = $id_task";
	echo "<h3>".__('Attached files');
	echo " - ".__('Task')." - ".$task_name."</h3>";
	echo "<table cellpadding=4 cellspacing=4 border='0' width=90% class='listing'>";
	echo "<tr><th>"; 
	echo __('Filename');
    echo "<th>"; 
	echo __('Timestamp');
	echo "<th>"; 
	echo __('User');
	echo "<th>"; 
	echo __('Size');
	echo "<th>"; 
	echo __('Description');
	if ($task_access["write"]) {
		echo "<th>"; 
		echo __('Delete');
	}
}

// Whole project
if ($id_task == -1){
	$sql = "SELECT tattachment.id_attachment, tattachment.size, tattachment.description, tattachment.filename, tattachment.id_usuario, ttask.name, ttask.id as task_id FROM tattachment, ttask
			WHERE ttask.id_project = $id_project AND ttask.id = tattachment.id_task";

	echo "<h3>".__('Attached files');
	echo " - ".__('Project')." - ".$project_name."</h3>";
	echo "<table cellpadding=4 cellspacing=4 border='0' width=95% class='listing'>";
	echo "<tr><th>"; 
	echo __('Task');
	echo "<th>"; 
	echo __('Filename');
	echo "<th>"; 
	echo __('Timestamp');
	echo "<th>"; 
	echo __('User');
	echo "<th>"; 
	echo __('Size');
	echo "<th>"; 
	echo __('Description');
	echo "<th>"; 
	echo __('Delete');
}

$color = 0;
if ($res = mysql_query($sql)) {
	while ($row=mysql_fetch_array($res)){
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		} else {
			$tdcolor = "datos2";
			$color = 1;
		}

		if (strlen($row["filename"]) > 35)
			$filename = substr($row["filename"],0,35)."...";
		else
			$filename = $row["filename"];

        $link = $config["base_url"]."/operation/common/download_file.php?type=project&id_attachment=".$row["id_attachment"];

        $real_filename = $config["homedir"]."/attachment/".$row["id_attachment"]."_".rawurlencode ($row["filename"]);

		// Show data
		if ($id_task == -1) {
			
			$task_id = $row["task_id"];
			
			// ACL
			$task_access = get_project_access ($config["id_user"], $id_project, $task_id, false, true);
			if (! $task_access["read"]) {
				continue;
			}
			
			echo "<tr><td class='$tdcolor' valign='top'>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=$id_project&id_task=$task_id&operation=view'>";
			echo $row["name"];
			echo "</a>";
			echo "<td class='$tdcolor' valign='top'>";
			echo '<b><a href="'.$link.'">'.$filename."</a></b>";
		} else {
			echo "<tr><td class='$tdcolor' valign='top'>";
			echo '<b><a href="'.$link.'">'.$filename."</a></b>";
		}

        // Show file datetime
		echo "<td class='$tdcolor f9' valign='top'>";
		$stat = stat ($real_filename);
        echo date ("F d Y H:i:s.", $stat['mtime']);

		echo "<td class='$tdcolor f9' valign='top'>";
		echo $row["id_usuario"];

		echo "<td class='$tdcolor f9' valign='top'>";
		echo $row["size"];

		echo "<td class='$tdcolor' valign='top'>";
		echo $row["description"];
		
		if ($id_task == -1 && $task_access["write"]) {
			echo "<td class='$tdcolor' valign='top'>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_files&id_project=$id_project&operation=delete&file=".$row["id_attachment"]."'><img src='images/cross.png' border=0></A>";
		} elseif ($task_access["write"]) {
			echo "<td class='$tdcolor' valign='top'>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_files&id_project=$id_project&operation=delete&file=".$row["id_attachment"]."'><img src='images/cross.png' border=0></A>";
		}
	}
}
echo "</table>";


?>
