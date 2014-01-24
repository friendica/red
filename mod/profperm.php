<?php

require_once('include/Contact.php');

function profperm_init(&$a) {

	if(! local_user())
		return;

	$channel = $a->get_channel();
	$which = $channel['channel_address'];

	$profile = $a->argv[1];		

	profile_load($a,$which,$profile);

}


function profperm_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied') . EOL);
		return;
	}


	if(argc() < 2) {
		notice( t('Invalid profile identifier.') . EOL );
		return;
	}

	// Switch to text mod interface if we have more than 'n' contacts or group members

	$switchtotext = get_pconfig(local_user(),'system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = get_config('system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = 400;


	if((argc() > 2) && intval(argv(1)) && intval(argv(2))) {
		$r = q("SELECT abook_id FROM abook WHERE abook_id = %d and abook_channel = %d limit 1",
			intval(argv(2)),
			intval(local_user())
		);
		if($r)
			$change = intval(argv(2));
	}


	if((argc() > 1) && (intval(argv(1)))) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d AND `is_default` = 0 LIMIT 1",
			intval(argv(1)),
			intval(local_user())
		);
		if(! $r) {
			notice( t('Invalid profile identifier.') . EOL );
			return;
		}

		$profile = $r[0];

		$r = q("SELECT * FROM abook left join xchan on abook_xchan = xchan_hash WHERE abook_channel = %d AND abook_profile = %d",
			intval(local_user()),
			intval(argv(1))
		);

		$ingroup = array();
		if($r)
			foreach($r as $member)
				$ingroup[] = $member['abook_id'];

		$members = $r;

		if($change) {
			if(in_array($change,$ingroup)) {
				q("UPDATE abook SET abook_profile = 0 WHERE abook_id = %d AND abook_channel = %d LIMIT 1",
					intval($change),
					intval(local_user())
				);
			}
			else {
				q("UPDATE abook SET abook_profile = %d WHERE abook_id = %d AND abook_channel = %d LIMIT 1",
					intval(argv(1)),
					intval($change),
					intval(local_user())
				);

			}

			$r = q("SELECT * FROM abook left join xchan on abook_xchan = xchan_hash WHERE abook_channel = %d AND abook_profile = %d",
				intval(local_user()),
				intval(argv(1))
			);

			$members = $r;

			$ingroup = array();
			if(count($r))
				foreach($r as $member)
					$ingroup[] = $member['abook_id'];
		}

		$o .= '<h2>' . t('Profile Visibility Editor') . '</h2>';

		$o .= '<h3>' . t('Profile') . ' \'' . $profile['profile_name'] . '\'</h3>';

		$o .= '<div id="prof-edit-desc">' . t('Click on a contact to add or remove.') . '</div>';

	}

	$o .= '<div id="prof-update-wrapper">';
	if($change) 
		$o = '';
	
	$o .= '<div id="prof-members-title">';
	$o .= '<h3>' . t('Visible To') . '</h3>';
	$o .= '</div>';
	$o .= '<div id="prof-members">';

	$textmode = (($switchtotext && (count($members) > $switchtotext)) ? true : false);

	foreach($members as $member) {
		if($member['xchan_url']) {
			$member['click'] = 'profChangeMember(' . $profile['id'] . ',' . $member['abook_id'] . '); return false;';
			$o .= micropro($member,true,'mpprof', $textmode);
		}
	}
	$o .= '</div><div id="prof-members-end"></div>';
	$o .= '<hr id="prof-separator" />';

	$o .= '<div id="prof-all-contcts-title">';
	$o .= '<h3>' . t("All Connections") . '</h3>';
	$o .= '</div>';
	$o .= '<div id="prof-all-contacts">';
		
		$r = abook_connections(local_user());

		if($r) {
			$textmode = (($switchtotext && (count($r) > $switchtotext)) ? true : false);
			foreach($r as $member) {
				if(! in_array($member['abook_id'],$ingroup)) {
					$member['click'] = 'profChangeMember(' . $profile['id'] . ',' . $member['abook_id'] . '); return false;';
					$o .= micropro($member,true,'mpprof',$textmode);
				}
			}
		}

		$o .= '</div><div id="prof-all-contacts-end"></div>';

	if($change) {
		echo $o;
		killme();
	}
	$o .= '</div>';
	return $o;

}

