<?php

require_once("Photo.php");

function profile_photo_init(&$a) {

	if(! local_user()) {
		return;
	}

	require_once("mod/profile.php");
	profile_load($a,$a->user['nickname']);

}


function profile_photo_post(&$a) {

        if(! local_user()) {
                notice ( t('Permission denied.') . EOL );
                return;
        }

	if((x($_POST,'cropfinal')) && ($_POST['cropfinal'] == 1)) {

		// phase 2 - we have finished cropping

		if($a->argc != 2) {
			notice( t('Image uploaded but image cropping failed.') . EOL );
			return;
		}

		$image_id = $a->argv[1];

		if(substr($image_id,-2,1) == '-') {
			$scale = substr($image_id,-1,1);
			$image_id = substr($image_id,0,-2);
		}
			

		$srcX = $_POST['xstart'];
		$srcY = $_POST['ystart'];
		$srcW = $_POST['xfinal'] - $srcX;
		$srcH = $_POST['yfinal'] - $srcY;
//dbg(3);
		$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = %d LIMIT 1",
			dbesc($image_id),
			dbesc($_SESSION['uid']),
			intval($scale));

		if(count($r)) {

			$base_image = $r[0];

			$im = new Photo($base_image['data']);
			$im->cropImage(175,$srcX,$srcY,$srcW,$srcH);

			$r = $im->store($_SESSION['uid'], 0, $base_image['resource-id'],$base_image['filename'], t('Profile Photos'), 4, 1);

			if($r === false)
				notice ( t('Image size reduction (175) failed.') . EOL );

			$im->scaleImage(80);

			$r = $im->store($_SESSION['uid'], 0, $base_image['resource-id'],$base_image['filename'], t('Profile Photos'), 5, 1);
			
			if($r === false)
				notice( t('Image size reduction (80) failed.') . EOL );

			// Unset the profile photo flag from any other photos I own

			$r = q("UPDATE `photo` SET `profile` = 0 WHERE `profile` = 1 AND `resource-id` != '%s' AND `uid` = %d",
				dbesc($base_image['resource-id']),
				intval($_SESSION['uid'])
			);

			$r = q("UPDATE `contact` SET `avatar-date` = '%s' WHERE `self` = 1 AND `uid` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval($_SESSION['uid'])
			);

		}
		goaway($a->get_baseurl() . '/profiles');
		return; // NOTREACHED
	}

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata);

	if(! ($image = $ph->getImage())) {
		notice( t('Unable to process image.') . EOL );
		@unlink($src);
		return;
	}

	@unlink($src);

	$width = $ph->getWidth();
	$height = $ph->getHeight();

	if($width < 175 || $height < 175) {
		$ph->scaleImageUp(200);
		$width = $ph->getWidth();
		$height = $ph->getHeight();
	}

	$hash = hash('md5',uniqid(mt_rand(),true));
	

	$smallest = 0;

	$r = $ph->store($_SESSION['uid'], 0 , $hash, $filename, t('Profile Photos'), 0 );	

	if($r)
		notice( t('Image uploaded successfully.') . EOL );
	else
		notice( t('Image upload failed.') . EOL );

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$r = $ph->store($_SESSION['uid'], 0 , $hash, $filename, t('Profile Photos'), 1 );	

		if($r === false)
			notice( t('Image size reduction (640) failed.') . EOL );
		else
			$smallest = 1;
	}

	$a->config['imagecrop'] = $hash;
	$a->config['imagecrop_resolution'] = $smallest;
	$a->page['htmlhead'] .= file_get_contents("view/crophead.tpl");
	return;
}


if(! function_exists('profile_photo_content')) {
function profile_photo_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	if(! x($a->config,'imagecrop')) {
	
		$tpl = file_get_contents('view/profile_photo.tpl');

		$o .= replace_macros($tpl,array(

		));

		return $o;
	}
	else {
		$filename = $a->config['imagecrop'] . '-' . $a->config['imagecrop_resolution'] . '.jpg';
		$resolution = $a->config['imagecrop_resolution'];
		$tpl = file_get_contents("view/cropbody.tpl");
		$o .= replace_macros($tpl,array(
			'$filename' => $filename,
			'$resource' => $a->config['imagecrop'] . '-' . $a->config['imagecrop_resolution'],
			'$image_url' => $a->get_baseurl() . '/photo/' . $filename
			));

		return $o;
	}

	return; // NOTREACHED
}}