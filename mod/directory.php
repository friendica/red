<?php

require_once('include/dir_fns.php');


function directory_init(&$a) {
	$a->set_pager_itemspage(60);

}

function directory_aside(&$a) {

	if(local_user()) {
		require_once('include/contact_widgets.php');
		$a->set_widget('find_people',findpeople_widget());
	}
}


function directory_content(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	$o = '';
	nav_set_selected('directory');

	if(x($_POST,'search'))
		$search = notags(trim($_POST['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');


	$tpl = get_markup_template('directory_header.tpl');


	$dirmode = get_config('system','directory_mode');
	if($dirmode === false)
		$dirmode = DIRECTORY_MODE_NORMAL;

	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		$localdir = true;
		return;
	}

// FIXME
$localdir = true;


	if(! $localdir) {
		$directory = find_upstream_directory($dirmode);

		if($directory) {
			$url = $directory['url'];
		}
		else {
			$url = DIRECTORY_FALLBACK_MASTER . '/post';
		}
	}



	if($localdir) {
		if($search)
			$search = dbesc($search);
		$sql_extra = ((strlen($search)) ? " AND MATCH (`profile`.`name`, channel.channel_address, `pdesc`, `locality`,`region`,`country_name`,`gender`,`marital`,`sexual`,`about`,`romance`,`work`,`education`,`keywords` ) AGAINST ('$search' IN BOOLEAN MODE) " : "");


		$r = q("SELECT COUNT(channel_id) AS `total` FROM channel left join profile  on channel.channel_id = profile.uid WHERE `is_default` = 1 and not ( channel_pageflags & %d ) $sql_extra ",
			intval(PAGE_HIDDEN)
		);
		if($r)
			$a->set_pager_total($r[0]['total']);

		$order = " ORDER BY `name` ASC "; 


		$r = q("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, channel_name, channel_address, channel_hash, channel_timezone, channel_pageflags FROM `profile` LEFT JOIN channel ON channel_id = `profile`.`uid` WHERE `is_default` = 1 and not ( channel_pageflags & %d ) $sql_extra $order LIMIT %d , %d ",
			intval(PAGE_HIDDEN),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);
		if($r) {

			$entries = array();

			$photo = 'thumb';

			foreach($r as $rr) {

				$profile_link = chanlink_hash($rr['channel_hash']);
		
				$pdesc = (($rr['pdesc']) ? $rr['pdesc'] . '<br />' : '');

				$details = '';
				if(strlen($rr['locality']))
					$details .= $rr['locality'];
				if(strlen($rr['region'])) {
					if(strlen($rr['locality']))
						$details .= ', ';
					$details .= $rr['region'];
				}
				if(strlen($rr['country_name'])) {
					if(strlen($details))
						$details .= ', ';
					$details .= $rr['country_name'];
				}
				if(strlen($rr['dob'])) {
					if(($years = age($rr['dob'],$rr['timezone'],'')) != 0)
						$details .= '<br />' . t('Age: ') . $years ; 
				}
				if(strlen($rr['gender']))
					$details .= '<br />' . t('Gender: ') . $rr['gender'];

				$page_type = '';

				$profile = $rr;

				if((x($profile,'address') == 1)
					|| (x($profile,'locality') == 1)
					|| (x($profile,'region') == 1)
					|| (x($profile,'postal_code') == 1)
					|| (x($profile,'country_name') == 1))
				$location = t('Location:');

				$gender = ((x($profile,'gender') == 1) ? t('Gender:') : False);

				$marital = ((x($profile,'marital') == 1) ?  t('Status:') : False);
	
				$homepage = ((x($profile,'homepage') == 1) ?  t('Homepage:') : False);

				$about = ((x($profile,'about') == 1) ?  t('About:') : False);
			


				$entry = array(
					'id' => $rr['id'],
					'profile_link' => $profile_link,
					'photo' => $rr[$photo],
					'alttext' => $rr['channel_name'],
					'name' => $rr['channel_name'],
					'details' => $pdesc . $details,
					'profile' => $profile,
					'location' => $location,
					'gender'   => $gender,
					'pdesc'	=> $pdesc,
					'marital'  => $marital,
					'homepage' => $homepage,
					'about' => $about,

				);

				$arr = array('contact' => $rr, 'entry' => $entry);

				call_hooks('directory_item', $arr);
			
				unset($profile);
				unset($location);

				$entries[] = $entry;

			}

			logger('entries: ' . print_r($entries,true));

			$o .= replace_macros($tpl, array(
				'$search' => $search,
				'$desc' => t('Find'),
				'$finddsc' => t('Finding:'),
				'$safetxt' => htmlspecialchars($search,ENT_QUOTES,'UTF-8'),
				'$entries' => $entries,
				'$dirlbl' => t('Directory'),
				'$submit' => t('Find')
			));


			$o .= paginate($a);

		}

	else
		info( t("No entries (some entries may be hidden).") . EOL);

	}

	return $o;
}
