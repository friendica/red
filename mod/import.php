<?php

// Import a channel, either by direct file upload or via
// connection to original server. 

require_once('include/Contact.php');
require_once('include/zot.php');

function import_post(&$a) {


	$data     = null;
	$seize    = ((x($_REQUEST,'make_primary')) ? intval($_REQUEST['make_primary']) : 0);

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);
	$filetype = $_FILES['userfile']['type'];


	if($src) {
		if($filesize) {
			$data = @file_get_contents($src);
		}
	}

	if(! $src) {
		$old_address = ((x($_REQUEST,'old_address')) ? $_REQUEST['old_address'] : '');
		if(! $old_address) {
			logger('mod_import: nothing to import.');
			notice( t('Nothing to import.') . EOL);
			return;
		}

		$email    = ((x($_REQUEST,'email'))    ? $_REQUEST['email']    : '');
		$password = ((x($_REQUEST,'password')) ? $_REQUEST['password'] : '');

		$channelname = substr($old_address,0,strpos($old_address,'@'));
		$servername  = substr($old_address,strpos($old_address,'@')+1);

		$scheme = 'https://';
		$api_path = '/api/red/channel/export/basic?f=&channel=' . $channelname;
		$binary = false;
		$redirects = 0;
		$opts = array('http_auth' => $email . ':' . $password);
		$url = $scheme . $servername . $api_path;
		$ret = z_fetch_url($url, $binary, $redirects, $opts);
		if(! $ret['success'])
			$ret = z_fetch_url('http://' . $servername . $api_path, $binary, $redirects, $opts);
		if($ret['success'])
			$data = $ret['body'];
		else
			notice( t('Unable to download data from old server') . EOL);

	}

	if(! $data) {
		logger('mod_import: empty file.');
		notice( t('Imported file is empty.') . EOL);
		return;
	}

	$data = json_decode($data,true);

//	logger('import: data: ' . print_r($data,true));

//	print_r($data);

	// import channel

	$channel = $data['channel'];

	$r = q("select * from channel where (channel_guid = '%s' or channel_hash = '%s' or channel_address = '%s' ) limit 1",
		dbesc($channel['channel_guid']),
		dbesc($channel['channel_hash']),
		dbesc($channel['channel_address'])
	);

	// We should probably also verify the hash 

	if($r) {
		logger('mod_import: duplicate channel. ', print_r($channel,true));
		notice( t('Cannot create a duplicate channel identifier on this system. Import failed.') . EOL);
		return;
	}

	unset($channel['channel_id']);
	$channel['channel_account_id'] = get_account_id();
	$channel['channel_primary'] = (($seize) ? 1 : 0);
	
	dbesc_array($channel);

	$r = dbq("INSERT INTO channel (`" 
		. implode("`, `", array_keys($channel)) 
		. "`) VALUES ('" 
		. implode("', '", array_values($channel)) 
		. "')" );

	if(! $r) {
		logger('mod_import: channel clone failed. ', print_r($channel,true));
		notice( t('Channel clone failed. Import failed.') . EOL);
		return;
	}

	$r = q("select * from channel where channel_account_id = %d and channel_guid = '%s' limit 1",
		intval(get_account_id()),
		$channel['channel_guid']   // Already dbesc'd
	);
	if(! $r) {
		logger('mod_import: channel not found. ', print_r($channel,true));
		notice( t('Cloned channel not found. Import failed.') . EOL);
		return;
	}
	// reset
	$channel = $r[0];

	set_default_login_identity($a->get_account(),$channel['channel_id'],false);

	if($data['photo']) {
		require_once('include/photo/photo_driver.php');
		import_channel_photo(base64url_decode($data['photo']['data']),$data['photo']['type'],get_account_id,$channel['channel_id']);
	}

	$profiles = $data['profile'];
	if($profiles) {
		foreach($profiles as $profile) {
			unset($profile['id']);
			$profile['aid'] = get_account_id();
			$profile['uid'] = $channel['channel_id'];

			// we are going to reset all profile photos to the original
			// somebody will have to fix this later and put all the applicable photos into the export

			$profile['photo'] = z_root() . '/photo/profile/l/' . $channel['channel_id'];
			$profile['thumb'] = z_root() . '/photo/profile/m/' . $channel['channel_id'];


			dbesc_array($profile);
			$r = dbq("INSERT INTO profile (`" 
				. implode("`, `", array_keys($profile)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($profile)) 
				. "')" );
		}
	}


	$hublocs = $data['hubloc'];
	if($hublocs) {
		foreach($hublocs as $hubloc) {
			$arr = array(
				'guid' => $hubloc['hubloc_guid'],
				'guid_sig' => $hubloc['guid_sig'],
				'url' => $hubloc['hubloc_url'],
				'url_sig' => $hubloc['hubloc_url_sig']
			);
			if(($hubloc['hubloc_hash'] === $channel['channel_hash']) && ($hubloc['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) && ($seize))
				$hubloc['hubloc_flags'] = ($hubloc['hubloc_flags'] ^ HUBLOC_FLAGS_PRIMARY);

			if(! zot_gethub($arr)) {				
				unset($hubloc['hubloc_id']);
				dbesc_array($hubloc);
		
				$r = dbq("INSERT INTO hubloc (`" 
					. implode("`, `", array_keys($hubloc)) 
					. "`) VALUES ('" 
					. implode("', '", array_values($hubloc)) 
					. "')" );

			}

		}
	}

	// create new hubloc for the new channel at this site

	$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_flags, 
		hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey )
		values ( '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s' )",
		dbesc($channel['channel_guid']),
		dbesc($channel['channel_guid_sig']),
		dbesc($channel['channel_hash']),
		dbesc($channel['channel_address'] . '@' . get_app()->get_hostname()),
		intval(($seize) ? HUBLOC_FLAGS_PRIMARY : 0),
		dbesc(z_root()),
		dbesc(base64url_encode(rsa_sign(z_root(),$channel['channel_prvkey']))),
		dbesc(get_app()->get_hostname()),
		dbesc(z_root() . '/post'),
		dbesc(get_config('system','pubkey'))
	);

	// reset the original primary hubloc if it is being seized

	if($seize)
		$r = q("update hubloc set hubloc_flags = (hubloc_flags ^ %d) where (hubloc_flags & %d) and hubloc_hash = '%s' and hubloc_url != '%s' ",
			intval(HUBLOC_FLAGS_PRIMARY),
			intval(HUBLOC_FLAGS_PRIMARY),
			dbesc($channel['channel_hash']),
			dbesc(z_root())
		);
 
	// import xchans and contact photos


	$xchans = $data['xchan'];
	if($xchans) {
		foreach($xchans as $xchan) {
			$r = q("select xchan_hash from xchan where xchan_hash = '%s' limit 1",
				dbesc($xchan['xchan_hash'])
			);
			if($r)
				continue;

			dbesc_array($xchan);
		
			$r = dbq("INSERT INTO xchan (`" 
				. implode("`, `", array_keys($xchan)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($xchan)) 
				. "')" );

	
			require_once('include/photo/photo_driver.php');
			$photos = import_profile_photo($xchan['xchan_photo_l'],$xchan['xchan_hash']);
			$r = q("update xchan set xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s'
				where xchan_hash = '%s' limit 1",
				dbesc($photos[0]),
				dbesc($photos[1]),
				dbesc($photos[2]),
				dbesc($photos[3]),
				dbesc($xchan_hash)
			);
			
		}
	}

// FIXME - ensure we have an xchan if somebody is trying to pull a fast one

	
	// import contacts
	$abooks = $data['abook'];
	if($abooks) {
		foreach($abooks as $abook) {
			unset($abook['abook_id']);
			$abook['abook_account'] = get_account_id();
			$abook['abook_channel'] = $channel['channel_id'];
			dbesc_array($abook);
			$r = dbq("INSERT INTO abook (`" 
				. implode("`, `", array_keys($abook)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($abook)) 
				. "')" );
		}
	}


	$configs = $data['config'];
	if($configs) {
		foreach($configs as $config) {
			unset($config['id']);
			$config['uid'] = $channel['channel_id'];
			dbesc_array($config);
			$r = dbq("INSERT INTO pconfig (`" 
				. implode("`, `", array_keys($config)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($config)) 
				. "')" );
		}
	}

	$groups = $data['group'];
	if($groups) {
		$saved = array();
		foreach($groups as $group) {
			$saved[$group['hash']] = array('old' => $group['id']);
			unset($group['id']);
			$group['uid'] = $channel['channel_id'];
			dbesc_array($group);
			$r = dbq("INSERT INTO group (`" 
				. implode("`, `", array_keys($group)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($group)) 
				. "')" );
		}
		$r = q("select * from `group` where uid = %d",
			intval($channel['channel_id'])
		);
		if($r) {
			foreach($r as $rr) {
				$saved[$rr['hash']]['new'] = $rr['id'];
			}
		} 
	}

	$group_members = $data['group_member'];
	if($groups_members) {
		foreach($group_members as $group_member) {
			unset($group_member['id']);
			$group_member['uid'] = $channel['channel_id'];
			foreach($saved as $x) {
				if($x['old'] == $group_member['gid'])
					$group_member['gid'] = $x['new'];
			}
			dbesc_array($group_member);
			$r = dbq("INSERT INTO group_member (`" 
				. implode("`, `", array_keys($group_member)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($group_member)) 
				. "')" );
		}
	}

// FIXME - ensure we have a self entry if somebody is trying to pull a fast one

	if($seize) {
		// notify old server that it is no longer primary.
		
	}

	// send out refresh requests

	notice( t('Import completed.') . EOL);

}


function import_content(&$a) {


	$o = replace_macros(get_markup_template('channel_import.tpl'),array(
		'$title' => t('Import Channel'),
		'$desc' => t('Use this form to import an existing channel from a different server/hub. You may retrieve the channel identity from the old server/hub via the network or provide an export file. Only identity and connections/relationships will be imported. Importation of content is not yet available.'),
		'$label_filename' => t('File to Upload'),
		'$choice' => t('Or provide the old server/hub details'),
		'$label_old_address' => t('Your old identity address (xyz@example.com)'),
		'$label_old_email' => t('Your old login email address'),
		'$label_old_pass' => t('Your old login password'),
		'$common' => t('For either option, please choose whether to make this hub your new primary address, or whether your old location should continue this role. You will be able to post from either location, but only one can be marked as the primary location for files, photos, and media.'),
		'$label_import_primary' => t('Make this hub my primary location'),
		'$email' => '',
		'$pass' => '',
		'$submit' => t('Submit')
	));

	return $o;

}
