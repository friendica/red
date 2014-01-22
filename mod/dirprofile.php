<?php

require_once('include/dir_fns.php');
require_once('include/bbcode.php');

function dirprofile_init(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	$hash = $_REQUEST['hash'];
	if(! $hash)
		return '';

	$o = '';

	$dirmode = intval(get_config('system','directory_mode'));

	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		$url = z_root() . '/dirsearch';
	}
	if(! $url) {
		$directory = find_upstream_directory($dirmode);

		if($directory) {
			$url = $directory['url'] . '/dirsearch';
		}
		else {
			$url = DIRECTORY_FALLBACK_MASTER . '/dirsearch';
		}
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

		$query = $url . '?f=&hash=' . $hash;

		$x = z_fetch_url($query);
		logger('dirprofile: return from upstream: ' . print_r($x,true), LOGGER_DATA);

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

						$qrlink = zid($rr['url']);
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

	
						$marital = ((x($profile,'marital') == 1) ?  t('Status: ') . $profile['marital']  : False);
						$sexual = ((x($profile,'sexual') == 1) ?  t('Sexual Preference: ') . $profile['sexual'] : False);
		
						$homepage = ((x($profile,'homepage') == 1) ?  t('Homepage: ') . linkify($profile['homepage']) : False);
						$hometown = ((x($profile,'hometown') == 1) ?  t('Hometown: ') . $profile['hometown']  : False);

						$about = ((x($profile,'about') == 1) ?  t('About: ') . bbcode($profile['about']) : False);
						
						$keywords = ((x($profile,'keywords')) ? $profile['keywords'] : '');
						if($keywords) {
							$keywords = str_replace(',',' ', $keywords);
							$keywords = str_replace('  ',' ', $keywords);
							$karr = explode(' ', $keywords);
							$out = '';
							if($karr) {
								if(local_user()) {
									$r = q("select keywords from profile where uid = %d and is_default = 1 limit 1",
										intval(local_user())
									);
									if($r) {
										$keywords = str_replace(',',' ', $r[0]['keywords']);
										$keywords = str_replace('  ',' ', $keywords);
										$marr = explode(' ', $keywords);
									}
								}
								foreach($karr as $k) {
									if(strlen($out))
										$out .= ', ';
									if($marr && in_arrayi($k,$marr))
										$out .= '<strong>' . $k . '</strong>';
									else
										$out .= $k;
								}
							}
			
						}
						$entry = replace_macros(get_markup_template('direntry_large.tpl'), array(
							'$id' => ++$t,
							'$profile_link' => $profile_link,
							'$qrlink' => $qrlink,
							'$photo' => $rr['photo_l'],
							'$alttext' => $rr['name'] . ' ' . $rr['address'],
							'$name' => $rr['name'],
							'$details' => $pdesc . $details,
							'$profile' => $profile,
							'$address' => $rr['address'],
							'$location' => $location,
							'$gender'   => $gender,
							'$pdesc'	=> $pdesc,
							'$marital'  => $marital,
							'$homepage' => $homepage,
							'$hometown' => $hometown,
							'$about' => $about,
							'$kw' => (($out) ? t('Keywords: ') : ''),
							'$keywords' => $out,
							'$conn_label' => t('Connect'),
							'$connect' => $connect_link,
						));


						echo $entry;
						killme();

					}
				}
				else {
					info( t("Not found.") . EOL);
				}
			}
		}
	}




}