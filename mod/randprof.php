<?php


function randprof_init(&$a) {
	require_once('include/Contact.php');
	$x = random_profile();
	if($x)
		goaway(chanlink_url($x));
	// FIXME this doesn't work at the moment as a fallback
	goaway($a->get_baseurl() . '/profile');
}
