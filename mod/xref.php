<?php

function xref_init(&$a) {
	// Sets a referral URL using an xchan directly
	// Link format: example.com/xref/[xchan]/[TargetURL]
	// Target URL is optional.
	// Cookie lasts 24 hours to survive a browser restart.  Contains no personal
	// information at all - just somebody else's xchan.
	$referrer = argv(1);
	$expire=time()+60*60*24;
	$path = 'xref';
	setcookie($path, $referrer, $expire, "/"); 
	$url = '';

	if (argc() > 2)
		$url = argv(2);
			
	goaway (z_root() . '/' . $url);
			
}
