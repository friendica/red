<?php


function group_add($uid,$name) {

	$ret = false;
	if(x($uid) && x($name)) {
		$r = group_byname($uid,$name); // check for dups
		if($r !== false) 
			return true;
		$r = q("INSERT INTO `group` ( `uid`, `name` )
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
		);
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
		$r = q("UPDATE `group` SET `deleted` = 1 WHERE `uid` = %d AND `name` = '%s' LIMIT 1",
			intval($uid),
			dbesc($name)
		);

		$ret = $r;

	}

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

function group_get_members($gid) {
	$ret = array();
	if(intval($gid)) {
		$r = q("SELECT `group_member`.`contact-id`, `contact`.* FROM `group_member` 
			LEFT JOIN `contact` ON `contact`.`id` = `group_member`.`contact-id` 
			WHERE `gid` = %d AND `group_member`.`uid` = %d ORDER BY `contact`.`name` ASC ",
			intval($gid),
			intval(local_user())
		);
		if(count($r))
			$ret = $r;
	}
	return $ret;
}

function group_public_members($gid) {
	$ret = 0;
	if(intval($gid)) {
		$r = q("SELECT `contact`.`id` AS `contact-id` FROM `group_member` 
			LEFT JOIN `contact` ON `contact`.`id` = `group_member`.`contact-id` 
			WHERE `gid` = %d AND `group_member`.`uid` = %d 
			AND `contact`.`network` != 'dfrn' AND `contact`.`network` != 'mail' AND `contact`.`network` != 'face' ",
			intval($gid),
			intval(local_user())
		);		
		if(count($r))
			$ret = count($r);
	}
	return $ret;
}



function group_side($every="contacts",$each="group",$edit = false, $group_id = 0) {

	$o = '';

	if(! local_user())
		return '';

	$createtext = t('Create a new group');
	$linktext= t('Everybody');
	$selected = (($group_id == 0 && $every !== 'contacts') ? ' class="group-selected" ' : '');
$o .= <<< EOT

<div id="group-sidebar">
<h3>Groups</h3>

<div id="sidebar-new-group">
<a href="group/new">$createtext</a>
</div>

<div id="sidebar-group-list">
	<ul id="sidebar-group-ul">
	<li class="sidebar-group-li" ><a href="$every" $selected >$linktext</a></li>

EOT;

	$r = q("SELECT * FROM `group` WHERE `deleted` = 0 AND `uid` = %d ORDER BY `name` ASC",
		intval($_SESSION['uid'])
	);
	if(count($r)) {
		foreach($r as $rr)
			$selected = (($group_id == $rr['id'] && $every !== 'contacts') ? ' class="group-selected" ' : '');
			$o .= '	<li class="sidebar-group-li">' . (($edit) ? "<a href=\"group/{$rr['id']}\" title=\"" . t('Edit') . "\" ><img src=\"images/spencil.gif\" alt=\"" . t('Edit') . "\"></a> " : "") . "<a href=\"$each/{$rr['id']}\" $selected >{$rr['name']}</a></li>\r\n";
	}
	$o .= "	</ul>\r\n	</div>\r\n</div>";	

	return $o;
}

function expand_groups($a) {
	if(! (is_array($a) && count($a)))
		return array();
	$groups = implode(',', $a);
	$groups = dbesc($groups);
	$r = q("SELECT `contact-id` FROM `group_member` WHERE `gid` IN ( $groups )");
	$ret = array();
	if(count($r))
		foreach($r as $rr)
			$ret[] = $rr['contact-id'];
	return $ret;
}


function member_of($c) {

	$r = q("SELECT `group`.`name`, `group`.`id` FROM `group` LEFT JOIN `group_member` ON `group_member`.`gid` = `group`.`id` WHERE `group_member`.`contact-id` = %d AND `group`.`deleted` = 0 ORDER BY `group`.`name`  ASC ",
		intval($c)
	);

	return $r;

}

