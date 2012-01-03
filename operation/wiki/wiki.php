<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2010 Ártica Soluciones Tecnológicas
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
$id_user = $config["id_user"];

if (check_login() != 0) {
	audit_db("Noauth", $config["REMOTE_ADDR"], "No authenticated access", 
		"Trying to access monthly report");
	require ("general/noaccess.php");
	
	exit;
}

if (! give_acl ($config['id_user'], $id_grupo, "WR")) {
 	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access agenda of group ".$id_grupo);
	include ("general/noaccess.php");
	exit;
}

require_once("include/wiki/lionwiki_lib.php");

$conf['self'] = 'index.php?sec=wiki&sec2=operation/wiki/wiki' . '&';
$conf['plugin_dir'] = 'include/wiki/plugins/';
$conf['fallback_template'] = '
<style type="text/css">
input[name="moveto"] {
	width: 100%;
}
</style>
<table width="100%" cellpadding="4">
	<tr><th colspan="3"><hr/><h2 id="page-title">{PAGE_TITLE}</h2></th></tr>
	<tr>
		<td colspan="3">
			{<div style="color:#F25A5A;font-weight:bold;"> ERROR </div>}
			{CONTENT} {<div style="background: #EBEBED"> plugin:TAG_LIST </div>}
			{plugin:TOOLBAR_TEXTAREA}
			{CONTENT_FORM} {RENAME_INPUT <br/><br/>} {CONTENT_TEXTAREA}
			<p style="float:right;margin:6px">{FORM_PASSWORD} {FORM_PASSWORD_INPUT} {plugin:CAPTCHA_QUESTION} {plugin:CAPTCHA_INPUT}
			{EDIT_SUMMARY_TEXT} {EDIT_SUMMARY_INPUT} {CONTENT_SUBMIT} {CONTENT_PREVIEW}</p>{/CONTENT_FORM}
		</td>
	</tr>
	<tr><td colspan="3"><hr/></td></tr>
	<tr>
		<td>' . __('Powered by ') . '<a href="http://lionwiki.0o.cz/">LionWiki</a>. {LAST_CHANGED_TEXT}: {LAST_CHANGED}</td>
		<td></td>
		<td></td>
	</tr>
</table>';
lionwiki_show($conf);
?>