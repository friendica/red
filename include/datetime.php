<?php

if(! function_exists('timezone_cmp')) {
function timezone_cmp($a, $b) {
	if(strstr($a,'/') && strstr($b,'/')) {
		if ($a == $b) return 0;
		return ($a < $b) ? -1 : 1;
	}
	if(strstr($a,'/')) return -1;
	if(strstr($b,'/')) return  1;
	if ($a == $b) return 0;
	return ($a < $b) ? -1 : 1;
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
				$o .= "<optgroup label=\"$continent\">";
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
				$o .= "<optgroup label=\"$continent\">";	
			}
		}
		$city = str_replace('_', ' ', $city);
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


// TODO rewrite this buggy sucker
function relative_date($posted_date) {

	$localtime = datetime_convert('UTC',date_default_timezone_get(),$posted_date); 
    
	$in_seconds = strtotime($localtime);

    	$diff = time() - $in_seconds;
    
	$months = floor($diff/2592000);
    	$diff -= $months*2419200;
    	$weeks = floor($diff/604800);
    	$diff -= $weeks*604800;
    	$days = floor($diff/86400);
    	$diff -= $days*86400;
    	$hours = floor($diff/3600);
    	$diff -= $hours*3600;
    	$minutes = floor($diff/60);
    	$diff -= $minutes*60;
    	$seconds = $diff;

	if($months > 2)
		return(datetime_convert('UTC',date_default_timezone_get(),$posted_date,'\o\n Y-m-d \a\t H:i:s'));
    if ($months>0) {
        // over a month old,
        return 'over a month ago';
    } else {
        if ($weeks>0) {
            // weeks and days
            $relative_date .= ($relative_date?', ':'').$weeks.' week'.($weeks!=1 ?'s':'');

        } elseif ($days>0) {
            // days and hours
            $relative_date .= ($relative_date?', ':'').$days.' day'.($days!=1?'s':'');

        } elseif ($hours>0) {
            // hours and minutes
            $relative_date .= ($relative_date?', ':'').$hours.' hour'.($hours!=1?'s':'');

        } elseif ($minutes>0) {
            // minutes only
            $relative_date .= ($relative_date?', ':'').$minutes.' minute'.($minutes!=1?'s':'');
        } else {
            // seconds only
            $relative_date .= ($relative_date?', ':'').$seconds.' second'.($seconds!=1?'s':'');
        }
    }
    // show relative date and add proper verbiage
    return $relative_date.' ago';
}

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
