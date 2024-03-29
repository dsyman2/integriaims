<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2013 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


global $config;

check_login();

if (! give_acl ($config["id_user"], 0, "KM")) {
	audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to access Download Management");
	require ("general/noaccess.php");
	exit;
}

$id_user = $config["id_user"];

// Database Creation
// ==================
if (isset($_GET["create2"])){ // Create
	
	$id_product = get_parameter ("product", 0);
	$id_group = get_parameter ("id_group", 0);
	
	$sql_insert="INSERT INTO tkb_product_group (id_product, id_group) 
		  		 VALUE ($id_product, $id_group)";

	$result=mysql_query($sql_insert);	
	if (! $result)
		echo "<h3 class='error'>".__('Could not be created')."</h3>"; 
	else {
		echo "<h3 class='suc'>".__('Successfully created')."</h3>";
		$id_cat = mysql_insert_id();
	}
	
}


// Database DELETE
// ==================
if (isset($_GET["delete"])){ // if modified any parameter
	$id_product = get_parameter ("id_product", 0);
	$id_group = get_parameter ("id_group", 0);
	
	$sql_delete ="DELETE FROM tkb_product_group WHERE 
	id_group = $id_group AND id_product = $id_product";
	$result=mysql_query($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".__('Could not be deleted')."</h3>"; 
	else {
		echo "<h3 class='suc'>".__('Successfully deleted')."</h3>";
	}
}

// CREATE form
if ((isset($_GET["create"]) OR (isset($_GET["update"])))) {
	if (isset($_GET["create"])){
		$id_group = 0;
		$name = "";
		$id = -1;
	} else {
		$id = get_parameter ("update",-1);
		$row = get_db_row ("tkb_product", "id", $id);
		$name = $row["name"];
		$icon = $row["icon"];
		$id_group = $row["id_group"];
	}

	echo "<h1>".__('Create a new product access')."</h1>";
	echo "<form name=catman method='post' action='index.php?sec=kb&
						sec2=operation/kb/manage_perms&create2'>";
	
	
	echo '<table width="99%" class="search-table-button">';
	echo "<tr>";
	echo "<td class=datos>";
	echo __('Product');
	echo "<td class=datos>";
	combo_kb_products ($id_product, 0);

	echo "<tr>";
	echo "<td class=datos2>";
	echo __('Group');
	echo "<td class=datos2>";
	combo_groups_visible_for_me ($config["id_user"], 'id_group', 1, 'KR', $id_group, false, 0 );
	if ($id == -1)
		echo "<tr><td colspan=2>" . print_submit_button (__('Create'), 'crt_btn', false, 'class="sub create"', true) . "</td></tr>";
	else
		echo "<tr><td colspan=2>" . print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"', true) . "</td></tr>";
	echo "</table>";
	echo "</form>";

}

// Show list of categories
// =======================
if ((!isset($_GET["update"])) AND (!isset($_GET["create"]))){
	echo "<h1>".__('KB data product access management')." &raquo; ".__('Assigned products / group')."</h1>";
	$sql1='SELECT tkb_product.name as product, tgrupo.nombre as grupo, tkb_product_group.id_product, tkb_product_group.id_group 
		FROM tkb_product_group, tkb_product, tgrupo 
		WHERE tkb_product_group.id_product = tkb_product.id AND tgrupo.id_grupo = tkb_product_group.id_group';
	$color =0;
	if ($result=mysql_query($sql1)){
		echo '<table width="99%" class="listing">';
		echo "<th>".__('Category')."</th>";
		echo "<th>".__('Group')."</th>";
		echo "<th>".__('Delete')."</th>";
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			
			// Category
			echo "<td class='".$tdcolor."' align='left'>";
			echo $row['product'];
			
			// Group
			echo "<td class='$tdcolor' valign='top'>";
			echo $row['grupo'];		
			// Delete
			echo "<td class='".$tdcolor."f9' align='center' valign='top'>";
			echo "<a href='index.php?sec=kb&
						sec2=operation/kb/manage_perms&
						delete=1&id_product=".$row["id_product"]."&id_group=".$row["id_group"]."' 
						onClick='if (!confirm(\' ".__('Are you sure?')."\')) 
						return false;'>
						<img border='0' src='images/cross.png'></a>";
		}
		echo "</table>";
	}			
	echo '<div style="width:99%; text-align: right;">';
	echo "<form method=post action='index.php?sec=kb&sec2=operation/kb/manage_perms&create=1'>";
	print_submit_button (__('Create'), 'crt_btn', false, 'class="sub next"');
	echo "</form></div>";
} // end of list

?>
