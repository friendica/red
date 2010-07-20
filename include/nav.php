
<?php

if(x($_SESSION['uid'])) {
		$a->page['nav'] .= "<a id=\"nav-logout-link\" class=\"nav-link\" href=\"logout\">Logout</a>\r\n";
}

	$a->page['nav'] .= "<span id=\"nav-link-wrapper\" >\r\n";

	if(($a->module != 'home') && (! (x($_SESSION['uid']))))
		$a->page['nav'] .= "<a id=\"nav-home-link\" class=\"nav-commlink\" href=\"\">Home</a>\r\n";
	
	$a->page['nav'] .= "<a id=\"nav-directory-link\" class=\"nav-link\" href=\"directory\">Site Directory</a>\r\n";

	if(x($_SESSION,'uid')) {

		$a->page['nav'] .= "<a id=\"nav-network-link\" class=\"nav-commlink\" href=\"network\">Network</a><span id=\"net-update\" class=\"nav-ajax-left\"></span>\r\n";

		$a->page['nav'] .= "<a id=\"nav-home-link\" class=\"nav-commlink\" href=\"profile/{$a->user['nickname']}\">Home</a><span id=\"home-update\" class=\"nav-ajax-left\"></span>\r\n";

		$a->page['nav'] .= "<a id=\"nav-notify-link\" class=\"nav-commlink\" href=\"notifications\">Notifications</a><span id=\"notify-update\" class=\"nav-ajax-left\"></span>\r\n";

		$a->page['nav'] .= "<a id=\"nav-messages-link\" class=\"nav-commlink\" href=\"Messages\">Messages</a><span id=\"mail-update\" class=\"nav-ajax-left\"></span>\r\n";
		


		$a->page['nav'] .= "<a id=\"nav-settings-link\" class=\"nav-link\" href=\"settings\">Settings</a>\r\n";

		$a->page['nav'] .= "<a id=\"nav-profiles-link\" class=\"nav-link\" href=\"profiles\">Profiles</a>\r\n";

		$a->page['nav'] .= "<a id=\"nav-contacts-link\" class=\"nav-link\" href=\"contacts\">Contacts</a>\r\n";


		
	}

	$a->page['nav'] .= "</span>\r\n<span id=\"nav-end\"></span>\r\n";
