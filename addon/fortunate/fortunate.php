<?php
/**
 * Name: Fortunate
 * Description: Add a random fortune cookie at the bottom of every pages.
 * Version: 1.0
 * Author: Mike Macgirvin <http://macgirvin.com/profile/mike>
 */


function fortunate_install() {
	register_hook('page_end', 'addon/fortunate/fortunate.php', 'fortunate_fetch');
}

function fortunate_uninstall() {
	unregister_hook('page_end', 'addon/fortunate/fortunate.php', 'fortunate_fetch');
}


function fortunate_fetch($a,&$b) {

	$a->page['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="' 
		. $a->get_baseurl() . '/addon/fortunate/fortunate.css' . '" media="all" />' . "\r\n";

	$s = fetch_url('http://fortunemod.com/cookie.php?numlines=2&equal=1&rand=' . mt_rand());
	$b .= '<div class="fortunate">' . $s . '</div>';
}

