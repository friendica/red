<?php

function randprof_fallback() {
        $r = q("select channel_address from channel where channel_r_stream = 1 order by rand() limit 1");
if($r)
        return $r[0]['channel_address'];
return '';
}

function randprof_init(&$a) {
	require_once('include/Contact.php');
	$x = random_profile();
	if($x)
		goaway(chanlink_url($x));
	// Nothing there, so try a local, public channel instead
	else $x = randprof_fallback();
	  if($x) {
	  $goaway = (z_root() . '/channel/' . $x);
	    goaway(chanlink_url($goaway));}
	
	// If we STILL haven't got anything, send them to their own profile, or the front page
	
	  goaway($a->get_baseurl());
}
