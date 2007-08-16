<?php

// TOPI - the Open Tracking System for the Enterprise
// ==================================================
// Copyright (c) 2007 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load globar vars
global $config;

check_login();

if (give_acl($config["id_user"], 0, "UM")==0) {
	audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to access User Management");
	require ("general/noaccess.php");
	exit;
}

	//  INSERTION
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if (isset($_POST["create"])){ // If create
		$name = give_parameter_post ("name");
		$description = give_parameter_post ("description");
		$cost = give_parameter_post ("cost");
		$sql_insert="INSERT INTO trole (name,description,cost) VALUES ('$name','$description','$cost') ";
		$result=mysql_query($sql_insert);	
		if (! $result)
			echo "<h3 class='error'>".$lang_label["create_no"]."</h3>";
		else {
			echo "<h3 class='suc'>".$lang_label["create_ok"]."</h3>";
			$id = mysql_insert_id();
		}
	}

	// UPDATE
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if (isset($_POST["update"])){ // if update
		$id = give_parameter_post ("id");
		$name = give_parameter_post ("name");
		$description = give_parameter_post ("description");
		$cost = give_parameter_post ("cost");
    	$sql_update = "UPDATE trole SET
    					cost = '$cost', name = '".$name."',
    					description = '$description'
    				   WHERE id = '$id'";
		$result=mysql_query($sql_update);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["modify_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["modify_ok"]."</h3>";
	}

	// DELETE
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if (isset($_GET["borrar"])){ // if delete
		$id = entrada_limpia($_GET["borrar"]);
		$sql_delete= "DELETE FROM tprofile WHERE id = ".$id;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["delete_ok"]."</h3>";

	}

	// EDIT ROLE
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if ((isset($_GET["form_add"])) or (isset($_GET["form_edit"]))){
		if (isset($_GET["form_edit"])){
			$creation_mode = 0;
				$id = give_parameter_get ("id",0);
				$sql1='SELECT * FROM trole WHERE id = '.$id;
				$result=mysql_query($sql1);
				if ($row=mysql_fetch_array($result)){
						$name = $row["name"];
						$description = $row["description"];
						$cost = $row["cost"];
                	}
				else echo "<h3 class='error'>".$lang_label["name_error"]."</h3>";
		} else { // form_add
			$creation_mode =1;
			$name = "";
			$description = "No description";
			$cost = 0;
		}

		// Create link
        echo "<h2>".$lang_label["setup_screen"]."</h2>";
		echo "<h3>".$lang_label["link_management"]."</h3>";
		echo '<table class="fon" cellpadding="3" cellspacing="3" width="500" class="databox_color">';
		echo '<form name="ilink" method="post" action="index.php?sec=users&sec2=godmode/usuarios/role_manager">';
		if ($creation_mode == 1){
			echo "<input type='hidden' name='create' value='1'>";
		} else {
			echo "<input type='hidden' name='update' value='1'>";
			echo "<input type='hidden' name='id' value='$id'>";
		}
		
		echo '<tr><td class="datos">'.$lang_label["role"].'<td class="datos"><input type="text" name="name" size="25" value="'.$name.'">';
		
		echo '<tr><td class="datos2">'.$lang_label["description"].'<td class="datos2"><input type="text" name="description" size="55" value="'.$description.'">';

		echo '<tr><td class="datos">'.$lang_label["cost"].'<td class="datos"><input type="text" name="cost" size="6" value="'.$cost.'">';
		echo "</table>";
		echo '<table class="fon" cellpadding="3" cellspacing="3" width="500">';
		echo "<tr><td align='right'>";
		echo "<input name='crtbutton' type='submit' class='sub next' value='".$lang_label["update"]."'>";
		echo '</form></table>';
	}

	// Role viewer
	// ~~~~~~~~~~~~~~~~~~~~~~~4
	else {  // Main list view for Links editor
		echo "<h2>".$lang_label["role_management"]."</h2>";
		echo "<table cellpadding=4 cellspacing=4 width=700 class='databox_color'>";
		echo "<th>".$lang_label["name"];
		echo "<th>".$lang_label["description"];
		echo "<th>".$lang_label["cost"];
		echo "<th>".$lang_label["delete"];
		$sql1='SELECT * FROM trole ORDER BY name';
		$result=mysql_query($sql1);
		$color=1;
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr><td valign='top' class='$tdcolor'><b><a href='index.php?sec=users&sec2=godmode/usuarios/role_manager&form_edit=1&id=".$row["id"]."'>".$row["name"]."</a></b>";
			echo '<td valign="top" class="'.$tdcolor.'">'.$row["description"];
			echo '<td valign="top" class="'.$tdcolor.'" align="center">'.$row["cost"];
			echo '<td valign="top" class="'.$tdcolor.'" align="center"><a href="index.php?sec=users&sec2=godmode/usuarios/role_manager&id='.$row["id"].'&delete='.$row["id"].'" onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;"><img border=0 src="images/cross.png"></a>';
		}
		echo "</table>";
		echo "<table cellpadding=4 cellspacing=4 width=700>";
		echo "<tr><td align='right'>";
		echo "<form method='post' action='index.php?sec=users&sec2=godmode/usuarios/role_manager&form_add=1'>";
		echo "<input type='submit' class='sub create' name='form_add' value='".$lang_label["add"]."'>";
		echo "</form></table>";
} // Fin bloque else

?>