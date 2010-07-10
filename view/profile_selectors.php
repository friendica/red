<?php


function gender_selector($current="",$suffix="") {
	$select = array('','Male', 'Female', 'Transsexual', 'Hermaphrodite', 'Neuter', 'Other', 'Undecided');

	$o .= "<select name=\"gender$suffix\" id=\"gender-select$suffix\" size=\"1\" >";
	foreach($select as $selection) {
		$selected = (($selection == $current) ? ' selected="selected" ' : '');
		$o .= "<option value=\"$selection\" $selected >$selection</option>";
	}
	$o .= '</select>';
	return $o;
}	

function sexpref_selector($current="",$suffix="") {
	$select = array('','Males', 'Females', 'Bisexual', 'Autosexual', 'Abstinent', 'Virgin', 'Nonsexual');

	$o .= "<select name=\"sexual$suffix\" id=\"sexual-select$suffix\" size=\"1\" >";
	foreach($select as $selection) {
		$selected = (($selection == $current) ? ' selected="selected" ' : '');
		$o .= "<option value=\"$selection\" $selected >$selection</option>";
	}
	$o .= '</select>';
	return $o;
}	


function marital_selector($current="",$suffix="") {
	$select = array('','Single', 'Lonely', 'Available', 'Unavailable', 'Dating', 'Unfaithful', 'Sex Addict', 'Friends', 'Friends/Benefits', 'Casual', 'Engaged', 'Married', 'Partners', 'Cohabiting', 'Happy', 'Not Looking', 'Swinger', 'Betrayed', 'Separated', 'Unstable', 'Divorced', 'Widowed', 'Uncertain', 'Complicated', 'Don\'t care', 'Ask me' );

	$o .= "<select name=\"marital[]\" id=\"marital-select\" multiple=\"multiple\" size=\"2\" >";
	foreach($select as $selection) {
		$selected = (($selection == $current) ? ' selected="selected" ' : '');
		$o .= "<option value=\"$selection\" $selected >$selection</option>";
	}
	$o .= '</select>';
	return $o;
}	


//function birthday_selector($current = '') {
//	if($current && (strlen($current)
//}