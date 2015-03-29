<?php
/**
 * @file include/datetime.php
 * @brief Some functions for date and time related tasks.
 */


/**
 * @brief Two-level sort for timezones.
 *
 * @param string $a
 * @param string $b
 * @return number
 */
function timezone_cmp($a, $b) {
	if(strstr($a,'/') && strstr($b,'/')) {
		if ( t($a) == t($b)) return 0;
		return ( t($a) < t($b)) ? -1 : 1;
	}
	if (strstr($a,'/')) return -1;
	if (strstr($b,'/')) return  1;
	if ( t($a) == t($b)) return 0;

	return ( t($a) < t($b)) ? -1 : 1;
}

/**
 * @brief Return timezones grouped (primarily) by continent.
 *
 * @return array
 */
function get_timezones( ){
	$timezone_identifiers = DateTimeZone::listIdentifiers();

	usort($timezone_identifiers, 'timezone_cmp');
	$continent = '';
	$continents = array();
	foreach ($timezone_identifiers as $value) {
		$ex = explode("/", $value);
		if (count($ex) > 1) {
			$continent = t($ex[0]);
			if (count($ex) > 2)
				$city = substr($value, strpos($value, '/')+1);
			else
				$city = $ex[1];
		} else {
			$city = $ex[0];
			$continent = t('Miscellaneous');
		}
		$city = str_replace('_', ' ',  t($city));

		if (!x($continents, $ex[0])) $continents[$ex[0]] = array();
		$continents[$continent][$value] = $city;
	}

	return $continents;
}

/**
 * @brief General purpose date parse/convert function.
 *
 * @param string $from source timezone
 * @param string $to dest timezone
 * @param string $s some parseable date/time string
 * @param string $fmt output format recognised from php's DateTime class
 *   http://www.php.net/manual/en/datetime.format.php
 * @return string
 */
function datetime_convert($from = 'UTC', $to = 'UTC', $s = 'now', $fmt = "Y-m-d H:i:s") {

	// Defaults to UTC if nothing is set, but throws an exception if set to empty string.
	// Provide some sane defaults regardless.

	if($from === '')
		$from = 'UTC';
	if($to === '')
		$to = 'UTC';
	if( ($s === '') || (! is_string($s)) )
		$s = 'now';

	// Slight hackish adjustment so that 'zero' datetime actually returns what is intended
	// otherwise we end up with -0001-11-30 ...
	// add 32 days so that we at least get year 00, and then hack around the fact that 
	// months and days always start with 1. 

	if(substr($s,0,10) == '0000-00-00') {
		$d = new DateTime($s . ' + 32 days', new DateTimeZone('UTC'));
		return str_replace('1', '0', $d->format($fmt));
	}

	try {
		$from_obj = new DateTimeZone($from);
	} catch(Exception $e) {
		$from_obj = new DateTimeZone('UTC');
	}

	try {
		$d = new DateTime($s, $from_obj);
	} catch(Exception $e) {
		logger('datetime_convert: exception: ' . $e->getMessage());
		$d = new DateTime('now', $from_obj);
	}

	try {
		$to_obj = new DateTimeZone($to);
	} catch(Exception $e) {
		$to_obj = new DateTimeZone('UTC');
	}

	$d->setTimeZone($to_obj);

	return($d->format($fmt));
}

/**
 * @brief Wrapper for date selector, tailored for use in birthday fields.
 *
 * @param string $dob Date of Birth
 * @return string
 */
function dob($dob) {
	list($year, $month, $day) = sscanf($dob, '%4d-%2d-%2d');
	$f = get_config('system', 'birthday_input_format');
	if (! $f)
		$f = 'ymd';

	if ($dob === '0000-00-00')
		$value = '';
	else
		$value = (($year) ? datetime_convert('UTC','UTC',$dob,'Y-m-d') : datetime_convert('UTC','UTC',$dob,'m-d'));

	$o = '<input type="text" name="dob" value="' . $value . '" placeholder="' . t('YYYY-MM-DD or MM-DD') . '" />';

//	if ($dob && $dob != '0000-00-00')
//		$o = datesel($f,mktime(0,0,0,0,0,1900),mktime(),mktime(0,0,0,$month,$day,$year),'dob');
//	else
//		$o = datesel($f,mktime(0,0,0,0,0,1900),mktime(),false,'dob');

	return $o;
}

/**
 * returns a date selector
 * @param $format
 *  format string, e.g. 'ymd' or 'mdy'. Not currently supported
 * @param $min
 *  unix timestamp of minimum date
 * @param $max
 *  unix timestap of maximum date
 * @param $default
 *  unix timestamp of default date
 * @param $id
 *  id and name of datetimepicker (defaults to "datetimepicker")
 */
function datesel($format, $min, $max, $default, $id = 'datepicker') {
	return datetimesel($format, $min, $max, $default, $id,true, false, '', '');
}

/**
 * returns a time selector
 * @param $format
 *  format string, e.g. 'ymd' or 'mdy'. Not currently supported
 * @param $h
 *  already selected hour
 * @param $m
 *  already selected minute
 * @param $id
 *  id and name of datetimepicker (defaults to "timepicker")
 */
function timesel($format, $h, $m, $id='timepicker') {
	return datetimesel($format,new DateTime(),new DateTime(),new DateTime("$h:$m"),$id,false,true);
}

/**
 * @brief Returns a datetime selector.
 *
 * @param $format
 *  format string, e.g. 'ymd' or 'mdy'. Not currently supported
 * @param $min
 *  unix timestamp of minimum date
 * @param $max
 *  unix timestap of maximum date
 * @param $default
 *  unix timestamp of default date
 * @param string $id
 *  id and name of datetimepicker (defaults to "datetimepicker")
 * @param boolean $pickdate
 *  true to show date picker (default)
 * @param boolean $picktime
 *  true to show time picker (default)
 * @param $minfrom
 *  set minimum date from picker with id $minfrom (none by default)
 * @param $maxfrom
 *  set maximum date from picker with id $maxfrom (none by default)
 * @param boolean $required default false
 * @return string Parsed HTML output.
 * 
 * @todo Once browser support is better this could probably be replaced with
 * native HTML5 date picker.
 */
function datetimesel($format, $min, $max, $default, $id = 'datetimepicker', $pickdate = true, $picktime = true, $minfrom = '', $maxfrom = '', $required = false) {

	$o = '';
	$dateformat = '';

	if($pickdate) $dateformat .= 'Y-m-d';
	if($pickdate && $picktime) $dateformat .= ' ';
	if($picktime) $dateformat .= 'H:i';

	$minjs = $min ? ",minDate: new Date({$min->getTimestamp()}*1000), yearStart: " . $min->format('Y') : '';
	$maxjs = $max ? ",maxDate: new Date({$max->getTimestamp()}*1000), yearEnd: " . $max->format('Y') : '';

	$input_text = $default ? 'value="' . date($dateformat, $default->getTimestamp()) . '"' : '';
	$defaultdatejs = $default ? ",defaultDate: new Date({$default->getTimestamp()}*1000)" : '';

	$pickers = '';
	if(!$pickdate) $pickers .= ',datepicker: false';
	if(!$picktime) $pickers .= ',timepicker: false';

	$extra_js = '';
	if($minfrom != '') 
		$extra_js .= "\$('#$minfrom').data('xdsoft_datetimepicker').setOptions({onChangeDateTime: function (currentDateTime) { \$('#$id').data('xdsoft_datetimepicker').setOptions({minDate: currentDateTime})}})";

	if($maxfrom != '') 
		$extra_js .= "\$('#$maxfrom').data('xdsoft_datetimepicker').setOptions({onChangeDateTime: function (currentDateTime) { \$('#$id').data('xdsoft_datetimepicker').setOptions({maxDate: currentDateTime})}})";

	$readable_format = $dateformat;
	$readable_format = str_replace('Y','yyyy',$readable_format);
	$readable_format = str_replace('m','mm',$readable_format);
	$readable_format = str_replace('d','dd',$readable_format);
	$readable_format = str_replace('H','HH',$readable_format);
	$readable_format = str_replace('i','MM',$readable_format);

	$o .= "<div class='date'><input type='text' placeholder='$readable_format' name='$id' id='$id' $input_text />";
	$o .= (($required) ? '<span class="required" title="' . t('Required') . '" >*</span>' : '');
	$o .= '</div>';
	$o .= "<script type='text/javascript'>\$(function () {var picker = \$('#$id').datetimepicker({step:5,format:'$dateformat' $minjs $maxjs $pickers $defaultdatejs}); $extra_js})</script>";

	return $o;
}

/**
 * @brief Returns a relative date string.
 *
 * Implements "3 seconds ago" etc.
 * Based on $posted_date, (UTC).
 * Results relative to current timezone.
 * Limited to range of timestamps.
 *
 * @param string $posted_date
 * @param string $format (optional) parsed with sprintf()
 *    <tt>%1$d %2$s ago</tt>, e.g. 22 hours ago, 1 minute ago
 * @return string with relative date
 */
function relative_date($posted_date, $format = null) {

	$localtime = datetime_convert('UTC', date_default_timezone_get(), $posted_date); 

	$abs = strtotime($localtime);

	if (is_null($posted_date) || $posted_date === NULL_DATE || $abs === false) {
		return t('never');
	}

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
			if (! $format)
				$format = t('%1$d %2$s ago', 'e.g. 22 hours ago, 1 minute ago');

			return sprintf($format, $r, (($r == 1) ? $str[0] : $str[1]));
		}
	}
}

/**
 * @brief Returns timezone correct age in years.
 *
 * Returns the age in years, given a date of birth, the timezone of the person
 * whose date of birth is provided, and the timezone of the person viewing the
 * result.
 *
 * Why? Bear with me. Let's say I live in Mittagong, Australia, and my birthday
 * is on New Year's. You live in San Bruno, California.
 * When exactly are you going to see my age increase?
 *
 * A: 5:00 AM Dec 31 San Bruno time. That's precisely when I start celebrating
 * and become a year older. If you wish me happy birthday on January 1
 * (San Bruno time), you'll be a day late.
 *
 * @param string $dob Date of Birth
 * @param string $owner_tz (optional) timezone of the person of interest
 * @param string $viewer_tz (optional) timezone of the person viewing
 * @return number
 */
function age($dob, $owner_tz = '', $viewer_tz = '') {
	if (! intval($dob))
		return 0;
	if (! $owner_tz)
		$owner_tz = date_default_timezone_get();
	if (! $viewer_tz)
		$viewer_tz = date_default_timezone_get();

	$birthdate = datetime_convert('UTC', $owner_tz, $dob . ' 00:00:00+00:00','Y-m-d');
	list($year,$month,$day) = explode("-", $birthdate);
	$year_diff  = datetime_convert('UTC', $viewer_tz, 'now', 'Y') - $year;
	$curr_month = datetime_convert('UTC', $viewer_tz, 'now', 'm');
	$curr_day   = datetime_convert('UTC', $viewer_tz, 'now', 'd');

	if (($curr_month < $month) || (($curr_month == $month) && ($curr_day < $day)))
		$year_diff--;

	return $year_diff;
}

/**
 * @brief Get days of a month in a given year.
 *
 * Returns number of days in the month of the given year.
 * $m = 1 is 'January' to match human usage.
 *
 * @param int $y year
 * @param int $m month (1=January, 12=December)
 * @return int number of days in the given month
 */
function get_dim($y, $m) {
	$dim = array( 0,
			31, 28, 31, 30, 31, 30,
			31, 31, 30, 31, 30, 31
	);

	if ($m != 2)
		return $dim[$m];

	if (((($y % 4) == 0) && (($y % 100) != 0)) || (($y % 400) == 0))
		return 29;

	return $dim[2];
}

/**
 * @brief Returns the first day in month for a given month, year.
 *
 * Months start at 1.
 *
 * @param int $y Year
 * @param int $m Month (1=January, 12=December)
 * @return day 0 = Sunday through 6 = Saturday
 */
function get_first_dim($y, $m) {
	$d = sprintf('%04d-%02d-01 00:00', intval($y), intval($m));

	return datetime_convert('UTC', 'UTC', $d, 'w');
}

/**
 * @brief Output a calendar for the given month, year.
 *
 * If $links are provided (array), e.g. $links[12] => 'http://mylink' ,
 * date 12 will be linked appropriately. Today's date is also noted by
 * altering td class.
 * Months count from 1.
 *
 * @param number $y Year
 * @param number $m Month
 * @param string $links (default false)
 * @param string $class
 * @return string
 *
 * @todo provide (prev,next) links, define class variations for different size calendars
 */
function cal($y = 0, $m = 0, $links = false, $class='') {

	// month table - start at 1 to match human usage.

	$mtab = array(' ',
		'January','February','March',
		'April','May','June',
		'July','August','September',
		'October','November','December'
	);

	$thisyear = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
	$thismonth = datetime_convert('UTC',date_default_timezone_get(),'now','m');
	if (! $y)
		$y = $thisyear;
	if (! $m)
		$m = intval($thismonth);

	$dn = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	$f = get_first_dim($y, $m);
	$l = get_dim($y, $m);
	$d = 1;
	$dow = 0;
	$started = false;

	if (($y == $thisyear) && ($m == $thismonth))
		$tddate = intval(datetime_convert('UTC',date_default_timezone_get(),'now','j'));

	$str_month = day_translate($mtab[$m]);
	$o = '<table class="calendar' . $class . '">';
	$o .= "<caption>$str_month $y</caption><tr>";
	for ($a = 0; $a < 7; $a ++)
		$o .= '<th>' . mb_substr(day_translate($dn[$a]),0,3,'UTF-8') . '</th>';

	$o .= '</tr><tr>';

	while ($d <= $l) {
		if (($dow == $f) && (! $started))
			$started = true;

		$today = (((isset($tddate)) && ($tddate == $d)) ? "class=\"today\" " : '');
		$o .= "<td $today>";
		$day = str_replace(' ','&nbsp;',sprintf('%2.2d', $d));
		if ($started) {
			if (is_array($links) && isset($links[$d]))
				$o .=  "<a href=\"{$links[$d]}\">$day</a>";
			else
				$o .= $day;

			$d ++;
		} else {
			$o .= '&nbsp;';
		}

		$o .= '</td>';
		$dow ++;
		if (($dow == 7) && ($d <= $l)) {
			$dow = 0;
			$o .= '</tr><tr>';
		}
	}
	if ($dow)
		for ($a = $dow; $a < 7; $a ++)
			$o .= '<td>&nbsp;</td>';

	$o .= '</tr></table>'."\r\n";

	return $o;
}

/**
 * @brief Return the next birthday, converted from the owner's timezone to UTC.
 * 
 * This makes it globally portable.
 * If the provided birthday lacks a month and or day, return an empty string.
 * A missing year is acceptable.
 *
 * @param string $dob Date of Birth
 * @param string $tz Timezone
 * @param string $format
 * @return string
 */
function z_birthday($dob, $tz, $format="Y-m-d H:i:s") {

	if (! strlen($tz))
		$tz = 'UTC';

	$birthday = '';
	$tmp_dob = substr($dob,5);
	$tmp_d = substr($dob,8);
	if (intval($tmp_dob) && intval($tmp_d)) {
		$y = datetime_convert($tz,$tz,'now','Y');
		$bd = $y . '-' . $tmp_dob . ' 00:00';
		$t_dob = strtotime($bd);
		$now = strtotime(datetime_convert($tz,$tz,'now'));
		if ($t_dob < $now)
			$bd = $y + 1 . '-' . $tmp_dob . ' 00:00';

		$birthday = datetime_convert($tz,'UTC',$bd,$format);
	}

	return $birthday;
}

/**
 * @brief Create a birthday event for any connections with a birthday in the next 1-2 weeks.
 *
 * Update the year so that we don't create another event until next year.
 */
function update_birthdays() {

	require_once('include/event.php');
	require_once('include/permissions.php');

	$r = q("SELECT * FROM abook left join xchan on abook_xchan = xchan_hash 
		WHERE abook_dob > %s + interval %s and abook_dob < %s + interval %s",
		db_utcnow(), db_quoteinterval('7 day'),
		db_utcnow(), db_quoteinterval('14 day')
	);
	if ($r) {
		foreach ($r as $rr) {
			if (! perm_is_allowed($rr['abook_channel'], $rr['xchan_hash'], 'send_stream'))
				continue;

			$ev = array();
			$ev['uid'] = $rr['abook_channel'];
			$ev['account'] = $rr['abook_account'];
			$ev['event_xchan'] = $rr['xchan_hash'];
			$ev['start'] = datetime_convert('UTC', 'UTC', $rr['abook_dob']);
			$ev['finish'] = datetime_convert('UTC', 'UTC', $rr['abook_dob'] . ' + 1 day ');
			$ev['adjust'] = 1;
			$ev['summary'] = sprintf( t('%1$s\'s birthday'), $rr['xchan_name']);
			$ev['description'] = sprintf( t('Happy Birthday %1$s'), 
				'[zrl=' . $rr['xchan_url'] . ']' . $rr['xchan_name'] . '[/zrl]') ;
			$ev['type'] = 'birthday';

			$z = event_store_event($ev);
			if ($z) {
				$item_id = event_store_item($ev, $z);
				q("update abook set abook_dob = '%s' where abook_id = %d",
					dbesc(intval($rr['abook_dob']) + 1 . substr($rr['abook_dob'], 4)),
					intval($rr['abook_id'])
				);
			}
		}
	}
}
