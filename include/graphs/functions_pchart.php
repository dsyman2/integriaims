<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include_once('functions_utils.php');
include_once('../functions.php');
include_once('../functions_html.php');

/* pChart library inclusions */
include_once("pChart/pData.class.php");
include_once("pChart/pDraw.class.php");
include_once("pChart/pImage.class.php");
include_once("pChart/pPie.class.php");
include_once("pChart/pScatter.class.php");
include_once("pChart/pRadar.class.php");

// Define default fine colors

$default_fine_colors = array();
$default_fine_colors[] = "#2222FF";
$default_fine_colors[] = "#00DD00";
$default_fine_colors[] = "#CC0033";
$default_fine_colors[] = "#9900CC";
$default_fine_colors[] = "#FFCC66";
$default_fine_colors[] = "#999999";

$graph_type = get_parameter('graph_type', '');

$id_graph = get_parameter('id_graph', false);

if (!$id_graph) {
	exit;
}

$graph = unserialize_in_temp($id_graph);

if (!isset($graph)) {
	exit;
}

$data = $graph['data'];
$width = $graph['width'];
$height = $graph['height'];
$colors = null;
if (isset($graph['color']))
	$colors = $graph['color'];
$legend = null;
if (isset($graph['legend']))
	$legend = $graph['legend'];
$xaxisname = '';
if(isset($graph['xaxisname'])) { 
	$xaxisname = $graph['xaxisname'];
}
$yaxisname = '';
if(isset($graph['yaxisname'])) { 
	$yaxisname = $graph['yaxisname'];
}

/*
$colors = array();
$colors['pep1'] = array('border' => '#000000', 'color' => '#000000', 'alpha' => 50);
$colors['pep2'] = array('border' => '#ff7f00', 'color' => '#ff0000', 'alpha' => 50);
$colors['pep3'] = array('border' => '#ff0000', 'color' => '#00ff00', 'alpha' => 50);
$colors['pep4'] = array('border' => '#000000', 'color' => '#0000ff', 'alpha' => 50);
*/

$pixels_between_xdata = 40;
$max_xdata_display = round($width / $pixels_between_xdata);
$ndata = count($data);
if($max_xdata_display > $ndata) {
	$xdata_display = $ndata;
}
else {
	$xdata_display = $max_xdata_display;
}

$step = round($ndata/$xdata_display);
$c = 0;

switch($graph_type) {
	case 'hbar':
	case 'vbar':
			foreach($data as $i => $values) {				
				foreach($values as $name => $val) {
					$data_values[$name][] = $val;
				}				
				
				if (($c % $step) == 0) {
					$data_keys[] = $i;
				}
				else {
					$data_keys[] = "";
				}
				
				$c++;
			}
			$fine_colors = array();

			// If is set fine colors we store it or set default
			if(isset($colors[reset(array_keys($data_values))]['fine'])) {
				$fine = $colors[reset(array_keys($data_values))]['fine'];
				if($fine === true) {
					$fine = $default_fine_colors;
				}
				
				foreach($fine as $i => $fine_color) {
					$rgb_fine = html2rgb($fine_color);
					$fine_colors[$i]['R'] = $rgb_fine[0];
					$fine_colors[$i]['G'] = $rgb_fine[1];
					$fine_colors[$i]['B'] = $rgb_fine[2];
					$fine_colors[$i]['Alpha'] = 100;
				}
			}
			
			break;
	case 'progress':
	case 'area':
	case 'stacked_area':
	case 'stacked_line':
	case 'line':
	case 'threshold':
	case 'scatter':
			foreach($data as $i => $d) {
				$data_values[] = $d;
				
				
				if (($c % $step) == 0) {
					$data_keys[] = $i;
				}
				else {
					$data_keys[] = "";
				}
				
				$c++;
			}
			
			break;
	case 'polar':
	case 'radar':
	case 'pie3d':
	case 'pie2d':
			break;
}

if (($graph_type != 'pie3d') && ($graph_type != 'pie2d')) {
	if(!is_array(reset($data_values))) {
		$data_values = array($data_values);
		if(is_array($colors) && !empty($colors)) {
			$colors = array($colors);
		}
	}
}

$rgb_color = array();

if (!isset($colors))
	$colors = array();

foreach($colors as $i => $color) {		
	$rgb['border'] = html2rgb($color['border']);
	$rgb_color[$i]['border']['R'] = $rgb['border'][0];
	$rgb_color[$i]['border']['G'] = $rgb['border'][1];
	$rgb_color[$i]['border']['B'] = $rgb['border'][2];
	
	$rgb['color'] = html2rgb($color['color']);
	$rgb_color[$i]['color']['R'] = $rgb['color'][0];
	$rgb_color[$i]['color']['G'] = $rgb['color'][1];
	$rgb_color[$i]['color']['B'] = $rgb['color'][2];
	
	$rgb_color[$i]['alpha'] = $color['alpha'];
}

/*foreach($colors as $i => $color) {
	if (isset($color['border'])) {
		$rgb['border'] = html2rgb($color['border']);
		$rgb_color[$i]['border']['R'] = $rgb['border'][0];
		$rgb_color[$i]['border']['G'] = $rgb['border'][1];
		$rgb_color[$i]['border']['B'] = $rgb['border'][2];
	}
	
	if (isset($color['color'])) {
		$rgb['color'] = html2rgb($color['color']);
		$rgb_color[$i]['color']['R'] = $rgb['color'][0];
		$rgb_color[$i]['color']['G'] = $rgb['color'][1];
		$rgb_color[$i]['color']['B'] = $rgb['color'][2];
	}
	
	if (isset($color['color'])) {
		$rgb_color[$i]['alpha'] = $color['alpha'];
	}
}*/

switch($graph_type) {
	case 'pie3d':
	case 'pie2d':
			pch_pie_graph($graph_type, array_values($data), array_keys($data), $width, $height);
			break;
	case 'polar':
	case 'radar':
			pch_kiviat_graph($graph_type, array_values($data), array_keys($data), $width, $height);
			break;
	case 'progress':
			pch_progress_graph($graph_type, $data_keys, $data_values, $width, $height, $xaxisname, $yaxisname);
			break;
	case 'hbar':
	case 'vbar':
			pch_bar_graph($graph_type, $data_keys, $data_values, $width, $height, $rgb_color, $xaxisname, $yaxisname, false, $legend, $fine_colors);
			break;
	case 'stacked_area':
	case 'area':
	case 'line':
			pch_vertical_graph($graph_type, $data_keys, $data_values, $width, $height, $rgb_color, $xaxisname, $yaxisname, false, $legend);
			break;
	case 'threshold':
			pch_threshold_graph($graph_type, $data_keys, $data_values, $width, $height, $xaxisname, $yaxisname, $title);
			break;
	case 'scatter':
			pch_scatter_graph($data_keys, $data_values, $width, $height, $xaxisname, $yaxisname);
			break;
}

function pch_pie_graph ($graph_type, $data_values, $legend_values, $width, $height) {
	 /* CAT:Pie charts */

	 /* Create and populate the pData object */
	 $MyData = new pData();   
	 $MyData->addPoints($data_values,"ScoreA");  
	 $MyData->setSerieDescription("ScoreA","Application A");

	 /* Define the absissa serie */
	 $MyData->addPoints($legend_values,"Labels");
	 $MyData->setAbscissa("Labels");
	 
	 /* Create the pChart object */
	 $myPicture = new pImage($width,$height,$MyData,TRUE);

	 /* Set the default font properties */ 
	 $myPicture->setFontProperties(array("FontName"=>"../fonts/code.ttf","FontSize"=>10,"R"=>80,"G"=>80,"B"=>80));

	 /* Create the pPie object */ 
	 $PieChart = new pPie($myPicture,$MyData);

	 /* Draw an AA pie chart */
	 switch($graph_type) {
		 case "pie2d":
			    $PieChart->draw2DPie($width/4,$height/2,array("DataGapAngle"=>0,"DataGapRadius"=>0, "Border"=>FALSE, "BorderR"=>200, "BorderG"=>200, "BorderB"=>200, "Radius"=>$width/4, "ValueR"=>0, "ValueG"=>0, "ValueB"=>0, "WriteValues"=>TRUE));
				break;
		 case "pie3d":
			    $PieChart->draw3DPie($width/4,$height/2,array("DataGapAngle"=>10,"DataGapRadius"=>6, "Border"=>TRUE, "Radius"=>$width/4, "ValueR"=>0, "ValueG"=>0, "ValueB"=>0, "WriteValues"=>TRUE));
				break;
	 }

	 /* Write down the legend next to the 2nd chart*/
	 $legend_size = $myPicture->getLegendSize(array("BoxSize"=>10));
	 $PieChart->drawPieLegend($legend_size['Width'],5, array("R"=>255,"G"=>255,"B"=>255, "BoxSize"=>10)); 
 
	 /* Enable shadow computing */ 
	 $myPicture->setShadow(TRUE,array("X"=>3,"Y"=>3,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
		 
	 /* Render the picture */
	 $myPicture->stroke(); 
}

function pch_kiviat_graph ($graph_type, $data_values, $legend_values, $width, $height) {
	 /* CAT:Radar/Polar charts */

	 /* Create and populate the pData object */
	 $MyData = new pData();   
	 $MyData->addPoints($data_values,"ScoreA");  
	 $MyData->setSerieDescription("ScoreA","Application A");

	 /* Define the absissa serie */
	 $MyData->addPoints($legend_values,"Labels");
	 $MyData->setAbscissa("Labels");
	 
	 /* Create the pChart object */
	 $myPicture = new pImage($width,$height,$MyData,TRUE);

	 /* Set the default font properties */ 
	 $myPicture->setFontProperties(array("FontName"=>"../fonts/code.ttf","FontSize"=>8,"R"=>80,"G"=>80,"B"=>80));

	 /* Create the pRadar object */ 
	 $SplitChart = new pRadar();

	 /* Draw a radar chart */ 
	 $myPicture->setGraphArea(20,25,$width-10,$height-10);
 
	 /* Draw an AA pie chart */
	 switch($graph_type) {
		 case "radar":
				$Options = array("SkipLabels"=>0,"LabelPos"=>RADAR_LABELS_HORIZONTAL, "LabelMiddle"=>FALSE,"Layout"=>RADAR_LAYOUT_STAR,"BackgroundGradient"=>array("StartR"=>255,"StartG"=>255,"StartB"=>255,"StartAlpha"=>100,"EndR"=>207,"EndG"=>227,"EndB"=>125,"EndAlpha"=>50), "FontName"=>"../fonts/code.ttf","FontSize"=>6);
			    $SplitChart->drawRadar($myPicture,$MyData,$Options); 
				break;
		 case "polar":
				$Options = array("Layout"=>RADAR_LAYOUT_CIRCLE,"BackgroundGradient"=>array("StartR"=>255,"StartG"=>255,"StartB"=>255,"StartAlpha"=>100,"EndR"=>207,"EndG"=>227,"EndB"=>125,"EndAlpha"=>50), "FontName"=>"../fonts/code.ttf","FontSize"=>6); 
 			    $SplitChart->drawRadar($myPicture,$MyData,$Options); 
				break;
	 }
		 
	 /* Render the picture */
	 $myPicture->stroke(); 
}

function pch_bar_graph ($graph_type, $index, $data, $width, $height, $rgb_color = false, $xaxisname = "", $yaxisname = "", $show_values = false, $legend = array(), $fine_colors = array()) {
	/* CAT: Vertical Bar Chart */
	if(!is_array($legend) || empty($legend)) {
		unset($legend);
	}

	 /* Create and populate the pData object */
	 $MyData = new pData();
	 $overridePalette = array();
	 foreach($data as $i => $values) {
		$MyData->addPoints($values,$i);
		if($rgb_color !== false) {
			$MyData->setPalette($i, 
					array("R" => $rgb_color[$i]['color']["R"], 
						"G" => $rgb_color[$i]['color']["G"], 
						"B" => $rgb_color[$i]['color']["B"],
						"BorderR" => $rgb_color[$i]['border']["R"], 
						"BorderG" => $rgb_color[$i]['border']["G"], 
						"BorderB" => $rgb_color[$i]['border']["B"], 
						"Alpha" => $rgb_color[$i]['alpha']));
		}
		
		// Assign cyclic colors to bars if are setted
		if($fine_colors) {
			$c = 0;
			foreach($values as $ii => $vv) {
					if(!isset($fine_colors[$c])) {
						$c = 0;
					}
					$overridePalette[$ii] = $fine_colors[$c];
					$c++;
			}
		}
		else {
			$overridePalette = false;
		}
	 }

	 $MyData->setAxisName(0,$yaxisname);
	 $MyData->addPoints($index,"Xaxis");
	 $MyData->setSerieDescription("Xaxis", $xaxisname);
	 $MyData->setAbscissa("Xaxis");

	 /* Create the pChart object */
	 $myPicture = new pImage($width,$height,$MyData);

	 /* Turn of Antialiasing */
	 $myPicture->Antialias = FALSE;

	 /* Add a border to the picture */
	 //$myPicture->drawRectangle(0,0,$width,$height,array("R"=>0,"G"=>0,"B"=>0));

	 /* Turn on shadow computing */ 
	 $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10)); 

	 /* Set the default font */
	 $myPicture->setFontProperties(array("FontName"=>"../fonts/code.ttf","FontSize"=>10));

	 /* Draw the scale */
	 // TODO: AvoidTickWhenEmpty = FALSE When the distance between two ticks will be less than 50 px
	 // TODO: AvoidTickWhenEmpty = TRUE When the distance between two ticks will be greater than 50 px
	 switch($graph_type) {
		case "vbar":
				$scaleSettings = array("AvoidTickWhenEmpty" => FALSE, "AvoidGridWhenEmpty" => FALSE, "GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE, "Mode"=>SCALE_MODE_START0, "LabelRotation" => 60);
				$leftmargin = 40;
				break;
		case "hbar":
				$scaleSettings = array("GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE, "Mode"=>SCALE_MODE_START0, "Pos"=>SCALE_POS_TOPBOTTOM);
				$leftmargin = 100;
				break;
	 }
	 
	 /* Define the chart area */
	 $myPicture->setGraphArea($leftmargin,20,$width,$height-80);

	 $myPicture->drawScale($scaleSettings);

	 if(isset($legend)) {
		/* Write the chart legend */
		$size = $myPicture->getLegendSize(array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));
		$myPicture->drawLegend($width-$size['Width'],0,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL, "BoxWidth"=>10, "BoxHeight"=>10));
	 }
	 
	 /* Turn on shadow computing */ 
	 $myPicture->setShadow(TRUE,array("X"=>0,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

	 /* Draw the chart */
	 $settings = array("ForceTransparency"=>"-1", "Gradient"=>TRUE,"GradientMode"=>GRADIENT_EFFECT_CAN,"DisplayValues"=>$show_values,"DisplayZeroValues"=>FALSE,"DisplayR"=>100,"DisplayG"=>100,"DisplayB"=>100,"DisplayShadow"=>TRUE,"Surrounding"=>5,"AroundZero"=>FALSE, "OverrideColors"=>$overridePalette);
	 
	 $myPicture->drawBarChart($settings);

	 /* Render the picture */
	 $myPicture->stroke(); 
}

function pch_vertical_graph ($graph_type, $index, $data, $width, $height, $rgb_color = false, $xaxisname = "", $yaxisname = "", $show_values = false, $legend = array()) {
	/* CAT:Vertical Charts */
	if(!is_array($legend) || empty($legend)) {
		unset($legend);
	}
	 /*$legend=array('pep1' => 'pep1','pep2' => 'pep2','pep3' => 'pep3','pep4' => 'pep4');
	 $data=array(array('pep1' => 1, 'pep2' => 1, 'pep3' => 3, 'pep4' => 3), array('pep1' => 1, 'pep2' => 3, 'pep3' => 1,'pep4' => 4), array('pep1' => 3, 'pep2' => 1, 'pep3' => 1,'pep4' =>1), array('pep1' => 1, 'pep2' =>1, 'pep3' =>1,'pep4' =>0));
	 $index=array(1,2,3,4);
     */
     if(is_array(reset($data))) {
	 	$data2 = array();
		foreach($data as $i =>$values) {
			$c = 0;
			foreach($values as $i2 => $value) {
				$data2[$i2][$i] = $value;
				$c++;
			}
		}
		$data = $data2;
	 }
	 else {
		$data = array($data);
	 }

	 /* Create and populate the pData object */
	 $MyData = new pData();

	 foreach($data as $i => $values) {
		 if(isset($legend)) {
			$point_id = $legend[$i];
		 }
		 else {
			$point_id = $i;
		 }

		$MyData->addPoints($values,$point_id);
		if (!empty($rgb_color)) {
			$MyData->setPalette($point_id, 
				array("R" => $rgb_color[$i]['color']["R"], 
					"G" => $rgb_color[$i]['color']["G"], 
					"B" => $rgb_color[$i]['color']["B"],
					"BorderR" => $rgb_color[$i]['border']["R"], 
					"BorderG" => $rgb_color[$i]['border']["G"], 
					"BorderB" => $rgb_color[$i]['border']["B"], 
					"Alpha" => $rgb_color[$i]['alpha']));
				
			/*$palette_color = array();
			if (isset($rgb_color[$i]['color'])) {
				$palette_color["R"] = $rgb_color[$i]['color']["R"];
				$palette_color["G"] = $rgb_color[$i]['color']["G"];
				$palette_color["B"] = $rgb_color[$i]['color']["B"];
			}
	 		if (isset($rgb_color[$i]['color'])) {
				$palette_color["BorderR"] = $rgb_color[$i]['border']["R"];
				$palette_color["BorderG"] = $rgb_color[$i]['border']["G"];
				$palette_color["BorderB"] = $rgb_color[$i]['border']["B"];
			}
			if (isset($rgb_color[$i]['color'])) {
				$palette_color["Alpha"] = $rgb_color[$i]['Alpha'];
			}
		
			$MyData->setPalette($point_id, $palette_color);*/
		}
	 }

	 //$MyData->addPoints($data,"Yaxis");
	 $MyData->setAxisName(0,$yaxisname);
	 $MyData->addPoints($index,"Xaxis");
	 $MyData->setSerieDescription("Xaxis", $xaxisname);
	 $MyData->setAbscissa("Xaxis");

	 /* Create the pChart object */
	 $myPicture = new pImage($width,$height,$MyData);

	 /* Turn of Antialiasing */
	 $myPicture->Antialias = FALSE;

	 /* Add a border to the picture */
	 //$myPicture->drawRectangle(0,0,$width,$height,array("R"=>0,"G"=>0,"B"=>0));

	 /* Set the default font */
	 $myPicture->setFontProperties(array("FontName"=>"../fonts/code.ttf","FontSize"=>10));

 	if(isset($legend)) {
		/* Write the chart legend */
		$size = $myPicture->getLegendSize(array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL));
		$myPicture->drawLegend($width-$size['Width'], 8,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL));
	 }
	 
	 //Calculate the bottom margin from the size of string in each index
	 $max_chars = 0;
	 foreach ($index as $string_index) {
	 	if (empty($string_index)) continue;
	 	
	 	$len = strlen($string_index);
	 	if ($len > $max_chars) {
	 		$max_chars = $len; 
	 	}
	 }
	 $margin_bottom = 10 * $max_chars;
	 //$margin_bottom = 90;
	 
	 if (isset($size['Height'])) {
	 	/* Define the chart area */
	 	$myPicture->setGraphArea(40,$size['Height'],$width,$height - 90);
	 }
	 else {
	 	/* Define the chart area */
	 	$myPicture->setGraphArea(40, 5,$width,$height - 90);
	 }

	 /* Draw the scale */
	 $scaleSettings = array("GridR"=>200,
		 "GridG"=>200,
		 "GridB"=>200,
		 "DrawSubTicks"=>TRUE,
		 "CycleBackground"=>TRUE, "Mode"=>SCALE_MODE_START0, "LabelRotation" => 60);
	 $myPicture->drawScale($scaleSettings);
	 
	 /* Turn on shadow computing */ 
	 //$myPicture->setShadow(TRUE,array("X"=>0,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

	 switch ($graph_type) {
	 	case 'stacked_area':
	 		$ForceTransparency = "-1";
	 		break;
	 	default:
	 		$ForceTransparency = "50";
	 		break;
	 }
	 
	 /* Draw the chart */
	 $settings = array("ForceTransparency"=> $ForceTransparency, //
	 	"Gradient"=>TRUE,
	 	"GradientMode"=>GRADIENT_EFFECT_CAN,
	 	"DisplayValues"=>$show_values,
	 	"DisplayZeroValues"=>FALSE,
	 	"DisplayR"=>100,
	 	"DisplayZeros"=> FALSE,
	 	"DisplayG"=>100,"DisplayB"=>100,"DisplayShadow"=>TRUE,"Surrounding"=>5,"AroundZero"=>FALSE);
	 
	 switch($graph_type) {
	 	case "stacked_area":
		case "area":
				$myPicture->drawAreaChart($settings);
				break;
		case "line":
				$myPicture->drawLineChart($settings);
				break;
	 }
	 
	 /* Render the picture */
	 $myPicture->stroke(); 
}

function pch_threshold_graph ($graph_type, $index, $data, $width, $height, $xaxisname = "", $yaxisname = "", $title = "", $show_values = false, $show_legend = false) {
	 /* CAT:Threshold Chart */

	/* Create and populate the pData object */
	 $MyData = new pData();  
	 $MyData->addPoints($data,"DEFCA");
	 $MyData->setAxisName(0,$yaxisname);
	 $MyData->setAxisDisplay(0,AXIS_FORMAT_CURRENCY);
	 $MyData->addPoints($index,"Labels");
	 $MyData->setSerieDescription("Labels",$xaxisname);
	 $MyData->setAbscissa("Labels");
	 $MyData->setPalette("DEFCA",array("R"=>55,"G"=>91,"B"=>127));

	 /* Create the pChart object */
	 $myPicture = new pImage(700,230,$MyData);
	 $myPicture->drawGradientArea(0,0,700,230,DIRECTION_VERTICAL,array("StartR"=>220,"StartG"=>220,"StartB"=>220,"EndR"=>255,"EndG"=>255,"EndB"=>255,"Alpha"=>100));
	 $myPicture->drawRectangle(0,0,699,229,array("R"=>200,"G"=>200,"B"=>200));
	 
	 /* Write the picture title */ 
	 $myPicture->setFontProperties(array("FontName"=>"../fonts/code.ttf","FontSize"=>11));
	 $myPicture->drawText(60,35,$title,array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMLEFT));

	 /* Do some cosmetic and draw the chart */
	 $myPicture->setGraphArea(60,40,670,190);
	 $myPicture->drawFilledRectangle(60,40,670,190,array("R"=>255,"G"=>255,"B"=>255,"Surrounding"=>-200,"Alpha"=>10));
	 $myPicture->drawScale(array("GridR"=>180,"GridG"=>180,"GridB"=>180, "Mode" => SCALE_MODE_START0));
	 $myPicture->setShadow(TRUE,array("X"=>2,"Y"=>2,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
	 $myPicture->setFontProperties(array("FontName"=>"../fonts/code.ttf","FontSize"=>6));
	 $settings = array("Gradient"=>TRUE,"GradientMode"=>GRADIENT_EFFECT_CAN,"DisplayValues"=>$show_values,"DisplayZeroValues"=>FALSE,"DisplayR"=>100,"DisplayG"=>100,"DisplayB"=>100,"DisplayShadow"=>TRUE,"Surrounding"=>5,"AroundZero"=>FALSE);
	 $myPicture->drawSplineChart($settings);
	 $myPicture->setShadow(FALSE);

	 if($show_legend) {
		/* Write the chart legend */ 
		$myPicture->drawLegend(643,210,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL)); 
	 }
	 
	 /* Render the picture */
	 $myPicture->stroke(); 
}

function pch_horizontal_graph ($graph_type, $index, $data, $width, $height, $xaxisname = "", $yaxisname = "", $show_values = false, $show_legend = false) {
	 /* CAT:Horizontal Charts */

	 /* Create and populate the pData object */
	 $MyData = new pData();  
	 $MyData->addPoints($data,"Xaxis");
	 $MyData->setAxisName(0,$yaxisname);
	 $MyData->addPoints($index,"Yaxis");
	 $MyData->setSerieDescription("Yaxis", $xaxisname);
	 $MyData->setAbscissa("Yaxis");

	 /* Create the pChart object */
	 $myPicture = new pImage($width,$height,$MyData);
	 $myPicture->drawGradientArea(0,0,$width,500,DIRECTION_VERTICAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>100));
	 $myPicture->drawGradientArea(0,0,$width,500,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>20));

	 /* Add a border to the picture */
	 //$myPicture->drawRectangle(0,0,$width,$height,array("R"=>0,"G"=>0,"B"=>0));

	 /* Set the default font */
	 $myPicture->setFontProperties(array("FontName"=>"../fonts/code.ttf","FontSize"=>7));

	 /* Define the chart area */
	 $myPicture->setGraphArea(75,20,$width,$height);

	 if(count($data) == 1) {
		 $xmargin = 110;
	 }
	 elseif(count($data) == 2) {
		$xmargin = 70;
	 }
	 else {
		$xmargin = 45;
	 }
	 /* Draw the scale */
	 $scaleSettings = array("GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE, "Mode"=>SCALE_MODE_START0, "XMargin" => $xmargin,"Pos"=>SCALE_POS_TOPBOTTOM);
	 $myPicture->drawScale($scaleSettings);

	 if($show_legend) {
		/* Write the chart legend */
		$myPicture->drawLegend(580,12,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));
	 }
	 
	 /* Turn on shadow computing */ 
	 $myPicture->setShadow(TRUE,array("X"=>0,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

	 /* Draw the chart */
	 $settings = array("Gradient"=>TRUE,"GradientMode"=>GRADIENT_EFFECT_CAN,"DisplayValues"=>$show_values,"DisplayZeroValues"=>FALSE,"DisplayR"=>100,"DisplayG"=>100,"DisplayB"=>100,"DisplayShadow"=>TRUE,"Surrounding"=>5,"AroundZero"=>FALSE);
	 $settings = array("DisplayPos"=>LABEL_POS_INSIDE,"DisplayValues"=>TRUE,"Rounded"=>TRUE,"Surrounding"=>30);
	 switch($graph_type) {
		case "hbar":
				$myPicture->drawBarChart($settings);
				break;
		case "area":
				$myPicture->drawAreaChart($settings);
				break;
		case "line":
				$myPicture->drawLineChart($settings);
				break;
	 }

	 /* Render the picture */
	 $myPicture->stroke(); 
}
?>
	$rgb_colo