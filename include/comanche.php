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



function comanche_parser(&$a,$s) {


	$cnt = preg_match("/\[layout\](.*?)\[\/layout\]/ism", $matches, $s);
	if($cnt)
		$a->page['template'] = trim($matches[1]);

	$cnt = preg_match("/\[theme\](.*?)\[\/theme\]/ism", $matches, $s);
	if($cnt)
		$a->layout['theme'] = trim($matches[1]);

	$cnt = preg_match_all("/\[region=(.*?)\](.*?)\[\/region\]/ism", $matches, $s, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$a->layout['region_' . $mtch[1]] = comanche_region($a,$mtch[2]);
		}
	}

}


function comanche_menu($name) {
	$a = get_app();
	$m = menu_fetch($name,$a->profile['profile_uid'],get_observer_hash());
	return render_menu($m);
}

function comanche_widget($name) {
	$a = get_app();
	// placeholder for now
	$m = menu_fetch($name,$a->profile['profile_uid'],get_observer_hash());
	return render_menu($m);
}


function comanche_region(&$a,$s) {


	$cnt = preg_match_all("/\[menu\](.*?)\[\/menu\]/ism", $matches, $s, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_menu(trim($mtch[1])),$s);
		}
	}

	// need to modify this to accept parameters

	$cnt = preg_match_all("/\[widget\](.*?)\[\/widget\]/ism", $matches, $s, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_widget(trim($mtch[1])),$s);
		}
	}

	return $s;
}