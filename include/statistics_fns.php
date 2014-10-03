<?php /** @file */

function update_channels_total_stat() {
	$r = q("select count(channel_id) as channels_total from channel left join account on account_id = channel_account_id
			where account_flags = 0 ");
	if($r) {
		$channels_total_stat = intval($r[0]['channels_total']);
		set_config('system','channels_total_stat',$channels_total_stat);
	} else {
		set_config('system','channels_total_stat',null);
	}
}

function update_channels_active_halfyear_stat() {
	$r = q("select channel_id from channel left join account on account_id = channel_account_id
			where account_flags = 0 and account_lastlog > UTC_TIMESTAMP - INTERVAL 6 MONTH");
	if($r) {
		$s = '';
		foreach($r as $rr) {
			if($s)
				$s .= ',';
			$s .= intval($rr['channel_id']);
		}
		$x = q("select uid from item where uid in ( $s ) and (item_flags & %d) and created > UTC_TIMESTAMP - INTERVAL 6 MONTH group by uid",
			intval(ITEM_WALL)
		);
		if($x) {
			$channels_active_halfyear_stat = count($x);
			set_config('system','channels_active_halfyear_stat',$channels_active_halfyear_stat);
		} else {
			set_config('system','channels_active_halfyear_stat',null);
		}
	} else {
		set_config('system','channels_active_halfyear_stat',null);
	}
}

function update_channels_active_monthly_stat() {
	$r = q("select channel_id from channel left join account on account_id = channel_account_id
			where account_flags = 0 and account_lastlog > UTC_TIMESTAMP - INTERVAL 1 MONTH");
	if($r) {
		$s = '';
		foreach($r as $rr) {
			if($s)
				$s .= ',';
			$s .= intval($rr['channel_id']);
		}
		$x = q("select uid from item where uid in ( $s ) and ( item_flags & %d ) and created > UTC_TIMESTAMP - INTERVAL 1 MONTH group by uid",
			intval(ITEM_WALL)
		);
		if($x) {
			$channels_active_monthly_stat = count($x);
			set_config('system','channels_active_monthly_stat',$channels_active_monthly_stat);
		} else {
			set_config('system','channels_active_monthly_stat',null);
		}
	} else {
		set_config('system','channels_active_monthly_stat',null);
	}
}

function update_local_posts_stat() {
	$posts = q("SELECT COUNT(*) AS local_posts FROM `item` WHERE (item_flags & %d) ",
			intval(ITEM_WALL) );
	if (is_array($posts)) {
		$local_posts_stat = intval($posts[0]["local_posts"]);
		set_config('system','local_posts_stat',$local_posts_stat);
	} else {
		set_config('system','local_posts_stat',null);
	}
}


