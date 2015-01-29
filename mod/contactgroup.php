<?php

require_once('include/group.php');

function contactgroup_content(&$a) {

	if(! local_channel()) {
		killme();
	}

	if((argc() > 2) && (intval(argv(1))) && (argv(2))) {
		$r = q("SELECT abook_xchan from abook where abook_xchan = '%s' and abook_channel = %d and not ( abook_flags & %d )>0 limit 1",
			dbesc(base64url_decode(argv(2))),
			intval(local_channel()),
			intval(ABOOK_FLAG_SELF)
		);
		if($r)
			$change = $r[0]['abook_xchan'];
	}

	if((argc() > 1) && (intval(argv(1)))) {

		$r = q("SELECT * FROM `groups` WHERE `id` = %d AND `uid` = %d AND `deleted` = 0 LIMIT 1",
			intval(argv(1)),
			intval(local_channel())
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
				group_rmv_member(local_channel(),$group['name'],$change);
			}
			else {
				group_add_member(local_channel(),$group['name'],$change);
			}
		}
	}

	killme();
}