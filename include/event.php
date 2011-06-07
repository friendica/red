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

	$o .= '<p>' . t('Ends: ') . '<abbr class="dtend" title="'
		. datetime_convert('UTC','UTC',$ev['finish'], ATOM_TIME)
		. '" >' 
		. (($ev['adjust']) ? datetime_convert('UTC', date_default_timezone_get(), 
			$ev['finish'] /*, format */ )
			:  datetime_convert('UTC', 'UTC', 
			$ev['finish'] /*, format */ ))
		. '</abbr></p>';

	$o .= '<p> ' . t('Location:') . '<span class="location">' 
		. bbcode($ev['location']) 
		. '</span></p>';

	$o .= '</div>';

return $o;
}



