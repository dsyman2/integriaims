<?php

global $config;

require_once ($config['homedir'].'include/functions_db.php');

function user_print_autocomplete_input($parameters) {
	
	if (isset($parameters['input_name'])) {
		$input_name = $parameters['input_name'];
	}
	
	$input_value = '';
	if (isset($parameters['input_value'])) {
		$input_value = $parameters['input_value'];
	}
	
	if (isset($parameters['input_id'])) {
		$input_id = $parameters['input_id'];
	}
	
	$return = false;
	if (isset($parameters['return'])) {
		$return = $parameters['return'];
	}
	$input_size = 15;
	if (isset($parameters['size'])) {
		$input_size = $parameters['size'];
	}
	
	$input_maxlength = 30;
	if (isset($parameters['maxlength'])) {
		$input_maxlength = $parameters['maxlength'];
	}
	
	$src_code = print_image('images/group.png', true, false, true);
	if (isset($parameters['image'])) {
		$src_code = print_image($parameters['image'], true, false, true);
	}
	
	$title = '';
	if (isset($parameters['title'])) {
		$title = $parameters['title'];
	}
	
	$help_message = "Type at least two characters to search";
	if (isset($parameters['help_message'])) {
		$help_message = $parameters['help_message'];
	}
	$return_help = true;
	if (isset($parameters['return_help'])) {
		$return_help = $parameters['return_help'];
	}
	$disabled = false;
	if (isset($parameters['disabled'])) {
		$disabled = $parameters['disabled'];
	}
	
	$attributes = '';
	
	return print_input_text_extended ($input_name, $input_value, $input_id, '', $input_size, $input_maxlength, $disabled, '', $attributes, $return, '', __($title)). print_help_tip (__($help_message), $return_help);
	
}

/*
 * IMPORT USERS FROM CSV. 
 */
function load_file ($users_file, $group, $profile, $nivel, $pass_policy, $avatar) {
	$file_handle = fopen($users_file, "r");
	global $config;

	enterprise_include ('include/functions_license.php', true);

	while (!feof($file_handle) && (($users_check = enterprise_hook('license_check_users_num')) === true || $users_check === ENTERPRISE_NOT_HOOK)) {
		$line = fgets($file_handle);
		
		preg_match_all('/(.*),/',$line,$matches);
		$values = explode(',',$line);
		
		$id_usuario = $values[0];
		$pass = $values[1];
		$pass = md5($pass);
		$nombre_real = $values[2];
		$mail = $values[3];
		$tlf = $values[4];
		$desc = $values[5];
		$avatar = $values[6];
		$disabled = $values[7];
		$id_company = $values[8];
		$num_employee = $values[9];
		$enable_login = $values[10];
		$force_change_pass = 0;
		
		if ($pass_policy) {
			$force_change_pass = 1;
		}
		
		$value = array(
			'id_usuario' => $id_usuario,
			'nombre_real' => $nombre_real,
			'password' => $pass,
			'comentarios' => $desc,
			'direccion' => $mail,
			'telefono' => $tlf,
			'nivel' => $nivel,
			'avatar' => $avatar,
			'disabled' => $disabled,
			'id_company' => $id_company,
			'num_employee' => $num_employee,
			'enable_login' => $enable_login,
			'force_change_pass' => $force_change_pass);
			
		if (($id_usuario!='')&&($nombre_real!='')) {
			if ($id_usuario == get_db_value ('id_usuario', 'tusuario', 'id_usuario', $id_usuario)){
				echo "<h3 class='error'>" . __ ('User '). $id_usuario . __(' already exists') . "</h3>";
			}
			else {
				$resul = process_sql_insert('tusuario', $value);
				
				if ($resul==false){
					$value2 = array(
						'id_usuario' => $id_usuario,
						'id_perfil' => $profile,
						'id_grupo' => $group,
						'assigned_by' => $config["id_user"]
					);
					
					if ($id_usuario!='') {
						process_sql_insert('tusuario_perfil', $value2);
					}
				}
			}
		}
	}

	if ($users_check === false) {
		echo "<h3 class='error'>".__('The number of users has reached the license limit')."</h3>";
	}
	
	fclose($file_handle);
	echo "<h3 class='info'>" . __ ('File loaded'). "</h3>";
	
	return;
}

function user_is_external ($id_user) {
	$nivel = get_db_value('nivel', 'tusuario', 'id_usuario', $id_user);
	
	if ($nivel == -1) {
		return true;
	}
	
	return false;
}

function user_get_projects($id_user) {
	$return = get_db_all_rows_field_filter('trole_people_project', 'id_user', $id_user);
	
	if (empty($return))
		return array();
	else
		return $return;
}

function user_get_task_roles ($id_user, $id_task) {
	
	if (dame_admin ($id_user)) {
		$sql = "SELECT id, name FROM trole";
	} else {
		$sql = "SELECT trole.id, trole.name 
			FROM trole, trole_people_task
			WHERE id_task=$id_task and id_user='$id_user'
			AND trole.id = trole_people_task.id_role";
	}
	
	$roles = get_db_all_rows_sql($sql);
	
	return $roles;
}

function user_delete_user($id_user) {
	global $config;
	
	// Delete user
	// Delete cols from table tgrupo_usuario
	if ($config["enteprise"] == 1){
		$query_del1 = "DELETE FROM tusuario_perfil WHERE id_usuario = '".$id_user."'";
		$resq1 = mysql_query($query_del1);
	}

	// Delete trole_people_task entries 
	mysql_query("DELETE FROM trole_people_task WHERE id_user = '".$id_user."'");

	// Delete trole_people_project entries
	mysql_query ("DELETE FROM trole_people_project WHERE id_user = '".$id_user."'");	

	$query_del2 = "DELETE FROM tusuario WHERE id_usuario = '".$id_user."'";
	$resq2 = mysql_query($query_del2);

	//Delet custom fields
	$query_del3 = "DELETE FROM tuser_field_data WHERE id_user = '".$id_user."'";
	$resq3 = mysql_query($query_del3);

	if (! $resq2) 
		echo "<h3 class='error'>".__('Could not be deleted')."</h3>";
	else
		echo "<h3 class='suc'>".__('Successfully deleted')."</h3>";
	
	return;
}

function user_is_disabled ($id_user) {
	
	$disabled = get_db_value('disabled', 'tusuario', 'id_usuario', $id_user);
	
	if ($disabled == 1) {
		return true;
	}
	
	return false;
}

function users_get_groups_for_select($id_user,  $privilege = "IR", $returnAllGroup = true,  $returnAllColumns = false, $id_groups = null, $keys_field = 'id_grupo') {
	if ($id_groups === false) {
		$id_groups = null;
	}
	
	$user_groups = get_user_groups ($id_user, $privilege, $returnAllGroup, $returnAllColumns);
	
	if ($id_groups !== null) {
		$childrens = groups_get_childrens($id_groups);
		foreach ($childrens as $child) {
			unset($user_groups[$child['id_grupo']]);
		}
		unset($user_groups[$id_groups]);
	}
	
	if (empty($user_groups)) {
		$user_groups_tree = array();
	}
	else {
		// First group it's needed to retrieve its parent group
		$first_group = reset(array_slice($user_groups, 0, 1));
		$parent_group = $first_group['parent'];
		
		$user_groups_tree = groups_get_groups_tree_recursive($user_groups, $parent_group);
	}
	$fields = array();
	
	foreach ($user_groups_tree as $group) {
		$groupName = ui_print_truncate_text($group['nombre'], GENERIC_SIZE_TEXT, false, true, false);
		
		$fields[$group[$keys_field]] = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $group['deep']) . $groupName;
	}
	
	return $fields;
}
?>
