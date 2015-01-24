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

	$o = profile_tabs($a, $is_owner, $channel['channel_address']);

	$o .= '<div class="section-title-wrapper">';

	$o .= '<a href="/sharedwithme/dropall" onclick="return confirmDelete();" class="btn btn-xs btn-default pull-right"><i class="icon-trash"></i>&nbsp;' . t('Remove all entries') . '</a>';
	
	$o .= '<h2>' . t('Files shared with me') . '</h2>';

	$o .= '</div>';

	$o .= '<div class="section-content-wrapper">';

	if($r) {
		foreach($r as $rr) {
			$object = json_decode($rr['object'],true);
			$url = rawurldecode(get_rel_link($object['link'],'alternate'));
			$o .= '<a href="' . $url . '?f=&zid=' . $channel['xchan_addr'] . '">' . $url . '</a>&nbsp;<a href="/sharedwithme/' . $rr['id'] . '/drop" onclick="return confirmDelete();"><i class="icon-trash drop-icons"></i></a><br><br>';
		}
	}

	$o .= '</div>';

	return $o;

}

