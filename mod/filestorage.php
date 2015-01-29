<?php
/**
 * @file mod/filestorage.php
 *
 */

require_once('include/attach.php');

/**
 *
 * @param object &$a
 */
function filestorage_post(&$a) {

	$channel_id = ((x($_POST, 'uid')) ? intval($_POST['uid']) : 0);

	if((! $channel_id) || (! local_channel()) || ($channel_id != local_channel())) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$recurse = ((x($_POST, 'recurse')) ? intval($_POST['recurse']) : 0);
	$resource = ((x($_POST, 'filehash')) ? notags($_POST['filehash']) : '');
	$no_activity = ((x($_POST, 'no_activity')) ? intval($_POST['no_activity']) : 0);

	if(! $resource) {
		notice(t('Item not found.') . EOL);
		return;
	}

	$str_group_allow   = perms2str($_REQUEST['group_allow']);
	$str_contact_allow = perms2str($_REQUEST['contact_allow']);
	$str_group_deny    = perms2str($_REQUEST['group_deny']);
	$str_contact_deny  = perms2str($_REQUEST['contact_deny']);
 
	attach_change_permissions($channel_id, $resource, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny, $recurse);

	//Build directory tree and redirect
	$channel = $a->get_channel();
	$cloudPath = get_parent_cloudpath($channel_id, $channel['channel_address'], $resource);
	$object = get_file_activity_object($channel_id, $resource, $cloudPath);

	file_activity($channel_id, $object, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny, 'post', $no_activity);

	goaway($cloudPath);
}

function filestorage_content(&$a) {

	if(argc() > 1)
		$which = argv(1);
	else {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$r = q("select * from channel where channel_address = '%s'",
		dbesc($which)
	);
	if($r) {
		$channel = $r[0];
		$owner = intval($r[0]['channel_id']);
	}

	$observer = $a->get_observer();
	$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

	$perms = get_all_perms($owner, $ob_hash);

	if(! $perms['view_storage']) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	// Since we have ACL'd files in the wild, but don't have ACL here yet, we
	// need to return for anyone other than the owner, despite the perms check for now.

	$is_owner = (((local_channel()) && ($owner  == local_channel())) ? true : false);
	if(! $is_owner) {
		info( t('Permission Denied.') . EOL );
		return;
	}

	if(argc() > 3 && argv(3) === 'delete') {
		if(! $perms['write_storage']) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		$file = intval(argv(2));
		$r = q("SELECT hash FROM attach WHERE id = %d AND uid = %d LIMIT 1",
			dbesc($file),
			intval($owner)
		);
		if(! $r) {
			notice( t('File not found.') . EOL);
			goaway(z_root() . '/cloud/' . $which);
		}

		$f = $r[0];
		$channel = $a->get_channel();

		$parentpath = get_parent_cloudpath($channel['channel_id'], $channel['channel_address'], $f['hash']);

		attach_delete($owner, $f['hash']);

		goaway($parentpath);
	}

	if(argc() > 3 && argv(3) === 'edit') {
		require_once('include/acl_selectors.php');
		if(! $perms['write_storage']) {
			notice( t('Permission denied.') . EOL);
			return;
		}
		$file = intval(argv(2));

		$r = q("select id, uid, folder, filename, revision, flags, hash, allow_cid, allow_gid, deny_cid, deny_gid from attach where id = %d and uid = %d limit 1",
			intval($file),
			intval($owner)
		);

		$f = $r[0];
		$channel = $a->get_channel();

		$cloudpath = get_cloudpath($f) . (($f['flags'] & ATTACH_FLAG_DIR) ? '?f=&davguest=1' : '');
		$parentpath = get_parent_cloudpath($channel['channel_id'], $channel['channel_address'], $f['hash']);

		$aclselect_e = populate_acl($f, false);
		$is_a_dir = (($f['flags'] & ATTACH_FLAG_DIR) ? true : false);

		$lockstate = (($f['allow_cid'] || $f['allow_gid'] || $f['deny_cid'] || $f['deny_gid']) ? 'lock' : 'unlock'); 

		// Encode path that is used for link so it's a valid URL
		// Keep slashes as slashes, otherwise mod_rewrite doesn't work correctly
		$encoded_path = str_replace('%2F', '/', rawurlencode($cloudpath));

		$o = replace_macros(get_markup_template('attach_edit.tpl'), array(
			'$header' => t('Edit file permissions'),
			'$file' => $f,
			'$cloudpath' => z_root() . '/' . $encoded_path,
			'$parentpath' => $parentpath,
			'$uid' => $channel['channel_id'],
			'$channelnick' => $channel['channel_address'],
			'$permissions' => t('Permissions'),
			'$aclselect' => $aclselect_e,
			'$lockstate' => $lockstate,
			'$permset' => t('Set/edit permissions'),
			'$recurse' => t('Include all files and sub folders'),
			'$backlink' => t('Return to file list'),
			'$isadir' => $is_a_dir,
			'$cpdesc' => t('Copy/paste this code to attach file to a post'),
			'$cpldesc' => t('Copy/paste this URL to link file from a web page'),
			'$submit' => t('Submit'),
			'$attach_btn_title' => t('Attach this file to a new post'),
			'$link_btn_title' => t('Show URL to this file'),
			'$activity_btn_title' => t('Do not show in shared with me folder of your connections')
		));

		echo $o;
		killme();
	}

	goaway(z_root() . '/cloud/' . $which);
}
