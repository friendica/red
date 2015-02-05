<?php /** @file */

require_once('include/permissions.php');

function find_upstream_directory($dirmode) {
	global $DIRECTORY_FALLBACK_SERVERS;

	$preferred = get_config('system','directory_server');
	if(! $preferred) {

		/**
		 * No directory has yet been set. For most sites, pick one at random
		 * from our list of directory servers. However, if we're a directory
		 * server ourself, point at the local instance
		 * We will then set this value so this should only ever happen once.
		 * Ideally there will be an admin setting to change to a different 
		 * directory server if you don't like our choice or if circumstances change.
		 */

		$dirmode = intval(get_config('system','directory_mode'));
		if($dirmode == DIRECTORY_MODE_NORMAL) {
			$toss = mt_rand(0,count($DIRECTORY_FALLBACK_SERVERS));
			$preferred = $DIRECTORY_FALLBACK_SERVERS[$toss];
			set_config('system','directory_server',$preferred);
		}
		else{
			set_config('system','directory_server',z_root());
		}
	}
	return array('url' => $preferred);
}

function check_upstream_directory() {

	/**
	* Directories may come and go over time.  We will need to check that our 
	* directory server is still valid occasionally, and reset to something that
	* is if our directory has gone offline for any reason
	*/

	$directory = get_config('system','directory_server');

	// it's possible there is no directory server configured and the local hub is being used.
	// If so, default to preserving the absence of a specific server setting.

	$isadir = true; 

	if($directory) {
		$h = parse_url($directory);
		if($h) {
			$x = zot_finger('sys@' . $h['host']);
			if($x['success']) {
				$j = json_decode($x['body'],true);
				if(array_key_exists('site',$j) && array_key_exists('directory_mode',$j['site'])) {
					if($j['site']['directory_mode'] === 'normal') {
						$isadir = false;
					}
				}
			}
		}
	}

	if(! $isadir)
		set_config('system','directory_server','');
	return;
}
	
function dir_sort_links() {
	// Build urls without order and pubforums so it's easy to tack on the changed value
	// Probably there's an easier way to do this

	$current_order = (($_REQUEST['order']) ? $_REQUEST['order'] : 'normal');
	$url = 'directory?f=';

	$tmp = array_merge($_GET,$_POST);
	unset($tmp['order']);
	unset($tmp['q']);
	unset($tmp['f']);
	$sorturl = $url . http_build_query($tmp);

	$tmp = array_merge($_GET,$_POST);
	unset($tmp['pubforums']);
	unset($tmp['q']);
	unset($tmp['f']);
	$forumsurl = $url . http_build_query($tmp);

	$o = replace_macros(get_markup_template('dir_sort_links.tpl'), array(
		'$header' => t('Directory Options'),
		'$normal' => t('Alphabetic'),
		'$reverse' => t('Reverse Alphabetic'),
		'$date' => t('Newest to Oldest'),
		'$reversedate' => t('Oldest to Newest'),
		'$pubforums' => t('Public Forums Only'),
		'$pubforumsonly' => x($_REQUEST,'pubforums') ? $_REQUEST['pubforums'] : '',
		'$sort' => t('Sort'),
		'$selected_sort' => $current_order,
		'$sorturl' => $sorturl,
		'$forumsurl' => $forumsurl,

	));
	return $o;
}

function dir_safe_mode() {
	$observer = get_observer_hash();
	if (! $observer)
		return;
	if ($observer)
		$safe_mode = get_xconfig($observer,'directory','safe_mode');		
	if($safe_mode === '0')
		$toggle = t('Enable Safe Search');
	else
		$toggle = t('Disable Safe Search');
	$o = replace_macros(get_markup_template('safesearch.tpl'), array(
		'$safemode' => t('Safe Mode'),
		'$toggle' => $toggle,
	));

	return $o;
}

function sync_directories($dirmode) {

	if($dirmode == DIRECTORY_MODE_STANDALONE || $dirmode == DIRECTORY_MODE_NORMAL)
		return;

	$realm = get_directory_realm();
	if($realm == DIRECTORY_REALM) {
		$r = q("select * from site where (site_flags & %d) > 0 and site_url != '%s' and ( site_realm = '%s' or site_realm = '') ",
			intval(DIRECTORY_MODE_PRIMARY|DIRECTORY_MODE_SECONDARY),
			dbesc(z_root()),
			dbesc($realm)
		);
	}
	else {
		$r = q("select * from site where (site_flags & %d) > 0 and site_url != '%s' and site_realm like '%s' ",
			intval(DIRECTORY_MODE_PRIMARY|DIRECTORY_MODE_SECONDARY),
			dbesc(z_root()),
			dbesc(protect_sprintf('%' . $realm . '%'))
		);
	}

	// If there are no directory servers, setup the fallback master
	// FIXME - what to do if we're in a different realm?

	if((! $r) && (z_root() != DIRECTORY_FALLBACK_MASTER)) {
		$r = array(
			'site_url' => DIRECTORY_FALLBACK_MASTER,
			'site_flags' => DIRECTORY_MODE_PRIMARY,
			'site_update' => NULL_DATE, 
			'site_directory' => DIRECTORY_FALLBACK_MASTER . '/dirsearch',
			'site_realm' => DIRECTORY_REALM
		);
		$x = q("insert into site ( site_url, site_flags, site_update, site_directory, site_realm )
			values ( '%s', %d', '%s', '%s', '%s' ) ",
			dbesc($r[0]['site_url']),
			intval($r[0]['site_flags']),
			dbesc($r[0]['site_update']),
			dbesc($r[0]['site_directory']),
			dbesc($r[0]['site_realm'])
		);

		$r = q("select * from site where (site_flags & %d) > 0 and site_url != '%s'",
			intval(DIRECTORY_MODE_PRIMARY|DIRECTORY_MODE_SECONDARY),
			dbesc(z_root())
		);

	} 
	if(! $r)
		return;

	foreach($r as $rr) {
		if(! $rr['site_directory'])
			continue;

		logger('sync directories: ' . $rr['site_directory']);

		// for brand new directory servers, only load the last couple of days. Everything before that will be repeats.

		$syncdate = (($rr['site_sync'] === NULL_DATE) ? datetime_convert('UTC','UTC','now - 2 days') : $rr['site_sync']);
		$x = z_fetch_url($rr['site_directory'] . '?f=&sync=' . urlencode($syncdate));

		if(! $x['success'])
			continue;
		$j = json_decode($x['body'],true);
		if((! $j['transactions']) || (! is_array($j['transactions'])))
			continue;

		q("update site set site_sync = '%s' where site_url = '%s'",
			dbesc(datetime_convert()),
			dbesc($rr['site_url'])
		);

		logger('sync_directories: ' . $rr['site_url'] . ': ' . print_r($j,true), LOGGER_DATA);

		if(count($j['transactions'])) {
			foreach($j['transactions'] as $t) {
				$r = q("select * from updates where ud_guid = '%s' limit 1",
					dbesc($t['transaction_id'])
				);
				if($r)
					continue;
				$ud_flags = 0;
				if(is_array($t['flags']) && in_array('deleted',$t['flags']))
					$ud_flags |= UPDATE_FLAGS_DELETED;
				if(is_array($t['flags']) && in_array('forced',$t['flags']))
					$ud_flags |= UPDATE_FLAGS_FORCED;

				$z = q("insert into updates ( ud_hash, ud_guid, ud_date, ud_flags, ud_addr )
					values ( '%s', '%s', '%s', %d, '%s' ) ",
					dbesc($t['hash']),
					dbesc($t['transaction_id']),
					dbesc($t['timestamp']),
					intval($ud_flags),
					dbesc($t['address'])
				);
			}
		}
		if(count($j['ratings'])) {
			foreach($j['ratings'] as $rr) {		
				$x = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1",
					dbesc($rr['channel']),
					dbesc($rr['target'])
				);
				if($x && $x[0]['xlink_updated'] >= $rr['edited'])
					continue;
				$y = q("select xchan_pubkey from xchan where xchan_hash = '%s' limit 1",
					dbesc($rr['channel'])
				);
				if(! $y) {
					logger('key unavailable on this site for ' . $rr['channel']);
					continue;
				}
				if(! rsa_verify($rr['target'] . '.' . $rr['rating'] . '.' . $rr['rating_text'], base64url_decode($rr['signature']),$y[0]['xchan_pubkey'])) {
			        logger('failed to verify rating');
					continue;
				}

				if($x) {
					$z = q("update xlink set xlink_rating = %d, xlink_rating_text = '%s', xlink_sig = '%s', xlink_updated = '%s' where xlink_id = %d",
						intval($rr['rating']),
						dbesc($rr['rating_text']),
						dbesc($rr['signature']),
						dbesc(datetime_convert()),
						intval($x[0]['xlink_id'])
	        		);
			        logger('rating updated');
    			}
    			else {
        			$z = q("insert into xlink ( xlink_xchan, xlink_link, xlink_rating, xlink_rating_text, xlink_sig, xlink_updated, xlink_static ) values( '%s', '%s', %d, '%s', '%s', 1 ) ",
						dbesc($rr['channel']),
						dbesc($rr['target']),
						intval($rr['rating']),
						dbesc($rr['rating_text']),
						dbesc($rr['signature']),
						dbesc(datetime_convert())
					);
					logger('rating created');
				}
			}
		}
	}
}


function update_directory_entry($ud) {

	logger('update_directory_entry: ' . print_r($ud,true), LOGGER_DATA);

	if($ud['ud_addr'] && (! ($ud['ud_flags'] & UPDATE_FLAGS_DELETED))) {
		$success = false;
		$x = zot_finger($ud['ud_addr'],'');
		if($x['success']) {
			$j = json_decode($x['body'],true);
			if($j)
				$success = true;
			$y = import_xchan($j,0,$ud);
		}
		if(! $success) {
			$r = q("update updates set ud_last = '%s' where ud_addr = '%s'",
				dbesc(datetime_convert()),
				dbesc($ud['ud_addr'])
			);
		}
	}

}


/**
 * @function local_dir_update($uid,$force)
 *     push local channel updates to a local directory server 
 *
 */

function local_dir_update($uid,$force) {

	logger('local_dir_update: uid: ' . $uid, LOGGER_DEBUG);

	$p = q("select channel.channel_hash, channel_address, channel_timezone, profile.* from profile left join channel on channel_id = uid where uid = %d and is_default = 1",
		intval($uid)
	);

	$profile = array();
	$profile['encoding'] = 'zot';

	if($p) {
		$hash = $p[0]['channel_hash'];

		$profile['description'] = $p[0]['pdesc'];
		$profile['birthday']    = $p[0]['dob'];
		if($age = age($p[0]['dob'],$p[0]['channel_timezone'],''))  
			$profile['age'] = $age;

		$profile['gender']      = $p[0]['gender'];
		$profile['marital']     = $p[0]['marital'];
		$profile['sexual']      = $p[0]['sexual'];
		$profile['locale']      = $p[0]['locality'];
		$profile['region']      = $p[0]['region'];
		$profile['postcode']    = $p[0]['postal_code'];
		$profile['country']     = $p[0]['country_name'];
		$profile['about']       = $p[0]['about'];
		$profile['homepage']    = $p[0]['homepage'];
		$profile['hometown']    = $p[0]['hometown'];

		if($p[0]['keywords']) {
			$tags = array();
			$k = explode(' ',$p[0]['keywords']);
			if($k)
				foreach($k as $kk)
					if(trim($kk))
						$tags[] = trim($kk);
			if($tags)
				$profile['keywords'] = $tags;
		}

		$hidden = (1 - intval($p[0]['publish']));

		logger('hidden: ' . $hidden);

		$r = q("select xchan_flags from xchan where xchan_hash = '%s' limit 1",
			dbesc($p[0]['channel_hash'])
		);

		// Be careful - XCHAN_FLAGS_HIDDEN should evaluate to 1
		if(($r[0]['xchan_flags'] & XCHAN_FLAGS_HIDDEN) != $hidden)
			$new_flags = $r[0]['xchan_flags'] ^ XCHAN_FLAGS_HIDDEN;
		else
			$new_flags = $r[0]['xchan_flags'];
		
		if($new_flags != $r[0]['xchan_flags']) {			

			$r = q("update xchan set xchan_flags = %d  where xchan_hash = '%s'",
				intval($new_flags),
				dbesc($p[0]['channel_hash'])
			);

		}

		$address = $p[0]['channel_address'] . '@' . get_app()->get_hostname();

		if(perm_is_allowed($uid,'','view_profile')) {
			import_directory_profile($hash,$profile,$address,0);
		}
		else {
			// they may have made it private
			$r = q("delete from xprof where xprof_hash = '%s'",
				dbesc($hash)
			);
			$r = q("delete from xtag where xtag_hash = '%s'",
				dbesc($hash)
			);
		}
	}

	$ud_hash = random_string() . '@' . get_app()->get_hostname();
	update_modtime($hash,$ud_hash,$p[0]['channel_address'] . '@' . get_app()->get_hostname(),(($force) ? UPDATE_FLAGS_FORCED : UPDATE_FLAGS_UPDATED));

}
	
