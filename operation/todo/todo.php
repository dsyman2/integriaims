<?php

// Load global vars
if (check_login() == 0){
   	$id_user = $config["id_user"];
	$operation = give_parameter_get ("operation");

	// ---------------
	// CREATE new todo
	// ---------------
	if ($operation == "create2") {
		$name = give_parameter_post ("name");
		$assigned_user = give_parameter_post ("user");
		$priority = give_parameter_post ("priority");
		$progress = give_parameter_post ("progress");
		$description = give_parameter_post ("description");
		$timestamp = date('Y-m-d H:i:s');
		$last_updated = $timestamp;

		$sql_insert="INSERT INTO ttodo (name, priority, assigned_user, created_by_user, progress, timestamp, last_update, description ) VALUES ('$name',$priority, '$assigned_user', '$id_user', '$progress', '$timestamp', '$last_updated', '$description') ";
		//echo "<h1>$sql_insert</h1>";
		$result=mysql_query($sql_insert);	
		if (! $result)
			echo "<h3 class='error'>".$lang_label["create_no"]."</h3>";
		else {
			echo "<h3 class='suc'>".$lang_label["create_ok"]."</h3>"; 
			$id_todo = mysql_insert_id();
		}
		$msgtext = "A new To-Do has been created by user [$id_user] for user [$assigned_user]. Todo information is:

Title   : $name
Priority: $priority
Description: $description

For more information please visit ".$config["base_url"]."index.php?sec=todo&sec2=operation/todo/todo";

		if ($id_user != $assigned_user){
			topi_sendmail (return_user_email($id_user), "[TOPI] New ToDo item has been created", $msgtext);
			topi_sendmail (return_user_email($assigned_user), "[TOPI] New ToDo item has been created", $msgtext);
		} else
			topi_sendmail (return_user_email($assigned_user), "[TOPI] New ToDo item has been created", $msgtext);

		$operation = "";
	}

	// ---------------
	// UPDATE new todo
	// ---------------
	if ($operation == "update2") {
		$id_todo = give_parameter_get ("id");
		$row = give_db_row ("ttodo", "id", $id_todo);
		if (($row["assigned_user"] != $id_user) AND ($row["created_by_user"] != $id_user)){
			no_permission();
		}
		$name = $row["name"];
		$created_by_user = $row["created_by_user"];
		
		$priority = give_parameter_post ("priority");
		$progress = give_parameter_post ("progress");
		$description = give_parameter_post ("description");
		$last_update = date('Y-m-d H:i:s');
		$sql_update = "UPDATE ttodo SET priority = '$priority', progress = '$progress', description = '$description', last_update = '$last_update' WHERE id = $id_todo";
		$result=mysql_query($sql_update);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["modify_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["modify_ok"]."</h3>";

		$msgtext = "A To-Do has been modified by user [$id_user]. Todo information is:

Title   : $name
Priority: $priority
Progress: $progress
Description: $description

For more information please visit ".$config["base_url"]."index.php?sec=todo&sec2=operation/todo/todo";

		topi_sendmail ($id_user, "[TOPI] ToDo '$name' has been updated", $msgtext);
		topi_sendmail ($created_by_user, "[TOPI] ToDo '$name' has been updated", $msgtext);
		$operation = "";
	}

	// ---------------
	// DELETE new todo
	// ---------------
	if ($operation == "delete") {
		$id_todo = give_parameter_get ("id");
		$row = give_db_row ("ttodo", "id", $id_todo);
		if (($row["assigned_user"] != $id_user) AND ($row["created_by_user"] != $id_user)){
			no_permission();
		}
		$assigned_user = $row["assigned_user"];
		$created_by_user = $row["created_by_user"];
		$progress = $row["progress"];
		$name = $row["name"];
		$description = $row["description"];
		$priority = $row["priority"];
		
		$sql_delete= "DELETE FROM ttodo WHERE id = $id_todo";
		$msgtext = "A To-Do has been deleted by user [$id_user]. Todo information was:

Title   : $name
Priority: $priority
Progress: $progress
Description: $description

For more information please visit ".$config["base_url"]."index.php?sec=todo&sec2=operation/todo/todo";

		topi_sendmail ($id_user, "[TOPI] ToDo '$name' has been deleted", $msgtext);
		topi_sendmail ($created_by_user, "[TOPI] ToDo '$name' has been deleted", $msgtext);
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["delete_ok"]."</h3>";
		$operation = "";
	}

	// ---------------
	// UPDATE todo (form)
	// ---------------
	if ($operation == "update") {
		$id_todo = give_parameter_get ("id");
		$row = give_db_row ("ttodo", "id", $id_todo);
		if (($row["assigned_user"] != $id_user) AND ($row["created_by_user"] != $id_user)){
			no_permission();
		}
		$assigned_user = $row["assigned_user"];
		$created_by_user = $row["created_by_user"];
		$progress = $row["progress"];
		$name = $row["name"];
		$description = $row["description"];
		$priority = $row["priority"];
		
        echo "<h2>".lang_string ("todo_update")." - $name </h2>";
		echo '<table class="databox_color" cellpadding="4" cellspacing="4" width="80%">';
		echo "<form name='todou' method='post' action='index.php?sec=todo&sec2=operation/todo/todo&operation=update2&id=$id_todo'>";
		
		echo "<tr><td class='datos2'>".lang_string ("priority");
		echo "<td class='datos2'><select name='priority'>";
		echo "<option value='$priority'>".render_priority($priority);
		for ($ax=0; $ax < 5; $ax++)
			echo "<option value='$ax'>".render_priority($ax);
		echo "</select>";

		echo "<tr><td class='datos'>".lang_string ("progress");
		echo "<td class='datos'><select name='progress'>";
		echo "<option value='$progress'>".$progress." %";
		for ($ax=0; $ax < 11; $ax++)
			echo "<option value='". ($ax * 10) ."'>".($ax*10)." %";
		echo "</select>";
		
		echo "<tr><td class='datos' valign='top'>".lang_string ("description");
		echo "<td class='datos'><textarea name='description' style='width:100%; height:100px'>";
		echo $description;
		echo "</textarea>";
		echo "</table>";
		echo '<table cellpadding="4" cellspacing="4" width="80%">';
		echo "<tr><td align='right'>";
		echo "<input name='crtbutton' type='submit' class='sub' value='".lang_string ("update")."'>";
		echo '</form></table>';
	}

	// ---------------
	// CREATE new todo (form)
	// ---------------
	if ($operation == "create") {
        echo "<h2>".lang_string ("todo_creation")."</h2>";
		echo '<table class="databox_color" cellpadding="4" cellspacing="4" width="80%">';
		echo '<form name="ilink" method="post" action="index.php?sec=todo&sec2=operation/todo/todo&operation=create2">';

		echo "<tr><td class='datos'>".lang_string ("todo");
		echo "<td class='datos'><input name='name' size=40>";

		echo "<tr><td class='datos2'>".lang_string ("priority");
		echo "<td class='datos2'><select name='priority'>";
		for ($ax=0; $ax < 5; $ax++)
			echo "<option value='$ax'>".render_priority($ax);
		echo "</select>";

		echo "<tr><td class='datos'>".lang_string ("progress");
		echo "<td class='datos'><select name='progress'>";
		for ($ax=0; $ax < 11; $ax++)
			echo "<option value='". ($ax * 10) ."'>".($ax*10)." %";
		echo "</select>";

		echo "<tr><td class='datos2'>".lang_string ("assigned_to_user");
		echo "<td class='datos2'>";
		echo combo_users();
		
		echo "<tr><td class='datos' valign='top'>".lang_string ("description");
		echo "<td class='datos'><textarea name='description' style='width:100%; height:100px'>";
		echo "</textarea>";
		echo "</table>";
		echo '<table cellpadding="4" cellspacing="4" width="80%">';
		echo "<tr><td align='right'>";
		echo "<input name='crtbutton' type='submit' class='sub' value='".lang_string ("create")."'>";
		echo '</form></table>';
	}

	// -------------------------
	// TODO VIEW of my OWN items
	// -------------------------
	if (($operation == "") OR ($operation == "notme")){
		if ($operation == "notme")
			echo "<h1>".$lang_label["todo_management"]. " - ". lang_string("assigned_to_other_users")."</h1>";
		else
			echo "<h1>".$lang_label["todo_management"]."</h1>";
		echo "<table cellpadding=4 cellspacing=4 width=100%>";
		echo "<th>".lang_string ("todo");
		echo "<th>".$lang_label["priority"];
		echo "<th>".$lang_label["progress"];
		if ($operation == "notme")
			echo "<th>".lang_string ("assigned_to");
		else
			echo "<th>".$lang_label["assigned_by"];
		echo "<th>".lang_string ("created");
		echo "<th>".lang_string ("updated");
		echo "<th>".lang_string ("delete");
		if ($operation == "notme")
			$sql1="SELECT * FROM ttodo WHERE created_by_user = '$id_user' AND assigned_user != '$id_user'ORDER BY priority DESC";
		else
			$sql1="SELECT * FROM ttodo WHERE assigned_user = '$id_user' ORDER BY priority DESC";
		$result=mysql_query($sql1);
		$color=1;
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
			echo "<a href='index.php?sec=todo&sec2=operation/todo/todo&operation=update&id=".$row["id"]."'>";
			echo $row["name"];
			echo "</A>";
			
			if (strlen($row["description"]) > 0){
				echo "<a href='#' class='$tip'>&nbsp;<span>";
				echo clean_output_breaks($row["description"]);
				echo "</span></a>";
			}
			
			echo '<td class="'.$tdcolor.'" align="center">';
			echo render_priority ($row["priority"]);
			echo '<td class="'.$tdcolor.'" align="center">';
			$completion = $row["progress"];
			echo "<img src='include/functions_graph.php?type=progress&width=90&height=20&percent=$completion'>";
			echo '<td class="'.$tdcolor.'" valign="middle">';
			if ($operation == "notme") 
				$avatar = give_db_value ("avatar", "tusuario", "id_usuario", $row["assigned_user"]);
			else
				$avatar = give_db_value ("avatar", "tusuario", "id_usuario", $row["created_by_user"]);
			echo "<img align='middle' src='images/avatars/".$avatar."_small.png'> ";

			if ($operation == "notme")
				echo $row["assigned_user"];
			else
				echo $row["created_by_user"];
				
			echo '<td class="'.$tdcolor.'f9">';
			echo  human_time_comparation ($row["timestamp"]);
			echo '<td class="'.$tdcolor.'f9">';
			echo human_time_comparation ($row["last_update"]);
			
			// DELETE
			echo '<td class="'.$tdcolor.'" align="center">';
			echo '<a href="index.php?sec=todo&sec2=operation/todo/todo&operation=delete&id='.$row["id"].'" onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;"><img border=0 src="images/cross.png"></a>';
			
		}
		echo "</table>";
	} // Fin bloque else
}

?>