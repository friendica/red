<?php

require_once('include/datetime.php');


function localtime_post(&$a) {

	$t = $_REQUEST['time'];
	if(! $t)
		$t = 'now';

	$bd_format = t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8 AM

	if($_POST['timezone'])
		$a->data['mod-localtime'] = datetime_convert('UTC',$_POST['timezone'],$t,$bd_format);

}

function localtime_content(&$a) {
	$t = $_REQUEST['time'];
	if(! $t)
		$t = 'now';

	$o .= '<h3>' . t('Time Conversion') . '</h3>';

	$o .= '<p>' . t('Friendika provides this service for sharing events with other networks and friends in unknown timezones.') . '</p>';


	if(x($a->data,'mod-localtime'))
		$o .= '<p>' . sprintf( t('Converted localtime: %s'),$a->data['mod-localtime']) . '</p>';

	$o .= '<p>' . sprintf( t('UTC time: %s'), $t) . '</p>';

	$o .= '<form action ="' . $a->get_baseurl() . '/localtime?f=&time=' . $t . '" method="post" >';

	$o .= '<p>' . t('Please select your timezone:') . '</p>'; 

	$o .= select_timezone();

	$o .= '<input type="submit" name="submit" value="' . t('Submit') . '" /></form>';

	return $o;

}