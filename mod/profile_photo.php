<?php

require_once("Photo.php");

function profile_photo_init(&$a) {

	if((! x($_SESSION,'authenticated')) && (x($_SESSION,'uid'))) {
		$_SESSION['sysmsg'] .= "Permission denied." . EOL;
		$a->error = 404;
		return;
	}
	require_once("mod/profile.php");
	profile_load($a,$_SESSION['uid']);
}


function profile_photo_post(&$a) {



        if((! x($_SESSION,'authenticated')) && (! (x($_SESSION,'uid')))) {
                $_SESSION['sysmsg'] .= "Permission denied." . EOL;
                return;
        }

        if($a->argc > 1)
                $profile_id = intval($a->argv[1]);

	if(x($_POST,'xstart') !== false) {
		// phase 2 - we have finished cropping
		if($a->argc != 3) {
			$_SESSION['sysmsg'] .= "Image uploaded but image cropping failed." . EOL;
			return;
		}
		$image_id = $a->argv[2];
		if(substr($image_id,-2,1) == '-') {
			$scale = substr($image_id,-1,1);
			$image_id = substr($image_id,0,-2);
		}
			

		$srcX = $_POST['xstart'];
		$srcY = $_POST['ystart'];
		$srcW = $_POST['xfinal'] - $srcX;
		$srcH = $_POST['yfinal'] - $srcY;

		$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `scale` = %d LIMIT 1",
			dbesc($image_id),
			intval($scale));
		if($r !== NULL && (count($r))) {
			$im = new Photo($r[0]['data']);
			$im->cropImage(175,$srcX,$srcY,$srcW,$srcH);
			$s = $im->imageString(); 
			$x = $im->getWidth();
			$y = $im->getHeight();

			$ret = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`, 
				`height`, `width`, `data`, `scale` )
				VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 4 )",
				intval($_SESSION['uid']),
				dbesc($r[0]['resource-id']),
				datetime_convert(),
				datetime_convert(),
				dbesc($r[0]['filename']),
				intval($y),
				intval($x),
				dbesc($s));
			if($r === NULL)
				$_SESSION['sysmsg'] .= "Image size reduction (175) failed." . EOL;

			$im->scaleImage(80);
			$s = $im->imageString();
			$x = $im->getWidth();
			$y = $im->getHeight();
			$ret = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`, 
				`height`, `width`, `data`, `scale` )
				VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 5 )",
				intval($_SESSION['uid']),
				dbesc($r[0]['resource-id']),
				datetime_convert(),
				datetime_convert(),
				dbesc($r[0]['filename']),
				intval($y),
				intval($x),
				dbesc($s));
			if($r === NULL)
				$_SESSION['sysmsg'] .= "Image size reduction (80) failed." . EOL;
			$r = q("UPDATE `profile` SET `photo` = '%s', `thumb` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc($a->get_baseurl() . '/photo/' . $image_id . '-4.jpg'),
				dbesc($a->get_baseurl() . '/photo/' . $image_id . '-5.jpg'),
				intval($profile_id));
			if($r === NULL)
				$_SESSION['sysmsg'] .= "Failed to add image to profile." . EOL;

		}
		goaway($a->get_baseurl() . '/profiles');
	}

        $extra_sql = (($profile_id) ? " AND `id` = " . intval($profile_id)  : " AND `is-default` = 1 " );


        $r = q("SELECT `id` FROM `profile` WHERE `uid` = %d $extra_sql LIMIT 1", intval($_SESSION['uid']));
        if($r === NULL || (! count($r))) {
                $_SESSION['sysmsg'] .= "Profile unavailable." . EOL;
                return;
        }

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata);

	if(! ($image = $ph->getImage())) {
		$_SESSION['sysmsg'] .= "Unable to process image." . EOL;
		@unlink($src);
		return;
	}

	@unlink($src);
	$width = $ph->getWidth();
	$height = $ph->getHeight();

	if($width < 175 || $width < 175) {
		$ph->scaleImageUp(200);
		$width = $ph->getWidth();
		$height = $ph->getHeight();
	}

	$hash = hash('md5',uniqid(mt_rand(),true));
	
	$str_image = $ph->imageString();
	$smallest = 0;
	$r = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`, 
		`height`, `width`, `data`, `scale` )
		VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 0 )",
		intval($_SESSION['uid']),
		dbesc($hash),
		datetime_convert(),
		datetime_convert(),
		dbesc(basename($filename)),
		intval($height),
		intval($width),
		dbesc($str_image));
	if($r)
		$_SESSION['sysmsg'] .= "Image uploaded successfully." . EOL;
	else
		$_SESSION['sysmsg'] .= "Image upload failed." . EOL;

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$str_image = $ph->imageString();
		$width = $ph->getWidth();
		$height = $ph->getHeight();

		$r = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`, 
			`height`, `width`, `data`, `scale` )
			VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 1 )",
			intval($_SESSION['uid']),
			dbesc($hash),
			datetime_convert(),
			datetime_convert(),
			dbesc(basename($filename)),
			intval($height),
			intval($width),
			dbesc($str_image));
		if($r === NULL)
			$_SESSION['sysmsg'] .= "Image size reduction (640) failed." . EOL;
		else
			$smallest = 1;
	}

	$a->config['imagecrop'] = $hash;
	$a->config['imagecrop_resolution'] = $smallest;
	$a->page['htmlhead'] .= file_get_contents("view/crophead.tpl");

}


if(! function_exists('profile_photo_content')) {
function profile_photo_content(&$a) {


	if(! x($a->config,'imagecrop')) {
		if((! x($_SESSION['authenticated'])) && (! (x($_SESSION,'uid')))) {
			$_SESSION['sysmsg'] .= "Permission denied." . EOL;
			return;
		}
	
		if($a->argc > 1)
			$profile_id = intval($a->argv[1]);
	
		$extra_sql = (($profile_id) ? " AND `id` = $profile_id " : " AND `is-default` = 1 " );


		$r = q("SELECT `id` FROM `profile` WHERE `uid` = %d $extra_sql LIMIT 1", intval($_SESSION['uid']));
		if($r === NULL || (! count($r))) {
			$_SESSION['sysmsg'] .= "Profile unavailable." . EOL;
			return;
		}
	
		$o = file_get_contents('view/profile_photo.tpl');

		$o = replace_macros($o,array(
			'$profile_id' => $r[0]['id'],
			'$uid' => $_SESSION['uid'],
			));

		return $o;
	}
	else {
		$filename = $a->config['imagecrop'] . '-' . $a->config['imagecrop_resolution'] . '.jpg';
		$resolution = $a->config['imagecrop_resolution'];
		$o = file_get_contents("view/cropbody.tpl");
		$o = replace_macros($o,array(
			'$filename' => $filename,
			'$profile_id' => $a->argv[1],
			'$resource' => $a->config['imagecrop'] . '-' . $a->config['imagecrop_resolution'],
			'$image_url' => $a->get_baseurl() . '/photo/' . $filename
			));

		return $o;
	}


}}