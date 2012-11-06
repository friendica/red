<?php

function profile_activity($changed, $value) {
	$a = get_app();

	if(! local_user() || ! is_array($changed) || ! count($changed))
		return;

	if(! get_pconfig(local_user(),'system','post_profilechange'))
		return;

	require_once('include/items.php');

	$self = $a->get_channel();

	if(! count($self))
		return;

	$arr = array();
	$arr['uri'] = $arr['parent_uri'] = item_message_id();
	$arr['uid'] = local_user();
	$arr['aid'] = $self['channel_account_id'];
	$arr['owner_xchan'] = $arr['author_xchan'] = $self['xchan_hash'];
	$arr['item_flags'] = ITEM_WALL|ITEM_ORIGIN|ITEM_THREAD_TOP;
	$arr['verb'] = ACTIVITY_UPDATE;
	$arr['obj_type'] = ACTIVITY_OBJ_PROFILE;
				
	$A = '[url=' . z_root() . '/profile/' . $self[0]['xchan_addr'] . ']' . $self[0]['xchan_name'] . '[/url]';


	$changes = '';
	$t = count($changed);
	$z = 0;
	foreach($changed as $ch) {
		if(strlen($changes)) {
			if ($z == ($t - 1))
				$changes .= t(' and ');
			else
				$changes .= ', ';
		}
		$z ++;
		$changes .= $ch;
	}

	$prof = '[url=' . z_root() . '/profile/' . $self[0]['xchan_addr'] . ']' . t('public profile') . '[/url]';	

	if($t == 1 && strlen($value)) {
		$message = sprintf( t('%1$s changed %2$s to &ldquo;%3$s&rdquo;'), $A, $changes, $value);
		$message .= "\n\n" . sprintf( t(' - Visit %1$s\'s %2$s'), $A, $prof);
	}
	else
		$message = 	sprintf( t('%1$s has an updated %2$s, changing %3$s.'), $A, $prof, $changes);
 

	$arr['body'] = $message;  

	$links = array();
	$links[] = array('rel' => 'alternate', 'type' => 'text/html', 'href' => z_root() . '/profile/' . $self[0]['xchan_addr']);
	$links[] = array('rel' => 'photo', 'type' => $self[0]['xchan_photo_mimetype'], 'href' => $self[0]['xchan_photo_l']); 

	$arr['object'] = json_encode(array(
		'type'  => ACTIVITY_OBJ_PROFILE,
		'title' => $self[0]['channel_name'],
		'id'    => $self[0]['xchan_url'] . '/' . $self[0]['xchan_hash'],
		'link'  => $links
	));

	
	$arr['allow_cid'] = $self[0]['channel_allow_cid'];
	$arr['allow_gid'] = $self[0]['channel_allow_gid'];
	$arr['deny_cid']  = $self[0]['channel_deny_cid'];
	$arr['deny_gid']  = $self[0]['channel_deny_gid'];

	$i = item_store($arr);

	if($i) {
		// FIXME - limit delivery in notifier.php to those specificed in the perms argument
	   	proc_run('php',"include/notifier.php","activity","$i", 'PERMS_R_PROFILE');
	}

}
