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

if (check_login() != 0) {
	audit_db("Noauth", $config["REMOTE_ADDR"], "No authenticated access", "Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

include_once ("include/functions_projects.php");


$id_user = $config["id_user"];
$operation = get_parameter ("operation");
$id_project = get_parameter ("id_project", -1);

$project_access = get_project_access ($id_user, $id_project);

if ($id_project != 1)
	$project_name = get_db_value ("name", "tproject", "id", $id_project);
else
	$project_name = "";

if ($id_project == -1){
	// Doesn't have access to this page
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to milestone manager without project");
	no_permission();
}
// ACL - To see the project milestones, you should have read access
if (! $project_access["read"]) {
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to milestone manager without permissions");
	no_permission();
}
// ACL - To manage a project milestone, you should have PM access
if ($operation != "" && !$project_access["write"]) {
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to create or delete a milestone without permissions");
	no_permission();
}

// ---------------
// CREATE new milestone
// ---------------
if ($operation == "create2") {
	$name = get_parameter ("name");
	$description = get_parameter ("description");
	$timestamp = get_parameter ("timestamp");
	$id_project = get_parameter ("id_project");
	$sql_insert="INSERT INTO tmilestone (name, description, timestamp, id_project) VALUES ('$name','$description', '$timestamp', '$id_project') ";
	$result=mysql_query($sql_insert);	
	if (! $result)
		echo "<h3 class='error'>".__('Not created. Error inserting data')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Successfully created')."</h3>"; 
		$id_ms = mysql_insert_id();
	}
	
	$operation = "";
}

// ---------------
// DELETE new todo
// ---------------
if ($operation == "delete") {
	$id_milestone = get_parameter ("id");
	$sql_delete= "DELETE FROM tmilestone WHERE id = $id_milestone";
	$result=mysql_query($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".__('Not deleted. Error deleting data')."</h3>";
	else
		echo "<h3 class='suc'>".__('Successfully deleted')."</h3>";
	$operation = "";
}


// ---------------
// CREATE new todo (form)
// ---------------
if ($operation == "create") {
	echo "<h1>".__('Milestone creation')."</h1>";
	echo '<table class="search-table-button"  width="99%">';
	echo '<form name="ilink" method="post" action="index.php?sec=projects&sec2=operation/projects/milestones&id_project='.$id_project.'&operation=create2">';

	echo "<tr><td class='datos'>".__('Name');
	echo "<td class='datos'><input name='name' size=40>";
	
	echo "<tr><td class='datos2'>".__('Timestamp');
	echo "<td class='datos2'>";
    $ahora_date = date("Y-m-d");
	echo "<input type='text' id='timestamp' name='timestamp' size=10 value='$ahora_date'>";

	echo "<tr><td class='datos' valign='top'>".__('Description');
	echo "<td class='datos'><textarea name='description' style='width:100%; height:100px'>";
	echo "</textarea>";
    $project_manager = get_db_value ("id_owner", "tproject", "id", $id_project);
    // milestone creation
    if ((give_acl($config["id_user"], 0, "PM")==1) OR ($project_manager == $config["id_user"])) {
	    echo "<tr><td align='right' colspan=2>";

	    echo "<input type=hidden name='id_project' value='$id_project'>";
	    echo "<input name='crtbutton' type='submit' class='sub create' value='".__('Create')."'>";
    }
    else {
		echo "<tr><td></td></tr>";
	}
	echo "</table>";
	echo '</form>';
}

// -------------------------
// Milestone view
// -------------------------
if ($operation == ""){
	echo "<h1>".__('Milestones management')."</h1>";
	echo "<table class='listing' width=99%>";
	echo "<th>".__('Milestone');
	echo "<th>".__('Description');
	echo "<th>".__('Timestamp');
	if ($project_access['write']) {
		echo "<th>".__('Delete');
	}
	$color=1;
	$sql1="SELECT * FROM tmilestone WHERE id_project = $id_project";
	if ($result=mysql_query($sql1))
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				$tip = "tip";
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
				$tip = "tip2";
			}
			echo "<tr><td class='$tdcolor'>";
			echo $row["name"];
			
			echo "<td class='".$tdcolor."f9'>";
			echo $row["description"];
			
			echo "<td class='".$tdcolor."f9'>";
			echo $row["timestamp"];
			
			// DELETE
			if ($project_access['write']) {
				echo '<td class="'.$tdcolor.'" align="center">';
				echo '<a href="index.php?sec=projects&sec2=operation/projects/milestones&id_project='.$id_project.'&operation=delete&id='.$row["id"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img border=0 src="images/cross.png"></a>';
			}
			
		}
	echo "</table>";

    $project_manager = get_db_value ("id_owner", "tproject", "id", $id_project);
    // milestone creation
    if ($project_access['write']) {
	    echo "<table class='button' width=99%>";
	    echo "<tr><td align=right>";
    
	    echo "<form name='ms' method='POST'  action='index.php?sec=projects&sec2=operation/projects/milestones&operation=create&id_project=$id_project'>";
	    echo "<input type='submit' class='sub create' name='crt' value='".__('Create')."'>";
	    echo "</form>";
	    echo "</table>";
    }
} // Fin bloque else


?>


<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

// Form validation
trim_element_on_submit('input[name="name"]');

add_datepicker ("#timestamp");
</script>
