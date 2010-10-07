<?php

function salmon_return($val) {

	if($val >= 500)
		$err = 'Error';
	if($val == 200)
		$err = 'OK';
	
	header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $err);
	killme();

}

function salmon_post(&$a) {

	$xml = file_get_contents('php://input');
	
	$debugging = get_config('system','debugging');
	if($debugging)
		file_put_contents('salmon.out',$xml,FILE_APPEND);

	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$mentions   = (($a->argc > 2 && $a->argv[2] === 'mention') ? true : false);

	$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
		dbesc($nick)
	);
	if(! count($r))
		salmon_return(500);

	$importer = $r[0];

	require_once('include/items.php');

	// Create a fake feed wrapper so simplepie doesn't choke

	$tpl = load_view_file('view/atom_feed.tpl');
	
	$base = substr($xml,strpos($xml,'<entry'));

	$xml = $tpl . $base . '</feed>';

salmon_return(500); // until the handler is finished

//	consume_salmon($xml,$importer);

	salmon_return(200);
}



