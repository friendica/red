<?php

require_once('include/dir_fns.php');
require_once('include/widgets.php');


function directory_init(&$a) {
	$a->set_pager_itemspage(60);

}

function directory_content(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	$safe_mode = 1;

	$observer = get_observer_hash();
	
	if($observer) {
		$safe_mode = get_xconfig($observer,'directory','safe_mode');
	}
	if($safe_mode === false)
		$safe_mode = 1;
	else
		$safe_mode = intval($safe_mode);

	if(x($_REQUEST,'safe'))
		$safe_mode = (intval($_REQUEST['safe']));




	$o = '';
	nav_set_selected('directory');

	if(x($_POST,'search'))
		$search = notags(trim($_POST['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

	$advanced = ((x($_REQUEST,'query')) ? notags(trim($_REQUEST['query'])) : '');

	$keywords = (($_GET['keywords']) ? $_GET['keywords'] : '');

	$tpl = get_markup_template('directory_header.tpl');

		
	$dirmode = intval(get_config('system','directory_mode'));

	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		$url = z_root() . '/dirsearch';
	}
	if(! $url) {
		$directory = find_upstream_directory($dirmode);
		$url = $directory['url'] . '/dirsearch';
	}

	logger('mod_directory: URL = ' . $url, LOGGER_DEBUG);

	$contacts = array();

	if(local_user()) {
		$x = q("select abook_xchan from abook where abook_channel = %d",
			intval(local_user())
		);
		if($x) {
			foreach($x as $xx)
				$contacts[] = $xx['abook_xchan'];
		}
	}



	if($url) {
		// We might want to make the tagadelic count (&kw=) configurable or turn it off completely.

		$numtags = get_config('system','directorytags');

		$kw = ((intval($numtags)) ? $numtags : 24);
		$query = $url . '?f=&kw=' . $kw . (($safe_mode != 1) ? '&safe=' . $safe_mode : '');

		if($search)
			$query .= '&name=' . urlencode($search) . '&keywords=' . urlencode($search);
		if(strpos($search,'@'))
			$query .= '&address=' . urlencode($search);
		if($keywords)
			$query .= '&keywords=' . urlencode($keywords);
		if($advanced)
			$query .= '&query=' . urlencode($advanced);

		$sort_order  = ((x($_REQUEST,'order')) ? $_REQUEST['order'] : '');
		if($sort_order)
			$query .= '&order=' . urlencode($sort_order);

		if($a->pager['page'] != 1)
			$query .= '&p=' . $a->pager['page'];

		logger('mod_directory: query: ' . $query);

		$x = z_fetch_url($query);
		logger('directory: return from upstream: ' . print_r($x,true), LOGGER_DATA);

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
						$connect_link = ((local_user()) ? z_root() . '/follow?f=&url=' . urlencode($rr['address']) : ''); 		

						if(in_array($rr['hash'],$contacts))
							$connect_link = '';

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
							'hash' => $rr['hash'],
							'alttext' => $rr['name'] . ' ' . $rr['address'],
							'name' => $rr['name'],
							'details' => $pdesc . $details,
							'profile' => $profile,
							'address' =>  $rr['address'],
							'location' => $location,
							'gender'   => $gender,
							'pdesc'	=> $pdesc,
							'marital'  => $marital,
							'homepage' => $homepage,
							'about' => $about,
							'conn_label' => t('Connect'),
							'connect' => $connect_link,
						);

						$arr = array('contact' => $rr, 'entry' => $entry);

						call_hooks('directory_item', $arr);
			
						$entries[] = $arr['entry'];
						unset($profile);
						unset($location);


					}

					if($j['keywords']) {
						$a->data['directory_keywords'] = $j['keywords'];
					}

					logger('mod_directory: entries: ' . print_r($entries,true), LOGGER_DATA);


					if($_REQUEST['aj']) {
						if($entries) {
							$o = replace_macros(get_markup_template('directajax.tpl'),array(
								'$entries' => $entries
							));
						}
						else {
							$o = '<div id="content-complete"></div>';
						}
						echo $o;
						killme();
					}
					else {

						$o .= "<script> var page_query = '" . $_GET['q'] . "'; var extra_args = '" . extra_query_args() . "' ; </script>";
						$o .= replace_macros($tpl, array(
							'$search' => $search,
							'$desc' => t('Find'),
							'$finddsc' => t('Finding:'),
							'$safetxt' => htmlspecialchars($search,ENT_QUOTES,'UTF-8'),
							'$entries' => $entries,
							'$dirlbl' => t('Directory'),
							'$submit' => t('Find'),
							'$next' => alt_pager($a,$j['records'], t('next page'), t('previous page'))

						));


					}

				}
				else {
					if($_REQUEST['aj']) {
						$o = '<div id="content-complete"></div>';
						echo $o;
						killme();
					}
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

