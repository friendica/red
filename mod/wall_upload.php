<?php

require_once('Photo.php');

function wall_upload_post(&$a) {

        if(! local_user()) {
                echo ( "Permission denied." . EOL );
                killme();
        }

	if(! x($_FILES,'userfile'))
		killme();

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata);

	if(! ($image = $ph->getImage())) {
		echo ("Unable to process image." . EOL);
		@unlink($src);
		killme();
	}

	@unlink($src);

	$width = $ph->getWidth();
	$height = $ph->getHeight();

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
	if(! $r) {
		echo ("Image upload failed." . EOL);
		killme();
	}

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);

		$r = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`, `album`,
			`height`, `width`, `data`, `scale` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', 1 )",
			intval($_SESSION['uid']),
			dbesc($hash),
			datetime_convert(),
			datetime_convert(),
			dbesc(basename($filename)),
			dbesc( t('Wall Photos') ),
			intval($ph->getHeight()),
			intval($ph->getWidth()),
			dbesc($ph->imageString())
		);
		if($r) 
			$smallest = 1;
	}

	if($width > 320 || $height > 320) {
		$ph->scaleImage(320);

		$r = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`, `album`,
			`height`, `width`, `data`, `scale` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', 2 )",
			intval($_SESSION['uid']),
			dbesc($hash),
			datetime_convert(),
			datetime_convert(),
			dbesc(basename($filename)),
			dbesc( t('Wall Photos') ),
			intval($ph->getHeight()),
			intval($ph->getWidth()),
			dbesc($ph->imageString())
		);
		if($r)
			$smallest = 2;
	}

	$basename = basename($filename);
	echo  "<br /><br /><img src=\"".$a->get_baseurl(). "/photo/{$hash}-{$smallest}.jpg\" alt=\"$basename\" /><br /><br />";

	killme();
	return; // NOTREACHED
}