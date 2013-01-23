<?php

require_once('include/group.php');

function contactgroup_content(&$a) {

	if(! local_user()) {
		killme();
	}

	if((argc() > 2) && (intval(argv(1))) && (argv(2))) {
		$r = q("SELECT abook_xchan from abook where abook_xchan = '%s' and abook_channel = %d and not ( abook_flags & %d ) limit 1",
			dbesc(argv(2)),
			intval(local_user()),
			intval(ABOOK_FLAG_SELF)
		);
		if($r)
			$change = $r[0]['abook_xchan'];
	}

	if((argc() > 1) && (intval(argv(1)))) {

		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d AND `deleted` = 0 LIMIT 1",
			intval(argv(1)),
			intval(local_user())
		);
		if(! $r) {
			killme();
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
		}
	}

	killme();
}