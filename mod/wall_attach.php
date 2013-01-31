<?php

require_once('include/attach.php');
require_once('include/datetime.php');

function wall_attach_post(&$a) {


	// Figure out who owns the page and if they allow attachments

	if(argc() > 1) {
		$nick = argv(1);
		$r = q("SELECT channel.* from channel where channel_address = '%s' limit 1",
			dbesc($nick)
		);
		if(! $r)
			killme();
		$channel = $r[0];

	}
	else
		killme();

	
	$can_post  = false;

	
	$visitor   = 0;

	$page_owner_uid   = $channel['channel_id'];

	if(! perm_is_allowed($page_owner_uid,get_observer_hash(),'write_storage')) {
		notice( t('Permission denied.') . EOL);
		killme();
	}

	if(! x($_FILES,'userfile'))
		killme();

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);


	$replace = ((x($_REQUEST,'replace')) ? intval($_REQUEST['replace']) : 0);
	$existing_size = 0;

	if($replace) {
		$x = q("select id, filesize, allow_cid, allow_gid, deny_cid, deny_gid from attach where id = %d and uid = %d limit 1",	
			intval($replace),
			intval($page_owner_uid)
		);
		if(! $x) {
			notice('Cannot locate file to replace');
			killme();
		}
		$existing_size = intval($x[0]['filesize']);

	}
	


	$maxfilesize = get_config('system','maxfilesize');

	if(($maxfilesize) && ($filesize > $maxfilesize)) {
		notice( sprintf(t('File exceeds size limit of %d'), $maxfilesize) . EOL);
		@unlink($src);
		killme();
	}

	$limit = service_class_fetch($page_owner_uid,'attach_upload_limit');
	if($limit !== false) {
		$r = q("select sum(filesize) as total from attach where uid = %d ",
			intval($page_owner_uid)
		);
		if(($r) &&  (($r[0]['total'] + $filesize) > ($limit - $existing_size))) {
			echo upgrade_message(true) . EOL ;
			@unlink($src);
			killme();
		}
	}

// TODO turn this into a general file upload api where permissions can be set on demand and move it out of the front end controller. 
// We're making several assumptions that we are uploading into a post, which defaults to owner privacy until the post is completed 
// and permissions are updated to match the post. 

	$filedata = @file_get_contents($src);
	$mimetype = z_mime_content_type($filename);
	$hash = random_string();
	$created = datetime_convert();
	if($replace) {
		$r = q("update attach set filename = '%s', filetype = '%s', filesize = %d, data = '%s', edited = '%s' where id = %d limit 1",
			dbesc($filename),
			dbesc($mimetype),
			intval($filesize),
			dbesc($filedata),
			dbesc($created),
			intval($replace)
		);
	}
	else {
		$r = q("INSERT INTO `attach` ( `aid`, `uid`, `hash`, `filename`, `filetype`, `filesize`, `data`, `created`, `edited`, `allow_cid`, `allow_gid`,`deny_cid`, `deny_gid` )
			VALUES ( %d, %d, '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
			intval($channel['channel_account_id']),
			intval($page_owner_uid),
			dbesc($hash),
			dbesc($filename),
			dbesc($mimetype),
			intval($filesize),
			dbesc($filedata),
			dbesc($created),
			dbesc($created),
			dbesc('<' . $channel['channel_hash'] . '>'),
			dbesc(''),
			dbesc(''),
			dbesc('')
		);
	}		

	@unlink($src);

	if(! $r) {
		echo ( t('File upload failed.') . EOL);
		killme();
	}

	$r = q("SELECT `hash` FROM `attach` WHERE `uid` = %d AND `created` = '%s' AND `hash` = '%s' LIMIT 1",
		intval($page_owner_uid),
		dbesc($created),
		dbesc($hash)
	);

	if(! $r) {
		echo ( t('File upload failed.') . EOL);
		killme();
	}

	echo  "\n\n" . '[attachment]' . $r[0]['hash'] . '[/attachment]' . "\n";
	
	killme();
	// NOTREACHED
}
