<?php


function randprof_init(&$a) {
	require_once('include/Contact.php');
	$x = random_profile();
	if($x)
		goaway(chanlink_url($x));
	goaway($a->get_baseurl() . '/profile');
}
