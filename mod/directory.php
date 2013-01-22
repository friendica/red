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

	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		$url = z_root() . '/dirsearch';
	}
	if(! $url) {
		$directory = find_upstream_directory($dirmode);

		if($directory) {
			$url = $directory['url'];
		}
		else {
			$url = DIRECTORY_FALLBACK_MASTER . '/dirsearch';
		}
	}

	if($url) {
		$query = $url . '?f=' ;
		if($search)
			$query .= '&name=' . urlencode($search);
		if(strpos($search,'@'))
			$query .= '&address=' . urlencode($search);

		if($a->pager['page'] != 1)
			$query .= '&p=' . $a->pager['page'];

		logger('mod_directory: query: ' . $query);

		$x = z_fetch_url($query);
		logger('directory: return from upstream: ' . print_r($x,true));

		if($x['success']) {
			$t = 0;
			$j = json_decode($x['body'],true);
			if($j) {

				if($j['results']) {

					$entries = array();

					$photo = 'thumb';

					foreach($j['results'] as $rr) {

						$profile_link = chanlink_url($rr['url']);
		
						$pdesc = (($rr['description']) ? $rr['description'] . '<br />' : '');
	
						$details = '';
						if(strlen($rr['locale']))
							$details .= $rr['locale'];
						if(strlen($rr['region'])) {
							if(strlen($rr['locale']))
								$details .= ', ';
							$details .= $rr['region'];
						}
						if(strlen($rr['country'])) {
							if(strlen($details))
								$details .= ', ';
							$details .= $rr['country'];
						}
						if(strlen($rr['birthday'])) {
							if(($years = age($rr['birthday'],'UTC','')) != 0)
								$details .= '<br />' . t('Age: ') . $years ; 
						}
						if(strlen($rr['gender']))
							$details .= '<br />' . t('Gender: ') . $rr['gender'];

						$page_type = '';

						$profile = $rr;

						if ((x($profile,'locale') == 1)
							|| (x($profile,'region') == 1)
							|| (x($profile,'postcode') == 1)
							|| (x($profile,'country') == 1))
						$location = t('Location:');

						$gender = ((x($profile,'gender') == 1) ? t('Gender:') : False);
	
						$marital = ((x($profile,'marital') == 1) ?  t('Status:') : False);
		
						$homepage = ((x($profile,'homepage') == 1) ?  t('Homepage:') : False);

						$about = ((x($profile,'about') == 1) ?  t('About:') : False);
			

						$entry = array(
							'id' => ++$t,
							'profile_link' => $profile_link,
							'photo' => $rr['photo'],
							'alttext' => $rr['name'] . ' ' . $rr['address'],
							'name' => $rr['name'],
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
			
						$entries[] = $entry;

						unset($profile);
						unset($location);


					}

					logger('mod_directory: entries: ' . print_r($entries,true), LOGGER_DATA);

					$o .= replace_macros($tpl, array(
						'$search' => $search,
						'$desc' => t('Find'),
						'$finddsc' => t('Finding:'),
						'$safetxt' => htmlspecialchars($search,ENT_QUOTES,'UTF-8'),
						'$entries' => $entries,
						'$dirlbl' => t('Directory'),
						'$submit' => t('Find')
					));


					$o .= alt_pager($a,$j['records'], t('more'), t('back'));

				}
				else {
					if($a->pager['page'] == 1 && $j['records'] == 0 && strpos($search,'@')) {
						goaway(z_root() . '/chanview/?f=&address=' . $search);
					}
					info( t("No entries (some entries may be hidden).") . EOL);
				}
			}
		}
	}
	return $o;
}

