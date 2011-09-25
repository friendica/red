<?php

function validate_members(&$item) {
	$item = intval($item);
}

function group_init(&$a) {
	if(local_user()) {
		require_once('include/group.php');
		$a->page['aside'] = group_side('contacts','group',false,(($a->argc > 1) ? intval($a->argv[1]) : 0));
	}
}



function group_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if(($a->argc == 2) && ($a->argv[1] === 'new')) {
		$name = notags(trim($_POST['groupname']));
		$r = group_add(local_user(),$name);
		if($r) {
			info( t('Group created.') . EOL );
			$r = group_byname(local_user(),$name);
			if($r)
				goaway($a->get_baseurl() . '/group/' . $r);
		}
		else
			notice( t('Could not create group.') . EOL );	
		goaway($a->get_baseurl() . '/group');
		return; // NOTREACHED
	}
	if(($a->argc == 2) && (intval($a->argv[1]))) {
		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Group not found.') . EOL );
			goaway($a->get_baseurl() . '/contacts');
			return; // NOTREACHED
		}
		$group = $r[0];
		$groupname = notags(trim($_POST['groupname']));
		if((strlen($groupname))  && ($groupname != $group['name'])) {
			$r = q("UPDATE `group` SET `name` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
				dbesc($groupname),
				intval(local_user()),
				intval($group['id'])
			);
			if($r)
				info( t('Group name changed.') . EOL );
		}

		$a->page['aside'] = group_side();
	}
	return;	
}

function group_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied') . EOL);
		return;
	}

	// Switch to text mod interface if we have more than 'n' contacts or group members

	$switchtotext = get_pconfig(local_user(),'system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = get_config('system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = 400;

	if(($a->argc == 2) && ($a->argv[1] === 'new')) {
		$tpl = get_markup_template('group_new.tpl');
		$o .= replace_macros($tpl,array(
			'$desc' => t('Create a group of contacts/friends.'),
			'$name' => t('Group Name: '),
			'$submit' => t('Submit')
		 ));
		return $o;
	}

	if(($a->argc == 3) && ($a->argv[1] === 'drop')) {
		if(intval($a->argv[2])) {
			$r = q("SELECT `name` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if(count($r)) 
				$result = group_rmv(local_user(),$r[0]['name']);
			if($result)
				info( t('Group removed.') . EOL);
			else
				notice( t('Unable to remove group.') . EOL);
		}
		goaway($a->get_baseurl() . '/group');
		// NOTREACHED
	}

	if(($a->argc > 2) && intval($a->argv[1]) && intval($a->argv[2])) {
		$r = q("SELECT `id` FROM `contact` WHERE `id` = %d AND `uid` = %d and `self` = 0 and `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if(count($r))
			$change = intval($a->argv[2]);
	}

	if(($a->argc > 1) && (intval($a->argv[1]))) {

		require_once('include/acl_selectors.php');
		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d AND `deleted` = 0 LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Group not found.') . EOL );
			goaway($a->get_baseurl() . '/contacts');
		}
		$group = $r[0];
		$members = group_get_members($group['id']);
		$preselected = array();
		if(count($members))	{
			foreach($members as $member)
				$preselected[] = $member['id'];
		}

		if($change) {
			if(in_array($change,$preselected)) {
				group_rmv_member(local_user(),$group['name'],$change);
			}
			else {
				group_add_member(local_user(),$group['name'],$change);
			}

			$members = group_get_members($group['id']);
			$preselected = array();
			if(count($members))	{
				foreach($members as $member)
					$preselected[] = $member['id'];
			}
		}


		$drop_tpl = get_markup_template('group_drop.tpl');
		$drop_txt = replace_macros($drop_tpl, array(
			'$id' => $group['id'],
			'$delete' => t('Delete')
		));

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$tpl = get_markup_template('group_edit.tpl');
		$o .= replace_macros($tpl, array(
			'$gid' => $group['id'],
			'$name' => $group['name'],
			'$drop' => $drop_txt,
			'$desc' => t('Click on a contact to add or remove.'),
			'$title' => t('Group Editor'),
			'$gname' => t('Group Name: '),
			'$submit' => t('Submit')
		));

	}

	if(! isset($group))
		return;

	$o .= '<div id="group-update-wrapper">';
	if($change) 
		$o = '';

	$o .= '<h3>' . t('Members') . '</h3>';
	$o .= '<div id="group-members">';
	$textmode = (($switchtotext && (count($members) > $switchtotext)) ? true : false);
	foreach($members as $member) {
		if($member['url']) {
			$member['click'] = 'groupChangeMember(' . $group['id'] . ',' . $member['id'] . '); return true;';
			$o .= micropro($member,true,'mpgroup', $textmode);
		}
		else
			group_rmv_member(local_user(),$group['name'],$member['id']);
	}

	$o .= '</div><div id="group-members-end"></div>';
	$o .= '<hr id="group-separator" />';
	
	$o .= '<h3>' . t('All Contacts') . '</h3>';
	$o .= '<div id="group-all-contacts">';

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `blocked` = 0 and `pending` = 0 and `self` = 0 ORDER BY `name` ASC",
			intval(local_user())
		);

		if(count($r)) {
			$textmode = (($switchtotext && (count($r) > $switchtotext)) ? true : false);
			foreach($r as $member) {
				if(! in_array($member['id'],$preselected)) {
					$member['click'] = 'groupChangeMember(' . $group['id'] . ',' . $member['id'] . '); return true;';
					$o .= micropro($member,true,'mpall', $textmode);
				}
			}
		}

		$o .= '</div><div id="group-all-contacts-end"></div>';

	if($change) {
		echo $o;
		killme();
	}
	$o .= '</div>';
	return $o;

}

