<?php
require_once('include/conversation.php');
require_once('include/text.php');

function sharedwithme_content(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
	
	$channel = $a->get_channel();

	$is_owner = (local_user() && (local_user() == $channel['channel_id']));

	//maintenance - see if a file got dropped and remove it systemwide - this should possibly go to include/poller
	$x = q("SELECT * FROM item WHERE verb = '%s' AND obj_type = '%s' AND uid = %d",
		dbesc(ACTIVITY_UPDATE),
		dbesc(ACTIVITY_OBJ_FILE),
		intval(local_user())
	);

	if($x) {

		foreach($x as $xx) {

			$object = json_decode($xx['object'],true);

			$d_mid = $object['d_mid'];
			$u_mid = $xx['mid'];

			$y = q("DELETE FROM item WHERE obj_type = '%s' AND (verb = '%s' AND mid = '%s') OR (verb = '%s' AND mid = '%s')",
				dbesc(ACTIVITY_OBJ_FILE),
				dbesc(ACTIVITY_POST),
				dbesc($d_mid),
				dbesc(ACTIVITY_UPDATE),
				dbesc($u_mid)
			);

		}

	}

	//drop single file - localuser
	if((argc() > 2) && (argv(2) === 'drop')) {
	
		$id = intval(argv(1));

		q("DELETE FROM item WHERE id = %d AND uid = %d",
			intval($id),
			intval(local_user())
		);

		goaway(z_root() . '/sharedwithme');
	}

	//drop all files - localuser
	if((argc() > 1) && (argv(1) === 'dropall')) {

		q("DELETE FROM item WHERE verb = '%s' AND obj_type = '%s' AND uid = %d",
			dbesc(ACTIVITY_POST),
			dbesc(ACTIVITY_OBJ_FILE),
			intval(local_user())
		);

		goaway(z_root() . '/sharedwithme');
	}

	//list files
	$r = q("SELECT * FROM item WHERE verb = '%s' AND obj_type = '%s' AND uid = %d AND owner_xchan != '%s'",
		dbesc(ACTIVITY_POST),
		dbesc(ACTIVITY_OBJ_FILE),
		intval(local_user()),
		dbesc($channel['channel_hash'])
	);

	$items =array();

	if($r) {
		foreach($r as $rr) {
			$object = json_decode($rr['object'],true);

			$item = array();
			$item['id'] = $rr['id'];
			$item['objfiletype'] = $object['filetype'];
			$item['objfiletypeclass'] = getIconFromType($object['filetype']);
			$item['objurl'] = rawurldecode(get_rel_link($object['link'],'alternate')) . '?f=&zid=' . $channel['xchan_addr'];
			$item['objfilename'] = $object['filename'];
			$item['objfilesize'] = userReadableSize($object['filesize']);
			$item['objedited'] = $object['edited'];

			$items[] = $item;

		}
	}

	$o = profile_tabs($a, $is_owner, $channel['channel_address']);

	$o .= replace_macros(get_markup_template('sharedwithme.tpl'), array(
		'$header' => t('Files: shared with me'),
		'$name' => t('Name'),
		'$size' => t('Size'),
		'$lastmod' => t('Last Modified'),
		'$dropall' => t('Remove all files'),
		'$drop' => t('Remove this file'),
		'$items' => $items
	));

	return $o;

}

