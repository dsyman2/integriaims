<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

class Home {
	private $global_search = '';
	
	function __construct() {
		$this->global_search = '';
	}
	
	public function show($error = null) {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		// Header
		$logo = "<img src='../images/integria_logo_header.png' style='border:0px;' alt='Home' >";
		$title = "<div style='text-align:center;'>$logo</div>";
		$left_button = $ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Exit'),
					'href' => 'index.php?action=logout'
					)
				);
		$ui->createHeader($title, $left_button, null, "logo");
		$ui->showFooter();
		$ui->beginContent();
			//List of buttons
			// Workunits
			$options = array('icon' => 'star',
					'pos' => 'right',
					'text' => __('Workunits'),
					'href' => 'index.php?page=workunit');
			$ui->contentAddHtml($ui->createButton($options));
			// Workorders
			$options = array('icon' => 'info',
					'pos' => 'right',
					'text' => __('Workorders'),
					'href' => 'index.php?page=workorders&filter_status=0&filter_owner='.$system->getConfig('id_user'));
			$ui->contentAddHtml($ui->createButton($options));
			// Incidents
			$options = array('icon' => 'alert',
					'pos' => 'right',
					'text' => __('Incidents'),
					'href' => 'index.php?page=incidents');
			$ui->contentAddHtml($ui->createButton($options));
			
			if (! empty($error)) {
				$options = array(
					'popup_id' => 'error_popup'
					);
				$ui->addWarningPopup($options);
				$ui->contentAddHtml("<script type=\"text/javascript\">
										$(document).on('pageshow', function() {
											$(\"#error_popup\").popup(\"open\");
										});
									</script>");
			}
			
		$ui->endContent();
		$ui->showPage();
		return;
	}
}
?>
