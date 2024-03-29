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

// Load global vars

global $config;

check_login ();

$delete = get_parameter("delete", 0);

if (give_acl($config["id_user"], 0, "FM")==0) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access project group management");
	no_permission();
}

echo "<h1>".__('Link management')."</h1>";

if (isset($_POST["create"])){ // If create
	$name = clean_input ($_POST["name"]);
	$link = clean_input ($_POST["link"]);
	$sql_insert="INSERT INTO tlink (name,link) VALUES ('$name','$link') ";
	$result=mysql_query($sql_insert);	
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem creating link')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Successfully created')."</h3>"; 
		$id_link = mysql_insert_id();
	}
}

if (isset($_POST["update"])){ // if update
	$id_link = get_parameter ("id_link","");
	$name = get_parameter ("name", "");
	$link = get_parameter ("link", "");
	$sql_update ="UPDATE tlink SET name = '".$name."', link ='".$link."'  WHERE id_link = '".$id_link."'";
	$result=mysql_query($sql_update);
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem modifying link')."</h3>";
	else
		echo "<h3 class='suc'>".__('Successfully updated')."</h3>";
}


if ($delete != 0){
	$id_link = get_parameter ("id_link");
	$sql_delete= "DELETE FROM tlink WHERE id_link = ".$id_link;
	$result=mysql_query($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".__('Could not be deleted')."</h3>";
	else
		echo "<h3 class='suc'>".__('Successfully deleted')."</h3>"; 

}

$add = (bool) get_parameter ('add');
$edit = (bool) get_parameter ('edit');

// Main form view for Links edit
if ($add || $edit) {
	if ($edit) {
		$creation_mode = 0;
			$id_link = get_parameter ("id_link","");
			$sql1='SELECT * FROM tlink WHERE id_link = '.$id_link;
			$result=mysql_query($sql1);
			if ($link=mysql_fetch_array($result)){
				$nombre = $link["name"];
				$link = $link["link"];
			} else {
				echo "<h3 class='error'>".__('Name error')."</h3>";
			}
	} else { // form_add
		$creation_mode =1;
		$nombre = "";
		$link = "";
	}

	// Create link
	echo '<table class="search-table-button" cellpadding="4" cellspacing="4" width="99%">';   
	echo '<form name="ilink" method="post" action="index.php?sec=godmode&sec2=godmode/setup/links">';	
	echo '<tr><td class="datos">'.__('Link name').'<td class="datos"><input type="text" name="name"  value="'.$nombre.'">';
	echo '<tr><td class="datos2">'.__('Link').'<td class="datos2"><input type="text" name="link"  value="'.$link.'">';
	if ($creation_mode == 1) {
		print_input_hidden ('create', 1);
		echo "<tr><td colspan='3' align='right'><input name='crtbutton' type='submit' class='sub create' value='".__('Create')."'>";
	} else {
		print_input_hidden ('update', 1);
		print_input_hidden ('id_link', $id_link);
		echo "<tr><td colspan='3' align='right'><input name='crtbutton' type='submit' class='sub upd' value='".__('Update')."'>";
	}
	echo '</form></table>';
} else {
	// Main list view for Links editor
	$table->width = '99%';
	$table->class = 'listing';
	$table->data = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->align = array ();
	$table->align[2] = 'center';
	$table->head = array ();
	$table->head[0] = __('Link name');
	$table->head[1] = __('URL');
	$table->head[2] = __('Delete');
	
	$links = get_db_all_rows_in_table ('tlink', 'name');
	if ($links === false)
		$links = array ();
	foreach ($links as $link) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=godmode&sec2=godmode/setup/links&edit=1&id_link='.
			$link['id_link'].'">'.$link['name'].'</a>';
		$data[1] = '<a href="'.$link['link'].'">'.$link['link'].'</a>';
		$data[2] = '<a href="index.php?sec=godmode&sec2=godmode/setup/links&delete=1&id_link='.
			$link["id_link"].'" onClick="if (!confirm(\''.__('Are you sure?').
			'\')) return false;"><img src="images/cross.png"></a>';
		array_push ($table->data, $data);
	}
	
	print_table ($table);
	echo '<div style="width: '.$table->width.'; text-align: right;">';
	echo '<form method="post">';
	print_input_hidden ('add', 1);
	print_submit_button (__('Add'), 'add_btn', false, 'class="sub create"');
	echo "</form>";
}
?>
