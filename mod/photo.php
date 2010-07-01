<?php

function photo_init(&$a) {

	if($a->argc != 2) {
		killme();
	}
	$resolution = 0;
	$photo = $a->argv[1];
	$photo = str_replace('.jpg','',$photo);
	if(substr($photo,-2,1) == '-') {
		$resolution = intval(substr($photo,-1,1));
		$photo = substr($photo,0,-2);
	}
	$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s'
		AND `scale` = %d LIMIT 1",
		dbesc($photo),
		intval($resolution));
	if($r === NULL || (! count($r))) {
		killme();
	}
        header("Content-type: image/jpeg");
        echo $r[0]['data'];

}