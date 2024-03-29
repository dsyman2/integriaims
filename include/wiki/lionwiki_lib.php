<?php
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Library version of LionWiki
// LionWiki 3.2.7, (c) Adam Zivner, licensed under GNU/GPL v2
// Conversion to library by Miguel de Dios Matias.
// Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

function loadConfig() {
	global $WIKI_TITLE;
	global $PASSWORD;
	global $TEMPLATE;
	global $PROTECTED_READ;
	global $NO_HTML;
	global $START_PAGE;
	global $SYNTAX_PAGE;
	global $DATE_FORMAT;
	global $LOCAL_HOUR;
	global $VAR_DIR;
	global $PLUGINS_DIR;
	global $LANG_DIR;
	
	//BY DEFAULT
	@include('config.php'); // config file is not required, see settings above}

	//BUT YOU CAN FILL WITH OTHER CODE FOR EXAMPLE GET THE CONF FROM DB
	//foo();
}

function header_lionwiki($header) {
	//BY DEFAULT
	//header($header);
	
	//BUT YOU CAN FILL WITH OTHER CODE FOR EXAMPLE GET THE CONF FROM DB
	//foo();
}

function error_show_lionwiki($location) {
	//BY DEFAULT
	//die(header("Location:$location"));
	
	
	//BUT YOU CAN FILL WITH OTHER CODE FOR EXAMPLE GET THE CONF FROM DB
	//show_error_page();
	global $page;
	global $start_page_conf;
	
	if (!empty($start_page_conf))
		$page = $start_page_conf;
}

function check_no_read() {
	global $PROTECTED_READ;
	
	$return = false;
	
	if (!plugin('check_no_read')) {
		if ($PROTECTED_READ && !authentified()) { // does user need password to read content of site. If yes, ask for it.
			$return = true;
		}
	}
	else {
		$return = true;
	}
	
	return $return;
}

function check_no_save() {
	if (plugin('check_no_save')) {
		return true;
	}
	
	return false;
}

function check_no_new_page() {
	if (plugin('check_no_new_page')) {
		return true;
	}
	
	return false;
}

function lionwiki_show($conf = null, $execute_actions_param = true) {
	global $plugins;
	global $TITLE;
	global $page;
	global $PG_DIR;
	global $DATE_FORMAT;
	global $HIST_DIR;
	global $plugin_ret_diff;
	global $PASSWORD;
	global $sc;
	global $PROTECTED_READ;
	global $content;
	global $error;
	global $action;
	global $html;
	global $preview;
	global $HEAD;
	global $PLUGINS_DIR;
	global $VAR_DIR;
	global $self;
	global $self_form;
	global $CON;
	global $execute_actions;
	global $rightnow;
	global $config;

	$execute_actions = $execute_actions_param;
	
	$is_enterprise = false;
	if (file_exists ("enterprise/include/functions_wiki.php")) {
		require_once ("enterprise/include/functions_wiki.php");
		$is_enterprise = true;
	}
	
	//Default confs
	$wiki_title_conf = 'My new wiki';
	$password_conf = '';
	$template_conf = 'templates/dandelion.html';
	$protected_read_conf = false;
	$no_html_conf = false;
	$start_page_conf = 'Main page';
	$syntax_page_conf = 'http://lionwiki.0o.cz/?page=Syntax+reference';
	$date_format_conf = 'Y/m/d H:i';
	$local_hour_conf = 0;
	$var_dir_conf = 'var/';
	$plugin_dir_conf = 'plugins/';
	$lang_dir_conf = 'lang/';
	$self = basename($_SERVER['PHP_SELF']) . '?';
	$fallback_template = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
	<title>{PAGE_TITLE_HEAD  - }{WIKI_TITLE}</title>
	<style type="text/css">
*{margin:0;padding:0}
body{font-size:12px;line-height:16px;padding:10px 20px 20px 20px}
p{margin:5px}
a{color:#060;text-decoration:none;border-bottom:1px dotted #060}
a.pending{color:#900}
a.external:after{content:"\2197"}
pre{border:1px dotted #ccc;padding:4px;overflow:auto;margin:3px}
img,a img{border:0}
h1,h2,h3,h4,h5,h6{letter-spacing:2px;font-weight:normal;margin:15px 0 15px 0;color:#060}
h1 a:hover,h2 a:hover,h3 a:hover,h4 a:hover,h5 a:hover,h6 a:hover{color:#060}
h1 a{border-bottom:0}
h2 .par-edit,h3 .par-edit,h4 .par-edit,h5 .par-edit,h6 .par-edit{visibility:hidden;font-size:x-small}
h2:hover .par-edit,h3:hover .par-edit,h4:hover .par-edit,h5:hover .par-edit,h6:hover .par-edit{visibility:visible}
hr{margin:10px 0 10px 0;height:1px;overflow:hidden;border:0;background:#060}
ul,ol{padding:5px 0px 5px 20px}
table{text-align:left}
input,select,textarea{border:1px solid #AAA;padding:2px;font-size:12px}
#toc{border:1px dashed #060;margin:5px 0 5px 10px;padding:6px 5px 7px 0;float:right;padding-right:2em;list-style:none}
#toc ul{list-style:none;padding:3px 0 3px 10px}
#toc li{font-size:11px;padding-left:10px}
#diff {padding:1em; white-space: pre-wrap; width: 97%;}
#diff ins{color:green;font-weight:bold}
#diff del{color:red;text-decoration:line-through}
#diff .orig{color:#666;font-size:90%}
/* Plugins */
.tagList{padding:0.2em 0.4em 0.2em 0.4em;margin-top:0.5em;border:1px dashed #060;clear:right}
.tagCloud{float:right;width:200px;padding:0.5em;margin:1em;border:1px dashed #060;clear:right}
.pageVersionsList{letter-spacing:0;font-variant:normal;font-size:12px}
.resizeTextarea a{border-bottom:none}

	</style>
	{HEAD}
</head>
<body>
<table width="100%" cellpadding="4">
<tr>
	<td colspan="2">{HOME} {RECENT_CHANGES}</td>
	<td style="text-align:right">{EDIT} {SYNTAX} {HISTORY}</td>
</tr>
<tr><th colspan="3"><hr/><h1 id="page-title">{PAGE_TITLE} {<span class="pageVersionsList">( plugin:VERSIONS_LIST )</span>}</h1></th></tr>
<tr>
	<td colspan="3">
		{<div style="color:#F25A5A;font-weight:bold;"> ERROR </div>}
		{CONTENT} {plugin:TAG_LIST}
		{CONTENT_FORM} {RENAME_TEXT} {RENAME_INPUT <br/><br/>} {CONTENT_TEXTAREA}
		<p style="float:right;margin:6px">{FORM_PASSWORD} {FORM_PASSWORD_INPUT} {plugin:CAPTCHA_QUESTION} {plugin:CAPTCHA_INPUT}
		{EDIT_SUMMARY_TEXT} {EDIT_SUMMARY_INPUT} {CONTENT_SUBMIT} {CONTENT_PREVIEW}</p>{/CONTENT_FORM}
	</td>
</tr>
<tr><td colspan="3"><hr/></td></tr>
<tr>
	<td><div>{SEARCH_FORM}{SEARCH_INPUT}{SEARCH_SUBMIT}{/SEARCH_FORM}</div></td>
	<td>{LAST_CHANGED_TEXT}: {LAST_CHANGED} {COOKIE}</td>
	<td style="text-align:right">{EDIT} {SYNTAX} {HISTORY}</td>
</tr>
</table>
</body>
</html>';
	
	if ($conf != null) {
		if (isset($conf['wiki_title'])) $wiki_title_conf = $conf['wiki_title'];
		if (isset($conf['password'])) $password_conf = $conf['password'];
		if (isset($conf['template'])) $template_conf = $conf['template'];
		if (isset($conf['protected_read'])) $protected_read_conf = $conf['protected_read'];
		if (isset($conf['no_html'])) $no_html_conf = $conf['no_html'];
		if (isset($conf['start_page'])) $start_page_conf = $conf['start_page'];
		if (isset($conf['syntax_page'])) $syntax_page_conf = $conf['syntax_page'];
		if (isset($conf['date_format'])) $date_format_conf = $conf['date_format'];
		if (isset($conf['local_hour'])) $local_hour_conf = $conf['local_hour'];
		if (isset($conf['var_dir'])) $var_dir_conf = $conf['var_dir'];
		if (isset($conf['plugin_dir'])) $plugin_dir_conf = $conf['plugin_dir'];
		if (isset($conf['lang_dir'])) $lang_dir_conf = $conf['lang_dir'];
		if (isset($conf['self'])) $self = $conf['self'];
		if (isset($conf['fallback_template'])) $fallback_template = $conf['fallback_template'];
	}
	else {
		loadConfig();
	}
	
	//Delete the last character, it is ? or & normality.
	$self_form = substr_replace($self, '', -1);
	
	/*
	 * Disabled for avoid lost the var $action or $page for example
	foreach ($_REQUEST as $k => $v)
		unset($$k); // register_globals = off
	*/ 
	
	// SETTINGS - default settings, can be overridden in config.php
	$WIKI_TITLE = $wiki_title_conf; // name of the site
	$PASSWORD = $password_conf; // SHA1 hash
	
	$TEMPLATE = $template_conf; // presentation template
	$PROTECTED_READ = $protected_read_conf; // if true, you need to fill password for reading pages too
	$NO_HTML = 0; //$no_html_conf; // XSS protection
	
	$START_PAGE = $start_page_conf; // Which page should be default (start page)?
	$SYNTAX_PAGE = $syntax_page_conf;
	
	$DATE_FORMAT = $date_format_conf;
	$LOCAL_HOUR = $local_hour_conf;
	
	@error_reporting(E_ERROR | E_WARNING | E_PARSE);
	@ini_set('default_charset', 'UTF-8');
	set_magic_quotes_runtime(0);
	umask(0);
	
	if (get_magic_quotes_gpc()) // magic_quotes_gpc can't be turned off
		for ($i = 0, $_SG = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST), $c = count($_SG); $i < $c; ++$i)
			$_SG[$i] = array_map('stripslashes', $_SG[$i]);
	
	$REAL_PATH = realpath(dirname(__FILE__)) . '/';
	$VAR_DIR = $var_dir_conf;
	$PG_DIR = $VAR_DIR . 'pages/';
	$HIST_DIR = $VAR_DIR . 'history/';
	$PLUGINS_DIR = $plugin_dir_conf;
	$PLUGINS_DATA_DIR = $VAR_DIR.'plugins/';
	$LANG_DIR = $lang_dir_conf;
	
	
	// default translation
	$T_HOME = 'Main page';
	$T_SYNTAX = 'Syntax';
	$T_DONE = 'Save changes';
	$T_DISCARD_CHANGES = 'Discard changes';
	$T_PREVIEW = 'Preview';
	$T_SEARCH = 'Search';
	$T_SEARCH_RESULTS = 'Wiki search results';
	$T_LIST_OF_ALL_PAGES = 'List of all pages';
	$T_RECENT_CHANGES = 'Recent changes';
	$T_LAST_CHANGED = 'Last changed';
	$T_HISTORY = 'History';
	$T_RESTORE = 'Restore';
	$T_REV_DIFF = '<b>Difference between revisions from {REVISION1} and {REVISION2}.</b>';
	$T_REVISION = "'''This revision is from {TIME}. You can {RESTORE} it.'''\n\n";
	$T_PASSWORD = 'Password';
	$T_EDIT = 'Edit';
	$T_EDIT_SUMMARY = 'Summary of changes';
	$T_EDIT_CONFLICT = 'Edit conflict: somebody saved this page after you started editing. See last {DIFF} before saving your changes.';
	$T_SHOW_SOURCE = 'Show source';
	$T_SHOW_PAGE = 'Show page';
	$T_ERASE_COOKIE = 'Erase cookies';
	$T_MOVE_TEXT = 'New name';
	$T_DIFF = 'diff';
	$T_CREATE_PAGE = 'Create page';
	$T_PROTECTED_READ = 'You need to enter password to view content of site: ';
	$T_WRONG_PASSWORD = 'Password is incorrect.';
	
	if ($_GET['lang']) {
		$LANG = clear_path($_GET['lang']);
		setcookie('LW_LANG', $LANG, time() + 365 * 86400);
	}
	elseif ($_COOKIE['LW_LANG'])
		$LANG = clear_path($_COOKIE['LW_LANG']);
	else
		list($LANG) = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	
	if ((@include("$LANG_DIR$LANG.php")) === false && (@include($LANG_DIR . substr($LANG, 0, 2) . '.php')) === false)
		$LANG = 'en';
	
	
	// Creating essential directories if they don't exist
	if (!file_exists($VAR_DIR) && !mkdir(rtrim($VAR_DIR, "/")))
		die("Can't create directory $VAR_DIR. Please create $VAR_DIR with 0777 rights.");
	else
		foreach (array($PG_DIR, $HIST_DIR, $PLUGINS_DATA_DIR) as $DIR)
			if (@mkdir(rtrim($DIR, '/'), 0777)) {
				$f = fopen($DIR . ".htaccess", "w"); fwrite($f, "deny from all"); fclose($f);
			}
	
	
	if ($_GET['erasecookie']) // remove cookie without reloading
		foreach ($_COOKIE as $k => $v)
			if (substr($k, 0, 3) == 'LW_') {
				setcookie($k);
				unset($_COOKIE[$k]);
			}
	
	//Load Plugins
	for ($plugins = array(), $dir = @opendir($PLUGINS_DIR); $dir && $f = readdir($dir);) // load plugins
		if (preg_match('/wkp_(.+)\.php$/', $f, $m) > 0) {
			require_once $PLUGINS_DIR . $f;
			$plugins[$m[1]] = new $m[1]();
			
			if (isset($$m[1]))
				foreach ($$m[1] as $name => $value)
					$plugins[$m[1]]->$name = $value;
		}
	
	plugin('pluginsLoaded');
	
	foreach (array('action', 'content', 'error', 'esum', 'f1', 'f2',
		'last_changed', 'moveto', 'page', 'par', 'preview', 'query', 'restore',
		'sc', 'showsource', 'time') as $req)
		$$req = $_REQUEST[$req]; // export request variables to global namespace
	
	if (empty($page)) {
		$page = $start_page_conf;
	}
	
	$TITLE = $page = clear_path($page);
	$moveto = clear_path($moveto);
	$f1 = clear_path($f1);
	$f2 = clear_path($f2);
	$CON = $content;
	
	plugin('actionBegin');
	
	if (!$action) {

		if (!$page) {

			error_show_lionwiki("Location:$self" . "page=" . u($START_PAGE));
		}
		elseif (file_exists("$PG_DIR$page.$LANG.txt")) { // language variant

			error_show_lionwiki("Location:$self" . "page=" . u("$page.$LANG"));
		} elseif (!file_exists("$PG_DIR$page.txt")) {

			$action = 'edit'; // create page if it doesn't exist
			
			if (check_no_new_page()) {
				if (!plugin('check_no_new_page')) {
					$action = 'view-html';
				}
				else {
					$action = 'view-html';
				}
			}
		}
	}
	
	//Disable the action
	if (!$execute_actions) {
		$action = '';
	}

	//Check the create new page
	if ($action == 'edit') {
		if (!file_exists("$PG_DIR$page.txt")) {
			if (check_no_new_page()) {
				if (!plugin('show_message_no_new_page')) {
					$action = 'view-html';
				}
				else {
					$action = 'view-html';
				}
			}
		}
	}
	
	if (check_no_read()) {
		if (!plugin('show_message_no_read')) {
			$CON = "<form action=\"$self_form" . "page=".u($page)."\" method=\"post\"><p>$T_PROTECTED_READ <input type=\"password\" name=\"sc\"/> <input class=\"submit\" type=\"submit\"/></p></form>";
			$action = 'view-html';
		}
		else {
			$action = 'view-html';
		}
	}
	else {
		if ($restore || $action == 'rev') { // Show old revision
			$CON = @file_get_contents("$HIST_DIR$page/$f1");
			
			if ($action == 'rev') {
				$rev_restore = "[$T_RESTORE|./$self" . "page=".u($page)."&amp;action=edit&amp;f1=$f1&amp;restore=1]";
				$CON = strtr($T_REVISION, array('{TIME}' => rev_time($f1), '{RESTORE}' => $rev_restore)) . $CON;
				$action = '';
			}
		}
		else if ($page) { // Load the page
			$last_changed_ts = @filemtime("$PG_DIR$page.txt");
			
			if (!$action || $action == 'edit') {
				$CON = @file_get_contents("$PG_DIR$page.txt");
				$CON = $par ? get_paragraph($CON, $par) : $CON;
				
				if (!$action && substr($CON, 0, 10) == '{redirect:' && $_REQUEST['redirect'] != 'no')
					error_show_lionwiki("Location:$self" . "page=".u(substr($CON, 10, strpos($CON, '}') - 10)));
			}
		}
	}
	
	if ($action == 'save' && check_no_save()) {
		if (!plugin('show_message_no_save')) {
			$action = 'view-html';
		}
		else {
			$action = 'view-html';
		}
	}
	else {
		$rightnow = date('Ymd-Hi-s', time() + $LOCAL_HOUR * 3600);
		
		if ($action == 'save' && !$preview && authentified()) { // do we have page to save?
			if (!trim($content) && !$par) // delete empty page
				@unlink("$PG_DIR$page.txt");
			elseif ($last_changed < @filemtime("$PG_DIR$page.txt")) {
				$action = 'edit';
				$error = str_replace('{DIFF}', "<a href=\"$self" . "page=".u($page)."&amp;action=diff\">$T_DIFF</a>", $T_EDIT_CONFLICT);
			}
			elseif (!plugin('writingPage')) { // are plugins OK with page? (e.g. checking for spam)
				if ($par) {
					$c = @file_get_contents("$PG_DIR$page.txt");
					$content = str_replace(get_paragraph($c, $par), $content, $c);
				}
				
				if (!$file = @fopen("$PG_DIR$page.txt", 'w'))
					die("Could not write page $PG_DIR$page.txt!");
				
				fwrite($file, $content); fclose($file);
				
				// Backup old revision
				@mkdir($HIST_DIR.$page, 0777); // Create directory if does not exist
				
				if (!$bak = @fopen("$HIST_DIR$page/$rightnow.bak", 'w'))
					die("Could not write to $HIST_DIR$page!");
				
				fwrite($bak, $content); fclose($bak);
				
				$es = fopen("$HIST_DIR$page/meta.dat", 'ab');
				
				fwrite($es, '!' . $rightnow .
					str_pad($_SERVER['REMOTE_ADDR'], 16, ' ', STR_PAD_LEFT) .
					str_pad(filesize("$PG_DIR$page.txt"), 11, ' ', STR_PAD_LEFT) . ' ' .
					str_pad(substr($esum, 0, 128), 128 + 2)) . "\n";
				
				fclose($es);
				
				if ($moveto != $page && $moveto)
					if (file_exists("$PG_DIR$moveto.txt"))
						die('Error: target filename already exists. Page was not moved.');
					elseif (!rename("$PG_DIR$page.txt", "$PG_DIR$moveto.txt"))
						die('Unknown error! Page was not moved.');
					elseif (!rename($HIST_DIR.$page, $HIST_DIR.$moveto)) {
						rename("$PG_DIR$moveto.txt", "$PG_DIR$page.txt"); // revert previous change
						die('Unknown error2! Page was not moved.');
					}
					else
						$page = $moveto;
				
				if (!plugin('pageWritten')) {
					error_show_lionwiki("Location:$self" . 
						"page=" . u($page) . '&redirect=no' . 
						($par ? "&par=$par" : '') . ($_REQUEST['ajax'] ? '&ajax=1' : ''));
				}
				else {
					$action = ''; // display content ...
				}
				
				$par = 1; //reset counter
				
				//Show all page not only the section.
				$CON = $content;
			}
			else {// there's some problem with page, give user a chance to fix it
				$action = 'edit';
			}
		}
		elseif ($action == 'save' && !$preview) { // wrong password, give user another chance
			$error = $T_WRONG_PASSWORD;
			$action = 'edit';
		}
	}
	
	if ($action == 'edit' || $preview) {

		$has_permission = true;
		if ($is_enterprise) {
			$has_permission = wiki_get_write_acl($config['id_user'], $page);

		}
		if ($has_permission) {
			$CON_FORM_BEGIN = "<form action=\"$self_form\" method=\"post\"><input type=\"hidden\" name=\"action\" value=\"save\"/><input type=\"hidden\" name=\"last_changed\" value=\"$last_changed_ts\"/><input type=\"hidden\" name=\"showsource\" value=\"$showsource\"/><input type=\"hidden\" name=\"par\" value=\"".h($par)."\"/><input type=\"hidden\" name=\"page\" value=\"".h($page)."\"/>";
			$CON_FORM_END = '</form>';
			$CON_TEXTAREA = '<textarea class="contentTextarea" name="content" rows="27">'.h($CON).'</textarea>';
			$CON_PREVIEW = '<input class="submit" type="submit" name="preview" value="'.$T_PREVIEW.'"/>';
			
			if (!$showsource) {
				$CON_SUBMIT = '<input class="submit" type="submit" value="'.$T_DONE.'"/>';
				$EDIT_SUMMARY_TEXT = $T_EDIT_SUMMARY;
				$EDIT_SUMMARY = '<input type="text" name="esum" value="'.h($esum).'"/>';
				
				if (!authentified()) { // if not logged on, require password
					$FORM_PASSWORD = $T_PASSWORD;
					$FORM_PASSWORD_INPUT = '<input type="password" name="sc"/>';
				}
				
				if (!$par) {
					$RENAME_TEXT = $T_MOVE_TEXT;
					$RENAME_INPUT = '<input type="text" name="moveto" value="'.h($page).'"/>';
				}
			}
			
			if ($preview)
				$TITLE = "$T_PREVIEW: $page";
		} else {
			include "general/noaccess.php";
			exit;
		}
	}
	elseif ($action == 'history') { // show whole history of page
		for ($files = array(), $dir = @opendir("$HIST_DIR$page/"); $f = @readdir($dir);)
			if (substr($f, -4) == '.bak')
				$files[] = $f;
		
		rsort($files);
		$CON = '<form action="'.$self_form.'" method="post"><input type="hidden" name="action" value="diff"/><input type="hidden" name="page" value="'.h($page).'"/><input type="submit" class="submit" value="'.$T_DIFF.'"/><br/>';
		$meta = @fopen("$HIST_DIR$page/meta.dat", "rb");
	
		for ($i = 0, $mi = 1, $c = count($files); $i < $c; $i++) {
			if (($m = meta_getline($meta, $mi)) && !strcmp(basename($files[$i], ".bak"), $m[0]))
				$mi++;
			
			global $return_loadMetadata;
			$return_loadMetadata = '';
			plugin('loadMetadata',$page, $m);
			
			//Replace IP for the return of loadMetaData
			if (!empty($return_loadMetadata)) {
				$m[1] = $return_loadMetadata;
			}
			
			$CON .= '<input type="radio" name="f1" value="'.h($files[$i]).'"/><input type="radio" name="f2" value="'.h($files[$i]).'"/>';
			$CON .= "<a href=\"$self" . "page=".u($page)."&amp;action=rev&amp;f1=".$files[$i]."\">".rev_time($files[$i])."</a> - ($m[2] B) $m[1] <i>".h($m[3])."</i><br/>";
		}
		
		$CON .= '</form>';
	}
	elseif ($action == 'diff') {
		if (!$f1 && $dir = @opendir("$HIST_DIR$page/")) { // diff is made on two last revisions
			while ($f = @readdir($dir))
				if (substr($f, -4) == '.bak')
					$files[] = $f;
			
			rsort($files);
			
			error_show_lionwiki("Location:$self" . "action=diff&page=".u($page)."&f1=$files[0]&f2=$files[1]");
		}
		
		$r1 = "<a href=\"$self" . "page=".u($page)."&amp;action=rev&amp;f1=$f1\">".rev_time($f1)."</a>";
		$r2 = "<a href=\"$self" . "page=".u($page)."&amp;action=rev&amp;f1=$f2\">".rev_time($f2)."</a>";
		
		$CON = str_replace(array("{REVISION1}", "{REVISION2}"), array($r1, $r2), $T_REV_DIFF);
		$CON .= diff($f1, $f2);
	}
	elseif ($action == 'search') {
		for ($files = array(), $dir = opendir($PG_DIR); $f = readdir($dir);) {
			if (substr($f, -4) == '.txt' && ($c = @file_get_contents($PG_DIR . $f)) !== false) {
				if (!$query || stristr($f . $c, $query) !== false)
					$files[] = substr($f, 0, -4);
			}
		}
		
		sort($files);
		
		foreach ($files as $f)
			$list .= "<li><a href=\"$self" . "page=".u($f).'&amp;redirect=no">'.h($f)."</a></li>";
		
		$CON = "<ul>$list</ul>";
		
		if ($query && !file_exists("$PG_DIR$query.txt")) // offer to create the page
			$CON = "<p><i><a href=\"$self" . "action=edit&amp;page=".u($query)."\">$T_CREATE_PAGE ".h($query)."</a>.</i></p>".$CON;
		
		$TITLE = (!$query ? $T_LIST_OF_ALL_PAGES : "$T_SEARCH_RESULTS $query") . " (".count($files).")";
	}
	elseif ($action == 'recent') { // recent changes
		for ($files = array(), $dir = opendir($PG_DIR); $f = readdir($dir);)
			if (substr($f, -4) == '.txt')
				$files[substr($f, 0, -4)] = filemtime($PG_DIR . $f);
		
		arsort($files);
		
		foreach (array_slice($files, 0, 100) as $f => $ts) { // just first 100 files
			$dir_name = basename($f, '.txt');
			if ($meta = @fopen($HIST_DIR . $dir_name . '/meta.dat', 'r')) {
				$m = meta_getline($meta, 1);
				fclose($meta);
			}
			global $return_loadMetadata;
			$return_loadMetadata = '';
			plugin('loadMetadata',$dir_name, $m);
			
			//Replace IP for the return of loadMetaData
			if (!empty($return_loadMetadata)) {
				$m[1] = $return_loadMetadata;
			}
			
			$recent .= "<tr><td class=\"rc-diff\"><a href=\"$self" . "page=".u($f)."&amp;action=diff\">$T_DIFF</a></td><td class=\"rc-date\" nowrap>".date($DATE_FORMAT, $ts + $LOCAL_HOUR * 3600)."</td><td class=\"rc-ip\">$m[1]</td><td class=\"rc-page\"><a href=\"$self" . "page=".u($f)."&amp;redirect=no\">".h($f)."</a> <span class=\"rc-size\">($m[2] B)</span><i class=\"rc-esum\"> ".h($m[3])."</i></td></tr>";
		}
		
		$CON = "<table>$recent</table>";
		$TITLE = $T_RECENT_CHANGES;
	}
	
	elseif ($action == 'read_acl') { 

		$page_acl = get_parameter('page');

		$delete_acl_read = get_parameter('delete_acl_read', 0);
		$add_acl_read = get_parameter('add_acl_read', 0);
		
		$acl = '';
		
		if ($add_acl_read) {

			$new_user = get_parameter('new_user');
			
			$values = array();
			
			$exists = wiki_exists_page($page_acl);
		
			if ($exists) {

				$update = wiki_update_wiki($page_acl, $new_user, 'read');
				if ($update === false) {
					$acl .= "<h4>Error updating user</h4>";
				} else {
					$acl .= "<h4>User succesfully added</h4>";
				}
			} else {

				$insert = wiki_insert_wiki($page_acl, $new_user, 'read');
				if ($insert === false) {
					$acl .= "<h4>Error adding user</h4>";
				} else {
					$acl .= "<h4>User succesfully created</h4>";
				}
			}
		}
		
		if ($delete_acl_read) {
			
			$id_user = get_parameter('id_user');
	
			$result = wiki_delete_acl($id_user, $page_acl, 'read');
			
			if ($result === false) {
				$acl .= "<h4>Error deleting user</h4>";
			} else {
				$acl .= "<h4>User succesfully deleted</h4>";
			}
		}	
		
		$acl .= "<h3>$page_acl".print_help_tip (__("If a content has any users in its reading list, it will be accessible to all people. When we add an user to the access lists, only those users will have reading or writing permissions."), true)."</h3>";
		include_once('include/functions_db.php');
		include_once('include/functions_html.php');
		include_once('include/functions_user.php');
		
		$acls = wiki_get_acls ($page_acl);
		
		$table->id = 'acl_list';
		$table->class = 'listing';
		$table->width = '98%';
		$table->data = array ();
		$table->head = array ();
		
		$table->size[1] = '80px';
		
		$table->head[0] = __('Read');
		$table->head[1] = __('Operation');
			
		if ($acls === false) {
			$acl .= "<h4>No acls</h4>";
		} 
			
		$read = $acls['read_page'];
		
		if (!empty($read)) {
			$users_read = explode(',', $read);
			
			foreach ($users_read as $key=>$user) {
				$data = array();
				$data[0] = $user;
				$data[1] = '<a href="index.php?sec=wiki&
							sec2=operation/wiki/wiki&action=read_acl&
							delete_acl_read=1&page='.$page_acl.'&id_user='.$user.'"
							onClick="if (!confirm(\''.__('Are you sure?').'\'))
							return false;">
							<img src="images/cross.png" /></a>';
				array_push ($table->data, $data);
			}
		}
		
		$data = array();
		
		$params_assigned['input_id'] = 'text-new_user';
		$params_assigned['input_name'] = 'new_user';
		$params_assigned['input_value'] = '';
		$params_assigned['title'] = 'New user';
		$params_assigned['return'] = true;

		$url = "index.php?sec=wiki&sec2=operation/wiki/wiki&action=read_acl&add_acl_read=1&page=$page_acl";

		$data[0] = "<form name=adduser method=post action='" . $url . "'>";
		$data[0] .= user_print_autocomplete_input($params_assigned);
		$data[1] = print_input_image("add_user", "images/add.png", 1, '', true);
		$data[1] .= "</form>";
						
		array_push ($table->data, $data);
		
		$acl .= print_table($table, true);
			

		$CON = "<table>$acl</table>";
		$TITLE = "READ ACL";
	}
	elseif ($action == 'write_acl') { 

		$page_acl = get_parameter('page');

		$delete_acl_write = get_parameter('delete_acl_write', 0);
		$add_acl_write = get_parameter('add_acl_write', 0);

		$new_user = get_parameter('new_user', '');
		
		$acl = '';
		
		if ($add_acl_write) {

			$new_user = get_parameter('new_user');
			
			$values = array();
			
			$exists = wiki_exists_page($page_acl);
		
			if ($exists) {

				$update = wiki_update_wiki($page_acl, $new_user, 'write');
				
				if ($update === false) {
					$acl .= "<h4>Error updating user</h4>";
				} else {
					$acl .= "<h4>User succesfully added</h4>";
				}
			} else {

				$insert = wiki_insert_wiki($page_acl, $new_user, 'write');
				
				if ($insert === false) {
					$acl .= "<h4>Error adding user</h4>";
				} else {
					$acl .= "<h4>User succesfully created</h4>";
				}
			}
		}
		
		if ($delete_acl_write) {
			
			$id_user = get_parameter('id_user');

			$result = wiki_delete_acl($id_user, $page_acl, 'write');
			
			if ($result === false) {
				$acl .= "<h4>Error deleting user</h4>";
			} else {
				$acl .= "<h4>User succesfully deleted</h4>";
			}
		}	
		
		$acl .= "<h3>$page_acl".print_help_tip (__("If a content has any users in its writing list, it will be accessible to all people. When we add an user to the access lists, only those users will have reading or writing permissions."), true)."</h3>";
		include_once('include/functions_db.php');
		include_once('include/functions_html.php');
		include_once('include/functions_user.php');
		
		$acls = wiki_get_acls ($page_acl);
		
		$table->id = 'acl_list';
		$table->class = 'listing';
		$table->width = '98%';
		$table->data = array ();
		$table->head = array ();
		
		$table->size[1] = '80px';
		
		$table->head[0] = __('Write');
		$table->head[1] = __('Operation');
			
		if ($acls === false) {
			$acl .= "<h4>No acls</h4>";
		} 
			
		$read = $acls['write_page'];
		
		if (!empty($read)) {
			$users_read = explode(',', $read);
			
			foreach ($users_read as $key=>$user) {
				$data = array();
				$data[0] = $user;
				$data[1] = '<a href="index.php?sec=wiki&
							sec2=operation/wiki/wiki&action=write_acl&
							delete_acl_write=1&page='.$page_acl.'&id_user='.$user.'"
							onClick="if (!confirm(\''.__('Are you sure?').'\'))
							return false;">
							<img src="images/cross.png" /></a>';
				array_push ($table->data, $data);
			}
		}

		$data = array();
		$params_assigned['input_id'] = 'text-new_user';
		$params_assigned['input_name'] = 'new_user';
		$params_assigned['input_value'] = '';
		$params_assigned['title'] = 'New user';
		$params_assigned['return'] = true;

		$url = "index.php?sec=wiki&sec2=operation/wiki/wiki&action=write_acl&add_acl_write=1&page=$page_acl";

		$data[0] = "<form id='form-adduser' name=adduser method=post action='" . $url . "'>";
		$data[0] .= user_print_autocomplete_input($params_assigned);
		$data[1] = print_input_image("add_user", "images/add.png", 1, '', true);
		$data[1] .= "</form>";
						
		array_push ($table->data, $data);
		
		$acl .= print_table($table, true);
			

		$CON = "<table>$acl</table>";
		$TITLE = "WRITE ACL";
	}
	else {
		if (!plugin('check_no_action', $action)) { //Check to block or not a action
			plugin('action', $action);
		}
		else {
			if (!plugin('show_message_no_save')) {
				$action = 'view-html';
			}
			else {
				$action = 'view-html';
			}
		}
	}
	
	if (!$action || $action == 'save' || $preview) { // page parsing
		if (preg_match("/(?<!\^)\{title:([^}\n]*)\}/U", $CON, $m)) { // Change page title
			$TITLE = $m[1];
			$CON = str_replace($m[0], "", $CON);
		}
		
		// subpages
		while (preg_match('/(?<!\^){include:([^}]+)}/Um', $CON, $m))
			if (!strcmp($m[1], $page)) // limited recursion protection
				$CON = str_replace($m[0], "'''Warning: subpage recursion!'''", $CON);
			elseif (file_exists("$PG_DIR$m[1].txt"))
				$CON = str_replace($m[0], file_get_contents("$PG_DIR$m[1].txt"), $CON);
			else
				$CON = str_replace($m[0], "'''Warning: subpage $m[1] was not found!'''", $CON);
		
		plugin('subPagesLoaded');
		
		// save content not intended for substitutions ({html} tag)
		if (!$NO_HTML) { // XSS protection
			preg_match_all("/(?<!\^)\{html\}(.+)\{\/html\}/Ums", $CON, $htmlcodes, PREG_PATTERN_ORDER);
			$CON = preg_replace("/(?<!\^)\{html\}.+\{\/html\}/Ums", "{HTML}", $CON);
		}
		
		$CON = preg_replace("/(?<!\^)<!--.*-->/U", "", $CON); // internal comments
		$CON = preg_replace("/\^(.)/e", "'&#'.ord('$1').';'", $CON);
		$CON = str_replace(array("<", "&"), array("&lt;", "&amp;"), $CON);
		$CON = preg_replace("/&amp;([a-z]+;|\#[0-9]+;)/U", "&$1", $CON); // keep HTML entities
		$CON = preg_replace("/(\r\n|\r)/", "\n", $CON); // unifying newlines to Unix ones
		
		preg_match_all("/{{(.+)}}/Ums", $CON, $codes, PREG_PATTERN_ORDER);
		$CON = preg_replace("/{{(.+)}}/Ums", "<pre>{CODE}</pre>", $CON);
		
		// spans
		preg_match_all("/\{([\.#][^\s\"\}]*)(\s([^\}\"]*))?\}/m", $CON, $spans, PREG_SET_ORDER);
		
		foreach ($spans as $m) {
			$class = $id = '';
			$parts = preg_split('/([\.#])/', $m[1], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			
			for ($i = 0, $c = count($parts); $c > 1 && $i < $c; $i += 2)
				if ($parts[$i] == '.')
					$class .= $parts[$i + 1] . ' ';
				else
					$id = $parts[$i + 1];
			
			$CON = str_replace($m[0], '<span'.($id ? " id=\"$id\"" : '').($class ? " class=\"$class\"" : '').($m[3] ? " style=\"$m[3]\"" : '').'>', $CON);
		}
		
		$CON = str_replace('{/}', '</span>', $CON);
		
		plugin('formatBegin');
		
		$CON = strtr($CON, array('&lt;-->' => '&harr;', '-->' => '&rarr;', '&lt;--' => '&larr;', "(c)" => '&copy;', "(r)" => '&reg;'));
		$CON = preg_replace("/\{small\}(.*)\{\/small\}/U", "<small>$1</small>", $CON); // small
		$CON = preg_replace("/\{su([bp])\}(.*)\{\/su([bp])\}/U", "<su$1>$2</su$3>", $CON); // sup and sub
		
		$CON = preg_replace("/^([^!\*#\n][^\n]+)$/Um", '<p>$1</p>', $CON); // paragraphs
		
		// images
		preg_match_all("#\[((https?://|\./)[^|\]]+\.(jpeg|jpg|gif|png))(\|[^\]]+)?\]#", $CON, $imgs, PREG_SET_ORDER);
		
		foreach ($imgs as $img) {
			$link = $i_attr = $a_attr = $center = $tag = "";
			
			preg_match_all("/\|([^\]\|=]+)(=([^\]\|\"]+))?(?=[\]\|])/", $img[0], $options, PREG_SET_ORDER);
			
			foreach ($options as $o)
				if ($o[1] == 'center') $center = true;
				elseif ($o[1] == 'right' || $o[1] == 'left') $i_attr .= " style=\"float:$o[1]\"";
				elseif ($o[1] == 'link') $link = (substr($o[3], 0, 4) == "http" || substr($o[3], 0, 2) == "./") ? $o[3] : $self . "page=" . u($o[3]);
				elseif ($o[1] == 'alt') $i_attr .= " alt=\"$o[3]\"";
				elseif ($o[1] == 'title') $a_attr .= " title=\"$o[3]\"";
			
			$tag = "<img src=\"$img[1]\"$i_attr/>";
			
			if ($link) $tag = "<a href=\"$link\"$a_attr>$tag</a>";
			if ($center) $tag = "<div style=\"text-align:center\">$tag</div>";
			
			$CON = str_replace($img[0], $tag, $CON);
		}
		
		$CON = preg_replace('#([0-9a-zA-Z\./~\-_]+@[0-9a-z/~\-_]+\.[0-9a-z\./~\-_]+)#i', '<a href="mailto:$0">$0</a>', $CON); // mail recognition
		
		// links
		$CON = preg_replace("#\[([^\]\|]+)\|(\./([^\]]+)|(https?://[^\]]+))\]#U", '<a href="$2" class="external">$1</a>', $CON);
		$CON = preg_replace("#(?<!\")https?://[0-9a-zA-Z\.\#/~\-_%=\?\&,\+\:@;!\(\)\*\$']*#i", '<a href="$0" class="external">$0</a>', $CON);
		
		preg_match_all("/\[(?:([^|\]]+)\|)?([^\]#]+)(?:#([^\]]+))?\]/", $CON, $matches, PREG_SET_ORDER); // matching Wiki links
		
		foreach ($matches as $m) {
			$m[1] = $m[1] ? $m[1] : $m[2]; // is page label same as its name?
			$m[3] = $m[3] ? '#'.u(preg_replace('/[^\da-z]/i', '_', $m[3])) : ''; // anchor
			
			$attr = file_exists("$PG_DIR$m[2].txt") ? $m[3] : '&amp;action=edit" class="pending';
			$CON = str_replace($m[0], '<a href="'.$self.'page='.u($m[2]).$attr.'">'.$m[1].'</a>', $CON);
		}
		
		for ($i = 10; $i >= 1; $i--) { // Lists, ordered, unordered
			$CON = preg_replace('/^'.str_repeat('\*', $i)."(.*)(\n?)/m", str_repeat('<ul>', $i).'<li>$1</li>'.str_repeat('</ul>', $i).'$2', $CON);
			$CON = preg_replace('/^'.str_repeat('\#', $i)."(.*)(\n?)/m", str_repeat('<ol>', $i).'<li>$1</li>'.str_repeat('</ol>', $i).'$2', $CON);
			$CON = preg_replace("#(</ol>\n?<ol>|</ul>\n?<ul>)#", '', $CON);
		}
		
		// headings
		preg_match_all('/^(!+)(.*)$/m', $CON, $matches, PREG_SET_ORDER);
		$stack = array();
		
		for ($h_id = max($par, 1), $i = 0, $c = count($matches); $i < $c && $m = $matches[$i]; $i++, $h_id++) {
			$excl = strlen($m[1]) + 1;
			$hash = preg_replace('/[^\da-z]/i', '_', $m[2]);
			
			for ($ret = ''; end($stack) >= $excl; $ret .= '</div>', array_pop($stack));
			
			$stack[] = $excl;
			
			$ret .= "<div class=\"par-div\" id=\"par-$h_id\"><h$excl id=\"$hash\">$m[2]";

			if ($is_enterprise) {
				if (wiki_get_write_acl ($config['id_user'], $page)) { 
					$ret .= "<span class=\"par-edit\">(<a href=\"$self" . "action=edit&amp;page=".u($page)."&amp;par=$h_id\">$T_EDIT</a>)</span>";
				}
			} else {
				if (is_writable($PG_DIR . $page . '.txt')) {
					$ret .= "<span class=\"par-edit\">(<a href=\"$self" . "action=edit&amp;page=".u($page)."&amp;par=$h_id\">$T_EDIT</a>)</span>";
				}
			}
	
			$CON = preg_replace('/' . preg_quote($m[0], '/') . '/', "$ret</h$excl>", $CON, 1);
			$TOC .= str_repeat("<ul>", $excl - 2).'<li><a href="'.$self.'page='.u($page).'#'.u($hash).'">'.$m[2].'</a></li>'.str_repeat("</ul>", $excl - 2);
		}

	
		$CON .= str_repeat('</div>', count($stack));
		
		$TOC = '<ul id="toc">' . preg_replace(array_fill(0, 5, "#</ul>\n*<ul>#"), array_fill(0, 5, ''), $TOC) . '</ul>';
		$TOC = str_replace(array('</li><ul>', '</ul><li>', '</ul></ul>', '<ul><ul>'), array('<ul>', '</ul></li><li>', '</ul></li></ul>', '<ul><li><ul>'), $TOC);
		
		$CON = preg_replace("/'--(.*)--'/Um", '<del>$1</del>', $CON); // strikethrough
		$CON = preg_replace("/'__(.*)__'/Um", '<u>$1</u>', $CON); // underlining
		$CON = preg_replace("/'''(.*)'''/Um", '<strong>$1</strong>', $CON); // bold
		$CON = preg_replace("/''(.*)''/Um", '<em>$1</em>', $CON); // italic
		$CON = str_replace('{br}', '<br style="clear:both"/>', $CON); // new line
		$CON = preg_replace('/-----*/', '<hr/>', $CON); // horizontal line
		$CON = str_replace('--', '&mdash;', $CON); // --
		
		$CON = preg_replace(array_fill(0, count($codes[1]) + 1, '/{CODE}/'), $codes[1], $CON, 1); // put HTML and "normal" codes back
		$CON = preg_replace(array_fill(0, count($htmlcodes[1]) + 1, '/{HTML}/'), $htmlcodes[1], $CON, 1);
		
		plugin('formatEnd');
	}
	
	plugin('formatFinished');
	
	// Loading template. If does not exist, use built-in default
	$html = file_exists($TEMPLATE) ?
		file_get_contents(clear_path($TEMPLATE)) : $fallback_template;
	
	// including pages in pure HTML
	while (preg_match('/{include:([^}]+)}/U', $html, $m)) {
		$inc = str_replace(array('{html}', '{/html}'), '', @file_get_contents("$PG_DIR$m[1].txt"));
		$html = str_replace($m[0], $inc, $html);
	}
	
	plugin('template'); // plugin templating
	
	
	$html = preg_replace('/\{([^}]* )?plugin:.+( [^}]*)?\}/U', '', $html); // get rid of absent plugin tags
	
	$has_perm = true;
	if ($is_enterprise) {
		$has_perm = wiki_get_write_acl($config['id_user'], $page); //if user has write permissions, he can read
		if (!$has_perm) {
			$has_perm = wiki_get_read_acl($config['id_user'], $page);
		}
	}
	
	if (!$has_perm) {
		$CON = __("You can't access this page");
	}
	
	$tpl_subs = array(
		'HEAD' => $HEAD . ($action ? '<meta name="robots" content="noindex, nofollow"/>' : ''),
		'SEARCH_FORM' => '<form action="'.$self_form.'" method="post"><span><input type="hidden" name="action" value="search"/><input type="submit" style="display:none;"/>',
		'\/SEARCH_FORM' => "</span></form>",
		'SEARCH_INPUT' => '<input type="text" name="query" value="'.h($query).'"/>',
		'SEARCH_SUBMIT' => "<input class=\"submit\" type=\"submit\" value=\"$T_SEARCH\"/>",
		'HOME' => "<a href=\"$self" . "page=".u($START_PAGE)."\">$T_HOME</a>",
		'RECENT_CHANGES' => "<a href=\"$self" . "action=recent\">$T_RECENT_CHANGES</a>",
		'READ' => "<a href=\"$self" . "page=".u($page). "&amp;action=read_acl\">Read ACL</a>",
		'WRITE' => "<a href=\"$self" . "page=".u($page). "&amp;action=write_acl\">Write ACL</a>",
		'ERROR' => $error,
		'HISTORY' => $page ? "<a href=\"$self" . "page=".u($page)."&amp;action=history\">$T_HISTORY</a>" : "",
		'PAGE_TITLE' => h($page == $START_PAGE && $page == $TITLE ? $WIKI_TITLE : $TITLE),
		'PAGE_TITLE_HEAD' => h($TITLE),
		'PAGE_URL' => u($page),
		'EDIT' => !$action?
			("<a href=\"$self" . "page=".u($page)."&amp;action=edit" . 
				(is_writable("$PG_DIR$page.txt") ? "\">$T_EDIT</a>" : "&amp;showsource=1\">$T_SHOW_SOURCE</a>"))
			: "",
		'WIKI_TITLE' => h($WIKI_TITLE),
		'LAST_CHANGED_TEXT' => $last_changed_ts ? $T_LAST_CHANGED : "",
		'LAST_CHANGED' => $last_changed_ts ? date($DATE_FORMAT, $last_changed_ts + $LOCAL_HOUR * 3600) : "",
		'CONTENT' => $action != "edit" ? $CON : "",
		'TOC' => $TOC,
		'SYNTAX' => $action == "edit" || $preview ? "<a href=\"$SYNTAX_PAGE\">$T_SYNTAX</a>" : "",
		'SHOW_PAGE' => $action == "edit" || $preview ? "<a href=\"$self" . "page=".u($page)."\">$T_SHOW_PAGE</a>" : "",
		'COOKIE' => '<a href="'.$self.'page='.u($page).'&amp;action='.u($action).'&amp;erasecookie=1">'.$T_ERASE_COOKIE.'</a>',
		'CONTENT_FORM' => $CON_FORM_BEGIN,
		'\/CONTENT_FORM' => $CON_FORM_END,
		'CONTENT_TEXTAREA' => $CON_TEXTAREA,
		'CONTENT_SUBMIT' => $CON_SUBMIT,
		'CONTENT_PREVIEW' => $CON_PREVIEW,
		'RENAME_TEXT' => $RENAME_TEXT,
		'RENAME_INPUT' => $RENAME_INPUT,
		'EDIT_SUMMARY_TEXT' => $EDIT_SUMMARY_TEXT,
		'EDIT_SUMMARY_INPUT' => $EDIT_SUMMARY,
		'FORM_PASSWORD' => $FORM_PASSWORD,
		'FORM_PASSWORD_INPUT' => $FORM_PASSWORD_INPUT
	);
	
	if ($action == '') {
		$tpl_subs['SYNTAX'] = "<a href=\"$SYNTAX_PAGE\">$T_SYNTAX</a>";
	}
	
	
	foreach ($tpl_subs as $tpl => $rpl) // substituting values

		$html = template_replace($tpl, $rpl, $html);
	
	header_lionwiki('Content-type: text/html; charset=UTF-8');
	
	echo($html);

}

// Function library
function h($t) { return htmlspecialchars($t); }
function u($t) { return urlencode($t); }

function template_replace($what, $subs, $where) { return preg_replace("/\{(([^}{]*) )?$what( ([^}]*))?\}/U", empty($subs) ? "" : "\${2}".str_replace("$", "&#36;", trim($subs))."\${4}", $where); }
function template_match($what, $where, &$dest) { return preg_match("/\{(([^}{]*) )?$what( ([^}]*))?\}/U", $where, $dest); }

function clear_path($s) {
	for($i = 0, $ret = "", $c = strlen($s); $i < $c; $i++)
		$ret .= ctype_cntrl($s[$i]) ? "" : $s[$i];

	return trim(str_replace("..", "", $ret), "/");
}

function rev_time($time) {
	preg_match('/(\d{4})(\d{2})(\d{2})-(\d{2})(\d{2})-(\d{2})/U', $time, $m);

	return date($GLOBALS['DATE_FORMAT'], mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]));
}

// get paragraph number $par_id.
function get_paragraph($text, $par_id) {
	$par = array(); // paragraph
	$count = 1; // paragraph count
	$par_excl = 0; // number of !
	$inside_code = $inside_html = false; // exclamation marks inside {{}} and {html}{/html} are not headings
	$lines = explode("\n", $text);

	foreach($lines as $l) {
		if($l[0] == '!' && !$inside_html && !$inside_code) {
			for($excl = 1, $c = strlen($l); $excl < $c && $l[$excl] == '!'; $excl++);

			if($count == $par_id) {
				$par[] = $l;
				$par_excl = $excl;
			} elseif($par_excl)
				if($excl > $par_excl)
					$par[] = $l;
				else
					break;

			$count++;
		}
		elseif($par_excl)
			$par[] = $l;

		if(preg_match('/(?<!\^)\{html\}/', $l)) $inside_html = true;
		if(preg_match('/(?<!\^)\{\/html\}/', $l)) $inside_html = false;
		if(preg_match('/(?<!\^)\{\{/', $l)) $inside_code = true;
		if(preg_match('/(?<!\^)\}\}/', $l)) $inside_code = false;
	}

	return join("\n", $par);
}

function diff($f1, $f2) { // executes either builtin simple diff or complex diff plugin, if present ...
	list($f1, $f2) = array(min($f1, $f2), max($f1, $f2));

	$dir = $GLOBALS['HIST_DIR'] . $GLOBALS['page'] . '/';

	return plugin('diff', $dir.$f1, $dir.$f2) ? $GLOBALS['plugin_ret_diff'] : diff_builtin($dir.$f1, $dir.$f2);
}

function diff_builtin($f1, $f2) {
	$a1 = explode("\n", @file_get_contents($f1));
	$a2 = explode("\n", @file_get_contents($f2));

	$d1 = array_diff($a1, $a2);
	$d2 = array_diff($a2, $a1);

	for($i = 0, $ret = ''; $i <= max(count($a2), count($a1)); $i++) {
		if($r1 = array_key_exists($i, $d1)) $ret .= '<del>'.h(trim($d1[$i]))."</del>\n";
		if($r2 = array_key_exists($i, $d2)) $ret .= '<ins>'.h(trim($d2[$i]))."</ins>\n";
		if(!$r1 && !$r2) $ret .= h(trim($a2[$i]))."\n";
	}

	return "<pre id=\"diff\">$ret</pre>";
}

function authentified() {
	/*
	if(!$GLOBALS['PASSWORD'] || !strcasecmp($_COOKIE['LW_AUT'], $GLOBALS['PASSWORD']) || !strcasecmp(sha1($GLOBALS['sc']), $GLOBALS['PASSWORD'])) {
		setsafecookie('LW_AUT', $GLOBALS['PASSWORD'], time() + ($GLOBALS['PROTECTED_READ'] ? 4 * 3600 : 365 * 86400));
		return true;
	} else
		return false;
	*/
		
	//BUT YOU CAN FILL WITH OTHER CODE FOR EXAMPLE GET THE CONF FROM DB
	//foo()
	
	//At the moment true
	return true;
}

function setsafecookie() { // setcookie for sensitive informations
	$args = func_get_args();

	if(version_compare(PHP_VERSION, '5.2.0') >= 0) {
		while(count($args) != 6)
			$args[] = '';

		$args[] = true; // httponly, supported only in some browsers and PHP >= 5.2.0. Successfully prevents XSS attacks.
	}

	call_user_func_array('setcookie', $args);
}

// returns "line" from meta.dat files. $lnum is number of line from the end of file starting with 1
function meta_getline($file, $lnum) {
	if(fseek($file, -($lnum * 175), SEEK_END) || !($line = fread($file, 175)) || $line[0] != "!")
		return false; // ! is control character

	$date = substr($line, 1, 16);
	$ip = trim(substr($line, 19, 15));
	$size = (int) substr($line, 35, 10);
	$esum = trim(substr($line, 45, 128));

	return array($date, $ip, $size, $esum);
}

// Call a method for all plugins, second to last arguments are forwarded to plugins as arguments
function plugin($method) {
	global $action;
	
	
	$ret = false;
	$args = array_slice(func_get_args(), 1);
	
	foreach($GLOBALS['plugins'] as $idx => $plugin) {
		$ret |= method_exists($GLOBALS['plugins'][$idx], $method) && call_user_func_array(array(&$GLOBALS['plugins'][$idx], $method), $args);
	}

	return $ret; // returns true if treated by a plugin
}
?>

<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>

<script type="text/javascript">
	
	var idUser = "<?php echo $config['id_user'] ?>";
	
	$(document).ready (function () {
		bindAutocomplete ("#text-new_user", idUser);	
	});
	// form-adduser
	validate_user ("#form-adduser", "#text-new_user", "<?php echo __('Invalid user')?>");
</script>
