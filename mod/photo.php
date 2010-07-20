<?php

function photo_init(&$a) {

	switch($a->argc) {
		case 3:
			$person = $a->argv[2];
			$type = $a->argv[1];
			break;
		case 2:
			$photo = $a->argv[1];
			break;
		case 1:
		default:
			killme();
			return; // NOTREACHED
	}

	if(x($type)) {
		switch($type) {

			case 'profile':
				$resolution = 4;
				break;
			case 'avatar':
			default:
				$resolution = 5;
				break;
		}

		$uid = str_replace('.jpg', '', $person);

		$r = q("SELECT * FROM `photo` WHERE `scale` = %d AND `uid` = %d AND `profile` = 1 LIMIT 1",
			intval($resolution),
			intval($uid)
		);
		if(count($r)) {
			$data = $r[0]['data'];
		}
		if(x($data) === false) {
			$data = file_get_contents(($resolution == 5) 
				? 'images/default-profile-sm.jpg' 
				: 'images/default-profile.jpg');
		}
	}
	else {
		$resolution = 0;
		$photo = str_replace('.jpg','',$photo);
	
		if(substr($photo,-2,1) == '-') {
			$resolution = intval(substr($photo,-1,1));
			$photo = substr($photo,0,-2);
		}

		$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `scale` = %d LIMIT 1",
			dbesc($photo),
			intval($resolution)
		);
		if(count($r)) {
			$data = $r[0]['data'];
		}
	}

	if(x($data) === false) {
		killme();
		return; // NOTREACHED
	}

        header("Content-type: image/jpeg");
        echo $data;
	killme();
	return; //NOTREACHED
}