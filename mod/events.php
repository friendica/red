<?php

require_once('include/datetime.php');
require_once('include/event.php');

function events_post(&$a) {

	if(! local_user())
		return;

	$event_id = ((x($_POST,'event_id')) ? intval($_POST['event_id']) : 0);
	$uid      = local_user();
	$start    = strip_tags($_POST['start']);
	$finish   = strip_tags($_POST['finish']);
	$desc     = escape_tags($_POST['desc']);
	$location = escape_tags($_POST['location']);
	$type     = 'event';
	$adjust   = intval($_POST['adjust']);

	$str_group_allow   = perms2str($_POST['group_allow']);
	$str_contact_allow = perms2str($_POST['contact_allow']);
	$str_group_deny    = perms2str($_POST['group_deny']);
	$str_contact_deny  = perms2str($_POST['contact_deny']);


	if($event_id) {
		$r = q("UPDATE `event` SET
			`edited` = '%s',
			`start` = '%s',
			`finish` = '%s',
			`desc` = '%s',
			`location` = '%s',
			`type` = '%s',
			`adjust` = %d,
			`allow_cid` = '%s',
			`allow_gid` = '%s',
			`deny_cid` = '%s',
			`deny_gid` = '%s'
			WHERE `id` = %d AND `uid` = %d LIMIT 1",

			dbesc(datetime_convert()),
			dbesc($start),
			dbesc($finish),
			dbesc($desc),
			dbesc($location),
			dbesc($type),
			intval($adjust),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny),
			intval($event_id),
			intval($local_user())
		);

	}
	else {

		$uri = item_new_uri($a->get_hostname(),local_user());

		$r = q("INSERT INTO `event` ( `uid`,`uri`,`created`,`edited`,`start`,`finish`,`desc`,`location`,`type`,
			`adjust`,`allow_cid`,`allow_gid`,`deny_cid`,`deny_gid`)
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s' ) ",
			intval(local_user()),

			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($start),
			dbesc($finish),
			dbesc($desc),
			dbesc($location),
			dbesc($type),
			intval($adjust),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny)

		);
	}

}



function events_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

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

	
		$o .= cal($y,$m,false);

		return $o;
	}

	if($mode === 'edit' || $mode === 'new') {
		$tpl = get_markup_template('event_form.tpl');

		$year = datetime_convert('UTC', date_default_timezone_get(), 'now', 'Y');


		$o .= replace_macros($tpl,array(
			'$post' => $a->get_baseurl() . '/events',
			'$e_text' => t('Event details'),
			'$s_text' => t('Starting date/time:'),
			'$s_dsel' => datesel('start',$year+5,$year,false,$year,0,0),
			'$s_tsel' => timesel('start',0,0),
			'$f_text' => t('Finish date/time:'),
			'$f_dsel' => datesel('start',$year+5,$year,false,$year,0,0),
			'$f_tsel' => timesel('start',0,0),
			'$d_text' => t('Description:'),
			'$d_orig' => '',
			'$l_text' => t('Location:'),
			'$l_orig' => '',
			'$submit' => t('Submit')

		));

		return $o;
	}
}