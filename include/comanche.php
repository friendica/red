<?php /** @file */

require_once('include/security.php');

// When editing a webpage - a dropdown is needed to select a page layout
// On submit, the pdl_select value (which is the mid of an item with item_restrict = ITEM_PDL) is stored in 
// the webpage's resource_id, with resource_type 'pdl'.

// Then when displaying a webpage, we can see if it has a pdl attached. If not we'll 
// use the default site/page layout.

// If it has a pdl we'll load it as we know the mid and pass the body through comanche_parser() which will generate the 
// page layout from the given description


function pdl_selector($uid,$current="") {

	$o = '';

	// You can use anybody's Comanche layouts on this site that haven't been protected in some way

	$sql_extra = item_permissions_sql($uid);

	// By default order by title (therefore at this time pdl's need a unique title across this system), 
	// though future work may allow categorisation
	// based on taxonomy terms

	$r = q("select title, mid from item where (item_restrict & %d) $sql_extra order by title",
		intval(ITEM_PDL)
	);

	$arr = array('channel_id' => $uid, 'current' => $current, 'entries' => $r);
	call_hooks('pdl_selector',$arr);

	$entries = $arr['entries'];
	$current = $arr['current'];		
 
	$o .= "<select name=\"pdl_select\" id=\"pdl_select\" size=\"1\" >";
	$entries[] = array('title' => t('Default'), 'mid' => '');
	foreach($entries as $selection) {
		$selected = (($selection == $current) ? ' selected="selected" ' : '');
		$o .= "<option value=\"{$selection['mid']}\" $selected >{$selection['title']}</option>";
	}

	$o .= '</select>';
	return $o;
}	

