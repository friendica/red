<?php

require_once('include/photo/photo_driver.php');

function profile_photo_init(&$a) {

	if(! local_user()) {
		return;
	}

	$channel = $a->get_channel();
	profile_load($a,$channel['channel_address']);

}


function profile_photo_aside(&$a) {

	if(! local_user()) {
		return;
	}

	profile_create_sidebar($a);
}


function profile_photo_post(&$a) {

	if(! local_user()) {
		return;
	}
	
	check_form_security_token_redirectOnErr('/profile_photo', 'profile_photo');
        
	if((x($_POST,'cropfinal')) && ($_POST['cropfinal'] == 1)) {

		// unless proven otherwise
		$is_default_profile = 1;

		if($_REQUEST['profile']) {
			$r = q("select id, is_default from profile where id = %d and uid = %d limit 1",
				intval($_REQUEST['profile']),
				intval(local_user())
			);
			if(($r) && (! intval($r[0]['is_default'])))
				$is_default_profile = 0;
		} 

		

		// phase 2 - we have finished cropping

		if(argc() != 2) {
			notice( t('Image uploaded but image cropping failed.') . EOL );
			return;
		}

		$image_id = argv(1);

		if(substr($image_id,-2,1) == '-') {
			$scale = substr($image_id,-1,1);
			$image_id = substr($image_id,0,-2);
		}
			

		$srcX = $_POST['xstart'];
		$srcY = $_POST['ystart'];
		$srcW = $_POST['xfinal'] - $srcX;
		$srcH = $_POST['yfinal'] - $srcY;

		$r = q("SELECT * FROM photo WHERE resource_id = '%s' AND uid = %d AND scale = %d LIMIT 1",
			dbesc($image_id),
			dbesc(local_user()),
			intval($scale));

		if($r) {

			$base_image = $r[0];
		
			$im = photo_factory($base_image['data'], $base_image['type']);
			if($im->is_valid()) {

				$im->cropImage(175,$srcX,$srcY,$srcW,$srcH);

				$aid = get_account_id();

				$p = array('aid' => $aid, 'uid' => local_user(), 'resource_id' => $base_image['resource_id'],
					'filename' => $base_image['filename'], 'album' => t('Profile Photos'));

				$p['scale'] = 4;
				$p['photo_flags'] = (($is_default_profile) ? PHOTO_PROFILE : PHOTO_NORMAL);

				$r1 = $im->save($p);

				$im->scaleImage(80);
				$p['scale'] = 5;

				$r2 = $im->save($p);
			
				$im->scaleImage(48);
				$p['scale'] = 6;

				$r3 = $im->save($p);
			
				if($r1 === false || $r2 === false || $r3 === false) {
					// if one failed, delete them all so we can start over.
					notice( t('Image resize failed.') . EOL );
					$x = q("delete from photo where resource_id = '%s' and uid = %d and scale >= 4 ",
						dbesc($base_image['resource_id']),
						local_user()
					);
					return;
				}

				// If setting for the default profile, unset the profile photo flag from any other photos I own

				if($is_default_profile) {
					$r = q("UPDATE photo SET profile = 0 WHERE profile = 1 AND resource_id != '%s' AND `uid` = %d",
						dbesc($base_image['resource_id']),
						intval(local_user())
					);
					$r = q("UPDATE photo SET photo_flags = ( photo_flags ^ %d ) WHERE ( photo_flags & %d ) 
						AND resource_id != '%s' AND `uid` = %d",
						intval(PHOTO_PROFILE),
						intval(PHOTO_PROFILE),
						dbesc($base_image['resource_id']),
						intval(local_user())
					);
				}
				else {
					$r = q("update profile set photo = '%s', thumb = '%s' where id = %d and uid = %d limit 1",
						dbesc($a->get_baseurl() . '/photo/' . $base_image['resource_id'] . '-4'),
						dbesc($a->get_baseurl() . '/photo/' . $base_image['resource_id'] . '-5'),
						intval($_REQUEST['profile']),
						intval(local_user())
					);
				}

				// We'll set the updated profile-photo timestamp even if it isn't the default profile,
				// so that browsers will do a cache update unconditionally

				$channel = $a->get_channel();

				$r = q("UPDATE xchan set xchan_photo_mimetype = '%s', xchan_photo_date = '%s' 
					where xchan_hash = '%s' limit 1",
					dbesc($im->getType()),
					dbesc(datetime_convert()),
					dbesc($channel['xchan_hash'])
				);

				info( t('Shift-reload the page or clear browser cache if the new photo does not display immediately.') . EOL);

				// Update directory in background
				proc_run('php',"include/directory.php",$channel['channel_id']);
			}
			else
				notice( t('Unable to process image') . EOL);
		}

		goaway($a->get_baseurl() . '/profiles');
		return; // NOTREACHED
	}

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);
	$filetype = $_FILES['userfile']['type'];
    if ($filetype=="") $filetype=guess_image_type($filename);
    
	$maximagesize = get_config('system','maximagesize');

	if(($maximagesize) && ($filesize > $maximagesize)) {
		notice( sprintf(t('Image exceeds size limit of %d'), $maximagesize) . EOL);
		@unlink($src);
		return;
	}

	$imagedata = @file_get_contents($src);
	$ph = photo_factory($imagedata, $filetype);

	if(! $ph->is_valid()) {
		notice( t('Unable to process image.') . EOL );
		@unlink($src);
		return;
	}
	$ph->orient($src);
	@unlink($src);
	return profile_photo_crop_ui_head($a, $ph);
	
}


if(! function_exists('profile_photo_content')) {
function profile_photo_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	$channel = $a->get_channel();

	$newuser = false;

	if(argc() == 2 && argv(1) === 'new')
		$newuser = true;

	if(argv(1) === 'use') {
		if (argc() < 3) {
			notice( t('Permission denied.') . EOL );
			return;
		};
		
//		check_form_security_token_redirectOnErr('/profile_photo', 'profile_photo');
        
		$resource_id = argv(2);


		$r = q("SELECT id, album, scale FROM photo WHERE uid = %d AND resource_id = '%s' ORDER BY scale ASC",
			intval(local_user()),
			dbesc($resource_id)
		);
		if(! $r) {
			notice( t('Photo not available.') . EOL );
			return;
		}
		$havescale = false;
		foreach($r as $rr) {
			if($rr['scale'] == 5)
				$havescale = true;
		}

		// set an already loaded photo as profile photo

		if(($r[0]['album'] == t('Profile Photos')) && ($havescale)) {
			// unset any existing profile photos
			$r = q("UPDATE photo SET profile = 0 WHERE profile = 1 AND uid = %d",
				intval(local_user()));
			$r = q("UPDATE photo SET photo_flags = (photo_flags ^ %d ) WHERE (photo_flags & %d ) AND uid = %d",
				intval(PHOTO_PROFILE),
				intval(PHOTO_PROFILE),
				intval(local_user()));
			
			// set all sizes of this one as profile photos
			$r = q("UPDATE photo SET profile = 1 WHERE uid = %d AND resource_id = '%s'",
				intval(local_user()),
				dbesc($resource_id)
				);

			$r = q("UPDATE photo SET photo_flags = ( photo_flags | %d ) WHERE uid = %d AND resource_id = '%s'",
				intval(PHOTO_PROFILE),
				intval(local_user()),
				dbesc($resource_id)
				);

			$r = q("UPDATE xchan set xchan_photo_date = '%s' 
				where xchan_hash = '%s' limit 1",
				dbesc(datetime_convert()),
				dbesc($channel['xchan_hash'])
			);

			proc_run('php','include/directory.php',local_user());			
			goaway($a->get_baseurl() . '/profiles');
		}

		$r = q("SELECT `data`, `type` FROM photo WHERE id = %d and uid = %d limit 1",
			intval($r[0]['id']),
			intval(local_user())

		);
		if(! $r) {
			notice( t('Photo not available.') . EOL );
			return;
		}

		$ph = photo_factory($r[0]['data'], $r[0]['type']);
		// go ahead as if we have just uploaded a new photo to crop
		profile_photo_crop_ui_head($a, $ph);
	}

	$profiles = q("select id, profile_name as name, is_default from profile where uid = %d",
		intval(local_user())
	);

	if(! x($a->data,'imagecrop')) {
	
		$tpl = get_markup_template('profile_photo.tpl');

		$o .= replace_macros($tpl,array(
			'$user' => $a->channel['channel_address'],
			'$lbl_upfile' => t('Upload File:'),
			'$lbl_profiles' => t('Select a profile:'),
			'$title' => t('Upload Profile Photo'),
			'$submit' => t('Upload'),
			'$profiles' => $profiles,
			'$form_security_token' => get_form_security_token("profile_photo"),
// FIXME - yuk  
			'$select' => sprintf('%s %s', t('or'), ($newuser) ? '<a href="' . $a->get_baseurl() . '">' . t('skip this step') . '</a>' : '<a href="'. $a->get_baseurl() . '/photos/' . $a->channel['channel_address'] . '">' . t('select a photo from your photo albums') . '</a>')
		));

		return $o;
	}
	else {
		$filename = $a->data['imagecrop'] . '-' . $a->data['imagecrop_resolution'] . '.' . $a->data['imagecrop_ext'];
		$resolution = $a->data['imagecrop_resolution'];
		$tpl = get_markup_template("cropbody.tpl");
		$o .= replace_macros($tpl,array(
			'$filename' => $filename,
			'$profile' => intval($_REQUEST['profile']),
			'$resource' => $a->data['imagecrop'] . '-' . $a->data['imagecrop_resolution'],
			'$image_url' => $a->get_baseurl() . '/photo/' . $filename,
			'$title' => t('Crop Image'),
			'$desc' => t('Please adjust the image cropping for optimum viewing.'),
			'$form_security_token' => get_form_security_token("profile_photo"),
			'$done' => t('Done Editing')
		));
		return $o;
	}

	return; // NOTREACHED
}}


if(! function_exists('profile_photo_crop_ui_head')) {
function profile_photo_crop_ui_head(&$a, $ph){

	$max_length = get_config('system','max_image_length');
	if(! $max_length)
		$max_length = MAX_IMAGE_LENGTH;
	if($max_length > 0)
		$ph->scaleImage($max_length);

	$width  = $ph->getWidth();
	$height = $ph->getHeight();

	if($width < 175 || $height < 175) {
		$ph->scaleImageUp(200);
		$width  = $ph->getWidth();
		$height = $ph->getHeight();
	}

	$hash = photo_new_resource();
	$smallest = 0;

	$p = array('aid' => get_account_id(), 'uid' => local_user(), 'resource_id' => $hash,
		'filename' => $filename, 'album' => t('Profile Photos'), 'scale' => 0);
	$r = $ph->save($p);

	if($r)
		info( t('Image uploaded successfully.') . EOL );
	else
		notice( t('Image upload failed.') . EOL );

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$p['scale'] = 1;

		$r = $ph->save($p);
		
		if($r === false)
			notice( sprintf(t('Image size reduction [%s] failed.'),"640") . EOL );
		else
			$smallest = 1;
	}

	$a->data['imagecrop'] = $hash;
	$a->data['imagecrop_resolution'] = $smallest;
	$a->data['imagecrop_ext'] = $ph->getExt();
	$a->page['htmlhead'] .= replace_macros(get_markup_template("crophead.tpl"), array());
	return;
}}

