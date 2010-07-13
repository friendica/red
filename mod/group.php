<?php

function validate_members(&$item) {
	$item = intval($item);
}

function group_init(&$a) {
	require_once('include/group.php');
	$a->page['aside'] .= group_side();

}



function group_post(&$a) {

	if(! local_user()) {
		notice("Access denied." . EOL);
		return;
	}

	if(($a->argc == 2) && ($a->argv[1] == 'new')) {
		$name = notags(trim($_POST['groupname']));
		$r = group_add($_SESSION['uid'],$name);
		if($r) {
			notice("Group created." . EOL );
			$r = group_byname($_SESSION['uid'],$name);
			if($r)
				goaway($a->get_baseurl() . '/group/' . $r);
		}
		else
			notice("Could not create group." . EOL );	
//		goaway($a->get_baseurl() . '/group');
		return; // NOTREACHED
	}
	if(($a->argc == 2) && (intval($a->argv[1]))) {
		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			notice("Group not found." . EOL );
			goaway($a->get_baseurl() . '/contacts');
		}
		$group = $r[0];
		$groupname = notags(trim($_POST['groupname']));
		if((strlen($groupname))  && ($groupname != $group['name'])) {
			$r = q("UPDATE `group` SET `name` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
				dbesc($groupname),
				intval($_SESSION['uid']),
				intval($group['id'])
			);
		}
		$members = $_POST['group_members_select'];
		array_walk($members,'validate_members');
		$r = q("DELETE FROM `group_member` WHERE `gid` = %d AND `uid` = %d",
			intval($a->argv[1]),
			intval($_SESSION['uid'])
		);
		if(count($members)) {
			foreach($members as $member) {
				$r = q("INSERT INTO `group_member` ( `uid`, `gid`, `contact-id`)
					VALUES ( %d, %d, %d )",
					intval($_SESSION['uid']),
					intval($group['id']),
					intval($member)
				);
			}
		}
	}
	
}

function group_content(&$a) {

	if(! local_user()) {
		notice("Access denied." . EOL);
		return;
	}

	if(($a->argc == 2) && ($a->argv[1] == 'new')) {
		$tpl = file_get_contents('view/group_new.tpl');
		$o .= replace_macros($tpl,array(

		));

	}
		



	if(($a->argc == 2) && (intval($a->argv[1]))) {
		require_once('view/acl_selectors.php');
		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			notice("Group not found." . EOL );
			goaway($a->get_baseurl() . '/contacts');
		}
		$group = $r[0];
		$ret = group_get_members($group['id']);
		$preselected = array();
		if(count($ret))	{
			foreach($ret as $p)
				$preselected[] = $p['id'];
		}

		$tpl = file_get_contents('view/group_edit.tpl');
		$o .= replace_macros($tpl, array(
			'$gid' => $group['id'],
			'$name' => $group['name'],
			'$selector' => contact_select('group_members_select','group_members_select',$preselected,25)
		));

	}





	return $o;

}