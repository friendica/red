<?php

/**
 * Module: update_profile
 * Purpose: AJAX synchronisation of profile page
 *
 */


require_once('mod/channel.php');

function update_channel_content(&$a) {

	$profile_uid = intval($_GET['p']);
	$load = (((argc() > 1) && (argv(1) == 'load')) ? 1 : 0);

	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";

	/**
	 * We can remove this hack once Internet Explorer recognises HTML5 natively
	 */

	echo (($_GET['msie'] == 1) ? '<div>' : '<section>');

	/**
	 *
	 * Grab the page inner contents by calling the content function from the profile module directly, 
	 * but move any image src attributes to another attribute name. This is because 
	 * some browsers will prefetch all the images for the page even if we don't need them.
	 * The only ones we need to fetch are those for new page additions, which we'll discover
	 * on the client side and then swap the image back.
	 *
	 */

	$text = channel_content($a,$profile_uid,$load);

	$pattern = "/<img([^>]*) src=\"([^\"]*)\"/";
	$replace = "<img\${1} dst=\"\${2}\"";
	$text = preg_replace($pattern, $replace, $text);

	if(! $load) {
		$replace = '<br />' . t('[Embedded content - reload page to view]') . '<br />';
		$pattern = "/<\s*audio[^>]*>(.*?)<\s*\/\s*audio>/i";
		$text = preg_replace($pattern, $replace, $text);
		$pattern = "/<\s*video[^>]*>(.*?)<\s*\/\s*video>/i";
		$text = preg_replace($pattern, $replace, $text);
		$pattern = "/<\s*embed[^>]*>(.*?)<\s*\/\s*embed>/i";
		$text = preg_replace($pattern, $replace, $text);
    	$pattern = "/<\s*iframe[^>]*>(.*?)<\s*\/\s*iframe>/i";
    	$text = preg_replace($pattern, $replace, $text);
	}

	/**
	 * reportedly some versions of MSIE don't handle tabs in XMLHttpRequest documents very well
	 */

	echo str_replace("\t",'       ',$text);
	echo (($_GET['msie'] == 1) ? '</div>' : '</section>');
	echo "</body></html>\r\n";
	killme();

}