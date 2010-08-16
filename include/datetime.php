<?php

if(! function_exists('timezone_cmp')) {
function timezone_cmp($a, $b) {
	if(strstr($a,'/') && strstr($b,'/')) {
		if ($a == $b) return 0;
		return ($a < $b) ? -1 : 1;
	}
	if(strstr($a,'/')) return -1;
	if(strstr($b,'/')) return  1;
	if ( t($a) == t($b)) return 0;
	return ( t($a) < t($b)) ? -1 : 1;
}}


if(! function_exists('select_timezone')) {
function select_timezone($current = 'America/Los_Angeles') {

	$timezone_identifiers = DateTimeZone::listIdentifiers();
	
	$o ='<select id="timezone_select" name="timezone">';

	usort($timezone_identifiers, 'timezone_cmp');
	$continent = '';
	foreach($timezone_identifiers as $value) {
		$ex = explode("/", $value);
		if(count($ex) > 1) {
			if($ex[0] != $continent) {
				if($continent != '')
					$o .= '</optgroup>';
				$continent = $ex[0];
				$o .= '<optgroup label="' . t($continent) . '">';
			}
			if(count($ex) > 2)
				$city = substr($value,strpos($value,'/')+1);
			else
				$city = $ex[1];
		}
		else {
			$city = $ex[0];
			if($continent != 'Miscellaneous') {
				$o .= '</optgroup>';
				$continent = 'Miscellaneous';
				$o .= '<optgroup label="' . t($continent) . '">';	
			}
		}
		$city = str_replace('_', ' ',  t($city));
		$selected = (($value == $current) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$value\" $selected >$city</option>";
	}    
	$o .= '</optgroup></select>';
	return $o;
}}


if(! function_exists('datetime_convert')) {
function datetime_convert($from = 'UTC', $to = 'UTC', $s = 'now', $fmt = "Y-m-d H:i:s") {
  $d = new DateTime($s, new DateTimeZone($from));
  $d->setTimeZone(new DateTimeZone($to));
  return($d->format($fmt));
}}

function dob($dob) {
	list($year,$month,$day) = sscanf($dob,'%4d-%2d-%2d');
	$y = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
	$o = datesel('',1920,$y,true,$year,$month,$day);
	return $o;
}

if(! function_exists('datesel')) {
function datesel($pre,$ymin,$ymax,$allow_blank,$y,$m,$d) {

	$o = '';
	$o .= "<select name=\"{$pre}year\" class=\"{$pre}year\" size=\"1\">";
	if($allow_blank) {
		$sel = (($y == '0000') ? " selected=\"selected\" " : "");
		$o .= "<option value=\"0000\" $sel ></option>";
	}

	for($x = $ymax; $x >= $ymin; $x --) {
		$sel = (($x == $y) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$x\" $sel>$x</option>";
	}
  
	$o .= "</select> <select name=\"{$pre}month\" class=\"{$pre}month\" size=\"1\">";
	for($x = 0; $x <= 12; $x ++) {
		$sel = (($x == $m) ? " selected=\"selected\" " : "");
		$y = (($x) ? $x : '');
		$o .= "<option value=\"$x\" $sel>$y</option>";
	}

	$o .= "</select> <select name=\"{$pre}day\" class=\"{$pre}day\" size=\"1\">";
	for($x = 0; $x <= 31; $x ++) {
		$sel = (($x == $d) ? " selected=\"selected\" " : "");
		$y = (($x) ? $x : '');
		$o .= "<option value=\"$x\" $sel>$y</option>";
	}

	$o .= "</select>";
	return $o;
}}


if(! function_exists('relative_date')) {
function relative_date($posted_date) {

	$localtime = datetime_convert('UTC',date_default_timezone_get(),$posted_date); 

	$abs = strtotime($localtime);
	$etime = time() - $abs;
    
	if ($etime < 1) {
		return t('less than a second ago');
	}
    
	$a = array( 12 * 30 * 24 * 60 * 60  =>  array( t('year'),   t('years')),
		    30 * 24 * 60 * 60       =>  array( t('month'),  t('months')),
		    7  * 24 * 60 * 60       =>  array( t('week'),   t('weeks')),
		    24 * 60 * 60            =>  array( t('day'),    t('days')),
		    60 * 60                 =>  array( t('hour'),   t('hours')),
		    60                      =>  array( t('minute'), t('minutes')),
		    1                       =>  array( t('second'), t('seconds'))
	);
    
	foreach ($a as $secs => $str) {
	$d = $etime / $secs;
	if ($d >= 1) {
		$r = round($d);
		return $r . ' ' . (($r == 1) ? $str[0] : $str[1]) . t(' ago');
        }
    }
}}


function age($dob,$owner_tz = '',$viewer_tz = '') {
	if(! intval($dob))
		return 0;
	if(! $owner_tz)
		$owner_tz = date_default_timezone_get();
	if(! $viewer_tz)
		$viewer_tz = date_default_timezone_get();

	$birthdate = datetime_convert('UTC',$owner_tz,$dob . ' 00:00:00+00:00','Y-m-d');
	list($year,$month,$day) = explode("-",$birthdate);
	$year_diff  = datetime_convert('UTC',$viewer_tz,'now','Y') - $year;
	$curr_month = datetime_convert('UTC',$viewer_tz,'now','m');
	$curr_day   = datetime_convert('UTC',$viewer_tz,'now','d');

	if(($curr_month < $month) || (($curr_month == $month) && ($curr_day < $day)))
		$year_diff--;
	return $year_diff;
}
