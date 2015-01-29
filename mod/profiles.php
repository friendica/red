<?php


function profiles_init(&$a) {

	nav_set_selected('profiles');

	if(! local_channel()) {
		return;
	}

	if((argc() > 2) && (argv(1) === "drop") && intval(argv(2))) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d AND `is_default` = 0 LIMIT 1",
			intval(argv(2)),
			intval(local_channel())
		);
		if(! count($r)) {
			notice( t('Profile not found.') . EOL);
			goaway($a->get_baseurl(true) . '/profiles');
			return; // NOTREACHED
		}
		$profile_guid = $r['profile_guid'];
		
		check_form_security_token_redirectOnErr('/profiles', 'profile_drop', 't');

		// move every contact using this profile as their default to the user default

		$r = q("UPDATE abook SET abook_profile = (SELECT profile_guid AS FROM profile WHERE is_default = 1 AND uid = %d LIMIT 1) WHERE abook_profile = '%s' AND abook_channel = %d ",
			intval(local_channel()),
			dbesc($profile_guid),
			intval(local_channel())
		);
		$r = q("DELETE FROM `profile` WHERE `id` = %d AND `uid` = %d",
			intval(argv(2)),
			intval(local_channel())
		);
		if($r)
			info( t('Profile deleted.') . EOL);

		goaway($a->get_baseurl(true) . '/profiles');
		return; // NOTREACHED
	}





	if((argc() > 1) && (argv(1) === 'new')) {
		
//		check_form_security_token_redirectOnErr('/profiles', 'profile_new', 't');

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval(local_channel()));
		$num_profiles = count($r0);

		$name = t('Profile-') . ($num_profiles + 1);

		$r1 = q("SELECT `name`, `photo`, `thumb` FROM `profile` WHERE `uid` = %d AND `is_default` = 1 LIMIT 1",
			intval(local_channel()));
		
		$r2 = q("INSERT INTO `profile` (`aid`, `uid` , `profile_guid`, `profile_name` , `name`, `photo`, `thumb`)
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s' )",
			intval(get_account_id()),
			intval(local_channel()),
			dbesc(random_string()),
			dbesc($name),
			dbesc($r1[0]['name']),
			dbesc($r1[0]['photo']),
			dbesc($r1[0]['thumb'])
		);

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile_name` = '%s' LIMIT 1",
			intval(local_channel()),
			dbesc($name)
		);

		info( t('New profile created.') . EOL);
		if(count($r3) == 1)
			goaway($a->get_baseurl(true) . '/profiles/' . $r3[0]['id']);
		
		goaway($a->get_baseurl(true) . '/profiles');
	} 

	if((argc() > 2) && (argv(1) === 'clone')) {
		
		check_form_security_token_redirectOnErr('/profiles', 'profile_clone', 't');

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval(local_channel()));
		$num_profiles = count($r0);

		$name = t('Profile-') . ($num_profiles + 1);
		$r1 = q("SELECT * FROM `profile` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval(local_channel()),
			intval($a->argv[2])
		);
		if(! count($r1)) {
			notice( t('Profile unavailable to clone.') . EOL);
			$a->error = 404;
			return;
		}
		unset($r1[0]['id']);
		$r1[0]['is_default'] = 0;
		$r1[0]['publish'] = 0;	
		$r1[0]['profile_name'] = dbesc($name);
		$r1[0]['profile_guid'] = dbesc(random_string());

		dbesc_array($r1[0]);

		$r2 = dbq("INSERT INTO `profile` (`" 
			. implode("`, `", array_keys($r1[0])) 
			. "`) VALUES ('" 
			. implode("', '", array_values($r1[0])) 
			. "')" );

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile_name` = '%s' LIMIT 1",
			intval(local_channel()),
			dbesc($name)
		);
		info( t('New profile created.') . EOL);
		if(count($r3) == 1)
			goaway($a->get_baseurl(true) . '/profiles/' . $r3[0]['id']);
		
		goaway($a->get_baseurl(true) . '/profiles');
		
		return; // NOTREACHED
	}

	if((argc() > 2) && (argv(1) === 'export')) {
		
		$r1 = q("SELECT * FROM `profile` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval(local_channel()),
			intval(argv(2))
		);
		if(! $r1) {
			notice( t('Profile unavailable to export.') . EOL);
			$a->error = 404;
			return;
		}
		header('content-type: application/octet_stream');
		header('content-disposition: attachment; filename="' . $r1[0]['profile_name'] . '.json"' );

		unset($r1[0]['id']);
		unset($r1[0]['aid']);
		unset($r1[0]['uid']);
		unset($r1[0]['is_default']);
		unset($r1[0]['publish']);
		unset($r1[0]['profile_name']);
		unset($r1[0]['profile_guid']);
		echo json_encode($r1[0]);
		killme();
	}




	// Run profile_load() here to make sure the theme is set before
	// we start loading content
	if(((argc() > 1) && (intval(argv(1)))) || !feature_enabled(local_channel(),'multi_profiles')) {
		if(feature_enabled(local_channel(),'multi_profiles'))
			$id = $a->argv[1];
		else {
			$x = q("select id from profile where uid = %d and is_default = 1",
				intval(local_channel())
			);
			if($x)
				$id = $x[0]['id'];
		}
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($id),
			intval(local_channel())
		);
		if(! count($r)) {
			notice( t('Profile not found.') . EOL);
			$a->error = 404;
			return;
		}

		$chan = $a->get_channel();

		profile_load($a,$chan['channel_address'],$r[0]['id']);
	}
}

function profiles_post(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	require_once('include/activities.php');

	$namechanged = false;

	call_hooks('profile_post', $_POST);

	// import from json export file.
 	// Only import fields that are allowed on this hub

	if(x($_FILES,'userfile')) {
		$src      = $_FILES['userfile']['tmp_name'];
		$filesize = intval($_FILES['userfile']['size']);
		if($filesize) {
			$j = @json_decode(@file_get_contents($src),true);
			@unlink($src);
			if($j) {
				$fields = get_profile_fields_advanced();
				if($fields) {
					foreach($j as $jj => $v) {
						foreach($fields as $f => $n) {
							if($jj == $f) {
								$_POST[$f] = $v;
								break;
							}
						}
					}
				}
			}
		}
	}
	


	if((argc() > 1) && (argv(1) !== "new") && intval(argv(1))) {
		$orig = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_channel())
		);
		if(! count($orig)) {
			notice( t('Profile not found.') . EOL);
			return;
		}
		
		check_form_security_token_redirectOnErr('/profiles', 'profile_edit');
		
		$is_default = (($orig[0]['is_default']) ? 1 : 0);

		$profile_name = notags(trim($_POST['profile_name']));
		if(! strlen($profile_name)) {
			notify( t('Profile Name is required.') . EOL);
			return;
		}

		$dob = $_POST['dob'] ? escape_tags(trim($_POST['dob'])) : '0000-00-00'; // FIXME: Needs to be validated?

		$y = substr($dob,0,4);
		if((! ctype_digit($y)) || ($y < 1900))
			$ignore_year = true;
		else
			$ignore_year = false;

		if($dob != '0000-00-00') {
			if(strpos($dob,'0000-') === 0) {
				$ignore_year = true;
				$dob = substr($dob,5);
			}
			$dob = datetime_convert('UTC','UTC',(($ignore_year) ? '1900-' . $dob : $dob),(($ignore_year) ? 'm-d' : 'Y-m-d'));
			if($ignore_year)
				$dob = '0000-' . $dob;
		}
			
		$name = escape_tags(trim($_POST['name']));

		if($orig[0]['name'] != $name)
			$namechanged = true;

		$pdesc        = escape_tags(trim($_POST['pdesc']));
		$gender       = escape_tags(trim($_POST['gender']));
		$address      = escape_tags(trim($_POST['address']));
		$locality     = escape_tags(trim($_POST['locality']));
		$region       = escape_tags(trim($_POST['region']));
		$postal_code  = escape_tags(trim($_POST['postal_code']));
		$country_name = escape_tags(trim($_POST['country_name']));
		$keywords     = escape_tags(trim($_POST['keywords']));
		$marital      = escape_tags(trim($_POST['marital']));
		$howlong      = escape_tags(trim($_POST['howlong']));
		$sexual       = escape_tags(trim($_POST['sexual']));
		$homepage     = escape_tags(trim($_POST['homepage']));
		$hometown     = escape_tags(trim($_POST['hometown']));
		$politic      = escape_tags(trim($_POST['politic']));
		$religion     = escape_tags(trim($_POST['religion']));

		$likes        = fix_mce_lf(escape_tags(trim($_POST['likes'])));
		$dislikes     = fix_mce_lf(escape_tags(trim($_POST['dislikes'])));

		$about        = fix_mce_lf(escape_tags(trim($_POST['about'])));
		$interest     = fix_mce_lf(escape_tags(trim($_POST['interest'])));
		$contact      = fix_mce_lf(escape_tags(trim($_POST['contact'])));
		$channels     = fix_mce_lf(escape_tags(trim($_POST['channels'])));
		$music        = fix_mce_lf(escape_tags(trim($_POST['music'])));
		$book         = fix_mce_lf(escape_tags(trim($_POST['book'])));
		$tv           = fix_mce_lf(escape_tags(trim($_POST['tv'])));
		$film         = fix_mce_lf(escape_tags(trim($_POST['film'])));
		$romance      = fix_mce_lf(escape_tags(trim($_POST['romance'])));
		$work         = fix_mce_lf(escape_tags(trim($_POST['work'])));
		$education    = fix_mce_lf(escape_tags(trim($_POST['education'])));

		$hide_friends = ((intval($_POST['hide_friends'])) ? 1: 0);

		require_once('include/text.php');
		linkify_tags($a, $likes, local_channel());
		linkify_tags($a, $dislikes, local_channel());
		linkify_tags($a, $about, local_channel());
		linkify_tags($a, $interest, local_channel());
		linkify_tags($a, $interest, local_channel());
		linkify_tags($a, $contact, local_channel());
		linkify_tags($a, $channels, local_channel());
		linkify_tags($a, $music, local_channel());
		linkify_tags($a, $book, local_channel());
		linkify_tags($a, $tv, local_channel());
		linkify_tags($a, $film, local_channel());
		linkify_tags($a, $romance, local_channel());
		linkify_tags($a, $work, local_channel());
		linkify_tags($a, $education, local_channel());


		$with         = ((x($_POST,'with')) ? escape_tags(trim($_POST['with'])) : '');

		if(! strlen($howlong))
			$howlong = NULL_DATE;
		else
			$howlong = datetime_convert(date_default_timezone_get(),'UTC',$howlong);
 
		// linkify the relationship target if applicable

		$withchanged = false;

		if(strlen($with)) {
			if($with != strip_tags($orig[0]['with'])) {
				$withchanged = true;
				$prf = '';
				$lookup = $with;
				if(strpos($lookup,'@') === 0)
					$lookup = substr($lookup,1);
				$lookup = str_replace('_',' ', $lookup);
				$newname = $lookup;

				$r = q("SELECT * FROM abook left join xchan on abook_xchan = xchan_hash WHERE xchan_name = '%s' AND abook_channel = %d LIMIT 1",
					dbesc($newname),
					intval(local_channel())
				);
				if(! $r) {
					$r = q("SELECT * FROM abook left join xchan on abook_xchan = xchan_hash WHERE xchan_addr = '%s' AND abook_channel = %d LIMIT 1",
						dbesc($lookup . '@%'),
						intval(local_channel())
					);
				}
				if($r) {
					$prf = $r[0]['xchan_url'];
					$newname = $r[0]['xchan_name'];
				}

	
				if($prf) {
					$with = str_replace($lookup,'<a href="' . $prf . '">' . $newname	. '</a>', $with);
					if(strpos($with,'@') === 0)
						$with = substr($with,1);
				}
			}
			else
				$with = $orig[0]['with'];
		}

		$profile_fields_basic    = get_profile_fields_basic();
		$profile_fields_advanced = get_profile_fields_advanced();
		$advanced = ((feature_enabled(local_channel(),'advanced_profiles')) ? true : false);
		if($advanced)
			$fields = $profile_fields_advanced;
		else
			$fields = $profile_fields_basic;

		$z = q("select * from profdef where true");
		if($z) {
			foreach($z as $zz) {
				if(array_key_exists($zz['field_name'],$fields)) {
					$w = q("select * from profext where channel_id = %d and hash = '%s' and k = '%s' limit 1",
						intval(local_channel()),
						dbesc($orig[0]['profile_guid']),
						dbesc($zz['field_name'])
					);
					if($w) {
						q("update profext set v = '%s' where id = %d",
							dbesc(escape_tags(trim($_POST[$zz['field_name']]))),
							intval($w[0]['id'])
						);
					}
					else {
						q("insert into profext ( channel_id, hash, k, v ) values ( %d, '%s', '%s', '%s') ",
							intval(local_channel()),
							dbesc($orig[0]['profile_guid']),
							dbesc($zz['field_name']),
							dbesc(escape_tags(trim($_POST[$zz['field_name']])))
						);
					}
				}
			}
		}
													
		$changes = array();
		$value = '';
		if($is_default) {
			if($marital != $orig[0]['marital']) {
				$changes[] = '[color=#ff0000]&hearts;[/color] ' . t('Marital Status');
				$value = $marital;
			}
			if($withchanged) {
				$changes[] = '[color=#ff0000]&hearts;[/color] ' . t('Romantic Partner');
				$value = strip_tags($with);
			}
			if($likes != $orig[0]['likes']) {
				$changes[] = t('Likes');
				$value = $likes;
			}
			if($dislikes != $orig[0]['dislikes']) {
				$changes[] = t('Dislikes');
				$value = $dislikes;
			}
			if($work != $orig[0]['work']) {
				$changes[] = t('Work/Employment');
			}
			if($religion != $orig[0]['religion']) {
				$changes[] = t('Religion');
				$value = $religion;
			}
			if($politic != $orig[0]['politic']) {
				$changes[] = t('Political Views');
				$value = $politic;
			}
			if($gender != $orig[0]['gender']) {
				$changes[] = t('Gender');
				$value = $gender;
			}
			if($sexual != $orig[0]['sexual']) {
				$changes[] = t('Sexual Preference');
				$value = $sexual;
			}
			if($homepage != $orig[0]['homepage']) {
				$changes[] = t('Homepage');
				$value = $homepage;
			}
			if($interest != $orig[0]['interest']) {
				$changes[] = t('Interests');
				$value = $interest;
			}
			if($address != $orig[0]['address']) {
				$changes[] = t('Address');
				// New address not sent in notifications, potential privacy issues
				// in case this leaks to unintended recipients. Yes, it's in the public
				// profile but that doesn't mean we have to broadcast it to everybody.
			}
			if($locality != $orig[0]['locality'] || $region != $orig[0]['region']
				|| $country_name != $orig[0]['country_name']) {
 				$changes[] = t('Location');
				$comma1 = ((($locality) && ($region || $country_name)) ? ', ' : ' ');
				$comma2 = (($region && $country_name) ? ', ' : '');
				$value = $locality . $comma1 . $region . $comma2 . $country_name;
			}

			profile_activity($changes,$value);

		}			
			
		$r = q("UPDATE `profile` 
			SET `profile_name` = '%s',
			`name` = '%s',
			`pdesc` = '%s',
			`gender` = '%s',
			`dob` = '%s',
			`address` = '%s',
			`locality` = '%s',
			`region` = '%s',
			`postal_code` = '%s',
			`country_name` = '%s',
			`marital` = '%s',
			`with` = '%s',
			`howlong` = '%s',
			`sexual` = '%s',
			`homepage` = '%s',
			`hometown` = '%s',
			`politic` = '%s',
			`religion` = '%s',
			`keywords` = '%s',
			`likes` = '%s',
			`dislikes` = '%s',
			`about` = '%s',
			`interest` = '%s',
			`contact` = '%s',
			`channels` = '%s',
			`music` = '%s',
			`book` = '%s',
			`tv` = '%s',
			`film` = '%s',
			`romance` = '%s',
			`work` = '%s',
			`education` = '%s',
			`hide_friends` = %d
			WHERE `id` = %d AND `uid` = %d",
			dbesc($profile_name),
			dbesc($name),
			dbesc($pdesc),
			dbesc($gender),
			dbesc($dob),
			dbesc($address),
			dbesc($locality),
			dbesc($region),
			dbesc($postal_code),
			dbesc($country_name),
			dbesc($marital),
			dbesc($with),
			dbesc($howlong),
			dbesc($sexual),
			dbesc($homepage),
			dbesc($hometown),
			dbesc($politic),
			dbesc($religion),
			dbesc($keywords),
			dbesc($likes),
			dbesc($dislikes),
			dbesc($about),
			dbesc($interest),
			dbesc($contact),
			dbesc($channels),
			dbesc($music),
			dbesc($book),
			dbesc($tv),
			dbesc($film),
			dbesc($romance),
			dbesc($work),
			dbesc($education),
			intval($hide_friends),
			intval(argv(1)),
			intval(local_channel())
		);

		if($r)
			info( t('Profile updated.') . EOL);

		$r = q("select * from profile where id = %d and uid = %d limit 1",
			intval(argv(1)),
			intval(local_channel())
		);
		if($r) {
			require_once('include/zot.php');
			build_sync_packet(local_channel(),array('profile' => $r));
		}

		$channel = $a->get_channel();

		if($namechanged && $is_default) {
			$r = q("UPDATE xchan SET xchan_name = '%s', xchan_name_date = '%s' WHERE xchan_hash = '%s'",
				dbesc($name),
				dbesc(datetime_convert()),
				dbesc($channel['xchan_hash'])
			);
		}

		if($is_default) {
			// reload the info for the sidebar widget - why does this not work?
			profile_load($a,$channel['channel_address']);
			proc_run('php','include/directory.php',local_channel());
		}
	}
}




function profiles_content(&$a) {

	$o = '';

	$channel = $a->get_channel();

	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	require_once('include/identity.php');

	$profile_fields_basic    = get_profile_fields_basic();
	$profile_fields_advanced = get_profile_fields_advanced();

	if(((argc() > 1) && (intval(argv(1)))) || !feature_enabled(local_channel(),'multi_profiles')) {
		if(feature_enabled(local_channel(),'multi_profiles'))
			$id = $a->argv[1];
		else {
			$x = q("select id from profile where uid = %d and is_default = 1",
				intval(local_channel())
			);
			if($x)
				$id = $x[0]['id'];
		}		
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($id),
			intval(local_channel())
		);
		if(! count($r)) {
			notice( t('Profile not found.') . EOL);
			return;
		}

		require_once('include/profile_selectors.php');


		$editselect = 'none';
//		if(feature_enabled(local_channel(),'richtext'))
//			$editselect = 'textareas';

		$a->page['htmlhead'] .= replace_macros(get_markup_template('profed_head.tpl'), array(
			'$baseurl'    => $a->get_baseurl(true),
			'$editselect' => $editselect,
		));

		$advanced = ((feature_enabled(local_channel(),'advanced_profiles')) ? true : false);
		if($advanced)
			$fields = $profile_fields_advanced;
		else
			$fields = $profile_fields_basic;


		$opt_tpl = get_markup_template("profile_hide_friends.tpl");
		$hide_friends = replace_macros($opt_tpl,array('$field' => array(
                       'hide_friends',
                       t('Hide your contact/friend list from viewers of this profile?'),
                       $r[0]['hide_friends'],
                       '',
               )));

		$q = q("select * from profdef where true");
		if($q) {
			$extra_fields = array();

			foreach($q as $qq) {
				$mine = q("select v from profext where k = '%s' and hash = '%s' and channel_id = %d limit 1",
					dbesc($qq['field_name']),					
					dbesc($r[0]['profile_guid']),
					intval(local_channel())
				);

				if(array_key_exists($qq['field_name'],$fields)) {
					$extra_fields[] = array($qq['field_name'],$qq['field_desc'],(($mine) ? $mine[0]['v'] : ''), $qq['field_help']);
				}
			}
		}

//logger('extra_fields: ' . print_r($extra_fields,true));

		$f = get_config('system','birthday_input_format');
		if(! $f)
			$f = 'ymd';

		$is_default = (($r[0]['is_default']) ? 1 : 0);
		$tpl = get_markup_template("profile_edit.tpl");
		$o .= replace_macros($tpl,array(

			'$form_security_token' => get_form_security_token("profile_edit"),
			'$profile_clone_link'  => ((feature_enabled(local_channel(),'multi_profiles')) ? 'profiles/clone/' . $r[0]['id'] . '?t=' 
				. get_form_security_token("profile_clone") : ''),
			'$profile_drop_link'   => 'profiles/drop/' . $r[0]['id'] . '?t=' 
				. get_form_security_token("profile_drop"),

			'$fields'       => $fields,
			'$guid'         => $r[0]['profile_guid'],
			'$banner'       => t('Edit Profile Details'),
			'$submit'       => t('Submit'),
			'$viewprof'     => t('View this profile'),
			'$editvis' 	    => t('Edit visibility'),
			'$profpic'      => t('Change Profile Photo'),
			'$cr_prof'      => t('Create a new profile using these settings'),
			'$cl_prof'      => t('Clone this profile'),
			'$del_prof'     => t('Delete this profile'),
			'$exportable'   => feature_enabled(local_channel(),'profile_export'),
			'$lbl_import'   => t('Import profile from file'),
			'$lbl_export'   => t('Export profile to file'),
			'$lbl_profname' => t('Profile Name:'),
			'$lbl_fullname' => t('Your Full Name:'),
			'$lbl_title'    => t('Title/Description:'),
			'$lbl_gender'   => t('Your Gender:'),
			'$lbl_bd'       => t("Birthday :"),
			'$lbl_address'  => t('Street Address:'),
			'$lbl_city'     => t('Locality/City:'),
			'$lbl_zip'      => t('Postal/Zip Code:'),
			'$lbl_country'  => t('Country:'),
			'$lbl_region'   => t('Region/State:'),
			'$lbl_marital'  => t('<span class="heart">&hearts;</span> Marital Status:'),
			'$lbl_with'     => t("Who: \x28if applicable\x29"),
			'$lbl_ex1'      => t('Examples: cathy123, Cathy Williams, cathy@example.com'),
			'$lbl_howlong'  => t('Since [date]:'),
			'$lbl_sexual'   => t('Sexual Preference:'),
			'$lbl_homepage' => t('Homepage URL:'),
			'$lbl_hometown' => t('Hometown:'),
			'$lbl_politic'  => t('Political Views:'),
			'$lbl_religion' => t('Religious Views:'),
			'$lbl_pubkey'   => t('Keywords:'),
			'$lbl_likes'    => t('Likes:'),
			'$lbl_dislikes' => t('Dislikes:'),
			'$lbl_ex2'      => t('Example: fishing photography software'),
			'$lbl_pubdsc'   => t("Used in directory listings"),
			'$lbl_about'    => t('Tell us about yourself...'),
			'$lbl_hobbies'  => t('Hobbies/Interests'),
			'$lbl_social'   => t('Contact information and Social Networks'),
			'$lbl_channels' => t('My other channels'),
			'$lbl_music'    => t('Musical interests'),
			'$lbl_book'     => t('Books, literature'),
			'$lbl_tv'       => t('Television'),
			'$lbl_film'     => t('Film/dance/culture/entertainment'),
			'$lbl_love'     => t('Love/romance'),
			'$lbl_work'     => t('Work/employment'),
			'$lbl_school'   => t('School/education'),
			'$disabled'     => (($is_default) ? 'onclick="return false;" style="color: #BBBBFF;"' : ''),
			'$baseurl'      => $a->get_baseurl(true),
			'$profile_id'   => $r[0]['id'],
			'$profile_name' => $r[0]['profile_name'],
			'$is_default'   => $is_default,
			'$default'      => t('This is your default profile.') . EOL . translate_scope(map_scope($channel['channel_r_profile'])),
			'$advanced'     => $advanced,
			'$name'         => $r[0]['name'],
			'$pdesc'        => $r[0]['pdesc'],
			'$dob'          => dob($r[0]['dob']),
			'$hide_friends' => $hide_friends,
			'$address'      => $r[0]['address'],
			'$locality'     => $r[0]['locality'],
			'$region'       => $r[0]['region'],
			'$postal_code'  => $r[0]['postal_code'],
			'$country_name' => $r[0]['country_name'],
			'$age'          => ((intval($r[0]['dob'])) ? '(' . t('Age: ') . age($r[0]['dob'],$a->user['timezone'],$a->user['timezone']) . ')' : ''),
			'$gender'       => gender_selector($r[0]['gender']),
			'$gender_min'       => gender_selector_min($r[0]['gender']),
			'$marital'      => marital_selector($r[0]['marital']),
			'$marital_min'      => marital_selector_min($r[0]['marital']),
			'$with'         => $r[0]['with'],
			'$howlong'      => ($r[0]['howlong'] === NULL_DATE ? '' : datetime_convert('UTC',date_default_timezone_get(),$r[0]['howlong'])),
			'$sexual'       => sexpref_selector($r[0]['sexual']),
			'$sexual_min'       => sexpref_selector_min($r[0]['sexual']),
			'$about'        => $r[0]['about'],
			'$homepage'     => $r[0]['homepage'],
			'$hometown'     => $r[0]['hometown'],
			'$politic'      => $r[0]['politic'],
			'$religion'     => $r[0]['religion'],
			'$keywords'     => $r[0]['keywords'],
			'$likes'        => $r[0]['likes'],
			'$dislikes'     => $r[0]['dislikes'],
			'$music'        => $r[0]['music'],
			'$book'         => $r[0]['book'],
			'$tv'           => $r[0]['tv'],
			'$film'         => $r[0]['film'],
			'$interest'     => $r[0]['interest'],
			'$romance'      => $r[0]['romance'],
			'$work'         => $r[0]['work'],
			'$education'    => $r[0]['education'],
			'$contact'      => $r[0]['contact'],
			'$channels'     => $r[0]['channels'],
			'$extra_fields' => $extra_fields,
		));

		$arr = array('profile' => $r[0], 'entry' => $o);
		call_hooks('profile_edit', $arr);

		return $o;
	}
	else {

		$r = q("SELECT * FROM `profile` WHERE `uid` = %d",
			local_channel());
		if(count($r)) {

			$tpl_header = get_markup_template('profile_listing_header.tpl');
			$o .= replace_macros($tpl_header,array(
				'$header' => t('Edit/Manage Profiles'),
				'$addstuff' => t('Add profile things'),
				'$stuff_desc' => t('Include desirable objects in your profile'),
				'$chg_photo' => t('Change profile photo'),
				'$cr_new' => t('Create New Profile'),
				'$cr_new_link' => 'profiles/new?t=' . get_form_security_token("profile_new")
			));


			$tpl = get_markup_template('profile_entry.tpl');

			foreach($r as $rr) {
				$o .= replace_macros($tpl, array(
					'$photo' => $rr['thumb'],
					'$id' => $rr['id'],
					'$alt' => t('Profile Image'),
					'$profile_name' => $rr['profile_name'],
					'$visible' => (($rr['is_default']) 
						? '<strong>' . translate_scope(map_scope($channel['channel_r_profile'])) . '</strong>' 
						: '<a href="' . $a->get_baseurl(true) . '/profperm/' . $rr['id'] . '" />' . t('Edit visibility') . '</a>')
				));
			}
			
		}
		return $o;
	}

}
