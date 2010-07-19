<?php

function can_write_wall(&$a,$owner) {
        if((! (local_user())) && (! (remote_user())))
                return false;
        if((local_user()) && ($_SESSION['uid'] == $owner))
                return true;

        $r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `id` = %d AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
                intval($owner),
                intval($_SESSION['visitor_id'])
        );
        if(count($r))
                return true;
        return false;

}
