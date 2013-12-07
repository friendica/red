<?php /** @file */


function follow_widget() {
	$a = get_app();
	$uid =$a->channel['channel_id'];
	$r = q("select count(*) as total from abook where abook_channel = %d and not (abook_flags & %d) ",
		intval($uid),
		intval(ABOOK_FLAG_SELF)
	);
	if($r)
		$total_channels = $r[0]['total'];	
	$limit = service_class_fetch($uid,'total_channels');
	if($limit !== false) {
			$abook_usage_message = sprintf( t("You have %1$.0f of %2$.0f allowed connections."), $total_channels, $limit);
	}
	else {
			$abook_usage_message = '';
 	}
	return replace_macros(get_markup_template('follow.tpl'),array(
		'$connect' => t('Add New Connection'),
		'$desc' => t('Enter the channel address'),
		'$hint' => t('Example: bob@example.com, http://example.com/barbara'),
		'$follow' => t('Connect'),
		'$abook_usage_message' => $abook_usage_message
	));

}

function findpeople_widget() {
	require_once('include/Contact.php');

	$a = get_app();

	if(get_config('system','invitation_only')) {
		$x = get_pconfig(local_user(),'system','invites_remaining');
		if($x || is_site_admin()) {
			$a->page['aside'] .= '<div class="side-link" id="side-invite-remain">' 
			. sprintf( tt('%d invitation available','%d invitations available',$x), $x) 
			. '</div>' . $inv;
		}
	}
 
	return replace_macros(get_markup_template('peoplefind.tpl'),array(
		'$findpeople' => t('Find Channels'),
		'$desc' => t('Enter name or interest'),
		'$label' => t('Connect/Follow'),
		'$hint' => t('Examples: Robert Morgenstein, Fishing'),
		'$findthem' => t('Find'),
		'$suggest' => t('Channel Suggestions'),
		'$similar' => '', // FIXME and uncomment when mod/match working // t('Similar Interests'),
		'$random' => t('Random Profile'),
		'$inv' => t('Invite Friends')
	));

}


function fileas_widget($baseurl,$selected = '') {
	$a = get_app();

	if(! local_user())
		return '';

	$terms = array();
	$r = q("select distinct(term) from term where uid = %d and type = %d order by term asc",
		intval(local_user()),
		intval(TERM_FILE)
	);
	if(! $r)
		return;

	foreach($r as $rr)
		$terms[] = array('name' => $rr['term'], 'selected' => (($selected == $rr['term']) ? 'selected' : ''));

	return replace_macros(get_markup_template('fileas_widget.tpl'),array(
		'$title' => t('Saved Folders'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('Everything'),
		'$terms' => $terms,
		'$base' => $baseurl,

	));
}

function categories_widget($baseurl,$selected = '') {

	$a = get_app();
	
	if(! feature_enabled($a->profile['profile_uid'],'categories'))
		return '';

	$terms = array();
	$r = q("select distinct(term.term)
                from term join item on term.oid = item.id
                where item.uid = %d
                and term.uid = item.uid
                and term.type = %d
                and item.author_xchan = '%s'
                order by term.term asc",
		intval($a->profile['profile_uid']),
	        intval(TERM_CATEGORY),
	        dbesc($a->profile['channel_hash'])
	);
	if($r && count($r)) {
		foreach($r as $rr)
			$terms[] = array('name' => $rr['term'], 'selected' => (($selected == $rr['term']) ? 'selected' : ''));

		return replace_macros(get_markup_template('categories_widget.tpl'),array(
			'$title' => t('Categories'),
			'$desc' => '',
			'$sel_all' => (($selected == '') ? 'selected' : ''),
			'$all' => t('Everything'),
			'$terms' => $terms,
			'$base' => $baseurl,

		));
	}
	return '';
}

function common_friends_visitor_widget($profile_uid) {

	$a = get_app();

	if(local_user() == $profile_uid)
		return;

	$observer_hash = get_observer_hash();

	if((! $observer_hash) || (! perm_is_allowed($profile_uid,$observer_hash,'view_contacts')))
		return;

	require_once('include/socgraph.php');

	$t = count_common_friends($profile_uid,$observer_hash);
	if(! $t)
		return;

	$r = common_friends($profile_uid,$observer_hash,0,5,true);

	return replace_macros(get_markup_template('remote_friends_common.tpl'), array(
		'$desc' =>  sprintf( tt("%d connection in common", "%d connections in common", $t), $t),
		'$base' => $a->get_baseurl(),
		'$uid' => $profile_uid,
		'$cid' => $observer,
		'$linkmore' => (($t > 5) ? 'true' : ''),
		'$more' => t('show more'),
		'$items' => $r
	)); 

};


function suggest_widget() {

	require_once('include/socgraph.php');

	$r = suggestion_query(local_user(),get_observer_hash(),0,20);

	if(! $r) {
		return;
	}

	$arr = array();

	// Get two random entries from the top 20 returned.
	// We'll grab the first one and the one immediately following.
	// This will throw some entropy intot he situation so you won't 
	// be looking at the same two mug shots every time the widget runs

	$index = mt_rand(0,count($r) - 2);
	
	

	for($x = $index; $x <= ($index+1); $x ++) {

		$rr = $r[$x];

		$connlnk = z_root() . '/follow/?url=' . $rr['xchan_addr'];

		$arr[] = array(
			'url' => chanlink_url($rr['xchan_url']),
			'profile' => $rr['xchan_url'],
			'name' => $rr['xchan_name'],
			'photo' => $rr['xchan_photo_m'],
			'ignlnk' => z_root() . '/suggest?ignore=' . $rr['xchan_hash'],
			'conntxt' => t('Connect'),
			'connlnk' => $connlnk,
			'ignore' => t('Ignore/Hide')
		);
	}


	$o = replace_macros(get_markup_template('suggest_widget.tpl'),array(
		'$title' => t('Suggestions'),
		'$more' => t('See more...'),
		'$entries' => $arr
	));

	return $o;

}

