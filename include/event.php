<?php


function format_event_html($ev) {

	require_once('include/bbcode.php');

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$bd_format = t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8 AM

	$o = '<div class="vevent">' . "\r\n";


	$o .= '<p class="summary event-summary">' . bbcode($ev['summary']) .  '</p>' . "\r\n";

	$o .= '<p class="description event-description">' . bbcode($ev['desc']) .  '</p>' . "\r\n";

	$o .= '<p class="event-start">' . t('Starts:') . ' <abbr class="dtstart" title="'
		. datetime_convert('UTC','UTC',$ev['start'], (($ev['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' ))
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

/*
function parse_event($h) {

	require_once('include/Scrape.php');
	require_once('library/HTMLPurifier.auto.php');
	require_once('include/html2bbcode');

	$h = '<html><body>' . $h . '</body></html>';

	$ret = array();


	try {
		$dom = HTML5_Parser::parse($h);
	} catch (DOMException $e) {
		logger('parse_event: parse error: ' . $e);
	}

	if(! $dom)
 		return $ret;

	$items = $dom->getElementsByTagName('*');

	foreach($items as $item) {
		if(attribute_contains($item->getAttribute('class'), 'vevent')) {
			$level2 = $item->getElementsByTagName('*');
			foreach($level2 as $x) {
				if(attribute_contains($x->getAttribute('class'),'dtstart') && $x->getAttribute('title')) {
					$ret['start'] = $x->getAttribute('title');
					if(! strpos($ret['start'],'Z'))
						$ret['adjust'] = true;
				}
				if(attribute_contains($x->getAttribute('class'),'dtend') && $x->getAttribute('title'))
					$ret['finish'] = $x->getAttribute('title');

				if(attribute_contains($x->getAttribute('class'),'description'))
					$ret['desc'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'location'))
					$ret['location'] = $x->textContent;
			}
		}
	}

	// sanitise

	if((x($ret,'desc')) && ((strpos($ret['desc'],'<') !== false) || (strpos($ret['desc'],'>') !== false))) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new HTMLPurifier($config);
		$ret['desc'] = html2bbcode($purifier->purify($ret['desc']));
	}

	if((x($ret,'location')) && ((strpos($ret['location'],'<') !== false) || (strpos($ret['location'],'>') !== false))) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new HTMLPurifier($config);
		$ret['location'] = html2bbcode($purifier->purify($ret['location']));
	}

	if(x($ret,'start'))
		$ret['start'] = datetime_convert('UTC','UTC',$ret['start']);
	if(x($ret,'finish'))
		$ret['finish'] = datetime_convert('UTC','UTC',$ret['finish']);

	return $ret;
}
*/

function format_event_bbcode($ev) {

	$o = '';

	if($ev['summary'])
		$o .= '[event-summary]' . $ev['summary'] . '[/event-summary]';

	if($ev['desc'])
		$o .= '[event-description]' . $ev['desc'] . '[/event-description]';

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
	if($ev['desc'])
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
		$ev['desc'] = $match[1];
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


function sort_by_date($arr) {
	if(is_array($arr))
		usort($arr,'ev_compare');
	return $arr;
}


function ev_compare($a,$b) {

	$date_a = (($a['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$a['start']) : $a['start']);
	$date_b = (($b['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$b['start']) : $b['start']);

	if($date_a === $date_b)
		return strcasecmp($a['desc'],$b['desc']);
	
	return strcmp($date_a,$date_b);
}



function event_store($arr) {

	require_once('include/datetime.php');
	require_once('include/items.php');
	require_once('include/bbcode.php');

	$a = get_app();

	$arr['created'] = (($arr['created']) ? $arr['created'] : datetime_convert());
	$arr['edited']  = (($arr['edited']) ? $arr['edited'] : datetime_convert());
	$arr['type']    = (($arr['type']) ? $arr['type'] : 'event' );	
	$arr['event_xchan'] = (($arr['event_xchan']) ? $arr['event_xchan'] : '');
	
	// Existing event being modified

	if($arr['id']) {

		// has the event actually changed?

		$r = q("SELECT * FROM `event` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($arr['id']),
			intval($arr['uid'])
		);
		if((! $r) || ($r[0]['edited'] === $arr['edited'])) {

			// Nothing has changed. Grab the item id to return.

			$r = q("SELECT id FROM item WHERE resource_type = 'event' and resource_id = '%s' AND uid = %d LIMIT 1",
				intval($arr['event_hash']),
				intval($arr['uid'])
			);
			return(($r) ? $r[0]['id'] : 0);
		}

		// The event changed. Update it.

		$r = q("UPDATE `event` SET
			`edited` = '%s',
			`start` = '%s',
			`finish` = '%s',
			`summary` = '%s',
			`desc` = '%s',
			`location` = '%s',
			`type` = '%s',
			`adjust` = %d,
			`nofinish` = %d,
			`allow_cid` = '%s',
			`allow_gid` = '%s',
			`deny_cid` = '%s',
			`deny_gid` = '%s'
			WHERE `id` = %d AND `uid` = %d LIMIT 1",

			dbesc($arr['edited']),
			dbesc($arr['start']),
			dbesc($arr['finish']),
			dbesc($arr['summary']),
			dbesc($arr['desc']),
			dbesc($arr['location']),
			dbesc($arr['type']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid']),
			intval($arr['id']),
			intval($arr['uid'])
		);

		$r = q("SELECT * FROM item left join xchan on author_xchan = xchan_hash WHERE resource_id = '%s' AND resource_type = 'event' and uid = %d LIMIT 1",
			intval($arr['event_hash']),
			intval($arr['uid'])
		);

		if($r) {

			$obj = json_encode(array(
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

			q("UPDATE item SET title = '%s', body = '%s', object = '%s', allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', edited = '%s', item_flags = %d WHERE id = %d AND uid = %d LIMIT 1",
				dbesc($arr['summary']),
				dbesc(format_event_bbcode($arr)),
				dbesc($object),
				dbesc($arr['allow_cid']),
				dbesc($arr['allow_gid']),
				dbesc($arr['deny_cid']),
				dbesc($arr['deny_gid']),
				dbesc($arr['edited']),
				intval(($private && ($r[0]['item_flags'] & ITEM_PRIVATE)) ? $r[0]['item_flags'] : $r[0]['item_flags'] ^ ITEM_PRIVATE),
				intval($r[0]['id']),
				intval($arr['uid'])
			);

			$item_id = $r[0]['id'];
		}
		else
			$item_id = 0;

		call_hooks('event_updated', $arr['id']);

		return $item_id;
	}
	else {

		// New event. Store it. 

		$hash = random_string();

		$r = q("INSERT INTO `event` ( `uid`,`aid`,`event_xchan`,`event_hash`,`created`,`edited`,`start`,`finish`,`summary`, `desc`,`location`,`type`,
			`adjust`,`nofinish`,`allow_cid`,`allow_gid`,`deny_cid`,`deny_gid`)
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
			dbesc($arr['desc']),
			dbesc($arr['location']),
			dbesc($arr['type']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid'])

		);

		$r = q("SELECT * FROM `event` WHERE `event_hash` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($hash),
			intval($arr['uid'])
		);
		if(count($r))
			$event = $r[0];

		$z = q("select * from channel where channel_hash = '%s' and channel_id = %d limit 1",
			dbesc($arr['event_xchan']),
			intval($arr['uid'])
		);

		$wall = (($z) ? true : false);

		$item_flags = ITEM_THREAD_TOP;
		if($wall) {
			$item_flags |= ITEM_WALL;
			$item_flags |= ITEM_ORIGIN;
		}


		$uri = item_message_id();

		$private = (($arr['allow_cid'] || $arr['allow_gid'] || $arr['deny_cid'] || $arr['deny_gid']) ? 1 : 0);
		if($private)
			$item_flags |= ITEM_PRIVATE;
				
		$item_arr = array();

		$item_arr['uid']           = $arr['uid'];
		$item_arr['author_xchan']  = $arr['event_xchan'];
		$item_arr['uri']           = $uri;
		$item_arr['parent_uri']    = $uri;

		$item_arr['item_flags']    = $item_flags;

		$item_arr['owner_xchan']   = (($wall) ? $z[0]['channel_hash'] : $arr['event_xchan']);
		$item_arr['author_xchan']  = $arr['event_xchan'];
		$item_arr['title']         = $arr['summary'];
		$item_arr['allow_cid']     = $arr['allow_cid'];
		$item_arr['allow_gid']     = $arr['allow_gid'];
		$item_arr['deny_cid']      = $arr['deny_cid'];
		$item_arr['deny_gid']      = $arr['deny_gid'];
		$item_arr['verb']          = ACTIVITY_POST;

		$item_arr['resource_type'] = 'event';
		$item_arr['resource_id']   = $hash;

		$item_arr['obj_type']   = ACTIVITY_OBJ_EVENT;
		$item_arr['body']          = format_event_bbcode($arr);

		$x = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($arr['event_xchan'])
		);
		if($x) {

			$item_arr['object'] = json_encode(array(
				'type'    => ACTIVITY_OBJ_EVENT,
				'id'      => z_root() . '/event/' . $hash,
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


		$item_id = item_store($item_arr);

		call_hooks("event_created", $event['id']);

		return $item_id;
	}
}
