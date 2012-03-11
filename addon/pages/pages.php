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

function pages_page_end($a,&$b) {
	if (($a->module != "network") OR ($a->user['uid'] == 0))
		return;

	$pages = '<div id="pages-sidebar" class="widget"><h3>'.t("Community").'</h3><ul>';
	$contacts = q("SELECT `contact`.`id`, `contact`.`url`, `contact`.`Name` FROM `contact`, `user` 
			WHERE `network`= 'dfrn' AND `duplex` 
			AND `contact`.`nick`=`user`.`nickname`
			AND `user`.`page-flags`= %d
			AND `contact`.`uid` = %d",
			intval(PAGE_COMMUNITY),
			intval($a->user['uid']));
	foreach($contacts as $contact) {
		$pages .= '<li class="tool"><a href="'.$contact["url"].'">'.$contact["Name"]."</a></li>";
	}
	$pages .= "</ul>";
	if (sizeof($contacts) > 0)
		$a->page['aside'] = $pages.$a->page['aside'];

}

?>
