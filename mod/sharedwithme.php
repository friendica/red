<?php
require_once('include/text.php');
require_once('include/conversation.php');

function sharedwithme_content(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
	
	$channel = $a->get_channel();

	$is_owner = (local_user() && (local_user() == $channel['channel_id']));

	$postverb = ACTIVITY_FILE . '/post/';
	$dropverb = ACTIVITY_FILE . '/drop/';

	//maintenance - see if a file got dropped and remove it systemwide 
	$x = q("SELECT * FROM item WHERE verb LIKE '%s' AND uid = %d",
		dbesc($dropverb . '%'),
		intval(local_user())
	);
	
	if($x) {
		
		foreach($x as $xx) {

			$hash = substr($xx['verb'], 39);

			$update = strpos($hash, '#');

			if($update === false) {
				q("DELETE FROM item WHERE verb = '%s' OR verb = '%s'",
					dbesc($postverb . $hash),
					dbesc($dropverb . $hash)
				);
			}

			else {
				
				$arr = explode('#', $hash);
				
				q("DELETE FROM item WHERE mid != '%s' AND verb = '%s' OR verb = '%s'",
					dbesc($arr[1]),
					dbesc($postverb . $arr[0]),
					dbesc($dropverb . $hash)
				);

			}

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

		q("DELETE FROM item WHERE verb LIKE '%s' AND uid = %d",
			dbesc($postverb . '%'),
			intval(local_user())
		);

		goaway(z_root() . '/sharedwithme');
	}

	//list files
	$r = q("SELECT * FROM item WHERE verb LIKE '%s' AND uid = %d",
		dbesc($postverb . '%'),
		intval(local_user())
	);
	
	$o = profile_tabs($a, $is_owner, $channel['channel_address']);

	$o .= '<div class="section-title-wrapper">';

	$o .= '<a href="/sharedwithme/dropall" onclick="return confirmDelete();" class="btn btn-xs btn-default pull-right"><i class="icon-trash"></i>&nbsp;' . t('Remove all entries') . '</a>';
	
	$o .= '<h2>' . t('Files shared with me') . '</h2>';

	$o .= '</div>';

	$o .= '<div class="section-content-wrapper">';

	if($r) {
		foreach($r as $rr) {
			//don't display the files we shared with others
			if($rr['owner_xchan'] != $channel['channel_hash']) {
				unobscure($rr);
				$url = rawurldecode($rr['body']);
				$o .= '<a href="' . $url . '?f=&zid=' . $channel['xchan_addr'] . '">' . $url . '</a>&nbsp;<a href="/sharedwithme/' . $rr['id'] . '/drop" onclick="return confirmDelete();"><i class="icon-trash drop-icons"></i></a><br><br>';
			}
		}
	}

	$o .= '</div>';

	return $o;
	

}

