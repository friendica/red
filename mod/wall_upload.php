<?php

require_once('Photo.php');

function wall_upload_post(&$a) {

        if(! local_user()) {
                echo ( t('Permission denied.') . EOL );
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
		echo ( t('Unable to process image.') . EOL);
		@unlink($src);
		killme();
	}

	@unlink($src);

	$width = $ph->getWidth();
	$height = $ph->getHeight();

	$hash = hash('md5',uniqid(mt_rand(),true));
	
	$smallest = 0;

	$r = $ph->store($_SESSION['uid'], 0, $hash, $filename, t('Wall Photos'), 0 );

	if(! $r) {
		echo ( t('Image upload failed.') . EOL);
		killme();
	}

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$r = $ph->store($_SESSION['uid'], 0, $hash, $filename, t('Wall Photos'), 1 );
		if($r) 
			$smallest = 1;
	}

	if($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$r = $ph->store($_SESSION['uid'], 0, $hash, $filename, t('Wall Photos'), 2 );
		if($r)
			$smallest = 2;
	}

	$basename = basename($filename);
	echo  "<br /><br /><img src=\"".$a->get_baseurl(). "/photo/{$hash}-{$smallest}.jpg\" alt=\"$basename\" /><br /><br />";

	killme();
	return; // NOTREACHED
}