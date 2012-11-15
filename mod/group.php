<?php

function group_aside(&$a) {
	if(local_user()) {
		require_once('include/group.php');
		$a->set_widget('groups_edit',group_side('collections','group',false,(($a->argc > 1) ? intval($a->argv[1]) : 0)));
	}
}


function group_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if((argc() == 2) && (argv(1) === 'new')) {
		check_form_security_token_redirectOnErr('/group/new', 'group_edit');
		
		$name = notags(trim($_POST['groupname']));
		$r = group_add(local_user(),$name);
		if($r) {
			info( t('Collection created.') . EOL );
			$r = group_byname(local_user(),$name);
			if($r)
				goaway($a->get_baseurl() . '/group/' . $r);
		}
		else
			notice( t('Could not create collection.') . EOL );	
		goaway($a->get_baseurl() . '/group');

	}
	if((argc() == 2) && (intval(argv(1)))) {
		check_form_security_token_redirectOnErr('/group', 'group_edit');
		
		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval(argv(1)),
			intval(local_user())
		);
		if(! $r) {
			notice( t('Collection not found.') . EOL );
			goaway($a->get_baseurl() . '/connections');

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
				info( t('Collection name changed.') . EOL );
		}

		goaway(z_root() . '/group/' . argv(1) . '/' . argv(2));
	}
	return;	
}

function group_content(&$a) {
	$change = false;
	
	if(! local_user()) {
		notice( t('Permission denied') . EOL);
		return;
	}

	// Switch to text mode interface if we have more than 'n' contacts or group members

	$switchtotext = get_pconfig(local_user(),'system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = get_config('system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = 400;

	$tpl = get_markup_template('group_edit.tpl');
	$context = array('$submit' => t('Submit'));

	if((argc() == 2) && (argv(1) === 'new')) {
		
		return replace_macros($tpl, $context + array(
			'$title' => t('Create a collection of connections.'),
			'$gname' => array('groupname',t('Collection Name: '), '', ''),
			'$gid' => 'new',
			'$form_security_token' => get_form_security_token("group_edit"),
		));


	}

	if((argc() == 3) && (argv(1) === 'drop')) {
		check_form_security_token_redirectOnErr('/group', 'group_drop', 't');
		
		if(intval(argv(2))) {
			$r = q("SELECT `name` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval(argv(2)),
				intval(local_user())
			);
			if($r) 
				$result = group_rmv(local_user(),$r[0]['name']);
			if($result)
				info( t('Collection removed.') . EOL);
			else
				notice( t('Unable to remove collection.') . EOL);
		}
		goaway($a->get_baseurl() . '/group');
		// NOTREACHED
	}


	if((argc() > 2) && intval(argv(1)) && argv(2)) {
		check_form_security_token_ForbiddenOnErr('group_member_change', 't');

		$r = q("SELECT abook_xchan from abook where abook_xchan = '%s' and abook_channel = %d and not (abook_flags & %d) and not (abook_flags & %d) limit 1",
			dbesc(argv(2)),
			intval(local_user()),
			intval(ABOOK_FLAG_SELF),
			intval(ABOOK_FLAG_BLOCKED)
		);
		if(count($r))
			$change = argv(2);

	}

	if((argc() > 1) && (intval(argv(1)))) {

		require_once('include/acl_selectors.php');
		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d AND `deleted` = 0 LIMIT 1",
			intval(argv(1)),
			intval(local_user())
		);
		if(! $r) {
			notice( t('Collection not found.') . EOL );
			goaway($a->get_baseurl() . '/connnections');
		}
		$group = $r[0];

		$members = group_get_members($group['id']);

		$preselected = array();
		if(count($members))	{
			foreach($members as $member)
				$preselected[] = $member['xchan_hash'];
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
					$preselected[] = $member['xchan_hash'];
			}
		}

		$drop_tpl = get_markup_template('group_drop.tpl');
		$drop_txt = replace_macros($drop_tpl, array(
			'$id' => $group['id'],
			'$delete' => t('Delete'),
			'$form_security_token' => get_form_security_token("group_drop"),
		));

		
		$context = $context + array(
			'$title' => t('Collection Editor'),
			'$gname' => array('groupname',t('Collection Name: '),$group['name'], ''),
			'$gid' => $group['id'],
			'$drop' => $drop_txt,
			'$form_security_token' => get_form_security_token('group_edit'),
		);

	}

	if(! isset($group))
		return;

	$groupeditor = array(
		'label_members' => t('Members'),
		'members' => array(),
		'label_contacts' => t('All Connected Channels'),
		'contacts' => array(),
	);
		
	$sec_token = addslashes(get_form_security_token('group_member_change'));
	$textmode = (($switchtotext && (count($members) > $switchtotext)) ? true : false);
	foreach($members as $member) {
		if($member['xchan_url']) {
			$member['click'] = 'groupChangeMember(' . $group['id'] . ',\'' . $member['xchan_hash'] . '\',\'' . $sec_token . '\'); return true;';
			$groupeditor['members'][] = micropro($member,true,'mpgroup', $textmode);
		}
		else
			group_rmv_member(local_user(),$group['name'],$member['xchan_hash']);
	}

	$r = q("SELECT abook.*, xchan.* FROM `abook` left join xchan on abook_xchan = xchan_hash WHERE `abook_channel` = %d AND  not (abook_flags & %d) and not (abook_flags & %d) order by xchan_name asc",
		intval(local_user()),
		intval(ABOOK_FLAG_BLOCKED),
		intval(ABOOK_FLAG_SELF)
	);

	if(count($r)) {
		$textmode = (($switchtotext && (count($r) > $switchtotext)) ? true : false);
		foreach($r as $member) {
			if(! in_array($member['xchan_hash'],$preselected)) {
				$member['click'] = 'groupChangeMember(' . $group['id'] . ',\'' . $member['xchan_hash'] . '\',\'' . $sec_token . '\'); return true;';
				$groupeditor['contacts'][] = micropro($member,true,'mpall', $textmode);
			}
		}
	}

	$context['$groupeditor'] = $groupeditor;
	$context['$desc'] = t('Click on a channel to add or remove.');

	if($change) {
		$tpl = get_markup_template('groupeditor.tpl');
		echo replace_macros($tpl, $context);
		killme();
	}
	
	return replace_macros($tpl, $context);

}

