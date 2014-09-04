<?php

function regver_content(&$a) {

	global $lang;

	$_SESSION['return_url'] = $a->cmd;

	if(argc() != 3)
		killme();

	$cmd  = argv(1);
	$hash = argv(2);

	if($cmd === 'deny') {
		if (!user_deny($hash)) killme();
	}

	if($cmd === 'allow') {
		if (!user_approve($hash)) killme();
	}
}
