<?php

require_once('include/conversation.php');
require_once('include/bbcode.php');
require_once('include/datetime.php');
require_once('include/event.php');
require_once('include/items.php');

function events_post(&$a) {

	if(! local_channel())
		return;

	$event_id = ((x($_POST,'event_id')) ? intval($_POST['event_id']) : 0);
	$event_hash = ((x($_POST,'event_hash')) ? $_POST['event_hash'] : '');

	$xchan = ((x($_POST,'xchan')) ? dbesc($_POST['xchan']) : '');
	$uid      = local_channel();

	$start_text = escape_tags($_REQUEST['start_text']);
	$finish_text = escape_tags($_REQUEST['finish_text']);

	$adjust   = intval($_POST['adjust']);
	$nofinish = intval($_POST['nofinish']);

	$categories = escape_tags(trim($_POST['category']));



	// only allow editing your own events. 

	if(($xchan) && ($xchan !== get_observer_hash()))
		return;

	// The default setting for the `private` field in event_store() is false, so mirror that	
	$private_event = false;

	if($start_text) {
		$start = $start_text;
	}
	else {
		$start = sprintf('%d-%d-%d %d:%d:0',$startyear,$startmonth,$startday,$starthour,$startminute);
	}

	if($nofinish) {
		$finish = NULL_DATE;
	}

	if($finish_text) {
		$finish = $finish_text;
	}
	else {
		$finish = sprintf('%d-%d-%d %d:%d:0',$finishyear,$finishmonth,$finishday,$finishhour,$finishminute);
	}

	if($adjust) {
		$start = datetime_convert(date_default_timezone_get(),'UTC',$start);
		if(! $nofinish)
			$finish = datetime_convert(date_default_timezone_get(),'UTC',$finish);
	}
	else {
		$start = datetime_convert('UTC','UTC',$start);
		if(! $nofinish)
			$finish = datetime_convert('UTC','UTC',$finish);
	}

	// Don't allow the event to finish before it begins.
	// It won't hurt anything, but somebody will file a bug report
	// and we'll waste a bunch of time responding to it. Time that 
	// could've been spent doing something else. 


	$summary  = escape_tags(trim($_POST['summary']));
	$desc     = escape_tags(trim($_POST['desc']));
	$location = escape_tags(trim($_POST['location']));
	$type     = 'event';

	require_once('include/text.php');
	linkify_tags($a, $desc, local_channel());
	linkify_tags($a, $location, local_channel());

	$action = ($event_hash == '') ? 'new' : "event/" . $event_hash;
	$onerror_url = $a->get_baseurl() . "/events/" . $action . "?summary=$summary&description=$desc&location=$location&start=$start_text&finish=$finish_text&adjust=$adjust&nofinish=$nofinish";
	if(strcmp($finish,$start) < 0 && !$nofinish) {
		notice( t('Event can not end before it has started.') . EOL);
		goaway($onerror_url);
	}

	if((! $summary) || (! $start)) {
		notice( t('Event title and start time are required.') . EOL);
		goaway($onerror_url);
	}

	$share = ((intval($_POST['share'])) ? intval($_POST['share']) : 0);

	$channel = $a->get_channel();

	if($event_id) {
		$x = q("select * from event where id = %d and uid = %d limit 1",
			intval($event_id),
			intval(local_channel())
		);
		if(! $x) {
			notice( t('Event not found.') . EOL);
			return;
		}
		if($x[0]['allow_cid'] === '<' . $channel['channel_hash'] . '>' 
			&& $x[0]['allow_gid'] === '' && $x[0]['deny_cid'] === '' && $x[0]['deny_gid'] === '') {
			$share = false;
		}
		else {
			$share = true;
			$str_group_allow = $x[0]['allow_gid'];
			$str_contact_allow = $x[0]['allow_cid'];
			$str_group_deny = $x[0]['deny_gid'];
			$str_contact_deny = $x[0]['deny_cid'];

			if(strlen($str_group_allow) || strlen($str_contact_allow) 
				|| strlen($str_group_deny) || strlen($str_contact_deny)) {
				$private_event = true;
			}
		}
	}
	else {
		if($share) {
			$str_group_allow   = perms2str($_POST['group_allow']);
			$str_contact_allow = perms2str($_POST['contact_allow']);
			$str_group_deny    = perms2str($_POST['group_deny']);
			$str_contact_deny  = perms2str($_POST['contact_deny']);

			if(strlen($str_group_allow) || strlen($str_contact_allow) 
				|| strlen($str_group_deny) || strlen($str_contact_deny)) {
				$private_event = true;
			}
		}
		else {
			// Note: do not set `private` field for self-only events. It will
			// keep even you from seeing them!
			$str_contact_allow = '<' . $channel['channel_hash'] . '>';
			$str_group_allow = $str_contact_deny = $str_group_deny = '';
		}
	}

	$post_tags = array();
	$channel = $a->get_channel();

	if(strlen($categories)) {
		$cats = explode(',',$categories);
		foreach($cats as $cat) {
			$post_tags[] = array(
				'uid'   => $profile_uid, 
				'type'  => TERM_CATEGORY,
				'otype' => TERM_OBJ_POST,
				'term'  => trim($cat),
				'url'   => $channel['xchan_url'] . '?f=&cat=' . urlencode(trim($cat))
			); 				
		}
	}

	$datarray = array();
	$datarray['start'] = $start;
	$datarray['finish'] = $finish;
	$datarray['summary'] = $summary;
	$datarray['description'] = $desc;
	$datarray['location'] = $location;
	$datarray['type'] = $type;
	$datarray['adjust'] = $adjust;
	$datarray['nofinish'] = $nofinish;
	$datarray['uid'] = local_channel();
	$datarray['account'] = get_account_id();
	$datarray['event_xchan'] = $channel['channel_hash'];
	$datarray['allow_cid'] = $str_contact_allow;
	$datarray['allow_gid'] = $str_group_allow;
	$datarray['deny_cid'] = $str_contact_deny;
	$datarray['deny_gid'] = $str_group_deny;
	$datarray['private'] = (($private_event) ? 1 : 0);
	$datarray['id'] = $event_id;
	$datarray['created'] = $created;
	$datarray['edited'] = $edited;

	$event = event_store_event($datarray);


	if($post_tags)	
		$datarray['term'] = $post_tags;

	$item_id = event_store_item($datarray,$event);

	if($share)
		proc_run('php',"include/notifier.php","event","$item_id");

}



function events_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	nav_set_selected('all_events');

	if((argc() > 2) && (argv(1) === 'ignore') && intval(argv(2))) {
		$r = q("update event set ignore = 1 where id = %d and uid = %d",
			intval(argv(2)),
			intval(local_channel())
		);
	}

	if((argc() > 2) && (argv(1) === 'unignore') && intval(argv(2))) {
		$r = q("update event set ignore = 0 where id = %d and uid = %d",
			intval(argv(2)),
			intval(local_channel())
		);
	}


	$plaintext = true;

//	if(feature_enabled(local_channel(),'richtext'))
//		$plaintext = false;



	$htpl = get_markup_template('event_head.tpl');
	$a->page['htmlhead'] .= replace_macros($htpl,array(
		'$baseurl' => $a->get_baseurl(),
		'$editselect' => (($plaintext) ? 'none' : 'textareas')
	));

	$o ="";
	// tabs

	$channel = $a->get_channel();

	$tabs = profile_tabs($a, True, $channel['channel_address']);	



	$mode = 'view';
	$y = 0;
	$m = 0;
	$ignored = ((x($_REQUEST,'ignored')) ? intval($_REQUEST['ignored']) : 0);

	if(argc() > 1) {
		if(argc() > 2 && argv(1) == 'event') {
			$mode = 'edit';
			$event_id = argv(2);
		}
		if(argc() > 2 && argv(1) === 'add') {
			$mode = 'add';
			$item_id = intval(argv(2));
		}
		if(argv(1) === 'new') {
			$mode = 'new';
			$event_id = '';
		}
		if(argc() > 2 && intval(argv(1)) && intval(argv(2))) {
			$mode = 'view';
			$y = intval(argv(1));
			$m = intval(argv(2));
		}
	}

	if($mode === 'add') {
		event_addtocal($item_id,local_channel());
		killme();
	}

	if($mode == 'view') {
		
		
		$thisyear = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
		$thismonth = datetime_convert('UTC',date_default_timezone_get(),'now','m');
		if(! $y)
			$y = intval($thisyear);
		if(! $m)
			$m = intval($thismonth);

		$export = false;
		if(argc() === 4 && argv(3) === 'export')
			$export = true;


		// Put some limits on dates. The PHP date functions don't seem to do so well before 1900.
		// An upper limit was chosen to keep search engines from exploring links millions of years in the future. 

		if($y < 1901)
			$y = 1900;
		if($y > 2099)
			$y = 2100;

		$nextyear = $y;
		$nextmonth = $m + 1;
		if($nextmonth > 12) {
				$nextmonth = 1;
			$nextyear ++;
		}

		$prevyear = $y;
		if($m > 1)
			$prevmonth = $m - 1;
		else {
			$prevmonth = 12;
			$prevyear --;
		}
			
		$dim    = get_dim($y,$m);
		$start  = sprintf('%d-%d-%d %d:%d:%d',$y,$m,1,0,0,0);
		$finish = sprintf('%d-%d-%d %d:%d:%d',$y,$m,$dim,23,59,59);


		if (argv(1) === 'json'){
			if (x($_GET,'start'))	$start = date("Y-m-d h:i:s", $_GET['start']);
			if (x($_GET,'end'))	$finish = date("Y-m-d h:i:s", $_GET['end']);
		}
	
		$start  = datetime_convert('UTC','UTC',$start);
		$finish = datetime_convert('UTC','UTC',$finish);

		$adjust_start = datetime_convert('UTC', date_default_timezone_get(), $start);
		$adjust_finish = datetime_convert('UTC', date_default_timezone_get(), $finish);

		if (x($_GET,'id')){
		  	$r = q("SELECT event.*, item.plink, item.item_flags, item.author_xchan, item.owner_xchan
                                from event left join item on resource_id = event_hash where resource_type = 'event' and event.uid = %d and event.id = %d limit 1",
				intval(local_channel()),
				intval($_GET['id'])
			);
		} else {

			// fixed an issue with "nofinish" events not showing up in the calendar.
			// There's still an issue if the finish date crosses the end of month.
			// Noting this for now - it will need to be fixed here and in Friendica.
			// Ultimately the finish date shouldn't be involved in the query. 

			$r = q("SELECT event.*, item.plink, item.item_flags, item.author_xchan, item.owner_xchan
                              from event left join item on event_hash = resource_id 
				where resource_type = 'event' and event.uid = %d and event.ignore = %d 
				AND (( `adjust` = 0 AND ( `finish` >= '%s' or nofinish = 1 ) AND `start` <= '%s' ) 
				OR  (  `adjust` = 1 AND ( `finish` >= '%s' or nofinish = 1 ) AND `start` <= '%s' )) ",
				intval(local_channel()),
				intval($ignored),
				dbesc($start),
				dbesc($finish),
				dbesc($adjust_start),
				dbesc($adjust_finish)
			);

		}

		$links = array();

		if($r) {
			xchan_query($r);
			$r = fetch_post_tags($r,true);

			$r = sort_by_date($r);

			foreach($r as $rr) {
				$j = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], 'j') : datetime_convert('UTC','UTC',$rr['start'],'j'));
				if(! x($links,$j)) 
					$links[$j] = $a->get_baseurl() . '/' . $a->cmd . '#link-' . $j;
			}
		}


		$events=array();

		$last_date = '';
		$fmt = t('l, F j');

		if($r) {

			foreach($r as $rr) {
				
				$j = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], 'j') : datetime_convert('UTC','UTC',$rr['start'],'j'));
				$d = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], $fmt) : datetime_convert('UTC','UTC',$rr['start'],$fmt));
				$d = day_translate($d);
				
				$start = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], 'c') : datetime_convert('UTC','UTC',$rr['start'],'c'));
				if ($rr['nofinish']){
					$end = null;
				} else {
					$end = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['finish'], 'c') : datetime_convert('UTC','UTC',$rr['finish'],'c'));
				}
				
				
				$is_first = ($d !== $last_date);
					
				$last_date = $d;
// FIXME
				$edit = (($rr['item_flags'] & ITEM_WALL) ? array($a->get_baseurl().'/events/event/'.$rr['event_hash'],t('Edit event'),'','') : null);
				$title = strip_tags(html_entity_decode(bbcode($rr['summary']),ENT_QUOTES,'UTF-8'));
				if(! $title) {
					list($title, $_trash) = explode("<br",bbcode($rr['desc']),2);
					$title = strip_tags(html_entity_decode($title,ENT_QUOTES,'UTF-8'));
				}
				$html = format_event_html($rr);
				$rr['desc'] = bbcode($rr['desc']);
				$rr['location'] = bbcode($rr['location']);
				$events[] = array(
					'id'=>$rr['id'],
					'hash' => $rr['event_hash'],
					'start'=> $start,
					'end' => $end,
					'allDay' => false,
					'title' => $title,
					
					'j' => $j,
					'd' => $d,
					'edit' => $edit,
					'is_first'=>$is_first,
					'item'=>$rr,
					'html'=>$html,
					'plink' => array($rr['plink'],t('Link to Source'),'',''),
				);


			}
		}
		 
		if($export) {
			header('Content-type: text/calendar');
			echo ical_wrapper($r);
			killme();
		}

		if ($a->argv[1] === 'json'){
			echo json_encode($events); killme();
		}
		
		// links: array('href', 'text', 'extra css classes', 'title')
		if (x($_GET,'id')){
			$tpl =  get_markup_template("event.tpl");
		} 
		else {
			$tpl = get_markup_template("events-js.tpl");
		}

		$o = replace_macros($tpl, array(
			'$baseurl'	=> $a->get_baseurl(),
			'$tabs'		=> $tabs,
			'$title'	=> t('Events'),
			'$new_event'=> array($a->get_baseurl().'/events/new',t('Create New Event'),'',''),
			'$previus'	=> array($a->get_baseurl()."/events/$prevyear/$prevmonth",t('Previous'),'',''),
			'$next'		=> array($a->get_baseurl()."/events/$nextyear/$nextmonth",t('Next'),'',''),
			'$export'   => array($a->get_baseurl()."/events/$y/$m/export",t('Export'),'',''),
			'$calendar' => cal($y,$m,$links, ' eventcal'),			
			'$events'	=> $events,
			
			
		));
		
		if (x($_GET,'id')){ echo $o; killme(); }
		
		return $o;
		
	}

	if($mode === 'edit' && $event_id) {
		$r = q("SELECT * FROM `event` WHERE event_hash = '%s' AND `uid` = %d LIMIT 1",
			dbesc($event_id),
			intval(local_channel())
		);
		if(count($r))
			$orig_event = $r[0];
	}

	$channel = $a->get_channel();

	// Passed parameters overrides anything found in the DB
	if($mode === 'edit' || $mode === 'new') {
		if(!x($orig_event)) $orig_event = array();
		// In case of an error the browser is redirected back here, with these parameters filled in with the previous values
		if(x($_REQUEST,'nofinish')) $orig_event['nofinish'] = $_REQUEST['nofinish'];
		if(x($_REQUEST,'adjust')) $orig_event['adjust'] = $_REQUEST['adjust'];
		if(x($_REQUEST,'summary')) $orig_event['summary'] = $_REQUEST['summary'];
		if(x($_REQUEST,'description')) $orig_event['description'] = $_REQUEST['description'];
		if(x($_REQUEST,'location')) $orig_event['location'] = $_REQUEST['location'];
		if(x($_REQUEST,'start')) $orig_event['start'] = $_REQUEST['start'];
		if(x($_REQUEST,'finish')) $orig_event['finish'] = $_REQUEST['finish'];
	}

	if($mode === 'edit' || $mode === 'new') {

		$n_checked = ((x($orig_event) && $orig_event['nofinish']) ? ' checked="checked" ' : '');
		$a_checked = ((x($orig_event) && $orig_event['adjust']) ? ' checked="checked" ' : '');
		$t_orig = ((x($orig_event)) ? $orig_event['summary'] : '');
		$d_orig = ((x($orig_event)) ? $orig_event['description'] : '');
		$l_orig = ((x($orig_event)) ? $orig_event['location'] : '');
		$eid = ((x($orig_event)) ? $orig_event['id'] : 0);
		$event_xchan = ((x($orig_event)) ? $orig_event['event_xchan'] : $channel['channel_hash']);
		$mid = ((x($orig_event)) ? $orig_event['mid'] : '');

		if(! x($orig_event))
			$sh_checked = '';
		else
			$sh_checked = (($orig_event['allow_cid'] === '<' . $channel['channel_hash'] . '>' && (! $orig_event['allow_gid']) && (! $orig_event['deny_cid']) && (! $orig_event['deny_gid'])) ? '' : ' checked="checked" ' );

		if($orig_event['event_xchan'])
			$sh_checked .= ' disabled="disabled" ';


		$sdt = ((x($orig_event)) ? $orig_event['start'] : 'now');
		$fdt = ((x($orig_event)) ? $orig_event['finish'] : 'now');

		$tz = date_default_timezone_get();
		if(x($orig_event))
			$tz = (($orig_event['adjust']) ? date_default_timezone_get() : 'UTC');

		$syear = datetime_convert('UTC', $tz, $sdt, 'Y');
		$smonth = datetime_convert('UTC', $tz, $sdt, 'm');
		$sday = datetime_convert('UTC', $tz, $sdt, 'd');


		$shour = ((x($orig_event)) ? datetime_convert('UTC', $tz, $sdt, 'H') : 0);
		$sminute = ((x($orig_event)) ? datetime_convert('UTC', $tz, $sdt, 'i') : 0);
		$stext = datetime_convert('UTC',$tz,$sdt);
		$stext = substr($stext,0,14) . "00:00";

		$fyear = datetime_convert('UTC', $tz, $fdt, 'Y');
		$fmonth = datetime_convert('UTC', $tz, $fdt, 'm');
		$fday = datetime_convert('UTC', $tz, $fdt, 'd');

		$fhour = ((x($orig_event)) ? datetime_convert('UTC', $tz, $fdt, 'H') : 0);
		$fminute = ((x($orig_event)) ? datetime_convert('UTC', $tz, $fdt, 'i') : 0);
		$ftext = datetime_convert('UTC',$tz,$fdt);
		$ftext = substr($ftext,0,14) . "00:00";

		$f = get_config('system','event_input_format');
		if(! $f)
			$f = 'ymd';

		$catsenabled = feature_enabled(local_channel(),'categories');

		$category = '';

		if($catsenabled && x($orig_event)){
			$itm = q("select * from item where resource_type = 'event' and resource_id = '%s' and uid = %d limit 1",
				dbesc($orig_event['event_hash']),
				intval(local_channel())
			);
			$itm = fetch_post_tags($itm);
			if($itm) {
				$cats = get_terms_oftype($itm[0]['term'], TERM_CATEGORY);
				foreach ($cats as $cat) {
					if(strlen($category))
						$category .= ', ';
					$category .= $cat['term'];
            	}
			}
		}

		require_once('include/acl_selectors.php');

		$perm_defaults = array(
			'allow_cid' => $channel['channel_allow_cid'], 
			'allow_gid' => $channel['channel_allow_gid'], 
			'deny_cid' => $channel['channel_deny_cid'], 
			'deny_gid' => $channel['channel_deny_gid']
		); 

		$tpl = get_markup_template('event_form.tpl');

		$o .= replace_macros($tpl,array(
			'$post' => $a->get_baseurl() . '/events',
			'$eid' => $eid, 
			'$xchan' => $event_xchan,
			'$mid' => $mid,
			'$event_hash' => $event_id,
	
			'$title' => t('Event details'),
			'$desc' => t('Starting date and Title are required.'),
			'$catsenabled' => $catsenabled,
			'$placeholdercategory' => t('Categories (comma-separated list)'),
			'$category' => $category,
			'$s_text' => t('Event Starts:'),
			'$stext' => $stext,
			'$ftext' => $ftext,
			'$required' =>  ' <span class="required" title="' . t('Required') . '">*</span>',
			'$ModalCANCEL' => t('Cancel'),
			'$ModalOK' => t('OK'),
			'$s_dsel' => datetimesel($f,new DateTime(),DateTime::createFromFormat('Y',$syear+5),DateTime::createFromFormat('Y-m-d H:i',"$syear-$smonth-$sday $shour:$sminute"),'start_text',true,true,'','',true),
			'$n_text' => t('Finish date/time is not known or not relevant'),
			'$n_checked' => $n_checked,
			'$f_text' => t('Event Finishes:'),
			'$f_dsel' => datetimesel($f,new DateTime(),DateTime::createFromFormat('Y',$fyear+5),DateTime::createFromFormat('Y-m-d H:i',"$fyear-$fmonth-$fday $fhour:$fminute"),'finish_text',true,true,'start_text'),
			'$adjust' => array('adjust', t('Adjust for viewer timezone'), $a_checked),
			'$a_text' => t('Adjust for viewer timezone'),
			'$d_text' => t('Description:'), 
			'$d_orig' => $d_orig,
			'$l_text' => t('Location:'),
			'$l_orig' => $l_orig,
			'$t_text' => t('Title:'),
			'$t_orig' => $t_orig,
			'$sh_text' => t('Share this event'),
			'$sh_checked' => $sh_checked,
			'$permissions' => t('Permissions'),
			'$acl' => (($orig_event['event_xchan']) ? '' : populate_acl(((x($orig_event)) ? $orig_event : $perm_defaults),false)),
			'$submit' => t('Submit')

		));

		return $o;
	}
}
