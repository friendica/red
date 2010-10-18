<?php

function can_write_wall(&$a,$owner) {
        if((! (local_user())) && (! (remote_user())))
                return false;
		$uid = get_uid();
        if(($uid) && ($uid === $owner))
                return true;

        $r = q("SELECT `contact`.*, `user`.`page-flags` FROM `contact` LEFT JOIN `user` on `user`.`uid` = `contact`.`uid` 
			WHERE `contact`.`uid` = %d AND `contact`.`id` = %d AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 
			AND `readonly` = 0  AND ( `contact`.`rel` IN ( %d , %d ) OR `user`.`page_flags` = %d ) LIMIT 1",
			intval($owner),
			intval($_SESSION['visitor_id']),
			intval(REL_VIP),
			intval(REL_BUD),
			intval(PAGE_COMMUNITY)
        );
        if(count($r))
                return true;
        return false;

}
