<?php

function can_write_wall(&$a,$owner) {

	static $verified = 0;

	if((! (local_user())) && (! (remote_user())))
		return false;

	$uid = local_user();

	if(($uid) && ($uid == $owner)) {
		return true;
	}

	if(remote_user()) {

		// user remembered decision and avoid a DB lookup for each and every display item
		// DO NOT use this function if there are going to be multiple owners

		if($verified === 2)
			return true;
		elseif($verified === 1)
			return false;
		else {
			$r = q("SELECT `contact`.*, `user`.`page-flags` FROM `contact` LEFT JOIN `user` on `user`.`uid` = `contact`.`uid` 
				WHERE `contact`.`uid` = %d AND `contact`.`id` = %d AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 
				AND `user`.`blockwall` = 0 AND `readonly` = 0  AND ( `contact`.`rel` IN ( %d , %d ) OR `user`.`page-flags` = %d ) LIMIT 1",
				intval($owner),
				intval(remote_user()),
				intval(REL_VIP),
				intval(REL_BUD),
				intval(PAGE_COMMUNITY)
			);
			if(count($r)) {
				$verified = 2;
				return true;
			}
			else {
				$verified = 1;
			}
		}
	}

	return false;
}
