<?php


function profiles_post(&$a) {

	if(! local_user()) {
		notice( "Permission denied." . EOL);
		return;
	}

	$namechanged = false;

	if(($a->argc > 1) && ($a->argv[1] != "new") && intval($a->argv[1])) {
		$orig = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval($_SESSION['uid'])
		);
		if(! count($orig)) {
			$_SESSION['sysmsg'] .= "Profile not found." . EOL;
			return;
		}
		$is_default = (($orig[0]['is-default']) ? 1 : 0);

		$profile_name = notags(trim($_POST['profile_name']));
		if(! strlen($profile_name)) {
			$a->$_SESSION['sysmsg'] .= "Profile Name is required." . EOL;
			return;
		}
	
		$year = intval($_POST['year']);
		if($year < 1900 || $year > 2100 || $year < 0)
			$year = 0;
		$month = intval($_POST['month']);
			if(($month > 12) || ($month < 0))
				$month = 0;
		$mtab = array(0,31,29,31,30,31,30,31,31,30,31,30,31);
		$day = intval($_POST['day']);
			if(($day > $mtab[$month]) || ($day < 0))
				$day = 0;
		$dob = '0000-00-00';
		$dob = sprintf('%04d-%02d-%02d',$year,$month,$day);

			
		$name = notags(trim($_POST['name']));

		if($orig[0]['name'] != $name)
			$namechanged = true;

		$gender = notags(trim($_POST['gender']));
		$address = notags(trim($_POST['address']));
		$locality = notags(trim($_POST['locality']));
		$region = notags(trim($_POST['region']));
		$postal_code = notags(trim($_POST['postal_code']));
		$country_name = notags(trim($_POST['country_name']));

		$marital = notags(trim(implode(', ',$_POST['marital'])));
		if($marital != $orig[0]['marital'])
			$maritalchanged = true;

		$sexual = notags(trim($_POST['sexual']));
		$homepage = notags(trim($_POST['homepage']));
		$politic = notags(trim($_POST['politic']));
		$religion = notags(trim($_POST['religion']));

		$about = escape_tags(trim($_POST['about']));
		$interest = escape_tags(trim($_POST['interest']));
		$contact = escape_tags(trim($_POST['contact']));
		$music = escape_tags(trim($_POST['music']));
		$book = escape_tags(trim($_POST['book']));
		$tv = escape_tags(trim($_POST['tv']));
		$film = escape_tags(trim($_POST['film']));
		$romance = escape_tags(trim($_POST['romance']));
		$work = escape_tags(trim($_POST['work']));
		$education = escape_tags(trim($_POST['education']));
		if(x($_POST,'profile_in_directory'))
			$publish = (($_POST['profile_in_directory'] == 1) ? 1: 0);

		$r = q("UPDATE `profile` 
			SET `profile-name` = '%s',
			`name` = '%s',
			`gender` = '%s',
			`dob` = '%s',
			`address` = '%s',
			`locality` = '%s',
			`region` = '%s',
			`postal-code` = '%s',
			`country-name` = '%s',
			`marital` = '%s',
			`sexual` = '%s',
			`homepage` = '%s',
			`politic` = '%s',
			`religion` = '%s',
			`about` = '%s',
			`interest` = '%s',
			`contact` = '%s',
			`music` = '%s',
			`book` = '%s',
			`tv` = '%s',
			`film` = '%s',
			`romance` = '%s',
			`work` = '%s',
			`education` = '%s'
			WHERE `id` = %d AND `uid` = %d LIMIT 1",
			dbesc($profile_name),
			dbesc($name),
			dbesc($gender),
			dbesc($dob),
			dbesc($address),
			dbesc($locality),
			dbesc($region),
			dbesc($postal_code),
			dbesc($country_name),
			dbesc($marital),
			dbesc($sexual),
			dbesc($homepage),
			dbesc($politic),
			dbesc($religion),
			dbesc($about),
			dbesc($interest),
			dbesc($contact),
			dbesc($music),
			dbesc($book),
			dbesc($tv),
			dbesc($film),
			dbesc($romance),
			dbesc($work),
			dbesc($education),
			intval($a->argv[1]),
			intval($_SESSION['uid'])
		);

		if($r)
			$_SESSION['sysmsg'] .= "Profile updated." . EOL;


		if($is_default) {
			$r = q("UPDATE `profile` 
			SET `publish` = %d
			WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($publish),
			intval($a->argv[1]),
			intval($_SESSION['uid'])

			);
		}
		if($namechanged && $is_default) {
			$r = q("UPDATE `contact` SET `name-date` = '%s' WHERE `self` = 1 AND `uid` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval($_SESSION['uid'])
			);
		}

	}



}




function profiles_content(&$a) {
	if(! local_user()) {
		$_SESSION['sysmsg'] .= "Unauthorised." . EOL;
		return;
	}

	if(($a->argc > 2) && ($a->argv[1] == "drop") && intval($a->argv[2])) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d AND `is-default` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			$_SESSION['sysmsg'] .= "Profile not found." . EOL;
			goaway($a->get_baseurl() . '/profiles');
			return; // NOTREACHED
		}

		// move every contact using this profile as their default to the user default

		$r = q("UPDATE `contact` SET `profile-id` = (SELECT `profile`.`id` AS `profile-id` FROM `profile` WHERE `profile`.`is-default` = 1 AND `profile`.`uid` = %d LIMIT 1) WHERE `profile-id` = %d AND `uid` = %d ",
			intval($_SESSION['uid']),
			intval($a->argv[2]),
			intval($_SESSION['uid'])
		);
		$r = q("DELETE FROM `profile` WHERE `id` = %d LIMIT 1",
			intval($a->argv[2])
		);
		if($r)
			notice("Profile deleted." . EOL);

		goaway($a->get_baseurl() . '/profiles');
		return; // NOTREACHED
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

	if(($a->argc > 2) && ($a->argv[1] == 'clone')) {

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval($_SESSION['uid']));
		$num_profiles = count($r0);

		$name = "Profile-" . ($num_profiles + 1);
		$r1 = q("SELECT * FROM `profile` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval($_SESSION['uid']),
			intval($a->argv[2])
		);
		if(! count($r1)) {
			notice("Profile unavailable to clone." . EOL);
			return;
		}
		unset($r1[0]['id']);
		$r1[0]['is-default'] = 0;
		$r1[0]['publish'] = 0;	
		$r1[0]['profile-name'] = dbesc($name);

		dbesc_array($r1[0]);

		$r2 = q("INSERT INTO `profile` (`" 
			. implode("`, `", array_keys($r1[0])) 
			. "`) VALUES ('" 
			. implode("', '", array_values($r1[0])) 
			. "')" );

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile-name` = '%s' LIMIT 1",
			intval($_SESSION['uid']),
			dbesc($name)
		);
		$_SESSION['sysmsg'] .= "New profile created." . EOL;
		if(count($r3) == 1)
			goaway($a->get_baseurl() . '/profiles/' . $r3[0]['id']);
	goaway($a->get_baseurl() . '/profiles');
	return; // NOTREACHED
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
		profile_load($a,$a->user['nickname'],$r[0]['id']);

		require_once('view/profile_selectors.php');

		$tpl = file_get_contents('view/profed_head.tpl');
		$opt_tpl = file_get_contents("view/profile-in-directory.tpl");
		$profile_in_dir = replace_macros($opt_tpl,array(
			'$yes_selected' => (($r[0]['publish']) ? " checked=\"checked\" " : ""),
			'$no_selected' => (($r[0]['publish'] == 0) ? " checked=\"checked\" " : "")
		));

		$opt_tpl = file_get_contents("view/profile-hide-friends.tpl");
		$hide_friends = replace_macros($opt_tpl,array(
			'$yes_selected' => (($r[0]['hide-friends']) ? " checked=\"checked\" " : ""),
			'$no_selected' => (($r[0]['hide-friends'] == 0) ? " checked=\"checked\" " : "")
		));


		$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));
		$a->page['htmlhead'] .= "<script type=\"text/javascript\" src=\"include/country.js\" ></script>";



	

		$is_default = (($r[0]['is-default']) ? 1 : 0);
		$tpl = file_get_contents("view/profile_edit.tpl");
		$o .= replace_macros($tpl,array(
			'$disabled' => (($is_default) ? 'onclick="return false;" style="color: #BBBBFF;"' : ''),
			'$baseurl' => $a->get_baseurl(),
			'$profile_id' => $r[0]['id'],
			'$profile_name' => $r[0]['profile-name'],
			'$default' => (($is_default) ? "<p id=\"profile-edit-default-desc\">This is your <strong>public</strong> profile.<br />It <strong>may</strong> be visible to anybody using the internet.</p>" : ""),
			'$name' => $r[0]['name'],
			'$dob' => dob($r[0]['dob']),
			'$hide_friends' => $hide_friends,
			'$address' => $r[0]['address'],
			'$locality' => $r[0]['locality'],
			'$region' => $r[0]['region'],
			'$postal_code' => $r[0]['postal-code'],
			'$country_name' => $r[0]['country-name'],
			'$age' => ((intval($r[0]['dob'])) ? '(Age: '. age($r[0]['dob'],$a->user['timezone'],$a->user['timezone']) . ')' : ''),
			'$gender' => gender_selector($r[0]['gender']),
			'$marital' => marital_selector($r[0]['marital']),
			'$sexual' => sexpref_selector($r[0]['sexual']),
			'$about' => $r[0]['about'],
			'$homepage' => $r[0]['homepage'],
			'$politic' => $r[0]['politic'],
			'$religion' => $r[0]['religion'],
			'$music' => $r[0]['music'],
			'$book' => $r[0]['book'],
			'$tv' => $r[0]['tv'],
			'$film' => $r[0]['film'],
			'$interest' => $r[0]['interest'],
			'$romance' => $r[0]['romance'],
			'$work' => $r[0]['work'],
			'$education' => $r[0]['education'],
			'$contact' => $r[0]['contact'],
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