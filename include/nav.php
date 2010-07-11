
<?php
	$a->page['nav'] .= "<span id=\"nav-link-wrapper\" >\r\n";

	if(($a->module != 'home') && (! (x($_SESSION['uid']))))
		$a->page['nav'] .= "<a id=\"nav-home-link\" class=\"nav-commlink\" href=\"\">Home</a>\r\n";
	
	$a->page['nav'] .= "<a id=\"nav-directory-link\" class=\"nav-commlink\" href=\"directory\">Site Directory</a>\r\n";

	if(x($_SESSION,'uid')) {

		$a->page['nav'] .= "<a id=\"nav-notify-link\" class=\"nav-commlink\" href=\"notifications\">Notifications</a>\r\n";

		$a->page['nav'] .= "<a id=\"nav-messages-link\" class=\"nav-commlink\" href=\"Messages\">Messages</a>\r\n";


		$a->page['nav'] .= "<a id=\"nav-logout-link\" class=\"nav-link\" href=\"logout\">Logout</a>\r\n";

		$a->page['nav'] .= "<a id=\"nav-settings-link\" class=\"nav-link\" href=\"settings\">Settings</a>\r\n";

		$a->page['nav'] .= "<a id=\"nav-profiles-link\" class=\"nav-link\" href=\"profiles\">Profiles</a>\r\n";

		$a->page['nav'] .= "<a id=\"nav-contacts-link\" class=\"nav-link\" href=\"contacts\">Contacts</a>\r\n";

		$a->page['nav'] .= "<a id=\"nav-home-link\" class=\"nav-link\" href=\"profile/{$_SESSION['uid']}\">Home</a>\r\n";
		
	}

	$a->page['nav'] .= "</span>\r\n<span id=\"nav-end\"></span>\r\n";
