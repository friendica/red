<?php

require_once('include/datetime.php');
require_once('include/event.php');
require_once('include/items.php');

function events_post(&$a) {

	if(! local_user())
		return;

	$event_id = ((x($_POST,'event_id')) ? intval($_POST['event_id']) : 0);
	$cid = ((x($_POST,'cid')) ? intval($_POST['cid']) : 0);
	$uid      = local_user();
	$startyear = intval($_POST['startyear']);
	$startmonth = intval($_POST['startmonth']);
	$startday = intval($_POST['startday']);
	$starthour = intval($_POST['starthour']);
	$startminute = intval($_POST['startminute']);

	$finishyear = intval($_POST['finishyear']);
	$finishmonth = intval($_POST['finishmonth']);
	$finishday = intval($_POST['finishday']);
	$finishhour = intval($_POST['finishhour']);
	$finishminute = intval($_POST['finishminute']);

	$adjust   = intval($_POST['adjust']);
	$nofinish = intval($_POST['nofinish']);


	$start    = sprintf('%d-%d-%d %d:%d:0',$startyear,$startmonth,$startday,$starthour,$startminute);
	if($nofinish)
		$finish = '0000-00-00 00:00:00';
	else
		$finish    = sprintf('%d-%d-%d %d:%d:0',$finishyear,$finishmonth,$finishday,$finishhour,$finishminute);

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

	if(strcmp($finish,$start) < 0)
		$finish = $start;

	$desc     = escape_tags(trim($_POST['desc']));
	$location = escape_tags(trim($_POST['location']));
	$type     = 'event';

	if((! $desc) || (! $start)) {
		notice( t('Event description and start time are required.') . EOL);
		goaway($a->get_baseurl() . '/events/new');
	}

	$share = ((intval($_POST['share'])) ? intval($_POST['share']) : 0);

	if($share) {
		$str_group_allow   = perms2str($_POST['group_allow']);
		$str_contact_allow = perms2str($_POST['contact_allow']);
		$str_group_deny    = perms2str($_POST['group_deny']);
		$str_contact_deny  = perms2str($_POST['contact_deny']);
	}
	else {
		$str_contact_allow = '<' . local_user() . '>';
		$str_group_allow = $str_contact_deny = $str_group_deny = '';
	}


	$datarray = array();
	$datarray['start'] = $start;
	$datarray['finish'] = $finish;
	$datarray['desc'] = $desc;
	$datarray['location'] = $location;
	$datarray['type'] = $type;
	$datarray['adjust'] = $adjust;
	$datarray['nofinish'] = $nofinish;
	$datarray['uid'] = $uid;
	$datarray['cid'] = $cid;
	$datarray['allow_cid'] = $str_contact_allow;
	$datarray['allow_gid'] = $str_group_allow;
	$datarray['deny_cid'] = $str_contact_deny;
	$datarray['deny_gid'] = $str_group_deny;
	$datarray['id'] = $event_id;
	$datarray['created'] = $created;
	$datarray['edited'] = $edited;

	$item_id = event_store($datarray);

	if(! $cid)
		proc_run('php',"include/notifier.php","event","$item_id");

}



function events_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$o ="";
	// tabs
	$o .= profile_tabs($a, True);	

	$o .= '<h2>' . t('Events') . '</h2>';


	$mode = 'view';
	$y = 0;
	$m = 0;

	if($a->argc > 1) {
		if($a->argc > 2 && $a->argv[1] == 'event') {
			$mode = 'edit';
			$event_id = intval($a->argv[2]);
		}
		if($a->argv[1] === 'new') {
			$mode = 'new';
			$event_id = 0;
		}
		if($a->argc > 2 && intval($a->argv[1]) && intval($a->argv[2])) {
			$mode = 'view';
			$y = intval($a->argv[1]);
			$m = intval($a->argv[2]);
		}
	}

	if($mode == 'view') {
	    $thisyear = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
    	$thismonth = datetime_convert('UTC',date_default_timezone_get(),'now','m');
		if(! $y)
			$y = intval($thisyear);
		if(! $m)
			$m = intval($thismonth);

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
	
		$start  = datetime_convert('UTC','UTC',$start);
		$finish = datetime_convert('UTC','UTC',$finish);

		$adjust_start = datetime_convert('UTC', date_default_timezone_get(), $start);
		$adjust_finish = datetime_convert('UTC', date_default_timezone_get(), $finish);


		$r = q("SELECT `event`.*, `item`.`id` AS `itemid`,`item`.`plink`,
			`item`.`author-name`, `item`.`author-avatar`, `item`.`author-link` FROM `event` LEFT JOIN `item` ON `item`.`event-id` = `event`.`id` 
			WHERE `event`.`uid` = %d
			AND (( `adjust` = 0 AND `start` >= '%s' AND `start` <= '%s' ) 
			OR  (  `adjust` = 1 AND `start` >= '%s' AND `start` <= '%s' )) ",
			intval(local_user()),
			dbesc($start),
			dbesc($finish),
			dbesc($adjust_start),
			dbesc($adjust_finish)
		);

		$links = array();

		if(count($r)) {
			$r = sort_by_date($r);
			foreach($r as $rr) {
				$j = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], 'j') : datetime_convert('UTC','UTC',$rr['start'],'j'));
				if(! x($links,$j)) 
					$links[$j] = $a->get_baseurl() . '/' . $a->cmd . '#link-' . $j;
			}
		}


		$o .= '<div id="new-event-link"><a href="' . $a->get_baseurl() . '/events/new' . '" >' . t('Create New Event') . '</a></div>';
		$o .= '<div id="event-calendar-wrapper">';

		$o .= '<a href="' . $a->get_baseurl() . '/events/' . $prevyear . '/' . $prevmonth . '" class="prevcal"><div id="event-calendar-prev" class="icon prev" title="' . t('Previous') . '"></div></a>';
		$o .= cal($y,$m,$links, ' eventcal');

		$o .= '<a href="' . $a->get_baseurl() . '/events/' . $nextyear . '/' . $nextmonth . '" class="nextcal"><div id="event-calendar-next" class="icon next" title="' . t('Next') . '"></div></a>';
		$o .= '</div>';
		$o .= '<div class="event-calendar-end"></div>';



		$last_date = '';
		$fmt = t('l, F j');

		if(count($r)) {
			$r = sort_by_date($r);
			foreach($r as $rr) {
				$j = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], 'j') : datetime_convert('UTC','UTC',$rr['start'],'j'));
				$d = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], $fmt) : datetime_convert('UTC','UTC',$rr['start'],$fmt));
				$d = day_translate($d);
				if($d !== $last_date)
					$o .= '<hr /><a name="link-' . $j . '" ><div class="event-list-date">' . $d . '</div></a>';
				$last_date = $d;
				if($rr['author-name']) {
					$o .= '<a href="' . $rr['author-link'] . '" ><img src="' . $rr['author-avatar'] . '" height="32" width="32" />' . $rr['author-name'] . '</a>';
				}
				$o .= format_event_html($rr);
				$o .= ((! $rr['cid']) ? '<a href="' . $a->get_baseurl() . '/events/event/' . $rr['id'] . '" title="' . t('Edit event') . '" class="edit-event-link icon pencil"></a>' : '');
				if($rr['plink'])
					$o .= '<a href="' . $rr['plink'] . '" title="' . t('link to source') . '" target="external-link" class="plink-event-link icon remote-link"></a></div>';

				$o .= '<div class="clear"></div>';

			}
		}
		return $o;
	}

	if($mode === 'edit' && $event_id) {
		$r = q("SELECT * FROM `event` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($event_id),
			intval(local_user())
		);
		if(count($r))
			$orig_event = $r[0];
	}

	if($mode === 'edit' || $mode === 'new') {

		$n_checked = ((x($orig_event) && $orig_event['nofinish']) ? ' checked="checked" ' : '');
		$a_checked = ((x($orig_event) && $orig_event['adjust']) ? ' checked="checked" ' : '');
		$d_orig = ((x($orig_event)) ? $orig_event['desc'] : '');
		$l_orig = ((x($orig_event)) ? $orig_event['location'] : '');
		$eid = ((x($orig_event)) ? $orig_event['id'] : 0);
		$cid = ((x($orig_event)) ? $orig_event['cid'] : 0);
		$uri = ((x($orig_event)) ? $orig_event['uri'] : '');


		if(! x($orig_event))
			$sh_checked = '';
		else
			$sh_checked = (($orig_event['allow_cid'] === '<' . local_user() . '>' && (! $orig_event['allow_gid']) && (! $orig_event['deny_cid']) && (! $orig_event['deny_gid'])) ? '' : ' checked="checked" ' );

		if($cid)
			$sh_checked .= ' disabled="disabled" ';

		$htpl = get_markup_template('event_head.tpl');
		$a->page['htmlhead'] .= replace_macros($htpl,array('$baseurl' => $a->get_baseurl()));

		$tpl = get_markup_template('event_form.tpl');

		$sdt = ((x($orig_event)) ? $orig_event['start'] : 'now');
		$fdt = ((x($orig_event)) ? $orig_event['finish'] : 'now');

		$tz = ((x($orig_event) && $orig_event['adjust']) ? date_default_timezone_get() : 'UTC');
 


		$syear = datetime_convert('UTC', $tz, $sdt, 'Y');
		$smonth = datetime_convert('UTC', $tz, $sdt, 'm');
		$sday = datetime_convert('UTC', $tz, $sdt, 'd');

		$shour = ((x($orig_event)) ? datetime_convert('UTC', $tz, $sdt, 'H') : 0);
		$sminute = ((x($orig_event)) ? datetime_convert('UTC', $tz, $sdt, 'i') : 0);

		$fyear = datetime_convert('UTC', $tz, $fdt, 'Y');
		$fmonth = datetime_convert('UTC', $tz, $fdt, 'm');
		$fday = datetime_convert('UTC', $tz, $fdt, 'd');

		$fhour = ((x($orig_event)) ? datetime_convert('UTC', $tz, $fdt, 'H') : 0);
		$fminute = ((x($orig_event)) ? datetime_convert('UTC', $tz, $fdt, 'i') : 0);

		$f = get_config('system','event_input_format');
		if(! $f)
			$f = 'ymd';

		$dateformat = datesel_format($f);
		$timeformat = t('hour:minute');

		require_once('include/acl_selectors.php');

		$o .= replace_macros($tpl,array(
			'$post' => $a->get_baseurl() . '/events',
			'$eid' => $eid, 
			'$cid' => $cid,
			'$uri' => $uri,
			'$e_text' => t('Event details'),
			'$e_desc' => sprintf( t('Format is %s %s. Starting date and Description are required.'),$dateformat,$timeformat),
			'$s_text' => t('Event Starts:') . ' <span class="required">*</span> ',
			'$s_dsel' => datesel($f,'start',$syear+5,$syear,false,$syear,$smonth,$sday),
			'$s_tsel' => timesel('start',$shour,$sminute),
			'$n_text' => t('Finish date/time is not known or not relevant'),
			'$n_checked' => $n_checked,
			'$f_text' => t('Event Finishes:'),
			'$f_dsel' => datesel($f,'finish',$fyear+5,$fyear,false,$fyear,$fmonth,$fday),
			'$f_tsel' => timesel('finish',$fhour,$fminute),
			'$a_text' => t('Adjust for viewer timezone'),
			'$a_checked' => $a_checked,
			'$d_text' => t('Description:') . ' <span class="required">*</span>',
			'$d_orig' => $d_orig,
			'$l_text' => t('Location:'),
			'$l_orig' => $l_orig,
			'$sh_text' => t('Share this event'),
			'$sh_checked' => $sh_checked,
			'$acl' => (($cid) ? '' : populate_acl(((x($orig_event)) ? $orig_event : $a->user),false)),
			'$submit' => t('Submit')

		));

		return $o;
	}
}
