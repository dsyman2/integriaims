<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2011 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include_once('functions_fsgraph.php');

function vbar_graph($flash_chart, $chart_data, $width, $height) {
	if($flash_chart) {
		echo fs_2d_column_chart ($chart_data, $width, $height);
	}
	else {
		echo "<img src='include/graphs/functions_pchart.php?graph_type=vbar&data=".json_encode($chart_data)."&width=".$width."&height=".$height."'>";
	}
}

function threshold_graph($flash_chart, $chart_data, $width, $height) {
	if($flash_chart) {
		echo fs_2d_column_chart ($chart_data, $width, $height);
	}
	else {
		echo "<img src='include/graphs/functions_pchart.php?graph_type=threshold&data=".json_encode($chart_data)."&width=".$width."&height=".$height."'>";
	}
}

function area_graph($flash_chart, $chart_data, $width, $height) {
	if($flash_chart) {
		echo "";
	}
	else {
		echo "<img src='include/graphs/functions_pchart.php?graph_type=area&data=".json_encode($chart_data)."&width=".$width."&height=".$height."'>";
	}	
}

function hbar_graph($flash_chart, $chart_data, $width, $height) {
	if($flash_chart) {
		echo fs_hbar_chart (array_values($chart_data), array_keys($chart_data), $width, $height);
	}
	else {
		echo "<img src='include/graphs/functions_pchart.php?graph_type=hbar&data=".json_encode($chart_data)."&width=".$width."&height=".$height."'>";
	}
}

function pie3d_graph($flash_chart, $chart_data, $width, $height, $others_str = "other") {
	return pie_graph('3d', $flash_chart, $chart_data, $width, $height, $others_str);
}

function pie2d_graph($flash_chart, $chart_data, $width, $height, $others_str = "other") {
	return pie_graph('2d', $flash_chart, $chart_data, $width, $height, $others_str);
}

function pie_graph($graph_type, $flash_chart, $chart_data, $width, $height, $others_str) {
	// This library allows only 9 colors
	$max_values = 9;

	if(count($chart_data) > $max_values) {
		$chart_data_trunc = array();
		$n = 1;
		foreach($chart_data as $key => $value) {
			if($n < $max_values) {
				$chart_data_trunc[$key] = $value;
			}
			else {
				$chart_data_trunc[$others_str] += $value;
			}
			$n++;
		}
		$chart_data = $chart_data_trunc;
	}
	
	switch($graph_type) {
		case "2d":
				if($flash_chart) {
					return fs_2d_pie_chart (array_values($chart_data), array_keys($chart_data), $width, $height);
				}
				else {
					return "<img src='include/graphs/functions_pchart.php?graph_type=pie2d&data=".json_encode($chart_data)."&width=".$width."&height=".$height."&other_str=".$other_str."'>";
				}
				break;
		case "3d":
				if($flash_chart) {
					return fs_3d_pie_chart (array_values($chart_data), array_keys($chart_data), $width, $height);
				}
				else {
					return "<img src='include/graphs/functions_pchart.php?graph_type=pie3d&data=".json_encode($chart_data)."&width=".$width."&height=".$height."&other_str=".$other_str."'>";
				}
				break;
	}
}

function gantt_graph($project_name, $from, $to, $tasks, $milestones, $width, $height) {
	return fs_gantt_chart ($project_name, $from, $to, $tasks, $milestones, $width, $height);
}
?>