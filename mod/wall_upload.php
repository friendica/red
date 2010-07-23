<?php

require_once('Photo.php');

function wall_upload_post(&$a) {

        if(! local_user()) {
                notice ( "Permission denied." . EOL );
                return;
        }

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata);

	if(! ($image = $ph->getImage())) {
		notice("Unable to process image." . EOL);
		@unlink($src);
		return;
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
	if($r)
		notice("Image uploaded successfully." . EOL);
	else
		notice("Image upload failed." . EOL);

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);

		$r = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`, 
			`height`, `width`, `data`, `scale` )
			VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 1 )",
			intval($_SESSION['uid']),
			dbesc($hash),
			datetime_convert(),
			datetime_convert(),
			dbesc(basename($filename)),
			intval($ph->getHeight()),
			intval($ph->getWidth()),
			dbesc($ph->imageString())
		);
		if($r === false)
			notice("Image size reduction (640) failed." . EOL );
		else
			$smallest = 1;
	}

	if($width > 320 || $height > 320) {
		$ph->scaleImage(320);

		$r = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`, 
			`height`, `width`, `data`, `scale` )
			VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 2 )",
			intval($_SESSION['uid']),
			dbesc($hash),
			datetime_convert(),
			datetime_convert(),
			dbesc(basename($filename)),
			intval($ph->getHeight()),
			intval($ph->getWidth()),
			dbesc($ph->imageString())
		);
		if($r === false)
			notice("Image size reduction (320) failed." . EOL );
		else
			$smallest = 2;
	}

	$basename = basename($filename);

	echo "<img src=\"".$a->get_baseurl(). "/photo/{$hash}-{$smallest}.jpg\" alt=\"$basename\" />";
	killme();

}