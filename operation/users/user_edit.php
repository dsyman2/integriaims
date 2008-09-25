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

if (check_login() == 0) {
	
	$view_mode = 0;
	$id_usuario = $_SESSION["id_usuario"];
	
	if (isset ($_GET["ver"])){ // Only view mode, 
		$id_ver = $_GET["ver"]; // ID given as parameter
		if ($id_usuario == $id_ver)
			$view_mode = 0;
		else
			$view_mode = 1;
	}

	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id_ver."'";
	$resq1=mysql_query($query1);
	$rowdup=mysql_fetch_array($resq1);
	$nombre=$rowdup["id_usuario"];
	

    if (user_visible_for_me ($config["id_user"], $nombre) == 0){
        audit_db($config["id_user"],$config["REMOTE_ADDR"],"ACL Forbidden","User ".$config["id_user"]." try to access to user detail of '$nombre' without access");
        no_permission();
    }

	// Get user ID to modify data of current user.

	if (isset ($_GET["modificado"])){
		// Se realiza la modificaci�
		if (isset ($_POST["pass1"])){
			if ( isset($_POST["nombre"]) && ($_POST["nombre"] != $_SESSION["id_usuario"])) {
				audit_db($_SESSION["id_usuario"],$REMOTE_ADDR,"Security Alert. Trying to modify another user: (".$_POST['nombre'].") ","Security Alert");
				no_permission;
			}
				
			// $nombre = $_POST["nombre"]; // Don't allow change name !!
			$pass1 = clean_input ($_POST["pass1"]);
			$pass2 = clean_input($_POST["pass2"]);
			$direccion = clean_input($_POST["direccion"]);
			$telefono = clean_input($_POST["telefono"]);
			$nombre_real = clean_input($_POST["nombre_real"]);
			$avatar = get_parameter ("avatar");
			$avatar = substr($avatar, 0, strlen($avatar)-4);
			
			if ($pass1 != $pass2) {
				echo "<h3 class='error'>".$lang_label["pass_nomatch"]."</h3>";
			}
			else {
				echo "<h3 class='suc'>".$lang_label["update_user_ok"]."</h3>";
			}
			$comentarios = clean_input($_POST["comentarios"]);
			if (dame_password($nombre)!=$pass1){
				// Only when change password
				$pass1=md5($pass1);
				$sql = "UPDATE tusuario SET nombre_real = '$nombre_real', password = '$pass1', telefono ='$telefono', direccion ='$direccion', avatar = '$avatar', comentarios = '$comentarios' WHERE id_usuario = '".$nombre."'";
			}
			else 
                $sql = "UPDATE tusuario SET nombre_real = '$nombre_real', telefono ='$telefono', direccion ='$direccion', avatar = '$avatar', comentarios = '$comentarios' WHERE id_usuario = '".$nombre."'";
				
			$resq2=mysql_query($sql);
			
			// Ahora volvemos a leer el registro para mostrar la info modificada
			// $id is well known yet
			$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id_ver."'";
			$resq1=mysql_query($query1);
			$rowdup=mysql_fetch_array($resq1);
			$nombre=$rowdup["id_usuario"];			
		}
		else {
			echo "<h3 class='error'>".$lang_label["pass_nomatch"]."</h3>";
		}
	} 
	
	if ($view_mode == 0)
		echo "<h2>" . __("user_edit_title");
	else
		echo "<h2>" . __("User details");

	if (user_visible_for_me ($config["id_user"], $id_ver) == 1)
		echo "&nbsp;&nbsp;<a href='index.php?sec=users&sec2=godmode/usuarios/configurar_usuarios&id_usuario_mio=$id_ver'><img src='images/wrench.png' border=0 title='".__("Edit")."'></A></h2>";

	// Si no se obtiene la variable "modificado" es que se esta visualizando la informacion y
	// preparandola para su modificacion, no se almacenan los datos
	
	$nombre = $rowdup["id_usuario"];
	if ($view_mode == 0)
		$password=$rowdup["password"];
	else 	
		$password="This is not a good idea :-)";
	
	$comentarios=$rowdup["comentarios"];
	$direccion=$rowdup["direccion"];
	$telefono=$rowdup["telefono"];
	$nombre_real=$rowdup["nombre_real"];
	$avatar = $rowdup ["avatar"];
	$lang = $rowdup ["lang"];

	?>
	<table width="550" class="databox";>
	<?php 
	if ($view_mode == 0) 
		echo '<form name="user_mod" method="post" action="index.php?sec=users&sec2=operation/users/user_edit&ver='.$id_usuario.'&modificado=1">';
	else 	
		echo '<form name="user_mod" method="post" action="">';
	?>
	<tr><td class="datos"><?php echo $lang_label["id_user"] ?>
	<td class="datos"><input class=input type="text" name="nombre" value="<?php echo $nombre ?>" disabled>
	<?php
	if (isset($avatar)) {
		echo "<td class='datos' rowspan=5>";
		echo '<img id="avatar-preview" src="images/avatars/'.$avatar.'.png">';
	}
 	?>
	<tr><td class="datos2"><?php echo $lang_label["real_name"] ?>
	<td class="dato2s"><input class=input type="text" name="nombre_real" value="<?php echo $nombre_real ?>">
	<tr><td class="datos"><?php echo $lang_label["password"] ?>
	<td class="datos"><input class=input type="password" name="pass1" value="<?php echo $password ?>">
	<tr><td class="datos2"><?php echo $lang_label["password"]; echo " ".$lang_label["confirmation"]?>
	<td class="datos2" colspan=2><input class=input type="password" name="pass2" value="<?php echo $password ?>">

<?PHP

	echo "<tr>";
	echo "<td>";
	echo lang_string ("Language");
	echo "<td>";
	if ($view_mode ==0)
		print_select_from_sql ("SELECT * FROM tlanguage", "lang", $lang, '', 'Default', '', false, false, true, false);
	else 
		echo $lang;


	if ($view_mode ==0) {
		// Avatar
		echo "<tr><td class='datos2'>".lang_string("avatar");
		echo '<td class="datos2" colspan="2">';
		$ficheros = list_files('images/avatars/', "png",1, 0, "small");
		$avatar_forlist = $avatar . ".png";
		echo print_select ($ficheros, "avatar", $avatar_forlist, '', '', 0, true, 0, false, false);
	}	

	echo '<tr><td>E-Mail';
	echo '<td colspan=2><input class=input type="text" name="direccion" size="40" value="'.$direccion.'">';

	?>

	<tr><td class="datos"><?php echo $lang_label["telefono"] ?>
	<td class="datos" colspan=2><input class=input type="text" name="telefono" size=15 value="<?php echo $telefono ?>">
	<tr><td class="datos2" colspan="3"><?php echo $lang_label["comments"] ?>
	<tr><td class="datos" colspan="3"><textarea name="comentarios" cols="55" rows="4"><?php echo $comentarios ?></textarea>
	</table>
<?php
	if ($view_mode ==0) {
		echo '<div style="width:550px" class="button">';
		echo "<input name='uptbutton' type='submit' class='sub upd' value='".$lang_label["update"]."'>";
		echo "</div>";
	}
	
	echo '<h3>'.$lang_label["listGroupUser"].'</h3>';
	echo "<table width='500' cellpadding='3' cellspacing='3' class='databox_color'>";
	$sql1='SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$nombre.'"';
	$result=mysql_query($sql1);
	if (mysql_num_rows($result)){
		$color=1;
		while ($row=mysql_fetch_array($result)) {
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo '<td class="'.$tdcolor.'">';
			echo "<b>".dame_perfil($row["id_perfil"])."</b> / ";
			echo "<b>".dame_grupo($row["id_grupo"])."</b><tr>";	
		}
	}
	else { 
		echo '<tr><td class="red" colspan="3">'.$lang_label["no_profile"]; 
	}

	echo '</form></td></tr></table> ';

} // fin pagina

?>

<script  type="text/javascript">
$(document).ready (function () {
	$("#avatar").change (function () {
		icon = this.value.substr(0,this.value.length-4);
		
		$("#avatar-preview").fadeOut ('normal', function () {
			$(this).attr ("src", "images/avatars/"+icon+".png").fadeIn ();
		});
	});
});
</script>
