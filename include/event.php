<?php
/**
 * @file include/event.php
 */

/**
 * @brief Returns an event as HTML
 *
 * @param array $ev
 * @return string
 */
function format_event_html($ev) {

	require_once('include/bbcode.php');

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$bd_format = t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8:01 AM

	$o = '<div class="vevent">' . "\r\n";

	$o .= '<p class="summary event-summary">' . bbcode($ev['summary']) .  '</p>' . "\r\n";

	$o .= '<p class="description event-description">' . bbcode($ev['description']) .  '</p>' . "\r\n";

	$o .= '<p class="event-start">' . t('Starts:') . ' <abbr class="dtstart" title="'
		. datetime_convert('UTC', 'UTC', $ev['start'], (($ev['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' ))
		. '" >' 
		. (($ev['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), 
			$ev['start'] , $bd_format ))
			:  day_translate(datetime_convert('UTC', 'UTC', 
			$ev['start'] , $bd_format)))
		. '</abbr></p>' . "\r\n";

	if(! $ev['nofinish'])
		$o .= '<p class="event-end" >' . t('Finishes:') . ' <abbr class="dtend" title="'
			. datetime_convert('UTC','UTC',$ev['finish'], (($ev['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' ))
			. '" >' 
			. (($ev['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), 
				$ev['finish'] , $bd_format ))
				:  day_translate(datetime_convert('UTC', 'UTC', 
				$ev['finish'] , $bd_format )))
			. '</abbr></p>'  . "\r\n";

	if(strlen($ev['location']))
		$o .= '<p class="event-location"> ' . t('Location:') . ' <span class="location">' 
			. bbcode($ev['location'])
			. '</span></p>' . "\r\n";

	$o .= '</div>' . "\r\n";

	return $o;
}


function ical_wrapper($ev) {

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$o .= "BEGIN:VCALENDAR";
	$o .= "\nVERSION:2.0";
	$o .= "\nMETHOD:PUBLISH";
	$o .= "\nPRODID:-//" . get_config('system','sitename') . "//" . RED_PLATFORM . "//" . strtoupper(get_app()->language). "\n";
	if(array_key_exists('start', $ev))
		$o .= format_event_ical($ev);
	else {
		foreach($ev as $e) {
			$o .= format_event_ical($e);
		}
	}
	$o .= "\nEND:VCALENDAR\n";

	return $o;
}

function format_event_ical($ev) {

	$o = '';

	$o .= "\nBEGIN:VEVENT";
	if($ev['start']) 
		$o .= "\nDTSTART:" . datetime_convert('UTC','UTC', $ev['start'],'Ymd\\THis' . (($ev['adjust']) ? '\\Z' : ''));
	if($ev['finish'] && ! $ev['nofinish']) 
		$o .= "\nDTEND:" . datetime_convert('UTC','UTC', $ev['finish'],'Ymd\\THis' . (($ev['adjust']) ? '\\Z' : ''));
	if($ev['summary']) 
		$o .= "\nSUMMARY:" . format_ical_text($ev['summary']);
	if($ev['location'])
		$o .= "\nLOCATION:" . format_ical_text($ev['location']);
	if($ev['description']) 
		$o .= "\nDESCRIPTION:" . format_ical_text($ev['description']);
	$o .= "\nEND:VEVENT\n";

	return $o;
}


function format_ical_text($s) {
	require_once('include/bbcode.php');
	require_once('include/html2plain.php');

	return(wordwrap(html2plain(bbcode($s)),72,"\n ",true));
}


function format_event_bbcode($ev) {

	$o = '';

	if($ev['summary'])
		$o .= '[event-summary]' . $ev['summary'] . '[/event-summary]';

	if($ev['description'])
		$o .= '[event-description]' . $ev['description'] . '[/event-description]';

	if($ev['start'])
		$o .= '[event-start]' . $ev['start'] . '[/event-start]';

	if(($ev['finish']) && (! $ev['nofinish']))
		$o .= '[event-finish]' . $ev['finish'] . '[/event-finish]';
 
	if($ev['location'])
		$o .= '[event-location]' . $ev['location'] . '[/event-location]';

	if($ev['adjust'])
		$o .= '[event-adjust]' . $ev['adjust'] . '[/event-adjust]';

	return $o;
}


function bbtovcal($s) {
	$o = '';
	$ev = bbtoevent($s);
	if($ev['description'])
		$o = format_event_html($ev);

	return $o;
}


function bbtoevent($s) {

	$ev = array();

	$match = '';
	if(preg_match("/\[event\-summary\](.*?)\[\/event\-summary\]/is",$s,$match))
		$ev['summary'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-description\](.*?)\[\/event\-description\]/is",$s,$match))
		$ev['description'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-start\](.*?)\[\/event\-start\]/is",$s,$match))
		$ev['start'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-finish\](.*?)\[\/event\-finish\]/is",$s,$match))
		$ev['finish'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-location\](.*?)\[\/event\-location\]/is",$s,$match))
		$ev['location'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-adjust\](.*?)\[\/event\-adjust\]/is",$s,$match))
		$ev['adjust'] = $match[1];
	$ev['nofinish'] = (((x($ev, 'start') && $ev['start']) && (!x($ev, 'finish') || !$ev['finish'])) ? 1 : 0);

	return $ev;
}

/**
 * @brief Sorts the given array of events by date.
 *
 * @see ev_compare()
 * @param array $arr
 * @return sorted array
 */
function sort_by_date($arr) {
	if (is_array($arr))
		usort($arr, 'ev_compare');

	return $arr;
}

/**
 * @brief Compare function for events.
 *
 * @see sort_by_date()
 * @param array $a
 * @param array $b
 * @return number return values like strcmp()
 */
function ev_compare($a, $b) {

	$date_a = (($a['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$a['start']) : $a['start']);
	$date_b = (($b['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$b['start']) : $b['start']);

	if ($date_a === $date_b)
		return strcasecmp($a['description'], $b['description']);

	return strcmp($date_a, $date_b);
}


function event_store_event($arr) {

	$arr['created']     = (($arr['created'])     ? $arr['created']     : datetime_convert());
	$arr['edited']      = (($arr['edited'])      ? $arr['edited']      : datetime_convert());
	$arr['type']        = (($arr['type'])        ? $arr['type']        : 'event' );
	$arr['event_xchan'] = (($arr['event_xchan']) ? $arr['event_xchan'] : '');

	// Existing event being modified

	if($arr['id'] || $arr['event_hash']) {

		// has the event actually changed?

		if($arr['event_hash']) {
			$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
				dbesc($arr['event_hash']),
				intval($arr['uid'])
			);
		}
		else {
			$r = q("SELECT * FROM event WHERE id = %d AND uid = %d LIMIT 1",
				intval($arr['id']),
				intval($arr['uid'])
			);
		}

		if(! $r)
			return false;

		if($r[0]['edited'] === $arr['edited']) {
			// Nothing has changed. Return the ID.
			return $r[0];
		}

		$hash = $r[0]['event_hash'];

		// The event changed. Update it.

		$r = q("UPDATE `event` SET
			`edited` = '%s',
			`start` = '%s',
			`finish` = '%s',
			`summary` = '%s',
			`description` = '%s',
			`location` = '%s',
			`type` = '%s',
			`adjust` = %d,
			`nofinish` = %d,
			`allow_cid` = '%s',
			`allow_gid` = '%s',
			`deny_cid` = '%s',
			`deny_gid` = '%s'
			WHERE `id` = %d AND `uid` = %d",

			dbesc($arr['edited']),
			dbesc($arr['start']),
			dbesc($arr['finish']),
			dbesc($arr['summary']),
			dbesc($arr['description']),
			dbesc($arr['location']),
			dbesc($arr['type']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid']),
			intval($r[0]['id']),
			intval($arr['uid'])
		);
	} else {

		// New event. Store it.

		$hash = random_string();

		$r = q("INSERT INTO event ( uid,aid,event_xchan,event_hash,created,edited,start,finish,summary,description,location,type,
			adjust,nofinish,allow_cid,allow_gid,deny_cid,deny_gid)
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s' ) ",
			intval($arr['uid']),
			intval($arr['account']),
			dbesc($arr['event_xchan']),
			dbesc($hash),
			dbesc($arr['created']),
			dbesc($arr['edited']),
			dbesc($arr['start']),
			dbesc($arr['finish']),
			dbesc($arr['summary']),
			dbesc($arr['description']),
			dbesc($arr['location']),
			dbesc($arr['type']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid'])
		);
	}

	$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
		dbesc($hash),
		intval($arr['uid'])
	);
	if($r)
		return $r[0];

	return false;
}

function event_addtocal($item_id, $uid) {

	$c = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $c)
		return false;

	$channel = $c[0];

	$r = q("select * from item where id = %d and uid = %d limit 1",
		intval($item_id),
		intval($channel['channel_id'])
	);

	if((! $r) || ($r[0]['obj_type'] !== ACTIVITY_OBJ_EVENT))
		return false;

	$item = $r[0];

	$ev = bbtoevent($r[0]['body']);

	if(x($ev,'summary') && x($ev,'start')) {
		$ev['event_xchan'] = $item['author_xchan'];
		$ev['uid']         = $channel['channel_id'];
		$ev['account']     = $channel['channel_account_id'];
		$ev['edited']      = $item['edited'];
		$ev['mid']         = $item['mid'];
		$ev['private']     = $item['item_private'];

		// is this an edit?

		if($item['resource_type'] === 'event') {
			$ev['event_hash'] = $item['resource_id'];
		}

		$event = event_store_event($ev);
		if($event) {
			$r = q("update item set resource_id = '%s', resource_type = 'event' where id = %d and uid = %d",
				dbesc($event['event_hash']),
				intval($item['id']),
				intval($channel['channel_id'])
			);

			return true;
		}
	}

	return false;
}


function event_store_item($arr, $event) {

	require_once('include/datetime.php');
	require_once('include/items.php');
	require_once('include/bbcode.php');

	$item = null;

	if($arr['mid'] && $arr['uid']) {
		$i = q("select * from item where mid = '%s' and uid = %d limit 1",
			dbesc($arr['mid']),
			intval($arr['uid'])
		);
		if($i) {
			xchan_query($i);
			$item = fetch_post_tags($i,true);
		}
	}

	$item_arr = array();
	$prefix = '';
//	$birthday = false;

	if($event['type'] === 'birthday') {
		$prefix =  t('This event has been added to your calendar.');
//		$birthday = true;

		// The event is created on your own site by the system, but appears to belong 
		// to the birthday person. It also isn't propagated - so we need to prevent
		// folks from trying to comment on it. If you're looking at this and trying to 
		// fix it, you'll need to completely change the way birthday events are created
		// and send them out from the source. This has its own issues.

		$item_arr['comment_policy'] = 'none';
	}

	$r = q("SELECT * FROM item left join xchan on author_xchan = xchan_hash WHERE resource_id = '%s' AND resource_type = 'event' and uid = %d LIMIT 1",
		dbesc($event['event_hash']),
		intval($arr['uid'])
	);

	if($r) {
		$object = json_encode(array(
			'type'    => ACTIVITY_OBJ_EVENT,
			'id'      => z_root() . '/event/' . $r[0]['resource_id'],
			'title'   => $arr['summary'],
			'content' => format_event_bbcode($arr),
			'author'  => array(
			'name'     => $r[0]['xchan_name'],
			'address'  => $r[0]['xchan_addr'],
			'guid'     => $r[0]['xchan_guid'],
			'guid_sig' => $r[0]['xchan_guid_sig'],
			'link'     => array(
				array('rel' => 'alternate', 'type' => 'text/html', 'href' => $r[0]['xchan_url']),
				array('rel' => 'photo', 'type' => $r[0]['xchan_photo_mimetype'], 'href' => $r[0]['xchan_photo_m'])),
			),
		));

		$private = (($arr['allow_cid'] || $arr['allow_gid'] || $arr['deny_cid'] || $arr['deny_gid']) ? 1 : 0);

		q("UPDATE item SET title = '%s', body = '%s', object = '%s', allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', edited = '%s', item_flags = %d, item_private = %d  WHERE id = %d AND uid = %d",
			dbesc($arr['summary']),
			dbesc($prefix . format_event_bbcode($arr)),
			dbesc($object),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid']),
			dbesc($arr['edited']),
			intval($r[0]['item_flags']),
			intval($private),
			intval($r[0]['id']),
			intval($arr['uid'])
		);

		q("delete from term where oid = %d and otype = %d",
			intval($r[0]['id']),
			intval(TERM_OBJ_POST)
		);

		if(($arr['term']) && (is_array($arr['term']))) {
			foreach($arr['term'] as $t) {
				q("insert into term (uid,oid,otype,type,term,url)
					values(%d,%d,%d,%d,'%s','%s') ",
					intval($arr['uid']),
					intval($r[0]['id']),
					intval(TERM_OBJ_POST),
					intval($t['type']),
					dbesc($t['term']),
					dbesc($t['url'])
				);
			}
		}

		$item_id = $r[0]['id'];
		call_hooks('event_updated', $event['id']);

		return $item_id;
	} else {

		$z = q("select * from channel where channel_id = %d limit 1",
			intval($arr['uid'])
		);

		$private = (($arr['allow_cid'] || $arr['allow_gid'] || $arr['deny_cid'] || $arr['deny_gid']) ? 1 : 0);

		if($item) {
			$item_arr['id'] = $item['id'];
		}
		else {
			$wall = (($z[0]['channel_hash'] == $event['event_xchan']) ? true : false);

			$item_flags = ITEM_THREAD_TOP;
			if($wall) {
				$item_flags |= ITEM_WALL;
				$item_flags |= ITEM_ORIGIN;
			}
			$item_arr['item_flags'] = $item_flags;
		}

		if(! $arr['mid'])
			$arr['mid'] = item_message_id();

		$item_arr['aid']           = $z[0]['channel_account_id'];
		$item_arr['uid']           = $arr['uid'];
		$item_arr['author_xchan']  = $arr['event_xchan'];
		$item_arr['mid']           = $arr['mid'];
		$item_arr['parent_mid']    = $arr['mid'];

		$item_arr['owner_xchan']   = (($wall) ? $z[0]['channel_hash'] : $arr['event_xchan']);
		$item_arr['author_xchan']  = $arr['event_xchan'];
		$item_arr['title']         = $arr['summary'];
		$item_arr['allow_cid']     = $arr['allow_cid'];
		$item_arr['allow_gid']     = $arr['allow_gid'];
		$item_arr['deny_cid']      = $arr['deny_cid'];
		$item_arr['deny_gid']      = $arr['deny_gid'];
		$item_arr['item_private']  = $private;
		$item_arr['verb']          = ACTIVITY_POST;

		if(array_key_exists('term', $arr))
			$item_arr['term'] = $arr['term'];

		$item_arr['resource_type'] = 'event';
		$item_arr['resource_id']   = $event['event_hash'];

		$item_arr['obj_type']      = ACTIVITY_OBJ_EVENT;

		$item_arr['body']          = $prefix . format_event_bbcode($arr);

		// if it's local send the permalink to the channel page.
		// otherwise we'll fallback to /display/$message_id

		if($wall)
			$item_arr['plink'] = z_root() . '/channel/' . $z[0]['channel_address'] . '/?f=&mid=' . $item_arr['mid'];
		else
			$item_arr['plink'] = z_root() . '/display/' . $item_arr['mid'];

		$x = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($arr['event_xchan'])
		);
		if($x) {
			$item_arr['object'] = json_encode(array(
				'type'    => ACTIVITY_OBJ_EVENT,
				'id'      => z_root() . '/event/' . $event['event_hash'],
				'title'   => $arr['summary'],
				'content' => format_event_bbcode($arr),
				'author'  => array(
					'name'     => $x[0]['xchan_name'],
					'address'  => $x[0]['xchan_addr'],
					'guid'     => $x[0]['xchan_guid'],
					'guid_sig' => $x[0]['xchan_guid_sig'],
					'link'     => array(
						array('rel' => 'alternate', 'type' => 'text/html', 'href' => $x[0]['xchan_url']),
						array('rel' => 'photo', 'type' => $x[0]['xchan_photo_mimetype'], 'href' => $x[0]['xchan_photo_m'])),
					),
			));
		}

		$res = item_store($item_arr);

		$item_id = $res['item_id'];

		call_hooks('event_created', $event['id']);

		return $item_id;
	}
}
