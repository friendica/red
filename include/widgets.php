<?php /** @file */


function widget_profile($args) {
	$a = get_app();
	$block = (((get_config('system','block_public')) && (! local_user()) && (! remote_user())) ? true : false);
	return profile_sidebar($a->profile, $block, true);
}

// FIXME The problem with the next widget is that we don't have a search function for webpages that we can send the links to.
// Then we should also provide an option to search webpages and conversations.

function widget_tagcloud($args) {

	$o = '';
	$tab = 0;
	$a = get_app();
	$uid = $a->profile_uid;
	$count = ((x($args,'count')) ? intval($args['count']) : 24);
	$flags = 0;
	$type = TERM_CATEGORY;

	$r = tagadelic($uid,$count,$authors,$flags,ITEM_WEBPAGE,$type);

	if($r) {
		$o = '<div class="tagblock widget"><h3>' . t('Categories') . '</h3><div class="tags" align="center">';
		foreach($r as $rr) {
			$o .= '<span class="tag'.$rr[2].'">'.$rr[0].'</span> ' . "\r\n";
		}
		$o .= '</div></div>';
	}
	return $o;
}

