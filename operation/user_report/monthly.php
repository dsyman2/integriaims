<?PHP
// Integria 1.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

if (check_login() != 0) {
 	audit_db("Noauth",$config["REMOTE_ADDR"], "No authenticated access","Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

$id_grupo = give_parameter_get ("id_grupo",0);
$id_user=$config['id_user'];
if (give_acl($id_user, $id_grupo, "PR") != 1){
 	// Doesn't have access to this page
	audit_db($id_user,$config["REMOTE_ADDR"], "ACL Violation","Trying to access to user report without projects rights");
	include ("general/noaccess.php");
	exit;
}
$id = give_parameter_get ("id","");
if (($id != "") && ($id != $id_user)){
	if (give_acl($id_user, 0, "PW"))
		$id_user = $id;
	else {
		audit_db("Noauth", $config["REMOTE_ADDR"], "No permission access", "Trying to access user workunit report");
		require ("general/noaccess.php");
		exit;
	}
	
}


// Get parameters for actual Calendar show
$time = time();
$month = give_parameter_get ( "month", date('n', $time));
$year = give_parameter_get ( "year", date('y', $time));

$today = date('j',$time);
$days_f = array();
$first_of_month = gmmktime(0,0,0,$month,1,$year);
$days_in_month=gmdate('t',$first_of_month);
$locale = $config["language_code"];

$prev_month = $month -1;
$prev_year = $year;
if ($prev_month == 0){
	$prev_month = 12;
	$prev_year = $prev_year -1;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month == 13){
	$next_month = 1;
	$next_year = $next_year +1;
}
$day = date('d', strtotime("now"));

$from_one_month = "$prev_year-$prev_month-$day";

echo "<h1>".lang_string("Monthly report for")." $id_user</h1>";
echo "<table width=700>";
echo "<tr><td>";
echo "<a href='index.php?sec=users&sec2=operation/user_report/monthly&month=$prev_month&year=$prev_year&id=$id_user'> Prev</A>";
echo "<td width=85%>";
echo "&nbsp;";
echo "<td>";
echo "<a href='index.php?sec=users&sec2=operation/user_report/monthly&month=$next_month&year=$next_year&id=$id_user'> Next</A>";
echo "</table>";

// Generate calendar

echo "<div >";
echo generate_work_calendar ($year, $month, $days_f, 3, NULL, 1, "", $id_user);
echo "</div>";



?>