<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function poke_init(&$a) {

	if(! local_user())
		return;

	$uid = local_user();
	$channel = $a->get_channel();


	$verb = notags(trim($_GET['verb']));
	
	if(! $verb) 
		return;

	$verbs = get_poke_verbs();

	if(! array_key_exists($verb,$verbs))
		return;

	$activity = ACTIVITY_POKE . '#' . urlencode($verbs[$verb][0]);

	$contact_id = intval($_GET['cid']);
	if(! $contact_id)
		return;

	$parent = ((x($_GET,'parent')) ? intval($_GET['parent']) : 0);


	logger('poke: verb ' . $verb . ' contact ' . $contact_id, LOGGER_DEBUG);


	$r = q("SELECT * FROM abook left join xchan on xchan_hash = abook_xchan where abook_id = %d and abook_channel = %d LIMIT 1",
		intval($contact_id),
		intval($uid)
	);

	if(! $r) {
		logger('poke: no target ' . $contact_id);
		return;
	}

	$target = $r[0];
	$parent_item = null;

	if($parent) {
		$r = q("select mid, item_private, owner_xchan, allow_cid, allow_gid, deny_cid, deny_gid 
			from item where id = %d and parent = %d and uid = %d limit 1",
			intval($parent),
			intval($parent),
			intval($uid)
		);
		if($r) {
			$parent_item  = $r[0];
			$parent_mid   = $r[0]['mid'];
			$item_private = $r[0]['item_private'];
			$allow_cid    = $r[0]['allow_cid'];
			$allow_gid    = $r[0]['allow_gid'];
			$deny_cid     = $r[0]['deny_cid'];
			$deny_gid     = $r[0]['deny_gid'];
		}
	}
	else {

		$item_private = ((x($_GET,'private')) ? intval($_GET['private']) : 0);

		$allow_cid     = (($item_private) ? '<' . $target['abook_hash']. '>' : $channel['channel_allow_cid']);
		$allow_gid     = (($item_private) ? '' : $channel['channel_allow_gid']);
		$deny_cid      = (($item_private) ? '' : $channel['channel_deny_cid']);
		$deny_gid      = (($item_private) ? '' : $channel['channel_deny_gid']);
	}





	$arr = array();
	$arr['item_flags']    = ITEM_WALL | ITEM_ORIGIN;
	if($parent_item)
		$arr['item_flags'] |= ITEM_THREAD_TOP;

	$arr['owner_xchan']   = (($parent_item) ? $parent_item['owner_xchan'] : $channel['channel_hash']);
	$arr['parent_mid']    = (($parent_mid) ? $parent_mid : $mid);
	$arr['title']         = '';
	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['verb']          = $activity;
	$arr['item_private']  = $item_private;
	$arr['obj_type']      = ACTIVITY_OBJ_PERSON;
	$arr['body']          = '[zrl=' . $channel['xchan_url'] . ']' . $channel['xchan_name'] . '[/zrl]' . ' ' . t($verbs[$verb][0]) . ' ' . '[zrl=' . $target['xchan_url'] . ']' . $target['xchan_name'] . '[/zrl]';

	$obj = array(
		'type' => ACTIVITY_OBJ_PERSON,
		'title' => $target['xchan_name'],
		'id' => $target['xchan_hash'],
		'link' => array(
			array('rel' => 'alternate', 'type' => 'text/html', 'href' => $target['xchan_url']),
			array('rel' => 'photo', 'type' => $target['xchan_photo_mimetype'], 'href' => $target['xchan_photo_l'])
		),
	);

	$arr['object'] = json_encode($obj);

	post_activity_item($arr);

	return;
}



function poke_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$name = '';
	$id = '';

	if(intval($_GET['c'])) {
		$r = q("select abook_id, xchan_name from abook left join xchan on abook_xchan = xchan_hash 
			where abook_id = %d and abook_channel = %d limit 1",
			intval($_GET['c']),
			intval(local_user())
		);
		if($r) {
			$name = $r[0]['xchan_name'];
			$id = $r[0]['abook_id'];
		}
	}


	$base = $a->get_baseurl();

	$a->page['htmlhead'] .= <<< EOT

<script>$(document).ready(function() { 
	var a; 
	a = $("#poke-recip").autocomplete({ 
		serviceUrl: '$base/acl',
		minChars: 2,
		width: 350,
		onSelect: function(value,data) {
			$("#poke-recip-complete").val(data);
		}			
	});
	a.setOptions({ params: { type: 'a' }});


}); 

</script>
EOT;

	$parent = ((x($_GET,'parent')) ? intval($_GET['parent']) : '0');



	$verbs = get_poke_verbs();

	$shortlist = array();
	foreach($verbs as $k => $v)
		if($v[1] !== 'NOTRANSLATION')
			$shortlist[] = array($k,$v[1]);

	$tpl = get_markup_template('poke_content.tpl');

	$o = replace_macros($tpl,array(
		'$title' => t('Poke/Prod'),
		'$desc' => t('poke, prod or do other things to somebody'),
		'$clabel' => t('Recipient'),
		'$choice' => t('Choose what you wish to do to recipient'),
		'$verbs' => $shortlist,
		'$parent' => $parent,
		'$prv_desc' => t('Make this post private'),
		'$submit' => t('Submit'),
		'$name' => $name,
		'$id' => $id
	));

	return $o;

}