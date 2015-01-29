<?php


function notify_init(&$a) {
	if(! local_channel())
		return;

	if(argc() > 2 && argv(1) === 'view' && intval(argv(2))) {
		$r = q("select * from notify where id = %d and uid = %d limit 1",
			intval(argv(2)),
			intval(local_channel())
		);
		if($r) {
			q("update notify set seen = 1 where (( parent != '' and parent = '%s' and otype = '%s' ) or link = '%s' ) and uid = %d",
				dbesc($r[0]['parent']),
				dbesc($r[0]['otype']),
				dbesc($r[0]['link']),
				intval(local_channel())
			);
			goaway($r[0]['link']);
		}
		goaway($a->get_baseurl(true));
	}


}


function notify_content(&$a) {
	if(! local_channel())
		return login();

	$notif_tpl = get_markup_template('notifications.tpl');
		
	$not_tpl = get_markup_template('notify.tpl');
	require_once('include/bbcode.php');

	$r = q("SELECT * from notify where uid = %d and seen = 0 order by date desc",
		intval(local_channel())
	);
		
	if($r) {
		foreach ($r as $it) {
			$notif_content .= replace_macros($not_tpl,array(
				'$item_link' => $a->get_baseurl(true).'/notify/view/'. $it['id'],
				'$item_image' => $it['photo'],
				'$item_text' => strip_tags(bbcode($it['msg'])),
				'$item_when' => relative_date($it['date'])
			));
		}
	} 
	else {
		$notif_content .= t('No more system notifications.');
	}
		
	$o .= replace_macros($notif_tpl,array(
		'$notif_header' => t('System Notifications'),
		'$tabs' => '', // $tabs,
		'$notif_content' => $notif_content,
	));

	return $o;

}