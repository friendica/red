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


	$dirmode = intval(get_config('system','directory_mode'));

//	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
//		$localdir = true;
//		return;
//	}

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
		$sql_extra = ((strlen($search)) ? " AND MATCH ( xchan_name, xchan_addr, xprof_desc, xprof_locale, xprof_region, xprof_country, xprof_gdner, xprof_marital, tags ) AGAINST ('$search' IN BOOLEAN MODE) " : "");


		$r = q("SELECT COUNT(xchan_hash) AS `total`, group_concat(xtag_term separator ', ') as tags FROM xchan left join xprof  on xchan_hash = xprof_hash left join xtag on xtag_hash = xchan_hash $sql_extra group by xchan_hash");
		if($r)
			$a->set_pager_total($r[0]['total']);

		$order = " ORDER BY `xchan_name` ASC "; 


		$r = q("SELECT xchan.*, xprof.*, group_concat(xtag_term separator ', ') as tags from xchan left join xprof on xchan_hash = xprof_hash left join xtag on xtag_hash = xchan_hash $sql_extra group by xchan_hash $order LIMIT %d , %d ",
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);


		if($r) {

			$entries = array();

			$photo = 'thumb';

			foreach($r as $rr) {

				$profile_link = chanlink_hash($rr['xchan_hash']);
		
				$pdesc = (($rr['xprof_desc']) ? $rr['xprof_desc'] . '<br />' : '');

				$details = '';
				if(strlen($rr['xprof_locale']))
					$details .= $rr['xprof_locale'];
				if(strlen($rr['xprof_region'])) {
					if(strlen($rr['xprof_locale']))
						$details .= ', ';
					$details .= $rr['xprof_region'];
				}
				if(strlen($rr['xprof_country'])) {
					if(strlen($details))
						$details .= ', ';
					$details .= $rr['xprof_country'];
				}
				if(strlen($rr['xprof_dob'])) {
					if(($years = age($rr['xprof_dob'],'UTC','')) != 0)
						$details .= '<br />' . t('Age: ') . $years ; 
				}
				if(strlen($rr['xprof_gender']))
					$details .= '<br />' . t('Gender: ') . $rr['xprof_gender'];

				$page_type = '';

				$profile = $rr;

				if ((x($profile,'xprof_locale') == 1)
					|| (x($profile,'xprof_region') == 1)
					|| (x($profile,'xprof_postcode') == 1)
					|| (x($profile,'xprof_country') == 1))
				$location = t('Location:');

				$gender = ((x($profile,'xprof_gender') == 1) ? t('Gender:') : False);

				$marital = ((x($profile,'marital') == 1) ?  t('Status:') : False);
	
				$homepage = ((x($profile,'homepage') == 1) ?  t('Homepage:') : False);

				$about = ((x($profile,'about') == 1) ?  t('About:') : False);
			
				$t = 0;

				$entry = array(
					'id' => ++$t,
					'profile_link' => $profile_link,
					'photo' => $rr[xchan_photo_m],
					'alttext' => $rr['xchan_name'],
					'name' => $rr['xchan_name'],
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
