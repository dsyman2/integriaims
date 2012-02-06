<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// CHECK LOGIN AND ACLs
check_login ();

if (! give_acl ($config['id_user'], 0, "IR")) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access incident viewer");
	require ("general/noaccess.php");
	exit;
}

// SHOW THE FILES
$table->class = 'result_table listing';
$table->width = $width;
$table->id = 'incident_search_result_table';
$separator_style = 'border-bottom: 1px solid rgb(204, 204, 204);border-top: 1px solid rgb(204, 204, 204);';
$table->style = array ();

$table->data = array();
$table->rowstyle = array();

$table->head = array ();

$files = '';
$row = 0;

if(empty($incident['files'])) {
	$table->colspan[$row][0] = 2;
	$table->data[$row][0] = '<i>'.__('No files were added to the incidence').'</i>';
}

$initial_showed_files = 3;
foreach($incident['files'] as $k => $file) {
	$table->data[$row+$k][0] = "";
	$table->data[$row+$k][1] = "";
	$table->data[$row+$k][0] .= "<a href='operation/incidents/incident_download_file.php?id_attachment=".$file['id_attachment']."' target='_blank'><img src='images/attach.png' border=0> ".$file['filename']."</a> (".round($file['size']/1024,2)." KB)<br>";
	$table->data[$row+$k][1] .= $file['description'];	
}

echo '<div id="files_data" class="tab_data" style="display:none">';
print_table($table);
echo '</div>';

unset($table);

?>
