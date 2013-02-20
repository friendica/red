<?php

// Import a channel, either by direct file upload or via
// connection to original server. 

require_once('include/Contact.php');


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
		$api_path = '/api/export/basic?f=&channel=' . $channelname;
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


//	logger('import: data: ' . print_r($data,true));

//	print_r($data);

	// import channel

	$channel = $data['channel'];

	$r = q("select * from channel where (channel_guid = '%s' or channel_hash = '%s') limit 1",
		dbesc($channel['channel_guid']),
		dbesc($channel['channel_hash'])
	);

	// We should probably also verify the hash 

	if($r) {
		logger('mod_import: duplicate channel. ', print_r($channel,true));
		notice( t('Duplicate channel unique ID. Import failed.') . EOL);
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

	$profiles = $data['profile'];
	if($profiles) {
		foreach($profiles as $profile) {
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
				dbesc_array($hubloc);
		
				$r = dbq("INSERT INTO hubloc (`" 
					. implode("`, `", array_keys($hubloc)) 
					. "`) VALUES ('" 
					. implode("', '", array_values($hubloc)) 
					. "')" );

			}

			// create new hubloc for the new channel at this site
			// and reset the original hubloc if it is being seized but wasn't created just now
		}
	}

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

	
			require_once("Photo.php");
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

	
	// import contacts
	$abooks = $data['abook'];
	if($abooks) {
		foreach($abooks as $abook) {
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


	if($seize) {
		// notify old server that it is no longer primary.
		
	}


	// send out refresh requests

}


function import_content(&$a) {

/*
 * Pass in a channel name and desired channel_address
 * Check this for validity and duplication
 * The page template should have a place to change it and check again
 */


$o .= <<< EOT

<form action="import" method="post" >
Old Address <input type="text" name="old_address" /><br />
Login <input type="text" name="email" /><br />
Password <input type="password" name="password" /><br />
<input type="submit" name="submit" value="submit" />
</form>

EOT;

return $o;
}