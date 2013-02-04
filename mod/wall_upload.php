<?php

require_once('Photo.php');




function wall_upload_post(&$a) {

	$using_api = ((x($_FILES,'media')) ? true : false); 

	if($using_api) {
		require_once('include/api.php');
		$user_info = api_get_user($a);
		$nick = $user_info['screen_name'];
	}
	else {
		if(argc() > 1)
			$nick = argv(1);
	}

	$channel = null;

	if($nick) {		
		$r = q("SELECT channel.* from channel where channel_address = '%s' limit 1",
			dbesc($nick)
		);
		if($r)
			$channel = $r[0];
	}

	if(! $channel) {
		if($using_api)
			return;
		else {
			notice( t('Channel not found.') . EOL);
			killme();
		}
	}

	$can_post  = false;
	$visitor   = 0;


	$page_owner_uid   = $r[0]['channel_id'];

	$observer = $a->get_observer();

	if(! perm_is_allowed($page_owner_uid,$observer['xchan_hash'],'post_photos')) {
		if($using_api)
			return;
		else {
			notice( t('Permission denied.') . EOL);
			killme();
		}
	}

	if(x($_FILES,'userfile')) {
		$src      = $_FILES['userfile']['tmp_name'];
		$filename = basename($_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
		$filetype = $_FILES['userfile']['type'];
	}
	elseif(x($_FILES,'media')) {
		$src = $_FILES['media']['tmp_name'];
		$filename = basename($_FILES['media']['name']);
		$filesize = intval($_FILES['media']['size']);
		$filetype = $_FILES['media']['type'];
	}
	else {
		if($using_api)
			return;
		else {
			notice( t('Empty upload.') . EOL);
			killme();
		}
	}

	
    if($filetype == "") 
		$filetype=guess_image_type($filename);
	$maximagesize = get_config('system','maximagesize');

	if(($maximagesize) && ($filesize > $maximagesize)) {
		@unlink($src);
		if($using_api)
			return;
		else {
			echo  sprintf( t('Image exceeds size limit of %d'), $maximagesize) . EOL;
			killme();
		}
	}


	$limit = service_class_fetch($page_owner_uid,'photo_upload_limit');
	if($limit !== false) {
		$r = q("select sum(size) as total from photo where uid = %d and scale = 0 ",
			intval($page_owner_uid)
		);
		if(($r) && (($r[0]['total'] + strlen($imagedata)) > $limit)) {
			@unlink($src);
			if($using_api)
				return;
			else {
				echo upgrade_message(true) . EOL ;
				killme();
			}
		}
	}

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata, $filetype);

	if(! $ph->is_valid()) {
		@unlink($src);
		if($using_api)
			return;
		else {
			echo ( t('Unable to process image.') . EOL);
			killme();
		}
	}

	$ph->orient($src);
	@unlink($src);

	$max_length = get_config('system','max_image_length');
	if(! $max_length)
		$max_length = MAX_IMAGE_LENGTH;
	if($max_length > 0)
		$ph->scaleImage($max_length);

	$width = $ph->getWidth();
	$height = $ph->getHeight();

	$hash = photo_new_resource();
	
	$smallest = 0;

	$defperm = '<' . $channel['channel_hash'] . '>';
	$aid = $channel['channel_account_id'];
	$visitor = ((remote_user()) ? remote_user() : '');

	$r = $ph->store($aid, $page_owner_uid, $visitor, $hash, $filename, t('Wall Photos'), 0, 0, $defperm);

	if(! $r) {
		if($using_api)
			return;
		else {
			echo ( t('Image upload failed.') . EOL);
			killme();
		}
	}

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$r = $ph->store($aid, $page_owner_uid, $visitor, $hash, $filename, t('Wall Photos'), 1, 0, $defperm);
		if($r) 
			$smallest = 1;
	}

	if($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$r = $ph->store($aid, $page_owner_uid, $visitor, $hash, $filename, t('Wall Photos'), 2, 0, $defperm);
		if($r)
			$smallest = 2;
	}

	$basename = basename($filename);

	if($_REQUEST['silent']) {
		$m = '[url=' . $a->get_baseurl() . '/photos/' . $channel['channel_address'] . '/image/' . $hash . '][img]' . $a->get_baseurl() . "/photo/{$hash}-{$smallest}.".$ph->getExt()."[/img][/url]";
		return($m);
	}
	else {
		echo  "\n\n" . '[url=' . $a->get_baseurl() . '/photos/' . $channel['channel_address'] . '/image/' . $hash . '][img]' . $a->get_baseurl() . "/photo/{$hash}-{$smallest}.".$ph->getExt()."[/img][/url]\n\n";
	}

	killme();
	// NOTREACHED
}
