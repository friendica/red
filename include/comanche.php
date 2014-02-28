<?php /** @file */

require_once('include/security.php');
require_once('include/menu.php');
require_once('include/widgets.php');

// When editing a webpage - a dropdown is needed to select a page layout
// On submit, the pdl_select value (which is the mid of an item with item_restrict = ITEM_PDL) is stored in 
// the webpage's resource_id, with resource_type 'pdl'.

// Then when displaying a webpage, we can see if it has a pdl attached. If not we'll 
// use the default site/page layout.

// If it has a pdl we'll load it as we know the mid and pass the body through comanche_parser() which will generate the 
// page layout from the given description


function pdl_selector($uid,$current="") {

	$o = '';

	$sql_extra = item_permissions_sql($uid);

	$r = q("select item_id.*, mid from item_id left join item on iid = item.id where item_id.uid = %d and item_id.uid = item.uid and service = 'PDL' order by sid asc",
		intval($owner)
	);

	$arr = array('channel_id' => $uid, 'current' => $current, 'entries' => $r);
	call_hooks('pdl_selector',$arr);

	$entries = $arr['entries'];
	$current = $arr['current'];		
 
	$o .= "<select name=\"pdl_select\" id=\"pdl_select\" size=\"1\" >";
	$entries[] = array('title' => t('Default'), 'mid' => '');
	foreach($entries as $selection) {
		$selected = (($selection == $current) ? ' selected="selected" ' : '');
		$o .= "<option value=\"{$selection['mid']}\" $selected >{$selection['sid']}</option>";
	}

	$o .= '</select>';
	return $o;
}	



function comanche_parser(&$a,$s) {

	$cnt = preg_match_all("/\[comment\](.*?)\[\/comment\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],'',$s);
		}
	}

	$cnt = preg_match("/\[layout\](.*?)\[\/layout\]/ism", $s, $matches);
	if($cnt)
		$a->page['template'] = trim($matches[1]);


	$cnt = preg_match("/\[theme=(.*?)\](.*?)\[\/theme\]/ism", $s, $matches);
	if($cnt) {
		$a->layout['schema'] = trim($matches[1]);
		$a->layout['theme'] = trim($matches[2]);
	}

	$cnt = preg_match("/\[theme\](.*?)\[\/theme\]/ism", $s, $matches);
	if($cnt)
		$a->layout['theme'] = trim($matches[1]);

	$cnt = preg_match_all("/\[region=(.*?)\](.*?)\[\/region\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$a->layout['region_' . $mtch[1]] = comanche_region($a,$mtch[2]);
		}
	}

	$cnt = preg_match_all("/\[webpage\](.*?)\[\/webpage\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		// only the last webpage definition is used if there is more than one
		foreach($matches as $mtch) {
			$a->layout['webpage'] = comanche_webpage($a,$mtch[1]);
		}
	}

}


function comanche_menu($name,$class = '') {
	$a = get_app();
	$m = menu_fetch($name,$a->profile['profile_uid'],get_observer_hash());
	return menu_render($m,$class);
}

function comanche_replace_region($match) {
	$a = get_app();
	if(array_key_exists($match[1],$a->page)) {
		return $a->page[$match[1]];
	}
}

function comanche_block($name) {
	
	$o = '';
	$r = q("select * from item inner join item_id on iid = item.id and item_id.uid = item.uid and item.uid = %d and service = 'BUILDBLOCK' and sid = '%s' limit 1",
		intval(get_app()->profile['profile_uid']),
		dbesc($name)
	);
	if($r) {
		$o = '<div class="widget bblock">';
		if($r[0]['title'])
			$o .= '<h3>' . $r[0]['title'] . '</h3>';
		$o .= prepare_text($r[0]['body'],$r[0]['mimetype']);
		$o .= '</div>';

	}
	return $o;
}

// This doesn't really belong in Comanche, but it could also be argued that it is the perfect place.
// We need to be able to select what kind of template and decoration to use for the webpage at the heart of our content.
// For now we'll allow an '[authored]' element which defaults to name and date, or 'none' to remove these, and perhaps
// 'full' to provide a social network style profile photo.
// But leave it open to have richer templating options and perhaps ultimately discard this one, once we have a better idea
// of what template and webpage options we might desire. 

function comanche_webpage(&$a,$s) {

	$ret = array();
	$cnt = preg_match_all("/\[authored\](.*?)\[\/authored\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$ret['authored'] = $mtch[1];
		}
	}
	return $ret;
}


// Widgets will have to get any operational arguments from the session,
// the global app environment, or config storage until we implement argument passing


function comanche_widget($name,$text) {
	$a = get_app();
	$vars = array();
	$cnt = preg_match_all("/\[var=(.*?)\](.*?)\[\/var\]/ism", $text, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$vars[$mtch[1]] = $mtch[2];
		}
	}

	$func = 'widget_' . trim($name);
	if(function_exists($func))
		return $func($vars);
}


function comanche_region(&$a,$s) {

	$cnt = preg_match_all("/\[menu\](.*?)\[\/menu\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_menu(trim($mtch[1])),$s);
		}
	}

	// menu class e.g. [menu=horizontal]my_menu[/menu] or [menu=tabbed]my_menu[/menu]
	// allows different menu renderings to be applied

	$cnt = preg_match_all("/\[menu=(.*?)\](.*?)\[\/menu\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_menu(trim($mtch[2]),$match[1]),$s);
		}
	}
	$cnt = preg_match_all("/\[block\](.*?)\[\/block\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_block(trim($mtch[1])),$s);
		}
	}

	// need to modify this to accept parameters

	$cnt = preg_match_all("/\[widget=(.*?)\](.*?)\[\/widget\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_widget(trim($mtch[1]),$mtch[2]),$s);
		}
	}

	return $s;
}

