<?php


function format_event_html($ev) {

	require_once('include/bbcode.php');

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$o = '<div class="vevent">';

	$o .= '<p class="description">' . bbcode($ev['desc']) .  '</p>';

	$o .= '<p>' . t('Starts: ') . '<abbr class="dtstart" title="'
		. datetime_convert('UTC','UTC',$ev['start'], ATOM_TIME)
		. '" >' 
		. (($ev['adjust']) ? datetime_convert('UTC', date_default_timezone_get(), 
			$ev['start'] /*, format */ )
			:  datetime_convert('UTC', 'UTC', 
			$ev['start'] /*, format */ ))
		. '</abbr></p>';

	if(! $ev['nofinish'])
		$o .= '<p>' . t('Ends: ') . '<abbr class="dtend" title="'
			. datetime_convert('UTC','UTC',$ev['finish'], ATOM_TIME)
			. '" >' 
			. (($ev['adjust']) ? datetime_convert('UTC', date_default_timezone_get(), 
				$ev['finish'] /*, format */ )
				:  datetime_convert('UTC', 'UTC', 
				$ev['finish'] /*, format */ ))
			. '</abbr></p>';

	if(strlen($ev['location']))
		$o .= '<p> ' . t('Location:') . '<span class="location">' 
			. bbcode($ev['location']) 
			. '</span></p>';

	$o .= '</div>';

	return $o;
}



function sort_by_date($a) {

	usort($a,'ev_compare');
	return $a;
}


function ev_compare($a,$b) {

	$date_a = (($a['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$a['start']) : $a['start']);
	$date_b = (($b['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$b['start']) : $b['start']);
	
	return strcmp($date_a,$date_b);
}