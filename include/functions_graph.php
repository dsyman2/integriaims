<?PHP


// INTEGRIA IMS v2.0
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es
// Copyright (c) 2007-2011 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

// If is called from index
if (file_exists("include/config.php")) {
	include_once ("include/config.php");
	include_once("include/graphs/fgraph.php");
	include_once ("include/functions_calendar.php");
} // If is called through url
elseif (file_exists("config.php")) {
	include_once ("config.php");
	include_once ("graphs/fgraph.php");
	include_once ("functions_calendar.php");
}

// =====================================================================
// Draw a the bubbles incidents per user
// =====================================================================
function print_bubble_incidents_per_user_graph($incidents_by_user) {
	$max_radius = 0;
	
	$data = array();
	//debugPrint($incidents_by_user);
	$id = 0;
	foreach ($incidents_by_user as $incident) {
		$radius = $incident['hours'] + (0.1 * $incident['files']);
		
		$content = '<b>' . __('Creator') . ':</b> ' . $incident['user_name'] . '<br>' .
			'<b>' . __('Incident') . ':</b> ' . $incident['incident_name'] . '<br>' .
			'<b>' . __('Hours') . ':</b> ' . $incident['hours'] . '<br>' .
			'<b>' . __('Files') . ':</b> ' . $incident['files'];
		
		if ($radius > $max_radius) {
			$max_radius = $radius;
		}
		
		$row = array();
		$row['radius'] = $radius;
		$row['id_creator'] = $incident['id_creator'];
		$row['content'] = $content;
		$row['link'] = 'index.php?' .
			'sec=incidents&' .
			'sec2=operation/incidents/incident_dashboard_detail&' .
			'id=' . $incident['id_incident'];
		$row['id'] = $id;
		
		$data[$id] = $row;
		$id++;
	}
	
	?>
	<script type="text/javascript">
		var nodes = [
			<?php
			$first = true;
			foreach ($data as $node) {
				if (!$first)
					echo ",\n";
				$first = false;
				
				echo "{
					'radius': " . $node['radius'] . ",
					'id_creator': '" . $node['id_creator'] . "',
					'content': '" . $node['content'] . "',
					'link': '" . $node['link'] . "',
					'id': " . $node['id'] . ",
					}\n";
			}
			?>
		];
	</script>
	<?php
	?>
	<div id="graph_container"></div>
	<style type="text/css">
		circle {
		  stroke: #fff;
		}
		
		circle.over {
			stroke: #999;
		}
		
		circle.mouse_down {
			stroke: #000;
		}
	</style>
	<script type="text/javascript">
		var margin = {top: 0, right: 0, bottom: 0, left: 0},
			width = 960 - margin.left - margin.right,
			height = 500 - margin.top - margin.bottom;
		
		var padding = 6;
		var radius = d3.scale.sqrt().range([0, <?php echo $max_radius; ?>]);
		var color = d3.scale.category20();
		
		var svg = d3.select("#graph_container").append("svg")
			.attr("width", width + margin.left + margin.right)
			.attr("height", height + margin.top + margin.bottom)
			.append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")");
		
		var force = d3.layout.force()
			.nodes(nodes)
			.size([width, height])
			.gravity(.02)
			.charge(0)
			.on("tick", tick)
			.start();
		
		
		var circle = svg.selectAll("circle")
			.data(nodes)
			.enter().append("circle")
			.attr("id", function(d) { return "node_" + d.id;})
			.attr("r", function(d) { return radius(d.radius); })
			.style("fill", function(d) { return color(d.id_creator); })
			.on("mouseover", over)
			.on("mouseout", out)
			.on("mousemove", move_tooltip)
			.on("mousedown", mouse_down)
			.on("mouseup", mouse_up)
			.call(force.drag);
		
		function tick(e) {
			circle
				.each(cluster(10 * e.alpha * e.alpha))
				.each(collide(0.5))
				.attr("cx", function(d) { return d.x; })
				.attr("cy", function(d) { return d.y; });
		}
		
		// Move d to be adjacent to the cluster node.
		function cluster(alpha) {
			var max = {};
			
			// Find the largest node for each cluster.
			nodes.forEach(function(d) {
				if (!(color(d.color) in max)
					|| (radius(d.radius) > radius(max[color(d.color)].radius))) {
					max[color(d.color)] = d;
				}
			});
			
			return function(d) {
				var node = max[color(d.color)],
				l,
				r,
				x,
				y,
				i = -1;
				
				if (node == d) return;
				
				x = d.x - node.x;
				y = d.y - node.y;
				l = Math.sqrt(x * x + y * y);
				r = radius(d.radius) + radius(node.radius);
				if (l != r) {
					l = (l - r) / l * alpha;
					d.x -= x *= l;
					d.y -= y *= l;
					node.x += x;
					node.y += y;
				}
			};
		}
		
		// Resolves collisions between d and all other circles.
		function collide(alpha) {
			var quadtree = d3.geom.quadtree(nodes);
			return function(d) {
				var r = radius(d.radius) + radius.domain()[1] + padding,
					nx1 = d.x - r,
					nx2 = d.x + r,
					ny1 = d.y - r,
					ny2 = d.y + r;
					
				quadtree.visit(function(quad, x1, y1, x2, y2) {
					if (quad.point && (quad.point !== d)) {
						var x = d.x - quad.point.x,
							y = d.y - quad.point.y,
							l = Math.sqrt(x * x + y * y),
							r = radius(d.radius) + quad.point.radius
								+ (color(d.color) !== quad.point.color) * padding;
						
						if (l < r) {
							l = (l - r) / l * alpha;
							d.x -= x *= l;
							d.y -= y *= l;
							quad.point.x += x;
							quad.point.y += y;
						}
					}
					return x1 > nx2
						|| x2 < nx1
						|| y1 > ny2
						|| y2 < ny1;
				});
			};
		}
		
		var mouse_click_x;
		var mouse_click_y;
		
		function mouse_up(d) {
			x = d3.event.clientX;
			y = d3.event.clientY;
			
			if ((x == mouse_click_x) && 
				(y == mouse_click_y)) {
				window.location = d.link;
			}
		}
		
		function mouse_down(d) {
			svg.select("#node_" + d.id)
				.attr("class", "mouse_down");
			
			mouse_click_x = d3.event.clientX;
			mouse_click_y = d3.event.clientY;
		}
		
		function over(d) {
			svg.select("#node_" + d.id)
				.attr("class", "over");
			
			show_tooltip(d);
		}
		
		function out(d) {
			svg.select("#node_" + d.id)
				.attr("class", "");
			
			hide_tooltip();
		}
		
		function move_tooltip(d) {
			x = d3.event.clientX + 10;
			y = d3.event.clientY + 10;
			
			$("#tooltip").css('left', x + 'px');
			$("#tooltip").css('top', y + 'px');
		}
		
		function create_tooltip(d, x, y) {
			if ($("#tooltip").length == 0) {
				$("body")
					.append($("<div></div>")
					.attr('id', 'tooltip')
					.html(d.content));
			}
			else {
				$("#tooltip").html(d.content);
			}
			
			$("#tooltip").attr('style', 'background: #fff;' + 
				'position: absolute;' + 
				'display: block;' + 
				'width: 200px;' + 
				'text-align: left;' + 
				'padding: 10px 10px 10px 10px;' + 
				'z-index: 2;' + 
				"-webkit-box-shadow: 7px 7px 5px rgba(50, 50, 50, 0.75);" +
				"-moz-box-shadow:    7px 7px 5px rgba(50, 50, 50, 0.75);" +
				"box-shadow:         7px 7px 5px rgba(50, 50, 50, 0.75);" +
				'left: ' + x + 'px;' + 
				'top: ' + y + 'px;');
		}
		
		
		function show_tooltip(d) {
			x = d3.event.clientX + 10;
			y = d3.event.clientY + 10;
			
			create_tooltip(d, x, y);
		}
		
		function hide_tooltip() {
			$("#tooltip").hide();
		}
	</script>
	<?php
}

// =====================================================================
// Draw a time graph for user with the projects
// =====================================================================
function print_project_user_timegraph($id_user, $start_date = false, $end_date = false) {
	include_once ("include/functions_user.php");
	
	$projects = user_get_projects($id_user);
	
	$data = array();
	
	$user_name = get_db_value('nombre_real', 'tusuario',
		'id_usuario', $id_user);
	
	foreach ($projects as $project) {
		$tasks = get_db_all_rows_field_filter('ttask', 'id_project',
			$project['id_project']);
		$project_name = get_db_value('name', 'tproject',
			'id', $project['id_project']);
		
		foreach ($tasks as $task) {
			$hours = get_task_workunit_hours_user ($task['id'],
				$id_user, 0, $start_date, $end_date);
			
			if (empty($hours))
				continue;
			
			$data[$project['id_project']][$task['id']] = array(
				'parent_name' => safe_output($project_name),
				'name' => safe_output($task['name']),
				'value' => $hours,
				'tooltip' => "<b>" . __('Project:') . "</b> " . $project_name . "<br />" .
					"<b>" . __('Task:') . "</b> " . $task['name'] . "<br />" .
					"<b>" . __('Hours:') . "</b> " . $hours,
				'id' => $project['id_project'] . "_" . $task['id']);
		}
	}
	
	if (empty($data)) {
		ui_print_error_message(__('There are not tasks with hours in this period.'));
		return;
	}
	
	graph_print_d3js_treemap($data);
}

// =====================================================================
// Draw a time graph for project
// =====================================================================
function print_project_timegraph($id_project, $start_date = false, $end_date = false, $id_user_filter = "") {
	
	if ($id_user_filter == "") {
		$users = get_users_project ($id_project);
	} else {
		$sql = "SELECT *
                FROM trole_people_project
                WHERE id_project = $id_project AND id_user = '$id_user_filter'";
        	$users = get_db_all_rows_sql ($sql);
	}

	$tasks = get_db_all_rows_field_filter('ttask', 'id_project', $id_project);
	
	$data = array();
	foreach ($tasks as $task) {
		foreach($users as $user) {
			$user_name = get_db_value('nombre_real', 'tusuario',
				'id_usuario', $user['id_user']);
			
			$hours = get_task_workunit_hours_user ($task['id'],
				$user['id_user'], 0, $start_date, $end_date);
			
			if (empty($hours))
				continue;
			
			$data[$task['id']][$user['id_user']] = array(
				'parent_name' => safe_output($task['name']),
				'name' => safe_output($user_name),
				'value' => $hours,
				'tooltip' => "<b>" . __('Task:') . "</b> " . $task['name'] . "<br />" .
					"<b>" . __('User:') . "</b> " . $user_name . "<br />" .
					"<b>" . __('Hours:') . "</b> " . $hours,
				'id' => $task['id'] . "_" . $user['id_user']);
		}
	}
	
	if (empty($data)) {
		ui_print_error_message(__('There are not tasks with hours in this period.'));
		return;
	}
	
	graph_print_d3js_treemap($data);
}

function graph_print_d3js_treemap($data) {
	?>
	<script type="text/javascript">
		var treemap_data = {
			"name": "treemap_data",
			"children" : [
				<?php
				$f = true;
				foreach ($data as $task) {
					if (!$f) {
						echo ",";
					}
					$f = false;
					
					$first = true;
					foreach($task as $user) {
						if ($first) {
							echo "{'name' : '" .$user['parent_name'] . "',\n";
							echo "'task': 1,\n";
							echo "'children' : [\n";
						}
						else {
							echo ",\n";
						}
						$first = false;
						
						echo "{'name': '" . $user['name'] . "',\n" .
							"'id' : 'id_" . $user['id'] . "',\n" .
							"'tooltip_content' : '" . $user['tooltip'] . "',\n" .
							"'value' : " . $user['value'] . "}\n";
					}
					echo "]\n";
					echo "}\n";
				}
				?>
			]
		};
	</script>
<style>
.node {
	border: solid 1px white;
	font: 10px sans-serif;
	color: #000000;
	line-height: 12px;
	overflow: hidden;
	position: absolute;
	text-align: center;
}
</style>
<script>
	var margin = {top: 10, right: 10, bottom: 10, left: 10},
		width = 800 - margin.left - margin.right,
		height = 500 - margin.top - margin.bottom;
	
	var color = d3.scale.category20();
	
	var treemap = d3.layout.treemap()
		.size([width, height])
		.sticky(true)
		.value(function(d) { return d.value; });
	
	var div = d3.select("#time_graph").append("div")
		.style("position", "relative")
		.style("width", (width + margin.left + margin.right) + "px")
		.style("height", (height + margin.top + margin.bottom) + "px")
		.style("left", margin.left + "px")
		.style("top", margin.top + "px");
	
	
	var node = div.datum(treemap_data).selectAll(".node")
		.data(treemap.nodes)
		.enter().append("div")
		.attr("id", function(d) {return d.id;})
		.attr("data-task", function(d) { if (d.task) return d.name; })
		.on("mouseover", over_user)
		.on("mouseout", out_user)
		.on("mousemove", move_tooltip)
		.attr("class", "node")
		.call(position)
		.style("background", function(d) { return d.children ? color(d.name) : null; })
		.style("line-height", "50px")
		.append("span")
		.text(function(d) {
				if (d.children) {
					return null;
				}
				else {
					return d.name;
				}
			});
	
	function position() {
		this.style("left", function(d) { return d.x + "px"; })
			.style("top", function(d) { return d.y + "px"; })
			.style("width", function(d) { return Math.max(0, d.dx - 1) + "px"; })
			.style("height", function(d) { return Math.max(0, d.dy - 1) + "px"; });
	}
	
	function move_tooltip(d) {
		x = d3.event.clientX + 10;
		y = d3.event.clientY + 10;
		
		$("#tooltip").css('left', x + 'px');
		$("#tooltip").css('top', y + 'px');
	}
	
	function over_user(d) {
		id = d.id;
		
		$("#" + id).css('border', '1px solid black');
		$("#" + id).css('z-index', '1');
		
		show_tooltip(d);
	}
	
	function out_user(d) {
		id = d.id;
		
		$("#" + id).css('border', '');
		$("#" + id).css('z-index', '');
		
		hide_tooltip();
	}
	
	function create_tooltip(d, x, y) {
		if ($("#tooltip").length == 0) {
			$("body")
				.append($("<div></div>")
				.attr('id', 'tooltip')
				.html(d.tooltip_content));
		}
		else {
			$("#tooltip").html(d.tooltip_content);
		}
		
		$("#tooltip").attr('style', 'background: #fff;' + 
			'position: absolute;' + 
			'display: block;' + 
			'width: 200px;' + 
			'text-align: left;' + 
			'padding: 10px 10px 10px 10px;' + 
			'z-index: 2;' + 
			"-webkit-box-shadow: 7px 7px 5px rgba(50, 50, 50, 0.75);" +
			"-moz-box-shadow:    7px 7px 5px rgba(50, 50, 50, 0.75);" +
			"box-shadow:         7px 7px 5px rgba(50, 50, 50, 0.75);" +
			'left: ' + x + 'px;' + 
			'top: ' + y + 'px;');
	}
	
	
	function show_tooltip(d) {
		x = d3.event.clientX + 10;
		y = d3.event.clientY + 10;
		
		create_tooltip(d, x, y);
	}
	
	function hide_tooltip() {
		$("#tooltip").hide();
	}
</script>
	<?php
}

// =====================================================================
// Draw a simple pie graph with incidents, by assigned user
// =====================================================================

function incident_peruser ($width, $height) {
	require_once ("../include/config.php");
	
	$res = mysql_query("SELECT * FROM tusuario");
	while ($row=mysql_fetch_array($res)) {
		$id_user = $row["id_usuario"];
		$datos = get_db_sqlf ("SELECT COUNT(id_usuario) FROM tincidencia WHERE id_usuario = '$id_user'");
		if ($datos > 0) {
			$data[] = $datos;
			$legend[] = $id_user;
		} 
	} 
	if (isset($data))
		generic_pie_graph ($width, $height, $data, $legend);
	else 
		graphic_error();
}

// =====================================================================
// Draw a simple pie graph with reported workunits for a specific TASK
// =====================================================================

function graph_workunit_task ($width, $height, $id_task) {
	global $config;
	$data = array();
	$legend = array();
	
	$res = mysql_query("SELECT SUM(duration) as duration, id_user
		FROM tworkunit, tworkunit_task
		WHERE tworkunit_task.id_task = $id_task AND 
			tworkunit_task.id_workunit = tworkunit.id 
		GROUP BY id_user
		ORDER BY duration DESC");
	
	$data = NULL;
	
	while ($row = mysql_fetch_array($res)) {
		$data[$row[1]] = $row[0];
	}
	
	if ($data == NULL) {
		echo __("There is no data to show");
	}
	else {
		return pie3d_graph($config['flash_charts'], $data, $width, $height, __('others'), "", "", $config['font'], $config['fontsize']);
	}
}

// ===============================================================================
// Draw a simple pie graph with reported workunits for a specific PROJECT
// ===============================================================================

function graph_workunit_project ($width, $height, $id_project, $ttl=1) {
	global $config;
	$data = array();
	
	$res = mysql_query("SELECT SUM(duration), ttask.name
		FROM tworkunit, tworkunit_task, ttask, tproject  
		WHERE tproject.id = '$id_project' AND 
			tworkunit.id = tworkunit_task.id_workunit AND 
			tworkunit_task.id_task = ttask.id AND
			tproject.id = ttask.id_project 
		GROUP BY ttask.name
		ORDER BY SUM(duration) DESC
		LIMIT 7");

	$data = NULL;
	while ($row = mysql_fetch_array($res)) {
		$row[1] = substr(safe_output ($row[1]),0,22);
		$data[$row[1]] = $row[0];
	}
	
	if ($data == NULL) {
		return __("There is no data to show");
	}
	else {
		return pie3d_graph($config['flash_charts'], $data, $width, $height, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);
	}
}

// ===============================================================================
// Draw a simple pie graph with the number of task per user in a specified project
// ===============================================================================

function graph_project_task_per_user ($width, $height, $id_project) {
	global $config;
	
	//Get project users
	$sql = sprintf("SELECT id_user
		FROM trole_people_project
		WHERE id_project = %d", $id_project);
	
	$project_users = process_sql($sql);
	
	//Initialize the data for all users
	$data = array();
	
	foreach ($project_users as $pu) {
		$data[$pu['id_user']] = 0;
	}
	
	//Get number of task per user
	$sql = sprintf("SELECT id_user, COUNT(id_user) AS tasks
		FROM trole_people_task
		WHERE id_task IN 
			(SELECT id
			FROM ttask
			WHERE id_project = %d)
		GROUP BY id_user", $id_project);
	
	$task_per_user = process_sql($sql);
	if (empty($task_per_user))
		$task_per_user = array();
	
	foreach ($task_per_user as $tpu) {
		$id_user = $tpu['id_user'];
		$number_tasks = $tpu['tasks'];
		
		$data[$id_user] = $number_tasks;
	}
	
	if ($data == NULL) {
		return __("There is no data to show");
	}
	else {
		return pie3d_graph($config['flash_charts'], $data, $width, $height, __('others'), "", "", $config['font'], $config['fontsize']);
	}
}

// ===============================================================================
// Draw a simple pie graph with task status for a specific PROJECT
// ===============================================================================

function graph_workunit_project_task_status ($width, $height, $id_project) {
	global $config;
	
	$sql = sprintf("SELECT id, completion
		FROM ttask
		WHERE id_project = %d", $id_project);
	
	$res = process_sql($sql);
	if (empty($res))
		$res = array();
	
	$verified = 0;
	$completed = 0;
	$in_process = 0;
	$pending = 0;
	
	foreach ($res as $r) {
		if ($r['completion'] < 40) {
			$pending++;
		}
		else if ($r['completion'] < 90) {
			$in_process++;
		}
		else if ($r['completion'] < 100) {
			$completed++;
		}
		else if ($r['completion'] == 100) {
			$verified++;
		}
	}
	$data = array();
	
	$data[__("Verified")] = $verified;
	$data[__("Completed")] = $completed;
	$data[__("InProcess")] = $in_process;
	$data[__("Pending")]= $pending;
	
	if ($data == NULL) {
		return __("There is no data to show");
	}
	else {
		return pie3d_graph($config['flash_charts'], $data, $width, $height, __('others'), "", "", $config['font'], $config['fontsize']);
	}
}


// ===============================================================================
// Draw a simple pie graph with reported workunits for a specific PROJECT, showing
// time by each user.
// ===============================================================================

function graph_workunit_project_user_single ($width, $height, $id_project, $ttl=1) {
	global $config;
	$data = array();
	
	$res = mysql_query("SELECT SUM(duration), tworkunit.id_user 
					FROM tworkunit, tworkunit_task, ttask, tproject  
					WHERE tproject.id = $id_project AND 
					tworkunit.id = tworkunit_task.id_workunit AND 
					tworkunit_task.id_task = ttask.id AND
					tproject.id = ttask.id_project 
					GROUP BY tworkunit.id_user ORDER BY SUM(duration) DESC");
	$data = NULL;
				
	while ($row = mysql_fetch_array($res)) {
		$data[$row[1]] = $row[0];
	}
		
	if ($data == NULL) {
		return __("There is no data to show");
	} else {
		return pie3d_graph($config['flash_charts'], $data, $width, $height, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);
	}
}

// ===============================================================================
// Draw a simple pie graph with reported workunits for a specific USER, per TASK/PROJECT
// ===============================================================================

function graph_workunit_user ($width, $height, $id_user, $date_from, $date_to = 0, $ttl = 1) {
	global $config;
	
	if ($date_to == 0) {
		$date_to = date("Y-m-d", strtotime("$date_from + 30 days"));
	}
	
	$res = mysql_query("SELECT SUM(duration), id_task, timestamp, ttask.name, tproject.name 
					FROM tworkunit, tworkunit_task, ttask, tproject  
					WHERE tworkunit.id_user = '$id_user' AND 
					tworkunit.id = tworkunit_task.id_workunit AND 
					tworkunit.timestamp > '$date_from' AND 
					tworkunit.timestamp < '$date_to' AND
					tworkunit_task.id_task = ttask.id AND
					tproject.id = ttask.id_project 
					GROUP BY id_task ORDER BY SUM(duration) DESC");
	$data = NULL;
	
	while ($row = mysql_fetch_array($res)) {
		$data[substr(clean_flash_string ($row[3]),0,40)]['graph'] = $row[0];
	}
	
	if ($data == NULL) {
		return __("There is no data to show");
	} else {
		$colors['graph']['fine'] = true;
		return hbar_graph($config['flash_charts'], $data, $width, $height, $colors, array(), "", "", true, "", "", $config['font'], $config['fontsize'], true, $ttl);
	}
}

// ===============================================================================
// Draw a simple pie graph with SLA fulfillment of the incident
// ===============================================================================

function graph_incident_statistics_sla_compliance($incidents, $width=200, $height=200, $ttl=1) {
	global $config;
	
	if ($incidents == false) {
		$incidents = array();
	}
	
	$incident_array = array();
	
	foreach ($incidents as $incident) {
		array_push($incident_array, $incident['id_incidencia']);
	}
		
	$incident_clause = implode(",", $incident_array);
	
	$incident_clause = "(".$incident_clause.")";
			
	$sql_ok = sprintf("SELECT COUNT(id_incident) FROM tincident_sla_graph WHERE value = 1 AND id_incident IN %s", $incident_clause);
	$sql_fail = sprintf("SELECT COUNT(id_incident) FROM tincident_sla_graph WHERE value = 0 AND id_incident IN %s", $incident_clause);
		
	$num_ok = process_sql($sql_ok);
	$num_fail = process_sql($sql_fail);

	$num_ok = $num_ok[0][0];
	$num_fail = $num_fail[0][0];
	$total = $num_ok + $num_fail;
		
	$data = array();
	
	if ($total == 0) {
		$data["FAIL"] = 0;
		$data["OK"] = 100;
	} else {
		$percent_ok = ($num_ok/$total)*100;
		$percent_fail = ($num_fail/$total)*100;
		
		$data["FAIL"] = $percent_fail;
		$data["OK"] = $percent_ok;
	}
	
	if (isset($data))
		return pie3d_graph ($config['flash_charts'], $data, $width, $height, "", "", "", $config['font'], $config['fontsize'], $ttl);
	else 
		graphic_error();
}

// ===============================================================================
// Draw a simple pie graph with incident ditribution by priority
// ===============================================================================

function graph_incident_priority($incidents, $width=300, $height=150, $ttl=1) {
	global $config;
	
	$incident_data = array();
	$colors = array();
	foreach ($incidents as $incident) {
		if (!isset( $incident_data[render_priority($incident["prioridad"])]))
			 $incident_data[render_priority($incident["prioridad"])] = 0;
			 
			 
		$colors[render_priority($incident["prioridad"])] = incidents_get_priority_color($incident);

		$incident_data[render_priority($incident["prioridad"])] = $incident_data[render_priority($incident["prioridad"])] + 1; 
	}
	
	if (isset($incident_data))
		return pie3d_graph ($config['flash_charts'], $incident_data, $width, $height, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);
	else 
		graphic_error();
	
	
}

// ===============================================================================
// Draw a simple pie graph with SLA fulfillment of the incident
// ===============================================================================

function graph_incident_sla_compliance($incident, $width=200, $height=200, $ttl=1) {
	global $config;
		
	$sql_ok = sprintf("SELECT COUNT(id_incident) FROM tincident_sla_graph WHERE value = 1 AND id_incident = %d", $incident);
	$sql_fail = sprintf("SELECT COUNT(id_incident) FROM tincident_sla_graph WHERE value = 0 AND id_incident = %d", $incident);
	
	$num_ok = process_sql($sql_ok);
	$num_fail = process_sql($sql_fail);

	$num_ok = $num_ok[0][0];
	$num_fail = $num_fail[0][0];
	$total = $num_ok + $num_fail;
	
	if ($total == 0) {
		$percent_ok = 100;
		$percent_fail = 0;
	
	} else {
	
		$percent_ok = ($num_ok/$total)*100;
		$percent_fail = ($num_fail/$total)*100;
	}
	
	$data = array();
	
	$data["FAIL"] = $percent_fail;
	$data["OK"] = $percent_ok;
	
	if (isset($data))
		return pie3d_graph ($config['flash_charts'], $data, $width, $height, "", "", "", $config['font'], $config['fontsize'], $ttl);
	else 
		graphic_error();
}

// ===============================================================================
// Draws a SLA slice graph for an incident
// ===============================================================================

function graph_sla_slicebar ($incident, $period, $width, $height, $ttl=1) {
	global $config;
	
	//Get time and calculate start date based on period
	$now = time();
	//This array sets the color of sla graph
	$colors = 	array(0 => '#FF0000', 1 => '#38B800');
	$start_period = $now - $period;
	
	//Get all sla graph data
	$sql = sprintf ("SELECT value as data, utimestamp FROM tincident_sla_graph 
				WHERE id_incident = %d AND utimestamp > %d ORDER BY utimestamp ASC", 
				$incident, $start_period);

	$aux_data = get_db_all_rows_sql($sql);
	
	//Check if we have data for this interval
	if ($aux_data == false) {
		//There is no data print a fake graph because there is no data
		//We asume the SLA compliance was OK
		
		$data [0]= array("data" => 1, "utimestamp" => $now);
		$data [1]= array("data" => 1, "utimestamp" => $now-$period);
		
		return slicesbar_graph($data, $period, $width, $height, $colors, $config['font'],
			false,'',$ttl);
	}
	
	//Get max timestamp from sla graph
	$sql2 = sprintf("SELECT MAX(utimestamp) FROM tincident_sla_graph 
					WHERE id_incident = %d", $incident);
		
	$max_utimestamp = get_db_sql($sql2);
	
	//Set previous value and time to create sla data array ranges
	$previous_value = $aux_data[0]["data"];
	$previous_time = $aux_data[0]["utimestamp"];	
	
	//Compare period set by user with max period of data stored
	$time_diff = ($max_utimestamp - $previous_time);

	//If period of data stored is lower than the period set by user
	//the period is stablished by the maximun period of data stored
	if ($period > $time_diff) {
		$period = $time_diff;
	}
	
	$data = array();
	
	foreach ($aux_data as $aux) {
	
		//If sla value changes we must calculate a range
		if ($previous_value != $aux["data"]) {

			$range = $aux["utimestamp"] - $previous_time;
			
			array_push($data, array("data" => $previous_value, "utimestamp" => $range));
			
			$previous_value = $aux["data"];
			$previous_time = $aux["utimestamp"];
		}
	}

	//We must add the last range for sla
	$last_value = $aux["data"];
	$last_time = $aux["utimestamp"];
	
	$range = $last_time - $previous_time;
	
	array_push($data, array("data" => $previous_value, "utimestamp" => $range));
			
	//Draw the graph
	return slicesbar_graph($data, $period, $width, $height, $colors, $config['font'],
		false,'',$ttl);
}

function graph_incident_user_activity ($incident, $width=200, $height=200, $ttl=1) {
	global $config;

	$sql = sprintf("SELECT count(WU.id_user) as WU, id_user as user from tworkunit WU, tworkunit_incident WUI 
					WHERE WUI.id_incident = %d AND WUI.id_workunit = WU.id group by id_user", $incident);

	$res = process_sql($sql);
	
	$data = array();
	
	foreach ($res as $r) {
		$user = $r["user"];
		$wu = $r["WU"];
		
		$data[$user] = $wu;
	}
		
	if (isset($data))
		return pie3d_graph ($config['flash_charts'], $data, $width, $height, "", "", "", $config['font'], $config['fontsize'], $ttl);
	else 
		graphic_error();
}

// ===============================================================================
// Draw a simple pie graph with reported workunits for a specific USER, per TASK/PROJECT
// ===============================================================================

function graph_workunit_project_user ($width, $height, $id_user, $date_from, $date_to = 0, $return = false) {
	global $config;

	$data= array();
	$legend = array();

	if ($date_to == 0) {
		$date_to = date("Y-m-d", strtotime("$date_from + 30 days"));
	}

	$res = mysql_query("SELECT SUM(duration), tproject.name 
					FROM tworkunit, tworkunit_task, ttask, tproject  
					WHERE tworkunit.id_user = '$id_user' AND 
					tworkunit.id = tworkunit_task.id_workunit AND 
					tworkunit.timestamp >= '$date_from' AND 
					tworkunit.timestamp <= '$date_to' AND
					tworkunit_task.id_task = ttask.id AND
					tproject.id = ttask.id_project 
					GROUP BY tproject.name ORDER BY SUM(duration) DESC");
	$data = NULL;
	
	while ($row = mysql_fetch_array($res)) {
		$data[clean_flash_string ($row[1])]['graph'] = $row[0];
	}
	
	if ($data == NULL) {
		$out = __("There is no data to show");
	} else {
		$colors['graph']['fine'] = true;
		
		$out = hbar_graph($config['flash_charts'], $data, $width, $height, $colors, array(), "", "", true, "", "", $config['font'], $config['fontsize']);
	}
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}

// ===============================================================================
// ===============================================================================
// ===============================================================================


function graphic_error ($flow = true) {
	global $config;
	if($flow) {
		Header('Content-type: image/png');
		$imgPng = imageCreateFromPng($config["homedir"].'/images/error.png');
		imageAlphaBlending($imgPng, true);
		imageSaveAlpha($imgPng, true);
		imagePng($imgPng);
	}
	else {
		return print_image('images/error.png', true);
	}
}

// ***************************************************************************
// Draw a dynamic progress bar using GDlib directly
// ***************************************************************************

function progress_bar ($progress, $width, $height, $ttl=1) {
	global $config;
	
	$out_of_lim_str = __("Out of limits");
	$title = "";

	return progressbar($progress, $width, $height, $title, $config['font'], 1, $out_of_lim_str, false, $ttl);
}

function project_activity_graph ($id_project, $width = 650, $height = 150, $area = false, $ttl = 1, $resolution = 10, $return = false) {
	global $config;

	$output = "";
    $incident = get_db_row ("tproject", "id", $id_project);
	
    $start_unixdate = strtotime ($incident["start"]);
    $end_unixdate = strtotime ("now");
    $period = $end_unixdate - $start_unixdate;
    
	$interval = (int) ($period / $resolution);
	
	if (! $area)  {
		$output .= "<div style='width: 800px; text-align: center;'>";
		$output .= "<span style='margin-right: 650px;'>";
		$output .= __("Each bar is"). " ". human_time_description_raw($interval);
		$output .= "</span>";
	}
	
	$data = get_db_all_rows_sql ("SELECT tworkunit.duration as duration, 
            tworkunit.timestamp as timestamp  FROM tworkunit, tworkunit_task, ttask 
			WHERE tworkunit_task.id_task = ttask.id
			AND ttask.id_project = $id_project
			AND tworkunit_task.id_workunit = tworkunit.id
			ORDER BY timestamp ASC");

	if ($data === false) {
		$data = array ();
	}

   	$min_necessary = 1;

	// Check available data
	if (count ($data) < $min_necessary) {
		return;
	}

	// Set initial conditions
	$chart = array();
	$names = array();
	$chart2 = array();

	// Calculate chart data
	for ($i = 0; $i < $resolution; $i++) {
		$timestamp = $start_unixdate + ($interval * $i);
		$total = 0;
		$j = 0;

		while (isset ($data[$j])){
            $dftime = strtotime($data[$j]['timestamp']);

			if ($dftime >= $timestamp && $dftime < ($timestamp + $interval)) {
				$total += ($data[$j]['duration']);
			}
			$j++;
		} 

    	$time_format = "M d";
        $timestamp_human = clean_flash_string (date($time_format, $timestamp));
		$chart2[$timestamp_human]['graph'] = $total;
   	}
   	
   	$colors['graph']['color'] = "#2179B1";
   	$colors['graph']['border'] = "#000";
   	$colors['graph']['alpha'] = 100;
	
	if ($area) {
		$output .= area_graph($config['flash_charts'], $chart2, $width, $height, $colors, array(), '', '', '', __('Hours'), '', '', $config['font'], $config['fontsize'], '', $ttl);
	} else {
		$output .= vbar_graph ($config['flash_charts'], $chart2, $width, $height, $colors, array(), "", "", "", "", $config['font'], $config['fontsize'],true, $ttl);
		$output .= "</div>";
	}
	
	if ($return) {
		return $output;
	} else {
		echo $output;
	}
}

function incident_activity_graph ($id_incident){
	global $config;

    $incident = get_db_row ("tincidencia", "id_incidencia", $id_incident);

    $start_unixdate = strtotime ($incident["inicio"]);
    $end_unixdate = strtotime ("now");
    $period = $end_unixdate - $start_unixdate;
    $resolution = 10;
    
	$interval = (int) ($period / $resolution);

	echo __("Each bar is"). " ". human_time_description_raw($interval);

	$data = get_db_all_rows_sql ("SELECT * FROM tincident_track WHERE id_incident = $id_incident ORDER BY timestamp ASC");

	if ($data === false) {
		$data = array ();
	}

   	$min_necessary = 1;

	// Check available data
	if (count ($data) < $min_necessary) {
		return;
	}

	// Set initial conditions
	$chart = array();
	$names = array();
	$chart2 = array();

	// Calculate chart data
	for ($i = 0; $i < $resolution; $i++) {
		$timestamp = $start_unixdate + ($interval * $i);
		$total = 0;
		$j = 0;

		while (isset ($data[$j])){
            $dftime = strtotime($data[$j]['timestamp']);

			if ($dftime >= $timestamp && $dftime < ($timestamp + $interval)) {
				$total++;
			}
			$j++;
		} 

    	$time_format = "y-m-d H:i";
        $timestamp_human = clean_flash_string (date($time_format, $timestamp));
		$chart2[$timestamp_human] = $total;
   	}

	echo vbar_graph ($config['flash_charts'], $chart2, 650, 300, array(), "", "", "", "", "", $config['font'], $config['fontsize']);
}

function task_activity_graph ($id_task, $width = 900, $height = 230, $area = false, $return = false){
	global $config;

    $task = get_db_row ("ttask", "id", $id_task);
	
	$output = "";
	
    $start_unixdate = strtotime ($task["start"]);
    $end_unixdate = strtotime ("now");
    $period = $end_unixdate - $start_unixdate;
    $resolution = 50;
    
	$interval = (int) ($period / $resolution);
	
	if (! $area) {
		$output .= __("Each bar is"). " ". human_time_description_raw($interval);
		$output .= "<br>";
	}

	$data = get_db_all_rows_sql ("SELECT tworkunit.duration as duration, 
            tworkunit.timestamp as timestamp  FROM tworkunit, tworkunit_task, ttask 
			WHERE tworkunit_task.id_task = $id_task
			AND tworkunit_task.id_workunit = tworkunit.id GROUP BY tworkunit.id  ORDER BY timestamp ASC");


	if ($data === false) {
		$data = array ();
	}

   	$min_necessary = 1;

	// Check available data
	if (count ($data) < $min_necessary) {
		return;
	}

	// Set initial conditions
	$chart = array();
	$names = array();
	$chart2 = array();

	// Calculate chart data
	for ($i = 0; $i < $resolution; $i++) {
		$timestamp = $start_unixdate + ($interval * $i);
		$total = 0;
		$j = 0;

		while (isset ($data[$j])){
            $dftime = strtotime($data[$j]['timestamp']);

			if ($dftime >= $timestamp && $dftime < ($timestamp + $interval)) {
				$total += ($data[$j]['duration']);
			}
			$j++;
		} 

    	$time_format = "M d";
        $timestamp_human = clean_flash_string (date($time_format, $timestamp));
		$chart2[$timestamp_human] = $total;
   	}
   	
   	$colors['1day']['color'] = "#2179B1";
   	$colors['1day']['border'] = "#000";
   	$colors['1day']['alpha'] = 100;

	foreach($chart2 as $key => $ch) { 
		$chart3[$key]['1day'] = $ch;
	}
	
	$legend = array();
		
	$xaxisname = __('Days');
	$yaxisname = __('Hours');
	
	if ($area) {
		$output .= area_graph($config['flash_charts'], $chart3, $width, $height, $colors, $legend, '', '', '', $yaxisname, '', '', $config['font'], $config['fontsize']);
	} else {
		$output .= vbar_graph ($config['flash_charts'], $chart3, $width, $height, $colors, $legend, $xaxisname, $yaxisname, "", "", $config['font'], $config['fontsize']);
	}
	
	if ($return) {
		return $output;
	} else {
		echo $output;
	}
}


function histogram_2values($valuea, $valueb, $labela = "a", $labelb = "b", $mode = 1, $width = 200, $height = 30, $title = "", $ttl=2) {
	global $config;
	
	$data = array();
	$data[$labela] = $valuea;
	$data[$labelb] = $valueb;		

	$data_json = json_encode($data);
	
	$max = max($valuea, $valueb);

	return histogram($data_json, $width, $height, $config['font'], $max, $title, $mode, $ttl);
}

function project_tree ($id_project, $id_user) {
	include ("../include/config.php");
	$config["id_user"] = $id_user;
	if (user_belong_project ($id_user, $id_project)==0) {
		audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to task manager of unauthorized project");
		include ($config["homedir"]."/general/noaccess.php");
		exit;
	}

	if ($id_project != -1)
		$project_name = get_db_value ("name", "tproject", "id", $id_project);
	else
		$project_name = "";

	$dotfilename = $config["homedir"]. "/attachment/tmp/$id_user.dot";
	$pngfilename = $config["homedir"]. "/attachment/tmp/$id_user.project.png";
	$dotfile = fopen ($dotfilename, "w");

	$total_task = 0;
	$sql2="SELECT * FROM ttask WHERE id_project = $id_project"; 
	if ($result2=mysql_query($sql2))	
	while ($row2=mysql_fetch_array($result2)) {
		if ((user_belong_task ($id_user, $row2["id"]) == 1)) {
			$task[$total_task] = $row2["id"];
			$task_name[$total_task] = $row2["name"];
			$task_parent[$total_task] = $row2["id_parent_task"];
			$task_workunit[$total_task] = get_task_workunit_hours ($row2["id"]);
			$total_task++;
		}
	}
	
	
	fwrite ($dotfile, "digraph Integria {\n");
	fwrite ($dotfile, "	  ranksep=2.0;\n");
	fwrite ($dotfile, "	  ratio=auto;\n");
	fwrite ($dotfile, "	  size=\"9,12\";\n");
	fwrite ($dotfile, "	  node[fontsize=".$config['fontsize']."];\n");
	fwrite ($dotfile, '	  project [label="'. wordwrap($project_name,12,'\\n').'",shape="ellipse", style="filled", color="grey"];'."\n");
	for ($ax=0; $ax < $total_task; $ax++) {
		fwrite ($dotfile, 'TASK'.$task[$ax].' [label="'.wordwrap($task_name[$ax],12,'\\n').'"];');
		fwrite ($dotfile, "\n");
	}
	
	// Make project first parent task relation visible
	for ($ax=0; $ax < $total_task; $ax++) {
		if ($task_parent[$ax] == 0) {
			fwrite ($dotfile, 'project -> TASK'.$task[$ax].';');
			fwrite ($dotfile, "\n");
		}
	}
	// Make task-subtask parent task relation visible
	for ($ax=0; $ax < $total_task; $ax++) {
		if ($task_parent[$ax] != 0) {
			fwrite ($dotfile, 'TASK'.$task_parent[$ax].' -> TASK'.$task[$ax].';');
			fwrite ($dotfile, "\n");
		}
	}
	
	fwrite ($dotfile,"}");
	fwrite ($dotfile, "\n");
	
	// exec ("twopi -Tpng $dotfilename -o $pngfilename");
	exec ("twopi -Tpng $dotfilename -o $pngfilename");
	Header('Content-type: image/png');
	$imgPng = imageCreateFromPng($pngfilename);
	imageAlphaBlending($imgPng, true);
	imageSaveAlpha($imgPng, true);
	imagePng($imgPng);
	//unlink ($pngfilename);
	//unlink ($dotfilename);
}

function all_project_tree ($id_user, $completion, $project_kind) {
	include ("../include/config.php");
	$config["id_user"] = $id_user;

	$dotfilename = $config["homedir"]. "/attachment/tmp/$id_user.all.dot";
	$pngfilename = $config["homedir"]. "/attachment/tmp/$id_user.projectall.png";
	$mapfilename = $config["homedir"]. "/attachment/tmp/$id_user.projectall.map";
	$dotfile = fopen ($dotfilename, "w");


	fwrite ($dotfile, "digraph Integria {\n");
	fwrite ($dotfile, "	  ranksep=1.8;\n");
	fwrite ($dotfile, "	  ratio=auto;\n");
	fwrite ($dotfile, "	  size=\"9,9\";\n");
	fwrite ($dotfile, 'URL="'.$config["base_url"].'/index.php?sec=projects&sec2=operation/projects/project_tree";'."\n");

	fwrite ($dotfile, "	  node[fontsize=".$config['fontsize']."];\n");
	fwrite ($dotfile, "	  me [label=\"$id_user\", style=\"filled\", color=\"yellow\"]; \n");

	$total_project = 0;
	$total_task = 0;
	if ($project_kind == "all")
		$sql1="SELECT * FROM tproject WHERE disabled = 0"; 
	else
		$sql1="SELECT * FROM tproject WHERE disabled = 0 AND end != '0000-00-00 00:00:00'"; 
	if ($result1=mysql_query($sql1))	
	while ($row1=mysql_fetch_array($result1)) {
		if ((user_belong_project ($id_user, $row1["id"],1 ) == 1)) {
			$project[$total_project] = $row1["id"];
			$project_name[$total_project] = $row1["name"];
			if ($completion < 0)
				$sql2="SELECT * FROM ttask WHERE id_project = ".$row1["id"]; 
			elseif ($completion < 101)
				$sql2="SELECT * FROM ttask WHERE completion < $completion AND id_project = ".$row1["id"]; 
			else
				$sql2="SELECT * FROM ttask WHERE completion = 100 AND id_project = ".$row1["id"]; 
			if ($result2=mysql_query($sql2))
			while ($row2=mysql_fetch_array($result2)) {
				if ((user_belong_task ($id_user, $row2["id"],1) == 1)) {
					$task[$total_task] = $row2["id"];
					$task_name[$total_task] = $row2["name"];
					$task_parent[$total_task] = $row2["id_parent_task"];
					$task_project[$total_task] = $project[$total_project];
					$task_workunit[$total_task] = get_task_workunit_hours ($row2["id"]);
					$task_completion[$total_task] = $row2["completion"];
					$total_task++;
				}
			}
			$total_project++;
		}
	}
	// Add project items
	for ($ax=0; $ax < $total_project; $ax++) {
		fwrite ($dotfile, 'PROY'.$project[$ax].' [label="'.wordwrap($project_name[$ax],12,'\\n').'", style="filled", color="grey", URL="'.$config["base_url"].'/index.php?sec=projects&sec2=operation/projects/task&id_project='.$project[$ax].'"];');
		fwrite ($dotfile, "\n");
	}
	// Add task items
	for ($ax=0; $ax < $total_task; $ax++) {

		$temp = 'TASK'.$task[$ax].' [label="'.wordwrap($task_name[$ax],12,'\\n').'"';
		if ($task_completion[$ax] < 10)
			$temp .= 'color="red"';
		elseif ($task_completion[$ax] < 100)
			$temp .= 'color="yellow"';
		elseif ($task_completion[$ax] == 100)
			$temp .= 'color="green"';
		$temp .= "URL=\"".$config["base_url"]."/index.php?sec=projects&sec2=operation/projects/task_detail&id_project=".$task_project[$ax]."&id_task=".$task[$ax]."&operation=view\"";
		$temp .= "];";
		fwrite ($dotfile, $temp);


	
		fwrite ($dotfile, "\n");
	}

	// Make project attach to user "me"
	for ($ax=0; $ax < $total_project; $ax++) {
		fwrite ($dotfile, 'me -> PROY'.$project[$ax].';');
		fwrite ($dotfile, "\n");
		
	}

	// Make project first parent task relation visible
	for ($ax=0; $ax < $total_task; $ax++) {
		if ($task_parent[$ax] == 0) {
			fwrite ($dotfile, 'PROY'.$task_project[$ax].' -> TASK'.$task[$ax].';');
			fwrite ($dotfile, "\n");
		}
	}

	
	// Make task-subtask parent task relation visible
	for ($ax=0; $ax < $total_task; $ax++) {
		if ($task_parent[$ax] != 0) {
			fwrite ($dotfile, 'TASK'.$task_parent[$ax].' -> TASK'.$task[$ax].';');
			fwrite ($dotfile, "\n");
		}
	}
	
	fwrite ($dotfile,"}");
	fwrite ($dotfile, "\n");
	// exec ("twopi -Tpng $dotfilename -o $pngfilename");

	exec ("twopi -Tcmapx -o$mapfilename -Tpng -o$pngfilename $dotfilename");

	Header('Content-type: image/png');
	$imgPng = imageCreateFromPng($pngfilename);
	imageAlphaBlending($imgPng, true);
	imageSaveAlpha($imgPng, true);
	imagePng($imgPng);
	require ($mapfilename);
	//unlink ($pngfilename);
	unlink ($dotfilename);
}

// ===============================================================================
// Draw a simple pie graph with the number of workorders, by owner
// ===============================================================================

function graph_workorder_num ($width, $height, $type = "owner", $where_clause = "WHERE 1=1", $max = 5, $ttl=1) {
	global $config;
	$data = array();
	
	if ($type == "submitter")
		$sql = "SELECT COUNT(id), created_by_user FROM ttodo $where_clause GROUP BY created_by_user ORDER BY 1 DESC, 2";
	else
		$sql = "SELECT COUNT(id), assigned_user FROM ttodo $where_clause GROUP BY assigned_user ORDER BY 1 DESC, 2";
	
	$wos = get_db_all_rows_sql ($sql);
	$data = NULL;
	$count = 0;
	if ($wos !== false) {
		foreach ($wos as $wo) {
			if ($type == "submitter") {
				if ($count < $max) {
					$data[$wo['created_by_user']] = $wo['COUNT(id)'];
				} else {
					$data[__('Others')] += $wo['COUNT(id)'];
				}
			} else {
				if ($count < $max) {
					$data[$wo['assigned_user']] = $wo['COUNT(id)'];
				} else {
					$data[__('Others')] += $wo['COUNT(id)'];
				}
			}
			$count++;
		}
	}
	
	if ($data == NULL) {
		return __("There is no data to show");
	} else {
		return pie3d_graph($config['flash_charts'], $data, $width, $height, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);
	}
}

// ****************************************************************************
//   MAIN Code
//   parse get parameters
// ****************************************************************************

if (isset($_GET["id_audit"]))
	$id_audit = $_GET["id_audit"];
else
	$id_audit = 0;
if (isset($_GET["id_group"]))
	$id_group = $_GET["id_group"];
else
	$id_group = 0;
if (isset($_GET["period"]))
	$period = $_GET["period"];
else
	$period = 129600; // Month
if (isset($_GET["width"]))
	$width= $_GET["width"];
else 
	$width= 280;
if (isset($_GET["height"]))
	$height= $_GET["height"];
else
	$height= 50;

$id_user = get_parameter ("id_user", "");
$id_project = get_parameter ("id_project",0);
$graphtype = get_parameter ("graphtype",0);
$completion = get_parameter ("completion",0);
$project_kind = get_parameter ("project_kind","");
$id_task = get_parameter ("id_task",0);
$max = get_parameter ("max" , 0);
$min = get_parameter ("min" , 0);
$labela = get_parameter("labela" , "");
$labelb = get_parameter ("labelb" , "");
$valuea = get_parameter ("a" , 0);
$valueb = get_parameter ("b" , 0);
$valuec = get_parameter ("c" , 0);
$lite = get_parameter ("lite" , 0);
$date_from = get_parameter ( "date_from", 0);
$date_to   = get_parameter ( "date_to", 0);
$mode = get_parameter ( "mode", 1);
$percent = get_parameter ( "percent", 0);
$days = get_parameter ( "days", 0);
$type= get_parameter ("type", "");
$background = get_parameter ("background", "#ffffff");
$id_incident = get_parameter("id_incident");
$period = get_parameter("period");
$ajax = get_parameter("is_ajax");


if ($type == "incident_a")
	incident_peruser ($width, $height);
elseif ($type == "workunit_task")
	graph_workunit_task($width, $height, $id_task);
elseif ($type == "workunit_user")
	graph_workunit_user ($width, $height, $id_user, $date_from);
elseif ($type == "workunit_project_user")
	graph_workunit_project_user ($width, $height, $id_user, $date_from, $date_to);
elseif ($type == "project_tree")
	project_tree ($id_project, $id_user);
elseif ($type == "all_project_tree")
	all_project_tree ($id_user, $completion, $project_kind);
elseif ($type == "sla_slicebar")
	if ($ajax) {
		echo graph_sla_slicebar ($id_incident, $period, $width, $height);
	} else {
		graph_sla_slicebar ($id_incident, $period, $width, $height);
	}

// Always at the end of the funtions_graph
include_flash_chart_script();
?>
