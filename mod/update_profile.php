<?php

// This page is fetched via ajax to update the profile page with
// new content while you are viewing it.

require_once('mod/profile.php');

function update_profile_content(&$a) {

	$profile_uid = intval($_GET['p']);

	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";
	echo (($_GET['msie'] == 1) ? '<div>' : '<section>');

        // Grab the page inner contents, but move any image src attributes to another attribute name.
        // Some browsers will prefetch all the images for the page even if we don't need them.
        // The only ones we need to fetch are those for new page additions, which we'll discover
        // on the client side and then swap the image back.

        $text = profile_content($a,$profile_uid);
        $pattern = "/<img([^>]*) src=\"([^\"]*)\"/";
        $replace = "<img\${1} dst=\"\${2}\"";
        $text = preg_replace($pattern, $replace, $text);

        echo str_replace("\t",'       ',$text);
	echo (($_GET['msie'] == 1) ? '</div>' : '</section>');
	echo "</body></html>\r\n";
	killme();

}