<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2011 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars

$accion = "";
global $config;

check_login ();

if (! give_acl ($config['id_user'], 0, "IR")) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

// Take input parameters
$id = (int) get_parameter ('id');
$id_creator = get_parameter('id_creator');

// Delete incident
if (isset ($_POST["quick_delete"])) {
	$id_inc = $_POST["quick_delete"];
	$sql2="SELECT * FROM tincidencia WHERE id_incidencia=".$id_inc;
	$result2=mysql_query($sql2);
	$row2=mysql_fetch_array($result2);
	if ($row2) {
		$id_author_inc = $row2["id_usuario"];
		$email_notify = $row2["notify_email"];
		if (give_acl ($config['id_user'], $row2["id_grupo"], "IM") || $config['id_user'] == $id_author_inc) {
			borrar_incidencia($id_inc);

			echo "<h3 class='suc'>".__('Ticket successfully deleted')."</h3>";
			audit_db($config["id_user"], $config["REMOTE_ADDR"], "Ticket deleted","User ".$config['id_user']." deleted ticket #".$id_inc);
		} else {
			audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Forbidden","User ".$config['id_user']." try to delete ticket");
			echo "<h3 class='error'>".__('There was a problem deleting ticketticket')."</h3>";
			no_permission();
		}
	}
}

/* Tabs code */
echo '<div id="tabs">';

/* Tabs list */
echo '<ul class="ui-tabs-nav">';
echo '<li class="ui-tabs-selected"><a href="#ui-tabs-1"><span><img src="images/zoom.png" title="'.__('Search').'"></span></a></li>';
echo '<li class="ui-tabs"><a href="index.php"><span><img src="images/chart_bar.png" title="'.__('Statistics').'"></span></a></li>';
echo '<li class="ui-tabs-disabled"><a href="index.php"><span valign=bottom><img src="images/bug.png" title="'.__('Details').'"></span></a></li>';
echo '<li class="ui-tabs-disabled"><a href="index.php"><span><img src="images/hourglass.png" title="'.__('Tracking').'"></span></a></li>';
echo '<li class="ui-tabs-disabled"><a href="index.php"><span><img src="images/page_white_text.png"  title="'.__('Ticket report').'"></span></a></li>';
echo '<li class="ui-tabs-disabled"><a href="index.php"><span><img src="images/chart_organisation.png"  title="'.__('Inventory').'"></span></a></li>';
echo '<li class="ui-tabs-disabled"><a href="index.php"><span><img src="images/user_comment.png"  title="'.__('Contacts').'"></span></a></li>';
echo '<li class="ui-tabs-disabled"><a href="index.php"><span><img src="images/award_star_silver_1.png"  title="'.__("Workunits").'"></span></a></li>';
echo '<li class="ui-tabs-disabled"><a href="index.php"><span><img src="images/disk.png" title="'.__('Files').'"></span></a></li>';
if ($config["want_chat"] == 1){
	echo '<li class="ui-tabs-disabled"><a href="index.php"><span><img src="images/comments.png" title="'.__('Chat').'"></span></a></li>';
}
echo '</ul>';

if (!$id) {///
	/* Tabs first container is manually set, so it loads immediately */
	echo '<div id="incident-search" class="ui-tabs-panel">';

	echo "<h1>".__('Ticket search');
	echo "<div class='button-bar-title'>";
	echo "<a href='#incident-operations' onClick='toggleDiv(\"incident-search\");toggleDiv(\"incident-stats\");'>".__("Search statistics")."</a>";
	echo "</div>";
	echo "</h1>";

	echo '<div class="result"></div>';

	$table->id = 'saved_searches_table';
	$table->width = '90%';
	$table->class = 'search-table';
	$table->size = array ();
	$table->size[0] = '120px';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->style[2] = 'font-weight: bold';
	$table->data = array ();
	$table->data[0][0] = __('Custom searches');
	$sql = sprintf ('SELECT id, name FROM tcustom_search
		WHERE id_user = "%s"
		AND section = "incidents"
		ORDER BY name',
		$config['id_user']);
	$table->data[0][1] = print_select_from_sql ($sql, 'saved_searches', 0, '', __('Select'), 0, true);
	$table->data[0][1] .= '<a href="ajax.php" style="display:none" id="delete_custom_search">';
	$table->data[0][1] .= '<img src="images/cross.png" /></a>';
	$table->data[0][2] = __('Save current search');
	$table->data[0][3] = print_input_text ('search_name', '', '', 10, 20, true);
	$table->data[0][4] = print_submit_button (__('Save'), 'save-search', false, 'class="sub next"', true);

	echo '<form id="saved-searches-form">';
	print_table ($table);
	echo '</form>';

	form_search_incident ();

	unset ($table);

	/* Loading message is always shown at first because we run a default search */
	echo '<div id="loading">'.__('Loading');
	echo '... <img src="images/wait.gif" /></div>';

	echo "<br>";
	echo sprintf(__('Max tickets shown: %d'),$config['limit_size']);
	echo print_help_tip (sprintf(__('You can change this value by changing %s parameter in setup'),"<b>".__("Max. tickets by search")."</b>", true));

	$table->class = 'hide result_table listing';
	$table->width = '99%';
	$table->id = 'incident_search_result_table';
	$table->head = array ();
	$table->head[0] = '';
	$table->head[1] = __('ID');
	$table->head[2] = __('SLA');
	$table->head[3] = __('Ticket');
	$table->head[4] = __('Group')."<br><i>".__('Company')."</i>";
	$table->head[5] = __('Status')."<br><i>".__('Resolution')."</i>";
	$table->head[6] = __('Priority');
	$table->head[7] = __('Updated')."<br><i>".__('Started')."</i>";
	$table->head[8] = __('Flags');
	if ($config["show_creator_incident"] == 1)
		$table->head[9] = __('Creator');	
	if ($config["show_owner_incident"] == 1)
		$table->head[10] = __('Owner');	
	$table->style = array ();
	$table->style[0] = '';

	print_table ($table);


	print_table_pager ();

	unset($table);

	$table->class = 'result_table listing';
	$table->width = '99%';
	$table->id = 'incident_massive';

	$table->style = array ();

	$table->head[0] = print_label (__('Status'), '', '', true);
	$table->head[1] = print_label (__('Priority'), '', '', true);
	$table->head[2] = print_label (__('Resolution'), '', '', true);
	$table->head[3] = print_label (__('Assigned user'), '', '', true);

	echo '<br><h2>&nbsp;'.print_image('images/arrow_ele_blue.png', true).' '.__('Massive operations over selected items').'</h2>';

	$table->data[0][0] = combo_incident_status (-1, 0, 0, true, true);
	$table->data[0][1] = print_select (get_priorities (),'mass_priority', -1, '', __('Select'), -1, true);
	$table->data[0][2] = combo_incident_resolution ($resolution, $disabled, true, true);
	$table->data[0][3] = print_select_from_sql('SELECT id_usuario, nombre_real FROM tusuario;', 'mass_assigned_user', '0', '', __('Select'), -1, true);

	print_table ($table);

	echo "<div style='width:".$table->width."'>";
	print_submit_button (__('Update selected items'), 'massive_update', false, 'class="sub next" style="float:right;');
	echo "</div>";

	/* End of first tab container */
	echo '</div>';

	echo '<div id="incident-stats" style="display">TODO STATS</div>';
}//////

echo '</div>';
/* End of tabs code */

?>

<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/jquery.metadata.js"></script>
<script type="text/javascript" src="include/js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="include/js/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="include/js/integria_incident_search.js"></script>

<script type="text/javascript">

var id_incident;
var old_incident = 0;

function tab_loaded (event, tab) {
	/* Details tab */
	if (tab.index == 2) {
		/* In integria_incident_search.js */
		configure_incident_form (true, false);
		
		if (id_incident == old_incident) {
			return;
		}
		if ($(".incident-menu").css ('display') != 'none') {
			$(".incident-menu").slideUp ('normal', function () {
				configure_incident_side_menu (id_incident, true);
				$(this).slideDown ();
			});
		} else {
			configure_incident_side_menu (id_incident, true);
			$(".incident-menu").slideDown ();
		}
		old_incident = id_incident;
	}
	/* Files tab */
	if (tab.index == 7) {
		$("#table_file_list td a.delete").click (function () {
			tr = $(this).parents ("tr");
			if (!confirm ("<?php echo __('Are you sure?'); ?>"))
				return false;
			jQuery.get (
				$(this).attr ("href"),
				null,
				function (data) {
					result_msg (data);
					$(tr).hide ().empty ();
				}
			);
			
			return false;
		});
	}
	
	$(".result").empty ();
}

function check_incident (id) {

	values = Array ();
	values.push ({name: "page",
		value: "operation/incidents/incident_detail"});
	values.push ({name: "id",
		value: id});
	values.push ({name: "check_incident",
		value: 1});
	jQuery.get ("ajax.php",
		values,
		function (data, status) {
			if (data == 1) {
				show_incident_details (id);
			}
			else {
				result_msg_error ("<?php echo __('Unable to load ticket')?> #" + id);
			}
		},
		"html"
	);
}

function show_incident_details (id) {
	id_incident = id;
	$("#tabs > ul").tabs ("url", 2, "ajax.php?page=operation/incidents/incident_dashboard_detail&id=" + id);
	$("#tabs > ul").tabs ("url", 3, "ajax.php?page=operation/incidents/incident_tracking&id=" + id);
	$("#tabs > ul").tabs ("url", 4, "ajax.php?page=operation/incidents/incident_report&id=" + id);
	$("#tabs > ul").tabs ("url", 5, "ajax.php?page=operation/incidents/incident_inventory_detail&id=" + id);
	$("#tabs > ul").tabs ("url", 6, "ajax.php?page=operation/incidents/incident_inventory_contacts&id=" + id);
	$("#tabs > ul").tabs ("url", 7, "ajax.php?page=operation/incidents/incident_workunits&id=" + id);
	$("#tabs > ul").tabs ("url", 8, "ajax.php?page=operation/incidents/incident_files&id=" + id);
	$("#tabs > ul").tabs ("url", 9, "ajax.php?page=operation/incidents/incident_chat&id=" + id);
	
	$("#tabs > ul").tabs ("enable", 2).tabs ("enable", 3).tabs ("enable", 4)
		.tabs ("enable", 5).tabs ("enable", 6).tabs ("enable", 7).tabs ("enable", 8)
		.tabs("enable", 9);
	if (tabs.data ("selected.tabs") == 1) {
		$("#tabs > ul").tabs ("load", 2);
	}
	else {
		$("#tabs > ul").tabs ("select", 2);
	}
	
}

function configure_statistics_tab (values) {
	values.push ({name: "return_filter", value: 1});
	
	jQuery.post ("ajax.php",
		values,
		function (data, status) {
			$("#tabs > ul").tabs ("url", 1, "ajax.php?page=operation/incidents/incident_statistics&filter=" + data);
		},
		"html"
	);
	
	values.pop();
	return false;
}

var tabs;
var first_search = false;

$(document).ready (function () {
	tabs = $("#tabs > ul").tabs ({"load" : tab_loaded});
<?php if ($id) : ?>
	old_incident = id_incident = <?php echo $id ?>;
	configure_incident_side_menu (id_incident, false);
	$(".incident-menu").slideDown ();
	show_incident_details (<?php echo $id; ?>);
<?php endif; ?>
	$("#saved-searches-form").submit (function () {
		search_values = get_form_input_values ('search_incident_form');
		
		values = get_form_input_values (this);
		values.push ({name: "page", value: "operation/incidents/incident_search"});
		$(search_values).each (function () {
			values.push ({name: "form_values["+this.name+"]", value: this.value});
		});
		values.push ({name: "create_custom_search", value: 1});
		jQuery.post ("ajax.php",
			values,
			function (data, status) {
				result_msg (data);
			},
			"html"
		);
		return false;
	});
	
	$("#saved_searches").change (function () {
		if (this.value == 0) {
			$("#delete_custom_search").hide ();
			return;
		}
		$("#delete_custom_search").show ();
		
		values = Array ();
		values.push ({name: "page", value: "operation/incidents/incident_search"});
		values.push ({name: "get_custom_search_values", value: 1});
		values.push ({name: "id_search", value: this.value});
		
		jQuery.get ("ajax.php",
			values,
			function (data, status) {
				load_form_values ("search_incident_form", data);
				$("#search_incident_form").submit ();
			},
			"json"
		);
	});
	
	$("#delete_custom_search").click (function () {
		id_search = $("#saved_searches").attr ("value");
		values = Array ();
		values.push ({name: "page", value: "operation/incidents/incident_search"});
		values.push ({name: "delete_custom_search", value: 1});
		values.push ({name: "id_search", value: id_search});
		
		jQuery.get ("ajax.php",
			values,
			function (data, status) {
				result_msg (data);
				$("#delete_custom_search").hide ();
				$("#saved_searches").attr ("value", 0);
				$("option[value="+id_search+"]", "#saved_searches").remove ();
			},
			"html"
		);
		return false;
	});
	
	$("#goto-incident-form").submit (function () {
		id = $("#text-id", this).attr ("value");
		check_incident (id);
		return false;
	});
	
	configure_incident_search_form (<?php echo $config['block_size']?>,
		function (id, name) {
			check_incident (id);
		},
		function (form) {
			val = get_form_input_values (form);
			val.push ({name: "page",
					value: "operation/incidents/incident_search"});
			configure_statistics_tab(val);
		
		}
	);
	
	$("#search_status").attr ("value", -10);
	$("#search_incident_form").submit ();
});

</script>
