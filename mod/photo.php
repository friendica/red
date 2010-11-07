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
			// NOTREACHED
	}

	$default = 'images/default-profile.jpg';

	if(isset($type)) {
		switch($type) {

			case 'profile':
				$resolution = 4;
				break;
			case 'micro':
				$resolution = 6;
				$default = 'images/default-profile-mm.jpg';
				break;
			case 'avatar':
			default:
				$resolution = 5;
				$default = 'images/default-profile-sm.jpg';
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
		if(! isset($data)) {
			$data = file_get_contents($default);
		}
	}
	else {

		$resolution = 0;
		$photo = str_replace('.jpg','',$photo);
	
		if(substr($photo,-2,1) == '-') {
			$resolution = intval(substr($photo,-1,1));
			$photo = substr($photo,0,-2);
		}

		$r = q("SELECT `uid` FROM `photo` WHERE `resource-id` = '%s' AND `scale` = %d LIMIT 1",
			dbesc($photo),
			intval($resolution)
		);
		if(count($r)) {
			
			$owner = $r[0]['uid'];

			$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = '' ";

			if(local_user() && ($owner == $_SESSION['uid'])) {

				// Owner can always see his/her photos
				$sql_extra = ''; 

			}
			elseif(remote_user()) {

				// authenticated visitor - here lie dragons

				$groups = init_groups_visitor($_SESSION['visitor_id']);
				$gs = '<<>>'; // should be impossible to match
				if(count($groups)) {
					foreach($groups as $g)
						$gs .= '|<' . intval($g) . '>';
				} 

				$sql_extra = sprintf(
					" AND ( `allow_cid` = '' OR `allow_cid` REGEXP '<%d>' ) 
					  AND ( `deny_cid`  = '' OR  NOT `deny_cid` REGEXP '<%d>' ) 
					  AND ( `allow_gid` = '' OR `allow_gid` REGEXP '%s' )
					  AND ( `deny_gid`  = '' OR NOT `deny_gid` REGEXP '%s') ",

					intval($_SESSION['visitor_id']),
					intval($_SESSION['visitor_id']),
					dbesc($gs),
					dbesc($gs)
				);
			}

			// Now we'll see if we can access the photo

			$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `scale` = %d $sql_extra LIMIT 1",
				dbesc($photo),
				intval($resolution)
			);

			if(count($r)) {
				$data = $r[0]['data'];
			}
		}
	}

	if(! isset($data)) {
		killme();
		// NOTREACHED
	}

	header("Content-type: image/jpeg");
	echo $data;
	killme();
	// NOTREACHED
}