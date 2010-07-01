<?php


function profiles_post(&$a) {

	if(! local_user()) {
		$_SESSION['sysmsg'] .= "Unauthorised." . EOL;
		return;
	}

	// todo - delete... ensure that all contacts using the to-be-deleted profile are moved to the default. 		



	if(($a->argc > 1) && ($a->argv[1] != "new") && intval($a->argv[1])) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			$_SESSION['sysmsg'] .= "Profile not found." . EOL;
			return;
		}

		$profile_name = notags(trim($_POST['profile_name']));
		if(! strlen($profile_name)) {
			$a->$_SESSION['sysmsg'] .= "Profile Name is required." . EOL;
			return;
		}
	
		$name = notags(trim($_POST['name']));
		$gender = notags(trim($_POST['gender']));
		$address = notags(trim($_POST['address']));
		$locality = notags(trim($_POST['locality']));
		$region = notags(trim($_POST['region']));
		$postal_code = notags(trim($_POST['postal_code']));
		$country_name = notags(trim($_POST['country_name']));
		$marital = notags(trim(implode(', ',$_POST['marital'])));
		$homepage = notags(trim($_POST['homepage']));
		$about = str_replace(array('<','>','&'),array('&lt;','&gt;','&amp;'),trim($_POST['about']));

		if(! in_array($gender,array('','Male','Female','Other')))
			$gender = '';

		$r = q("UPDATE `profile` 
			SET `profile-name` = '%s',
			`name` = '%s',
			`gender` = '%s',
			`address` = '%s',
			`locality` = '%s',
			`region` = '%s',
			`postal-code` = '%s',
			`country-name` = '%s',
			`marital` = '%s',
			`homepage` = '%s',
			`about` = '%s'
			WHERE `id` = %d AND `uid` = %d LIMIT 1",
			dbesc($profile_name),
			dbesc($name),
			dbesc($gender),
			dbesc($address),
			dbesc($locality),
			dbesc($region),
			dbesc($postal_code),
			dbesc($country_name),
			dbesc($marital),
			dbesc($homepage),
			dbesc($about),
			intval($a->argv[1]),
			intval($_SESSION['uid'])
		);

		if($r)
			$_SESSION['sysmsg'] .= "Profile updated." . EOL;
	}



}




function profiles_content(&$a) {
	if(! local_user()) {
		$_SESSION['sysmsg'] .= "Unauthorised." . EOL;
		return;
	}

	if(($a->argc > 1) && ($a->argv[1] == 'new')) {

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval($_SESSION['uid']));
		$num_profiles = count($r0);

		$name = "Profile-" . ($num_profiles + 1);

		$r1 = q("SELECT `name`, `photo`, `thumb` FROM `profile` WHERE `uid` = %d AND `is-default` = 1 LIMIT 1",
			intval($_SESSION['uid']));
		
		$r2 = q("INSERT INTO `profile` (`uid` , `profile-name` , `name`, `photo`, `thumb`)
			VALUES ( %d, '%s', '%s', '%s', '%s' )",
			intval($_SESSION['uid']),
			dbesc($name),
			dbesc($r1[0]['name']),
			dbesc($r1[0]['photo']),
			dbesc($ra[0]['thumb'])
		);

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile-name` = '%s' LIMIT 1",
			intval($_SESSION['uid']),
			dbesc($name)
		);
		$_SESSION['sysmsg'] .= "New profile created." . EOL;
		if(count($r3) == 1)
			goaway($a->get_baseurl() . '/profiles/' . $r3[0]['id']);
		goaway($a->get_baseurl() . '/profiles');
	}		 


	if(intval($a->argv[1])) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			$_SESSION['sysmsg'] .= "Profile not found." . EOL;
			return;
		}

		require_once('mod/profile.php');
		profile_load($a,$_SESSION['uid'],$r[0]['id']);

		require_once('view/profile_selectors.php');

		$tpl = file_get_contents('view/jot-header.tpl');
		$profile_in_dir = file_get_contents("view/profile-in-directory.tpl");

		$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));
	
		$a->page['aside'] = file_get_contents('view/sidenote.tpl');	
		$is_default = (($r[0]['is-default']) ? 1 : 0);
		$tpl = file_get_contents("view/profile_edit.tpl");
		$o .= replace_macros($tpl,array(
			'$baseurl' => $a->get_baseurl(),
			'$profile_id' => $r[0]['id'],
			'$profile_name' => $r[0]['profile-name'],
			'$default' => (($is_default) ? "<p id=\"profile-edit-default-desc\">This is your <strong>public</strong> profile.</p>" : ""),
			'$name' => $r[0]['name'],
			'$dob' => $r[0]['dob'],
			'$address' => $r[0]['address'],
			'$locality' => $r[0]['locality'],
			'$region' => $r[0]['region'],
			'$postal_code' => $r[0]['postal-code'],
			'$country_name' => $r[0]['country-name'],
			'$age' => $r[0]['age'],
			'$gender' => gender_selector($r[0]['gender']),
			'$marital' => marital_selector($r[0]['marital']),
			'$about' => $r[0]['about'],
			'$homepage' => $r[0]['homepage'],
			'$profile_in_dir' => (($is_default) ? $profile_in_dir : '')
		));

		return $o;


	}
	else {

		$r = q("SELECT * FROM `profile` WHERE `uid` = %d",
			$_SESSION['uid']);
		if(count($r)) {

			$o .= file_get_contents('view/profile_listing_header.tpl');
			$tpl_default = file_get_contents('view/profile_entry_default.tpl');
			$tpl = file_get_contents('view/profile_entry.tpl');

			foreach($r as $rr) {
				$template = (($rr['is-default']) ? $tpl_default : $tpl);
				$o .= replace_macros($template, array(
					'$photo' => $rr['thumb'],
					'$id' => $rr['id'],
					'$profile_name' => $rr['profile-name']
				));
			}
		}
		return $o;
	}

}