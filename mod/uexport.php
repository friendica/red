<?php

function uexport_init(&$a) {

	if(! local_user())
		killme();

	$user = array();
	$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		local_user()
	);
	if(count($r)) {
		foreach($r as $rr)
			foreach($rr as $k => $v)
				$user[$k] = $v;

	}
	$contact = array();
	$r = q("SELECT * FROM `contact` WHERE `uid` = %d ",
		local_user()
	);
	if(count($r)) {
		foreach($r as $rr)
			foreach($rr as $k => $v)
				$contact[][$k] = $v;

	}

	$profile = array();
	$r = q("SELECT * FROM `profile` WHERE `uid` = %d ",
		local_user()
	);
	if(count($r)) {
		foreach($r as $rr)
			foreach($rr as $k => $v)
				$profile[][$k] = $v;
	}

	$output = array('user' => $user, 'contact' => $contact, 'profile' => $profile );

	header("Content-type: text/json");
	echo str_replace('\\/','/',json_encode($output));

	killme();

}