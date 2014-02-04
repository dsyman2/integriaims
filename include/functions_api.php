<?php
// INTEGRIA IMS v2.1
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2010 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

/**
 * Check a IP and see if it's on the ACL validation list
 * @param $ip
 * @return unknown_type
 */
function ip_acl_check ($ip) {
	global $config;
	
	//If set * in the list ACL return true 
	if (preg_match("/\*/",$config['api_acl']))
		return true;

	if (preg_match("/$ip/",$config['api_acl']))
		return true;
	else
		return false;
}

/**
 * Create an user
 * @param $return_type xml or csv
 * @param $user user who call function
 * @param $params array (username, group_id, profile_id, email, password, description, simplemode, realname)
 * @return unknown_type
 */

function api_create_users ($return_type, $user, $params){
	global $config;
	
	$config['id_user'] = $user;
	
	$group = $params[1];
	$username = $params[0];
	$profile_id = $params[2];
	$email = $params[3];
	$password = $params[4];
	$description = $params[5];
	$simplemode = $params[6];
	$realname = $params[7];
	$external = $params[8];

	// 1 means "admin" 
	if ($external == 1)
		$external = -1;
	else
		$external = 0;

	if (! give_acl ($user, $group, "UM")){
		audit_db ($user,  $_SERVER['REMOTE_ADDR'],
			"ACL Forbidden from API",
			"User ".$user." try to create user");
		exit;
	}

	$timestamp = print_mysql_timestamp();

	$sql = sprintf ('INSERT INTO tusuario
			(nombre_real, id_usuario, password, comentarios,
			fecha_registro, direccion, simple_mode, nivel)
			VALUES ("%s", "%s", md5("%s"), "%s", "%s", "%s", %d, %d)', $realname, $username, $password, $description, $timestamp, $email, $simplemode, $external);

	$new_id = process_sql ($sql, 'insert_id');
	if ($new_id !== false) {

			$sql = sprintf ('INSERT INTO tusuario_perfil
			(id_usuario, id_perfil, id_grupo, assigned_by)
			VALUES ("%s", "%s", "%s", "%s")', $username, $profile_id, $group,  $user);

			$new_id = process_sql ($sql, 'insert_id');
			if ($new_id > 0)
				$result = 1;

	} else {
		$result = 0;
	}
	
	switch($return_type) {
		case "xml": 
				echo xml_node($result);
				break;
		case "csv": 
				echo $result;
				break;
	}
}

/**
 * Create an incident
 * @param $return_type xml or csv
 * @param $user user who call function
 * @param $params array (title, group, priority, description)
 * @return unknown_type
 */

function api_create_incident ($return_type, $user, $params){
	global $config;
	
	$config['id_user'] = $user;

	// $id is the user who create the incident
	
	$group = $params[1];

	if (! give_acl ($user, $group, "IW")){
		audit_db ($user,  $_SERVER['REMOTE_ADDR'],
			"ACL Forbidden from API",
			"User ".$user." try to create ticket");
		exit;
	}

	// Read input variables
	$title = $params[0];
	$description = $params[3];
	$source = 1; // User report
	$priority = $params[2];
	$id_creator = $user;
	$status = 1; // new
	$resolution = 9; // In process / Pending
	$id_inventory = $params[4];

	$email_notify = get_db_sql ("select forced_email from tgrupo WHERE id_grupo = $group");
	$owner = get_db_sql ("select id_user_default from tgrupo WHERE id_grupo = $group");
	if($id_inventory == 0) {
		$id_inventory = get_db_sql ("select id_inventory_default from tgrupo WHERE id_grupo = $group");
	}
	$timestamp = print_mysql_timestamp();

	$sql = sprintf ('INSERT INTO tincidencia
			(inicio, actualizacion, titulo, descripcion,
			id_usuario, estado, prioridad,
			id_grupo, id_creator, notify_email, 
			resolution)
			VALUES ("%s", "%s", "%s", "%s", "%s", %d, %d, %d, "%s",
			"%s", %d)', $timestamp, $timestamp, $title, $description, $owner,
			$status, $priority, $group, $id_creator,
			$email_notify, $resolution);
	
	$id = process_sql ($sql, 'insert_id');
	if ($id !== false) {

		$inventories = array();
		$inventories[0] = $id_inventory;

		/* Update inventory objects in incident */
		update_incident_inventories ($id, $inventories);
		
		$result = $id;

		audit_db ($id_creator, $_SERVER['REMOTE_ADDR'],
			"Incident created (From API)",
			"User ".$id_creator." created ticket #".$id);
		
		incident_tracking ($id, INCIDENT_CREATED);

		// Email notify to all people involved in this incident
		if ($email_notify) {
			mail_incident ($id, $user, "", 0, 1);
		}

	} else {
		$result = -1;
	}
	
	switch($return_type) {
		case "xml": 
				echo xml_node($result);
				break;
		case "csv": 
				echo $result;
				break;
	}
}



function api_get_incidents ($return_type, $user, $params){
	$filter = array();
	
	$filter['string'] = $params[0];
	$filter['status'] = $params[1];
	$filter['id_group'] = $params[2];
	$filter["limit"] = 5000;
	// If the user is admin, all the incidents are showed
	if(!get_admin_user ($user)) {
		$filter['id_user_or_creator'] = $user;
	}
	
	global $config;
	$config['id_user'] = $user;
	
	$result = filter_incidents ($filter);
	
	if($result === false) {
		return "<xml></xml>";
	}
	
	$ret = '';
	
	if($return_type == 'xml') {
		$ret = "<xml version='1.0' encoding='UTF-8'>\n";
	}
	
	$result = clean_numerics($result);


	foreach($result as $index => $item) {
		$item['workunits_hours'] = get_incident_workunit_hours ($item['id_incidencia']);
		$item['workunits_count'] = get_incident_count_workunits ($item['id_incidencia']);
		switch($return_type) {
			case "xml":
				$ret .= xml_node($item, 'incident', false);
				break;
			case "csv":
				$ret .= array_to_csv($item);
				break;
		}
	}
	
	if($return_type == 'xml') {
		$ret .= "</xml>\n";
	}
	return $ret;
}

function xml_node($node, $node_name = "data", $xml_header = true) {
	$ret = "";

	if($xml_header) {
		$ret .= "<xml version='1.0' encoding='UTF-8'>\n";
	}
	
	if($node_name !== false) {
		$ret .= "<".$node_name.">";
	}
	if(is_array($node)) {
		foreach($node as $key => $value) {
			$ret .= "<".$key."><![CDATA[".$value."]]></".$key.">\n";
		}
	}
	else {
		$ret .= $node;
	}
	
	if($node_name !== false) {
		$ret .= "</".$node_name.">\n";
	}
	
	if($xml_header) {
		$ret .= "</xml>\n";
	}
	
	return $ret;
}

function clean_numerics($array) {
	$array_clean = array();
	
	foreach($array as $index => $item) {
		foreach($item as $key => $value) {
			if(is_numeric($key)) {
				unset($item[$key]);
			}
		}
		
		$array_clean[$index] = $item;
	}

return $array_clean;
}

function array_to_csv($array) {
	// output up to 5MB is kept in memory, if it becomes bigger it will automatically be written to a temporary file
	$csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');

	fputcsv($csv, $array);

	rewind($csv);

	// put it all in a variable
	return stream_get_contents($csv);
}

function api_get_incident_details ($return_type, $user, $id_incident){
	if(!check_user_incident($user, $id_incident)) {
		return;
	}
	
	$result = get_incident ($id_incident);
		
	if($result === false) {
		return '';
	}
	
	$result = clean_numerics(array($result));
	
	$ret = '';
	
	switch($return_type) {
		case 'xml':
				$ret = xml_node($result[0], false, true);
				
				break;
		case 'csv':
				$ret = array_to_csv($result);
				break;
	}
	
	return $ret;
}

function api_update_incident ($return_type, $user, $params){	
	$id_incident = $params[0];
	
	if(!check_user_incident($user, $id_incident)) {
		return;
	}
	
	$values['titulo'] = $params[1];
	$values['descripcion'] = $params[2];
	$values['epilog'] = $params[3];
	$values['id_grupo'] = $params[4];
	$values['prioridad'] = $params[5];
	$values['resolution'] = $params[6];
	$values['estado'] = $params[7];
	$values['id_usuario'] = $params[8];
		
	$result = process_sql_update ('tincidencia', $values, array('id_incidencia' => $id_incident));
		
	switch($return_type) {
		case "xml": 
				echo xml_node($result);
				break;
		case "csv": 
				echo $result;
				break;
	}
}

function api_delete_incident ($return_type, $user, $id_incident){	
	if(!check_user_incident($user, $id_incident)) {
		return;
	}
	
	$result = borrar_incidencia($id_incident);
	
	//We suppose that incident erased always work
	//if some part of erased fails no error will be shown
	$result = 1;
	
	switch($return_type) {
		case "xml": 
				echo xml_node($result);
				break;
		case "csv": 
				echo $result;
				break;
	}
}

function api_get_incident_tracking ($return_type, $user, $id_incident){
	if(!check_user_incident($user, $id_incident)) {
		return;
	}
	
	$filter = array();
	
	$result = get_incident_tracking($id_incident);
	
	if($result === false) {
		return '';
	}
	
	$result = clean_numerics($result);
	
	$ret = '';
	
	if($return_type == 'xml') {
		$ret = "<xml>\n";
	}

	foreach($result as $index => $item) {
		switch($return_type) {
			case "xml":
				$ret .= "<tracking>\n";
				foreach($item as $key => $value) {
					if(!is_numeric($key)) {
						$ret .= "<".$key.">".$value."</".$key.">\n";
					}
				}
				$ret .= "</tracking>\n";
				break;
			case "csv":
				$ret .= array_to_csv($item);
				break;
		}
	}
	
	if($return_type == 'xml') {
		$ret .= "</xml>\n";
	}
	
	return $ret;
}

function api_get_incident_workunits ($return_type, $user, $id_incident){
	if(!check_user_incident($user, $id_incident)) {
		return;
	}
	
	$filter = array();
	$workunits = get_incident_workunits ($id_incident);

	if($workunits == false) {
		return '';
	}
	
	$result = array();
	foreach($workunits as $wu) {
		$result[$wu['id']] = get_workunit_data($wu['id_workunit']);
	}

	if($result === false) {
		return '';
	}
	
	$ret = '';
	
	if($return_type == 'xml') {
		$ret = "<xml>\n";
	}

	foreach($result as $index => $item) {
		
		switch($return_type) {
			case "xml":
				$ret .= "<workunit>\n";
				foreach($item as $key => $value) {
					if(!is_numeric($key)) {
						$ret .= "<".$key.">".$value."</".$key.">\n";
					}
				}
				$ret .= "</workunit>\n";
				break;
			case "csv":
				$ret .= array_to_csv($item);
				break;
		}
	}
	
	if($return_type == 'xml') {
		$ret .= "</xml>\n";
	}
	
	return $ret;
}

function api_create_incident_workunit ($return_type, $user, $params){	
	$id_incident = $params[0];

	if(!check_user_incident($user, $id_incident)) {
		return;
	}
	
	$values['timestamp'] = print_mysql_timestamp();
	$values['id_user'] = $user;
	$values['description'] = $params[1];
	$values['duration'] = $params[2];
	$values['have_cost'] = $params[3];
	$values['public'] = $params[4];
	$values['id_profile'] = $params[5];
	
	$id_workunit = process_sql_insert ('tworkunit', $values);

	$result = process_sql_insert('tworkunit_incident', array('id_incident' => $id_incident, 'id_workunit' => $id_workunit));
	
	switch($return_type) {
		case "xml": 
				echo xml_node($result);
				break;
		case "csv": 
				echo $result;
				break;
	}	
}

function api_get_incident_files ($return_type, $user, $id_incident){
	if(!check_user_incident($user, $id_incident)) {
		return;
	}
	
	$filter = array();
	
	$result = get_incident_files ($id_incident);
	
	if($result === false) {
		return '';
	}

	$ret = '';
	
	if($return_type == 'xml') {
		$ret = "<xml>\n";
	}

	foreach($result as $index => $item) {
		switch($return_type) {
			case "xml":
				$ret .= "<file>\n";
				foreach($item as $key => $value) {
					if(!is_numeric($key)) {
						$ret .= "<".$key.">".$value."</".$key.">\n";
					}
				}
				$ret .= "</file>\n";
				break;
			case "csv":
				$ret .= array_to_csv($item);
				break;
		}
	}
	
	if($return_type == 'xml') {
		$ret .= "</xml>\n";
	}
	
	return $ret;
}

function check_user_incident($id_user, $id_incident) {
	$users = get_incident_users($id_incident);
	return in_array($id_user, $users['owner']) || in_array($id_user, $users['creator']) || get_admin_user ($id_user);
}

function api_download_file ($return_type, $user, $id_file){
	global $config;

	$data = get_db_row ("tattachment", "id_attachment", $id_file);
	
	if(!check_user_incident($user, $data['id_incidencia'])) {
		return;
	}
	
	$fileLocation = $config["homedir"]."/attachment/".$data["id_attachment"]."_".$data["filename"];
	
	switch($return_type) {
		case "xml": 
				echo xml_node(base64_encode(file_get_contents($fileLocation)));
				break;
		case "csv": 
				echo base64_encode(file_get_contents($fileLocation));
				break;
	}
}

function api_delete_file ($return_type, $user, $id_attachment){
	global $config;
	
	if (give_acl ($user, $id_grupo, "IM")) {
		$filename = get_db_value ('filename', 'tattachment',
			'id_attachment', $id_attachment);
		$id_incident = get_db_value ('id_incidencia', 'tattachment',
			'id_attachment', $id_attachment);
			
		if(!check_user_incident($user, $id_incident)) {
			return;
		}
	
		$sql = sprintf ('DELETE FROM tattachment WHERE id_attachment = %d',
			$id_attachment);
		process_sql ($sql);
		$result = '0';
			
		include_once ("config.php");
		$error = unlink ($config["homedir"].'attachment/'.$id_attachment.'_'.$filename);
		if (!$error) {
			$result = '-2';
		}
			
		$config['id_user'] = $user;
		incident_tracking ($id_incident, INCIDENT_FILE_REMOVED);
	} else {
		$result = '-1';
	}
	
	switch($return_type) {
		case "xml": 
				echo xml_node($result);
				break;
		case "csv": 
				echo $result;
				break;
	}
}

function api_attach_file ($return_type, $user, $params){
	global $config;
	
	$id_incident = $params[0];
	
	if(!check_user_incident($user, $id_incident)) {
		return;
	}
	
	// Insert into database
	$filename= $params[1];
	$filesize = $params[2];
	$file_description = $params[3];
	$file_content = base64_decode($params[4]);

	$sql = sprintf ('INSERT INTO tattachment (id_incidencia, id_usuario,
			filename, description, size)
			VALUES (%d, "%s", "%s", "%s", %d)',
			$id_incident, $user, $filename, $file_description, $filesize);

	$id_attachment = process_sql ($sql, 'insert_id');
	$config['id_user'] = $user;
	incident_tracking ($id_incident, INCIDENT_FILE_ADDED);

	/*
	// Email notify to all people involved in this incident
	if ($email_notify == 1) {
		if ($config["email_on_incident_update"] == 1){
			mail_incident ($id_incident, $user, 0, 0, 2);
		}
	}*/
	
	include_once ("config.php");

	$homedir = get_db_value ('value', 'tconfig', 'token', $condition = 1);
	
	// Copy file to directory and change name
	$short_filename = $filename;
	$filename = $config["homedir"]."/attachment/".$id_attachment."_".$filename;
	 
	$file_handler = fopen($filename,"w"); 

	fputs($file_handler,$file_content); 

	fclose($DescriptorFichero); 
	
	if (! $file_handler) {
		$result = '-1';
		$sql = sprintf ('DELETE FROM tattachment
				WHERE id_attachment = %d', $id_attachment);
		process_sql ($sql);
	} else {
		// Adding a WU noticing about this
		$link = "<a target='_blank' href='operation/common/download_file.php?type=incident&id_attachment=".$id_attachment."'>".$short_filename."</a>";
		$nota = "Automatic WU: Added a file to this issue. Filename uploaded: ". $link;
		$public = 1;
		$timestamp = print_mysql_timestamp();
		$timeused = "0.05";
		$sql = sprintf ('INSERT INTO tworkunit (timestamp, duration, id_user, description, public) VALUES ("%s", %.2f, "%s", "%s", %d)', $timestamp, $timeused, $user, $nota, $public);

		$id_workunit = process_sql ($sql, "insert_id");
		$sql = sprintf ('INSERT INTO tworkunit_incident (id_incident, id_workunit) VALUES (%d, %d)', $id_incident, $id_workunit);
		process_sql ($sql);
		
		$result = '0';
	}
	
	switch($return_type) {
		case "xml": 
				echo xml_node($result);
				break;
		case "csv": 
				echo $result;
				break;
	}
}

function api_get_incidents_resolutions ($return_type, $user){
	$resolutions = get_db_all_rows_in_table('tincident_resolution');
	
	$resolutions = clean_numerics($resolutions);
	
	$ret = '';
	
	if($return_type == 'xml') {
		$ret = "<xml>\n";
	}
	
	foreach($resolutions as $index => $item) {
		switch($return_type) {
			case "xml":
				$ret .= "<resolution>\n";
				foreach($item as $key => $value) {
					if(!is_numeric($key)) {
						$ret .= "<".$key.">".$value."</".$key.">\n";
					}
				}
				$ret .= "</resolution>\n";
				break;
			case "csv":
				$ret .= array_to_csv($item);
				break;
		}
	}
	
	if($return_type == 'xml') {
		$ret .= "</xml>\n";
	}
	
	return $ret;
}

function api_get_incidents_status ($return_type, $user){
	$status = get_db_all_rows_in_table('tincident_status');
	
	$status = clean_numerics($status);
	
	$ret = '';
	
	if($return_type == 'xml') {
		$ret = "<xml>\n";
	}
	
	foreach($status as $index => $item) {
		switch($return_type) {
			case "xml":
				$ret .= "<status>\n";
				foreach($item as $key => $value) {
					if(!is_numeric($key)) {
						$ret .= "<".$key.">".$value."</".$key.">\n";
					}
				}
				$ret .= "</status>\n";
				break;
			case "csv":
				$ret .= array_to_csv($item);
				break;
		}
	}
	
	if($return_type == 'xml') {
		$ret .= "</xml>\n";
	}
	
	return $ret;
}

function api_get_groups ($return_type, $user, $return_group_all){
	$groups = get_user_groups($user);
	if(!$return_group_all) {
		unset($groups[1]);
	}
	
	$ret = '';
	
	if($return_type == 'xml') {
		$ret = "<xml>\n";
	}
	
	foreach($groups as $index => $item) {
		switch($return_type) {
			case "xml":
				$ret .= "<group>\n";
				$ret .= "<id>".$index."</id>\n";
				$ret .= "<name>".$item."</name>\n";
				$ret .= "</group>\n";
				break;
			case "csv":
				$ret .= array_to_csv(array($index,$item));
				break;
		}
	}
	
	if($return_type == 'xml') {
		$ret .= "</xml>\n";
	}

	return $ret;
}

function api_get_users ($return_type, $user){	

	$users = get_user_visible_users ($user, "IR", false);

	$ret = '';	
	
	if($return_type == 'xml') {
		$ret = "<xml>\n";
	}
	
	foreach($users as $index => $item) {
		switch($return_type) {
			case "xml":
				$ret .= "<id_user>".$index."</id_user>\n";
				break;
			case "csv":
				$ret .= $item['id_usuario']."\n";
				break;
		}
	}
	
	if($return_type == 'xml') {
		$ret .= "</xml>\n";
	}

	return $ret;
}

function api_get_stats ($return_type, $param, $token, $user){

    global $config;
    $config['id_user'] = $user;

    $param = explode ($token, $param);

    $filter = array ();

    if (isset($param[0]))
        $filter['metric'] = $param[0];
    else 
        return; // No valid metric passed as parameter

    if (isset($param[1]))
        $filter['string'] = $param[1];
    else 
        $filter["string"]= "";

    if (isset($param[2]))
        $filter['status'] = $param[2];
    else 
        $filter["status"] = "1,2,3,4,5,6,7";

    // If we need closed incidents, status is fixed to 5 and 6 status
    if ($filter["metric"] == "closed"){
        $filter["status"] = "6,7";
    }

    if ($filter["metric"] == "sla_compliance"){
    	$filter["status"] = "1,2,3,4,5";
    } 

    if ($filter["metric"] == "avg_life"){
        $filter["status"] = "6,7";
    }

    if ($filter["metric"] == "avg_scoring"){
        $filter["status"] = "6,7";
    }

    if (isset($param[3]))
        $filter['id_user'] = $param[3];
    else 
        $filter["id_user"]= "";

    if (isset($param[4]))
        $filter['id_group'] = $param[4];
    else 
        $filter["id_group"]= 1;

    if (isset($param[5]))
        $filter['id_company'] = $param[5];
    else 
        $filter["id_company"]= 0;


    if (isset($param[6]))
        $filter['id_product'] = $param[6];
    else 
        $filter["id_product"]= 0;

    if (isset($param[7]))
        $filter['id_inventory'] = $param[7];
    else 
        $filter["id_inventory"]= 0;

    // No values defined for other filters available but not used:

    $filter['priority'] = (int) get_parameter ('search_priority', -1);
    $filter['serial_number'] = (string) get_parameter ('search_serial_number');
    $filter['id_building'] = (int) get_parameter ('search_id_building');
    $filter['sla_fired'] = false;
    $filter['id_incident_type'] = (int) get_parameter ('search_id_incident_type');
    $filter['first_date'] = (string) get_parameter ('search_first_date');
    $filter['last_date'] = (string) get_parameter ('search_last_date');

    $incidents = filter_incidents ($filter);
    $stats = get_incidents_stats ($incidents);

/*
    $data ["total_incidents"] = $total;
    $data ["opened"] = $opened;
    $data ["closed"] = $total - $opened;
    $data ["avg_life"] = $mean_lifetime;
    $data ["avg_worktime"] = $mean_work;
    $data ["sla_compliance"] = $sla_compliance;
    $data ["avg_scoring"] = $scoring_avg;

*/
	$ret = '';
	
	if($return_type == 'xml') {
		$ret = "<xml>\n";
        $ret .= "<data>";
	}

    switch ($filter['metric']){
		case "total_incidents": 
			$ret .= $stats["total_incidents"];
			break;
		case "opened": 
			$ret .= $stats["opened"];
			break;
		case "closed": 
			$ret .= $stats["closed"];
			break;
		case "avg_life": 
			$ret .= $stats["avg_life"];
			break;
		case "sla_compliance": 
			$ret .= $stats["sla_compliance"];
			break;
		case "avg_scoring": 
			$ret .= $stats["avg_scoring"];
			break;
		case "avg_worktime": 
			$ret .= $stats["avg_worktime"];
			break;
    }	
	
	if($return_type == 'xml') {
        $ret .= "</data>\n";
		$ret .= "</xml>\n";
	}

	return $ret;
}

function api_get_inventories($return_type, $param){
	$inventories = get_inventories();
	$ret = '';
	
	if($return_type == 'xml') {
		$ret = "<xml>\n";
	}

	if (empty($inventories)){
		$ret .= "false";
	}	
	foreach($inventories as $index => $item) {
		switch($return_type) {
			case "xml":
				$ret .= "<inventory>\n";
				$ret .= "<id>".$index."</id>\n";
				$ret .= "<name>".$item."</name>\n";
				$ret .= "</inventory>\n";
				break;
			case "csv":
				$ret .= array_to_csv(array($index, $item));
				break;
		}
	}
	
	if($return_type == 'xml') {
		$ret .= "</xml>\n";
	}

	return $ret;
}

function api_validate_user ($return_type, $user, $pass){
	return get_db_sql ("select count(id_usuario) FROM tusuario WHERE disabled = 0 AND id_usuario = '$user' AND password = md5('$pass')");
}

/**
 * Create a lead
 * @param $return_type xml or csv
 * @param $user user who call function
 * @param $params array (fullname, company, email, country, estimated_sale, progress, phone, mobile, position, managed_by, owner, language)
 * @return unknown_type
 */

function api_create_lead ($return_type, $user, $params){
	global $config;
	
	$config['id_user'] = $user;
	
	$fullname = trim($params[0]);
	$company = trim($params[1]);
	$email = trim($params[2]);
	$country = trim($params[3]);
	$estimated_sale = trim($params[4]);
	$progress = trim($params[5]);
	$phone = trim($params[6]);
	$mobile = trim($params[7]);
	$position = trim($params[8]);
	$owner = trim($params[9]);
	$language = trim($params[10]);
	$comments = trim($params[11]);
	$id_category = trim($params[12]);
	$id_company = trim($params[13]);
	$id_campaign = trim($params[14]);

	// Search if any current lead with the same email already exists
	$duped_id = get_db_value('id','tlead','email',$email);

	// Duped	
	if ($duped_id != ""){
		$duped = 1;
	} else {
		$duped = 0;
	}

	// Invalid lead information, abort
	if (($fullname == "") OR ($email == "") ){
			$result = 0;
	} else { 

		if ($duped == 1){
			$fullname = $fullname . " (DUPED LEAD!)";
			$comments = $comments . " (DUPED LEAD!)";
		}

		$sql = sprintf ('INSERT INTO tlead
				(fullname, company, email, country, estimated_sale, progress, phone, mobile, position, owner, id_language, description, id_category, id_company, creation, modification, id_campaign)  
				VALUES ("%s", "%s", "%s", "%s", "%s", %d, "%s", "%s", "%s", "%s", "%s", "%s", %d, %d, "%s", "%s", %d)', $fullname, $company, $email, $country, $estimated_sale, $progress, $phone, $mobile, $position, $owner, $language, $comments, $id_category, $id_company, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id_campaign);

		$new_id = process_sql ($sql, 'insert_id');

		if ($new_id !== false) {
			$datetime =  date ("Y-m-d H:i:s");
			$sql = sprintf ('INSERT INTO tlead_history (id_lead, id_user, timestamp, description) VALUES (%d, "%s", "%s", "%s")', $new_id, "API", $datetime, "Created lead via API");
			process_sql ($sql);
			$result = 1;			
		} else {
			$result = 0;
		}
	}

	switch($return_type) {
		case "xml": 
				echo xml_node($result);
				break;
		case "csv": 
				echo $result;
				break;
	}
}

function api_get_last_cron_execution ($return_type, $user, $params) {

	$now = strtotime(date('Y/m/d H:i:s'));
	
	$last_exec = get_db_value('value', 'tconfig', 'token', 'crontask');
	
	if (($last_exec === false) || ($last_exec == '')){
		$minutes = -1;
	} else {
		$unix = strtotime($last_exec);
		
		$minutes = ($now - $unix) / 60;
		$minutes = round($minutes, 0);
	}

	$return = '';
	
	if($return_type == 'xml') {
		$return = "<xml>\n";
		$return .= "<cronjob>\n";
		$return .= "<last_exec>".$minutes."</last_exec>\n";
		$return .= "</cronjob>\n";
		$return .= "</xml>\n";
	} else {
		$return = $minutes;
	}
	
	return $return;
	
}

function get_num_queued_emails ($return_type, $user, $params) {

	$sql = "SELECT COUNT(*) FROM tpending_mail";
	
	$count_aux = process_sql ($sql);
	
	$count = $count_aux[0][0];
	
	if($return_type == 'xml') {
		$return = "<xml>\n";
		$return .= "<pending_mail>\n";
		$return .= "<num>".$count."</num>\n";
		$return .= "</pending_mail>\n";
		$return .= "</xml>\n";
	} else {
		$return = $count;
	}
	
	return $return;
}

function api_get_last_invoice_id ($return_type) {

	$sql = sprintf("SELECT bill_id FROM tinvoice WHERE invoice_type = 'Submitted' ORDER BY invoice_create_date DESC, bill_id DESC LIMIT 1");

	$res = process_sql($sql);
	
	if ($res) {
		$res = $res[0]["bill_id"];
	} else {
		$res = "";
	}

	if($return_type == 'xml') {
		$return = "<xml>\n";
		$return .= "<last_invoice_id>\n";
		$return .= "<id><![CDATA[".$res."]]></id>\n";
		$return .= "</last_invoice_id>\n";
		$return .= "</xml>\n";
	} else {
		$return = $res;
	}
	
	return $return;

}

function api_get_invoice ($return_type, $params) {
	global $config;

	$bill_id = trim($params);

	$sql = sprintf('SELECT * FROM tinvoice WHERE bill_id = "%s"', $bill_id);

	$res = get_db_row_sql($sql);

	$data = array();

	if ($res) {
		
		//Create and CSV array
		$data = array(
				"id" => $res["id"],
				"id_user" => $res["id_user"],
				"id_task" => $res["id_task"],
				"id_company" => $res["id_company"],
				"bill_id" => $res["bill_id"],
				"concept1" => $res["concept1"],
				"concept2" => $res["concept2"],
				"concept3" => $res["concept3"],
				"concept4" => $res["concept4"],
				"concept5" => $res["concept5"],
				"amount1" => $res["amount1"],
				"amount2" => $res["amount2"],
				"amount3" => $res["amount3"],
				"amount4" => $res["amount4"],
				"amount5" => $res["amount5"],
				"tax" => $res["tax"],
				"currency" => $res["currency"],
				"description" => $res["description"],
				"id_attachment" => $res["id_attachment"],
				"locked" => $res["locked"],
				"locked_id_user" => $res["locked_id_user"],
				"invoice_create_date" => $res["invoice_create_date"],
				"invoice_payment_date" => $res["invoice_payment_date"],
				"status" => $res["status"],
				"reference" => $res["reference"],
				"internal_note" => $res["internal_note"],
				"invoice_type" => $res["invoice_type"],
				"id_language" => $res["id_language"],
			);
	}

	if($return_type == 'xml') {

		$return = "<xml>\n";
		$return .= "<invoice>\n";
		
		foreach ($data as $key => $value) {
			$return .="<".$key.">";
			$return .="<![CDATA[".$value."]]>";
			$return .="</".$key.">\n";
		}

		$return .= "</invoice>\n";
		$return .= "</xml>\n";
	} else {
		$return = array_to_csv($data);
	}

	return $return;
}

function api_create_invoice ($return_type, $params) {

	$data = array(
		"id_user" => trim($params[0]),
		"id_task" => trim($params[1]),
		"id_company" => trim($params[2]),
		"bill_id" => trim($params[3]),
		"concept1" => trim($params[4]),
		"amount1" => trim($params[5]),
		"tax" => trim($params[6]),
		"currency" => trim($params[7]),
		"description" => trim($params[8]),
		"locked" => trim($params[9]),
		"locked_id_user" => trim($params[10]),
		"invoice_create_date" => trim($params[11]),
		"invoice_payment_date" => trim($params[12]),
		"status" => trim($params[13]),
		"reference" => trim($params[14]),
		"internal_note" => trim($params[15]),
		"invoice_type" => trim($params[16]),
		"id_language" => trim($params[17])
	);
	
	$res_data = array("status" => 1, "error" => "invoice created");

	#Set some default values
	if (!$data["status"]) {
		$data["status"] = "pending";
	}

	if ($data["invoice_create_date"] == "") {
		$data["invoice_create_date"] = date('Y-m-d H:i:s', time());
	}

	if ($data["status"] && !$data["invoice_payment_date"]) {
		$data["invoice_payment_date"] = date('Y-m-d H:i:s', time());	
	}

	#Check for empty billing id
	if (!$data["bill_id"]) {
		$res_data["status"] = 0;
		$res_data["error"] = "empty billing id";
	} else if (!$data["id_company"]) {
 		$res_data["status"] = 0;
		$res_data["error"] = "empty invoice company";
	} else if (!$data["concept1"]) {
 		$res_data["status"] = 0;
		$res_data["error"] = "empty invoice concept";
	} else if (!$data["amount1"]) {
 		$res_data["status"] = 0;
		$res_data["error"] = "empty invoice amount";
	} else if (!$data["currency"]) {
 		$res_data["status"] = 0;
		$res_data["error"] = "empty invoice currency";	
 	} else if (!$data["invoice_type"]) {
 		$res_data["status"] = 0;
		$res_data["error"] = "empty invoice type (Submitted or Received)";
 	} else {

 		#Check if billing id exists
		$invoice_id = get_db_value("id", "tinvoice", "bill_id", $data["bill_id"]);

		if (!$invoice_id) {
			$res = process_sql_insert("tinvoice", $data);
			
			if (!$res) {
				$res_data["status"] = 0;
				$res_data["error"] = "error creating invoice";
			}
		} else {
			$res_data["status"] = 0;
			$res_data["error"] = "invalid billing id";
		}
	}

	if($return_type == 'xml') {

		$return = "<xml>\n";
		$return .= "<invoice>\n";
		
		foreach ($res_data as $key => $value) {
			$return .="<".$key.">";
			$return .="<![CDATA[".$value."]]>";
			$return .="</".$key.">\n";
		}

		$return .= "</invoice>\n";
		$return .= "</xml>\n";
	} else {
		$return = array_to_csv($res_data);
	}

	return $return;	
}

function api_create_company ($return_type, $params) {

	$name = $params[0];
	$address = $params[1];
	$fiscal_id = $params[2];
	$id_company_role = $params[3];
	$country = $params[4];
	$manager = $params[5];
	$id_parent = $params[6];

	$comments = "Created from SaaS portal";
	$website = "";

	$sql = "INSERT INTO tcompany (name, address, comments, fiscal_id, id_company_role, website, country, manager, id_parent)
				 VALUES ('$name', '$address', '$comments', '$fiscal_id', $id_company_role, '$website', '$country', '$manager', $id_parent)";
	
	$id = process_sql ($sql, 'insert_id');	

	$res = $id;
	if (!$id) {
		$res = 0;
	}

	if ($return_type == 'xml') {
		$return = "<xml>\n";
		$return .= "<company>\n";
		$return .="<id>";
		$return .="<![CDATA[".$res."]]>";
		$return .="</id>\n";
		$return .= "</company>\n";
		$return .= "</xml>\n";
	} else {
		$return = $res;
	}

	return $return;
}

function api_get_user_exists ($return_type, $params) {

	$user = get_db_value("id_usuario", "tusuario", "id_usuario", $params);

	$res = 0;

	if ($user) {
		$res = 1;
	}

	if ($return_type == 'xml') {
		$return = "<xml>\n";
		$return .= "<user>\n";
		$return .="<exists>";
		$return .="<![CDATA[".$res."]]>";
		$return .="</exists>\n";
		$return .= "</user>\n";
		$return .= "</xml>\n";
	} else {
		$return = $res;
	}

	return $return;

}

function api_delete_user($return_type, $params) {

	$sql = sprintf('DELETE FROM tusuario WHERE id_usuario = "%s"', $params);

	$ret = process_sql($sql);

	$res = 0;

	if ($ret) {
		$res = 1;
	}

	if ($return_type == 'xml') {
		$return = "<xml>\n";
		$return .= "<user>\n";
		$return .="<exists>";
		$return .="<![CDATA[".$res."]]>";
		$return .="</exists>\n";
		$return .= "</user>\n";
		$return .= "</xml>\n";
	} else {
		$return = $res;
	}

	return $return;	
}

?>
