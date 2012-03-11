<?php
/**
 * Name: Pages
 * Description: Shows lists of community pages
 * Version: 1.0
 * Author: Michael Vogel <ike@piratenpartei.de>
 *
 */

function pages_install() {
	register_hook('page_end', 'addon/pages/pages.php', 'pages_page_end');
}

function pages_uninstall() {
	unregister_hook('page_end', 'addon/pages/pages.php', 'pages_page_end');
}

function pages_iscommunity($url, &$pagelist) {
	// check every week for the status - should be enough
	if ($pagelist[$url]["checked"]<time()-86400*7) {
		// When too old or not found fetch the status from the profile
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);
 
		$page = curl_exec($ch);
 
		curl_close($ch);

		$iscommunity = (strpos($page, '<meta name="friendika.community" content="true" />') != 0);

		$pagelist[$url] = array("community" => $iscommunity, "checked" => time());
	} else // Fetch from cache
		$iscommunity = $pagelist[$url]["community"];
	return($iscommunity);
}

function pages_getpages($uid) {

	// Fetch cached pagelist from configuration
	$pagelist = get_pconfig($uid,'pages','pagelist');

	if (sizeof($pagelist) == 0)
		$pagelist = array();

	$contacts = q("SELECT `id`, `url`, `Name` FROM `contact`
			WHERE `network`= 'dfrn' AND `uid` = %d",
			intval($uid));

	$pages = array();

	// Look if the profile is a community page
	foreach($contacts as $contact) {
		if (pages_iscommunity($contact["url"], $pagelist))
			$pages[] = array("url"=>$contact["url"], "Name"=>$contact["Name"], "id"=>$contact["id"]);
	}

	// Write back cached pagelist
	set_pconfig($uid,'pages','pagelist', $pagelist);
	return($pages);
}

function pages_page_end($a,&$b) {
	// Only move on if if it's the "network" module and there is a logged on user
	if (($a->module != "network") OR ($a->user['uid'] == 0))
		return;

	$pages = '<div id="pages-sidebar" class="widget">
			<div class="title tool">
			<h3>'.t("Community").'</h3></div>
			<div id="sidebar-pages-list"><ul>';

	$contacts = pages_getpages($a->user['uid']);

	foreach($contacts as $contact) {
		$pages .= '<li class="tool"><a href="'.$a->get_baseurl().'/redir/'.$contact["id"].'" class="label" target="external-link">'.
				$contact["Name"]."</a></li>";
	}
	$pages .= "</ul></div></div>";
	if (sizeof($contacts) > 0)
		$a->page['aside'] = $pages.$a->page['aside'];
}
?>
