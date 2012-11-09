<?php


function randprof_init(&$a) {
	require_once('include/Contact.php');
	$x = random_profile();
	if($x)
		goaway(zid($x));
	goaway($a->get_baseurl() . '/profile');
}
