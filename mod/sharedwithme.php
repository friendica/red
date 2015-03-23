<?php
require_once('include/conversation.php');
require_once('include/text.php');

function sharedwithme_content(&$a) {
	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
	
	$channel = $a->get_channel();

	$is_owner = (local_channel() && (local_channel() == $channel['channel_id']));

	//check for updated items and remove them
	require_once('include/sharedwithme.php');
	apply_updates();

	//drop single file - localuser
	if((argc() > 2) && (argv(2) === 'drop')) {
	
		$id = intval(argv(1));

		q("DELETE FROM item WHERE id = %d AND uid = %d",
			intval($id),
			intval(local_channel())
		);

		goaway(z_root() . '/sharedwithme');
	}

	//drop all files - localuser
	if((argc() > 1) && (argv(1) === 'dropall')) {

		q("DELETE FROM item WHERE verb = '%s' AND obj_type = '%s' AND uid = %d",
			dbesc(ACTIVITY_POST),
			dbesc(ACTIVITY_OBJ_FILE),
			intval(local_channel())
		);

		goaway(z_root() . '/sharedwithme');
	}

	//list files
	$r = q("SELECT id, uid, object, item_unseen FROM item WHERE verb = '%s' AND obj_type = '%s' AND uid = %d AND owner_xchan != '%s'",
		dbesc(ACTIVITY_POST),
		dbesc(ACTIVITY_OBJ_FILE),
		intval(local_channel()),
		dbesc($channel['channel_hash'])
	);

	$items =array();
	$ids = '';

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
			$item['unseen'] = $rr['item_unseen'];

			$items[] = $item;

			if($item['unseen'] > 0) {
				$ids .= " '" . $rr['id'] . "',";
			}

		}

	}

	if($ids) {

		//remove trailing ,
		$ids = rtrim($ids, ",");

		q("UPDATE item SET item_unseen = 0 WHERE id IN ( $ids ) AND uid = %d",
			intval(local_channel())
		);

	}

	$o = profile_tabs($a, $is_owner, $channel['channel_address']);

	$o .= replace_macros(get_markup_template('sharedwithme.tpl'), array(
		'$header' => t('Files: shared with me'),
		'$name' => t('Name'),
		'$label_new' => t('NEW'),
		'$size' => t('Size'),
		'$lastmod' => t('Last Modified'),
		'$dropall' => t('Remove all files'),
		'$drop' => t('Remove this file'),
		'$items' => $items
	));

	return $o;

}

