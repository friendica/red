<?php

function attach_init(&$a) {

	if($a->argc != 2) {
		notice( t('Item not available.') . EOL);
		return;
	}

	$item_id = intval($a->argv[1]);

	$r = q("SELECT * FROM `attach` WHERE `id` = %d LIMIT 1",
		intval($item_id)
	);
	if(! count($r)) {
		notice( t('Item was not found.'). EOL);
		return;
	}

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

	// Now we'll see if we can access the attachment

	$r = q("SELECT * FROM `attach` WHERE `id` = '%d' $sql_extra LIMIT 1",
		dbesc($item_id)
	);

	if(count($r)) {
		$data = $r[0]['data'];
	}
	else {
		notice( t('Permission denied.') . EOL);
		return;
	}

	header('Content-type: ' . $r[0]['filetype']);
	header('Content-disposition: attachment; filename=' . $r[0]['filename']);
	echo $data;
	killme();
	// NOTREACHED
}