<?php

require_once('include/attach.php');

function filestorage_post(&$a) {

	$channel_id = ((x($_POST,'uid')) ? intval($_POST['uid']) : 0);

	if((! $channel_id) || (! local_user()) || ($channel_id != local_user())) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$recurse = ((x($_POST,'recurse')) ? intval($_POST['recurse']) : 0);
	$resource = ((x($_POST,'filehash')) ? notags($_POST['filehash']) : '');

	if(! $resource) {
		notice(t('Item not found.') . EOL);
		return;
	}

	$str_group_allow   = perms2str($_REQUEST['group_allow']);
	$str_contact_allow = perms2str($_REQUEST['contact_allow']);
	$str_group_deny    = perms2str($_REQUEST['group_deny']);
	$str_contact_deny  = perms2str($_REQUEST['contact_deny']);
 
	attach_change_permissions($channel_id,$resource,$str_contact_allow,$str_group_allow,$str_contact_deny,$str_group_deny,$recurse = false);

}





function filestorage_content(&$a) {

	if(argc() > 1)
		$which = argv(1);
	else {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$r = q("select channel_id from channel where channel_address = '%s'",
		dbesc($which)
	);
	if($r) {
		$owner = intval($r[0]['channel_id']);
	}

	$observer = $a->get_observer();
	$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

	$perms = get_all_perms($owner,$ob_hash);

	if(! $perms['view_storage']) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	//	Since we have ACL'd files in the wild, but don't have ACL here yet, we 
	//	need to return for anyone other than the owner, despite the perms check for now.

	$is_owner = (((local_user()) && ($owner  == local_user())) ? true : false);
	if(! $is_owner) {
		info( t('Permission Denied.') . EOL );
		return;
	}

	// 	TODO This will also need to check for files on disk and delete them from there as well as the DB.

	if(argc() > 3 && argv(3) === 'delete') {
		if(! $perms['write_storage']) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		$file = intval(argv(2));
		$r = q("delete from attach where id = %d and uid = %d limit 1",
			dbesc($file),
			intval($owner)
		);
		goaway(z_root() . '/filestorage' . $which);
	}	


	if(argc() > 3 && argv(3) === 'edit') {
		require_once('include/acl_selectors.php');
		if(! $perms['write_storage']) {
			notice( t('Permission denied.') . EOL);
			return;
		}
		$file = intval(argv(2));

		$r = q("select id, folder, filename, revision, flags, hash, allow_cid, allow_gid, deny_cid, deny_gid from attach where id = %d and uid = %d limit 1",
			intval($file),
			intval($owner)
		);

		$f = $r[0];

		$channel = $a->get_channel();


		$aclselect_e = populate_acl($f);
		$is_a_dir = (($f['flags'] & ATTACH_FLAG_DIR) ? true : false);


		$o = replace_macros(get_markup_template('attach_edit.tpl'), array(
			'$header' => t('Edit file permissions'),
			'$file' => $f,
			'$uid' => $channel['channel_id'],
			'$channelnick' => $channel['channel_address'],
			'$permissions' => t('Permissions'),
			'$aclselect' => $aclselect_e,
			'$recurse' => t('Include all files and sub folders'),
			'$backlink' => t('Return to file list'),
			'$isadir' => $is_a_dir,
			'$cpdesc' => t('Copy/paste this code to attach file to a post'),
			'$submit' => t('Submit')

		));

		return $o;
	}	

	$r = q("select * from attach where uid = %d order by edited desc",
		intval($owner)
	);

	$files = null;

	if($r) {
		$files = array();
		foreach($r as $rr) {
			$files[$rr['id']][] = array(
				'id' => $rr['id'],
				'download' => $rr['hash'], 
				'title' => $rr['filename'], 
				'size' => $rr['filesize'],
				'rev' => $rr['revision'],
				'dir' => (($rr['flags'] & ATTACH_FLAG_DIR) ? true : false)
			);
		} 
	}

	$limit = service_class_fetch ($owner,'attach_upload_limit'); 
		$r = q("select sum(filesize) as total from attach where uid = %d ",
		intval($owner)
	);
	$used = $r[0]['total'];

	$url = z_root() . "/filestorage/" . $which; 
	return $o . replace_macros(get_markup_template("filestorage.tpl"), array(
		'$baseurl' => $url,
		'$download' => t('Download'),
		'$files' => $files,
		'$channel' => $which,
		'$edit' => t('Edit'),
		'$delete' => t('Delete'),
		'$used' => $used,
		'$usedlabel' => t('Used: '),
		'$directory' => t('[directory]'),
		'$limit' => $limit,
		'$limitlabel' => t('Limit: '),
	));
    
}
