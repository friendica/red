<?php /** @file */


function group_add($uid,$name,$public = 0) {

	$ret = false;
	if(x($uid) && x($name)) {
		$r = group_byname($uid,$name); // check for dups
		if($r !== false) {

			// This could be a problem. 
			// Let's assume we've just created a group which we once deleted
			// all the old members are gone, but the group remains so we don't break any security
			// access lists. What we're doing here is reviving the dead group, but old content which
			// was restricted to this group may now be seen by the new group members. 

			$z = q("SELECT * FROM `group` WHERE `id` = %d LIMIT 1",
				intval($r)
			);
			if(count($z) && $z[0]['deleted']) {
				$r = q("UPDATE `group` SET `deleted` = 0 WHERE `uid` = %d AND `name` = '%s' LIMIT 1",
					intval($uid),
					dbesc($name)
				);
				notice( t('A deleted group with this name was revived. Existing item permissions <strong>may</strong> apply to this group and any future members. If this is not what you intended, please create another group with a different name.') . EOL); 
			}
			return true;
		}

		do {
			$dups = false;
			$hash = random_string() . $name;

			$r = q("SELECT id FROM `group` WHERE hash = '%s' LIMIT 1", dbesc($hash));
			if($r)
				$dups = true;
		} while($dups == true);


		$r = q("INSERT INTO `group` ( hash, uid, visible, name )
			VALUES( '%s', %d, %d, '%s' ) ",
			dbesc($hash),
			intval($uid),
			intval($public),
			dbesc($name)
		);
		$ret = $r;
	}	
	return $ret;
}


function group_rmv($uid,$name) {
	$ret = false;
	if(x($uid) && x($name)) {
		$r = q("SELECT id, hash FROM `group` WHERE `uid` = %d AND `name` = '%s' LIMIT 1",
			intval($uid),
			dbesc($name)
		);
		if($r) {
			$group_id = $r[0]['id'];
			$group_hash = $r[0]['hash'];
		}

		if(! $group_id)
			return false;

		// remove group from default posting lists
		$r = q("SELECT channel_default_group, channel_allow_gid, channel_deny_gid FROM channel WHERE channel_id = %d LIMIT 1",
		       intval($uid)
		);
		if($r) {
			$user_info = $r[0];
			$change = false;

			if($user_info['channel_default_group'] == $group_hash) {
				$user_info['channel_default_group'] = '';
				$change = true;
			}
			if(strpos($user_info['channel_allow_gid'], '<' . $group_id . '>') !== false) {
				$user_info['channel_allow_gid'] = str_replace('<' . $group_hash . '>', '', $user_info['channel_allow_gid']);
				$change = true;
			}
			if(strpos($user_info['channel_deny_gid'], '<' . $group_id . '>') !== false) {
				$user_info['channel_deny_gid'] = str_replace('<' . $group_hash . '>', '', $user_info['channel_deny_gid']);
				$change = true;
			}

			if($change) {
				q("UPDATE channel SET channel_default_group = '%s', channel_allow_gid = '%s', channel_deny_gid = '%s' 
				WHERE channel_id = %d",
				  intval($user_info['channel_default_group']),
				  dbesc($user_info['channel_allow_gid']),
				  dbesc($user_info['channel_deny_gid']),
				  intval($uid)
				);
			}
		}

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


function group_rec_byhash($uid,$hash) {
	if((! $uid) || (! strlen($hash)))
		return false;
	$r = q("SELECT * FROM `group` WHERE `uid` = %d AND `hash` = '%s' LIMIT 1",
		intval($uid),
		dbesc($hash)
	);
	if($r)
		return $r[0];
	return false;
}

function group_rmv_member($uid,$name,$member) {
	$gid = group_byname($uid,$name);
	if(! $gid)
		return false;
	if(! ( $uid && $gid && $member))
		return false;
	$r = q("DELETE FROM `group_member` WHERE `uid` = %d AND `gid` = %d AND xchan = '%s' LIMIT 1 ",
		intval($uid),
		intval($gid),
		dbesc($member)
	);
	return $r;
	

}


function group_add_member($uid,$name,$member,$gid = 0) {
	if(! $gid)
		$gid = group_byname($uid,$name);
	if((! $gid) || (! $uid) || (! $member))
		return false;

	$r = q("SELECT * FROM `group_member` WHERE `uid` = %d AND `gid` = %d AND `xchan` = '%s' LIMIT 1",	
		intval($uid),
		intval($gid),
		dbesc($member)
	);
	if(count($r))
		return true;	// You might question this, but 
				// we indicate success because the group member was in fact created
				// -- It was just created at another time
 	if(! count($r))
		$r = q("INSERT INTO `group_member` (`uid`, `gid`, `xchan`)
			VALUES( %d, %d, '%s' ) ",
			intval($uid),
			intval($gid),
			dbesc($member)
	);
	return $r;
}

function group_get_members($gid) {
	$ret = array();
	if(intval($gid)) {
		$r = q("SELECT * FROM `group_member` 
			LEFT JOIN abook ON abook_xchan = `group_member`.`xchan` left join xchan on xchan_hash = abook_xchan
			WHERE `gid` = %d AND abook_channel = %d and `group_member`.`uid` = %d and not ( abook_flags & %d ) and not ( abook_flags & %d ) and not ( abook_flags & %d ) ORDER BY xchan_name ASC ",
			intval($gid),
			intval(local_user()),
			intval(local_user()),
			intval(ABOOK_FLAG_SELF),
			intval(ABOOK_FLAG_BLOCKED),
			intval(ABOOK_FLAG_PENDING)
		);
		if(count($r))
			$ret = $r;
	}
	return $ret;
}

function mini_group_select($uid,$group = '') {
	
	$grps = array();
	$o = '';

	$r = q("SELECT * FROM `group` WHERE `deleted` = 0 AND `uid` = %d ORDER BY `name` ASC",
		intval($uid)
	);
	$grps[] = array('name' => '', 'hash' => '0', 'selected' => '');
	if(count($r)) {
		foreach($r as $rr) {
			$grps[] = array('name' => $rr['name'], 'id' => $rr['hash'], 'selected' => (($group == $rr['hash']) ? 'true' : ''));
		}

	}
	logger('mini_group_select: ' . print_r($grps,true), LOGGER_DATA);

	$o = replace_macros(get_markup_template('group_selection.tpl'), array(
		'$label' => t('Default privacy group for new contacts'),
		'$groups' => $grps 
	));
	return $o;
}




function group_side($every="contacts",$each="group",$edit = false, $group_id = 0, $cid = '',$mode = 1) {

	$o = '';

	if(! local_user())
		return '';

	$groups = array();
	
	$groups[] = array(
		'text' 	=> t('All Channels'),
		'id' => 0,
		'selected' => (($group_id == 0) ? 'group-selected' : ''),
		'href' 	=> $every,
	);


	$r = q("SELECT * FROM `group` WHERE `deleted` = 0 AND `uid` = %d ORDER BY `name` ASC",
		intval($_SESSION['uid'])
	);
	$member_of = array();
	if($cid) {
		$member_of = groups_containing(local_user(),$cid);
	} 

	if(count($r)) {
		foreach($r as $rr) {
			$selected = (($group_id == $rr['id']) ? ' group-selected' : '');
			
			if ($edit) {
				$groupedit = array(
					'href' => "group/".$rr['id'],
					'title' => t('edit'),
				);
			} else {
				$groupedit = null;
			}
			
			$groups[] = array(
				'id'		=> $rr['id'],
				'cid'		=> $cid,
				'text' 		=> $rr['name'],
				'selected' 	=> $selected,
				'href'		=> (($mode == 0) ? $each.'?f=&gid='.$rr['id'] : $each."/".$rr['id']),
				'edit'		=> $groupedit,
				'ismember'	=> in_array($rr['id'],$member_of),
			);
		}
	}
	
	
	$tpl = get_markup_template("group_side.tpl");
	$o = replace_macros($tpl, array(
		'$title'		=> t('Collections'),
		'$edittext'     => t('Edit collection'),
		'$createtext' 	=> t('Create a new collection'),
		'$ungrouped'    => (($every === 'contacts') ? t('Channels not in any collection') : ''),
		'$groups'		=> $groups,
		'$add'			=> t('add'),
	));
		
	
	return $o;
}

function expand_groups($a) {
	if(! (is_array($a) && count($a)))
		return array();
	$x = $a;
	stringify_array_elms($x,true);
	$groups = implode(',', $x);

	if($groups)
		$r = q("SELECT xchan FROM group_member WHERE gid IN ( select id from `group` where hash in ( $groups ))");
	$ret = array();

	if($r)
		foreach($r as $rr)
			$ret[] = $rr['xchan'];
	return $ret;
}


function member_of($c) {

	$r = q("SELECT `group`.`name`, `group`.`id` FROM `group` LEFT JOIN `group_member` ON `group_member`.`gid` = `group`.`id` WHERE `group_member`.`xchan` = '%s' AND `group`.`deleted` = 0 ORDER BY `group`.`name`  ASC ",
		dbesc($c)
	);

	return $r;

}

function groups_containing($uid,$c) {

	$r = q("SELECT `gid` FROM `group_member` WHERE `uid` = %d AND `group_member`.`xchan` = '%s' ",
		intval($uid),
		dbesc($c)
	);

	$ret = array();
	if(count($r)) {
		foreach($r as $rr)
			$ret[] = $rr['gid'];
	}

	return $ret;
}
