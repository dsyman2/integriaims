<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

$get_license_info = get_parameter('get_license_info', 0);

if ($get_license_info) {
	enterprise_include('include/functions_license.php');
	
	// check license 
	$is_enteprise = enterprise_hook('license_show_info');
	
	// If Open show info
	if ($is_enteprise === ENTERPRISE_NOT_HOOK) {
		$table->width = '98%';
		$table->data = array ();
		$table->style = array();
		$table->style[0] = 'text-align: left';
		
		echo '<div style="float: left; width: 20%; margin-top: 40px; margin-left: 20px;">'; 
		print_image('images/lock_license.png', false);
		echo '</div>';
		
		$table->data[0][0] = '<strong>'.__('Expires').'</strong>';
		$table->data[0][1] = __('Never');
		$table->data[1][0] = '<strong>'.__('Platform Limit').'</strong>';
		$table->data[1][1] = __('Unlimited');
		$table->data[2][0] = '<strong>'.__('Current Platform Count').'</strong>';
		$count_users = get_db_value_sql ('SELECT count(*) FROM tusuario WHERE enable_login=1');
		$table->data[2][1] = $count_users;
		$table->data[3][0] = '<strong>'.__('License Mode').'</strong>';
		$table->data[3][1] = __('Open Source Version');
		
		echo '<div style="width: 70%; margin-top: 30px; margin-left: 20px; float: right;">';
		print_table ($table);
		echo '</div>';
	}
}

return;

?>