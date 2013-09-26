<?php

// A basic toolbar for observers with write_pages permissions
function writepages_widget ($who,$which){
	return replace_macros(get_markup_template('write_pages.tpl'), array(
			'$new' => t('New Page'),
			'$newurl' => "webpages/$who",
                        '$edit' => t('Edit'),
                        '$editurl' => "editwebpage/$who/$which"
			));
}



// Chan is channel_id, $who is channel_address - we'll need to pass observer later too.
function pagelist_widget ($chan,$who){
	$r = q("select * from item_id where uid = %d and service = 'WEBPAGE' order by sid asc",
	intval($chan)
	);
		$pages = null;
// TODO - only list public pages.  Doesn't matter for now, since we don't have ACL anyway.

		if($r) {
			$pages = array();
			foreach($r as $rr) {
				$pages[$rr['iid']][] = array('url' => $rr['iid'],'title' => $rr['sid']);
			} 
		}

	return replace_macros(get_markup_template('webpagelist.tpl'), array(
		'$baseurl' => $url,
		'$edit' => '',
		'$pages' => $pages,
		'$channel' => $who,
		'$preview' => '',
		'$widget' => 1,
	));

}
