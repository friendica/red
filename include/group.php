<?php


function group_add($uid,$name) {

	$ret = false;
	if(x($uid) && x($name)) {
		$r = group_byname($uid,$name); // check for dups
		if($r !== false) 
			return true;
		$r = q("INSERT INTO `group` ( `uid`', `name` )
			VALUES( %d, '%s' ) ",
			intval($uid),
			dbesc($name)
		);
		$ret = $r;
	}	
	return $ret;
}


function group_rmv($uid,$name) {
	$ret = false;
	if(x($uid) && x($name)) {
		$r = q("SELECT * FROM `group` WHERE `uid` = %d AND `name` = '%s' LIMIT 1",
			intval($uid),
			dbesc($name)
		}
		if(count($r))
			$group_id = $r[0]['id'];
		if(! $group_id)
			return false;

		// remove all members
		$r = q("DELETE FROM `group_member` WHERE `uid` = %d AND `gid` = %d ",
			intval($uid),
			intval($group_id)
		);

		// remove group
		$r = q("DELETE FROM `group` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval($uid),
			dbesc($name)
		);

		$ret = $r;

	}
	// TODO!! remove this group from all content ACL's !!

	return $ret;
}

function group_byname($uid,$name) {
	if((! $uid) || (! strlen($name)))
		return false;
	$r = q("SELECT * FROM `group` WHERE `uid` = %d AND `name` = '%s' LIMIT 1",
		intval($uid),
		dbesc($name)
	);
	if(count($r))
		return $r[0]['id'];
	return false;
}

function group_rmv_member($uid,$name,$member) {
	$gid = group_byname($uid,$name);
	if(! $gid)
		return false;
	if(! ( $uid && $gid && $member))
		return false;
	$r = q("DELETE FROM `group_member` WHERE `uid` = %d AND `gid` = %d AND `contact-id` = %d LIMIT 1 ",
		intval($uid),
		intval($gid),
		intval($member)
	);
	return $r;
	

}


function group_add_member($uid,$name,$member) {
	$gid = group_byname($uid,$name);
	if((! $gid) || (! $uid) || (! $member))
		return false;

	$r = q("SELECT * FROM `group_member` WHERE `uid` = %d AND `id` = %d AND `contact-id` = %d LIMIT 1",	
		intval($uid),
		intval($gid),
		intval($member)
	);
	if(count($r))
		return true;	// You might question this, but 
				// we indicate success because the group was in fact created
				// -- It was just created at another time
 	if(! count($r))
		$r = q("INSERT INTO `group_member` (`uid`, `gid`, `contact-id`)
			VALUES( %d, %d, %d ) ",
			intval($uid),
			intval($gid),
			intval($member)
	);
	return $r;
}