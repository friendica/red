<?php


function format_event_html($ev) {

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$o = '<div class="vevent">';

	$o .= '<p class="description">' . $ev['desc'] .  '</p>';

	$o .= '<p>' . t('Starts: ') . '<abbr class="dtstart" title="'
		. datetime_convert('UTC','UTC',$ev['start'], ATOM_TIME)
		. '" >' 
		. datetime_convert('UTC', date_default_timezone_get(), 
			$ev['start'] /*, format */ ) 
		. '</abbr></p>';

	$o .= '<p>' . t('Ends: ') . '<abbr class="dtend" title="'
		. datetime_convert('UTC','UTC',$ev['finish'], ATOM_TIME)
		. '" >' 
		. datetime_convert('UTC', date_default_timezone_get(), 
			$ev['finish'] /*, format */ ) 
		. '</abbr></p>';

	$o .= '<p> ' . t('Location:') . '<span class="location">' 
		. $ev['location'] 
		. '</span></p>';

	$o .= '</div>';

return $o;
}



