<?php

function can_write_wall(&$a,$owner) {
        if((! (local_user())) && (! (remote_user())))
                return false;
        if((local_user()) && ($_SESSION['uid'] == $owner))
                return true;

        $r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `id` = %d AND `blocked` = 0 AND `pending` = 0 
		AND `readonly` = 0  AND `rel` IN ( %d , %d ) LIMIT 1",
                intval($owner),
                intval($_SESSION['visitor_id']),
		intval(DIRECTION_OUT),
		intval(DIRECTION_BOTH)
        );
        if(count($r))
                return true;
        return false;

}
