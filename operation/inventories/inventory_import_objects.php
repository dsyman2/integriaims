<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2012 Ártica Soluciones Tecnológicas
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

include_once('include/functions_inventories.php');

echo "<h1>".__('Import objects from CSV')."</h1>";


$upload_file = (int) get_parameter('upload_file');
$id_object_type = get_parameter('id_object_type');

if ($upload_file) {
	if ($_FILES["file"]["error"] == 0) {
		if (($_FILES["file"]["type"] != 'text/csv') && ($_FILES["file"]["type"] != 'application/vnd.ms-excel')) {
			echo "<h3 class='error'>" . __ ('Unsupported file type') . "</h3>";
		}
		else {
			inventories_load_file ($_FILES["file"]["tmp_name"]);
		}
	}
}

$table->width = '99%';
$table->class = 'search-table';
$table->size = array ();

$table->data = array ();

$table->data[1][0] = "<b>".__('Load file')."</b>";
$table->data[1][0] = '<input class="sub" name="file" type="file" />&nbsp;';
$table->data[1][0] .= '<input type="submit" class="sub upload" value="' . __('Upload File') . '" />';

echo '<form enctype="multipart/form-data" action="index.php?sec=inventory&sec2=operation/inventories/inventory_import_objects" method="POST">';
print_input_hidden ('upload_file', 1);
print_table ($table);


echo '</form>';

?>
