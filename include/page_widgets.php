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



// Chan is channel_id, $which is channel_address - we'll need to pass observer later too.
function pagelist_widget ($owner,$which){

	$r = q("select * from item_id left join item on item_id.iid = item.id where item_id.uid = %d and service = 'WEBPAGE' order by item.created desc",
		intval($owner)
	);

	$pages = null;

	if($r) {
		$pages = array();
		foreach($r as $rr) {
			$pages[$rr['iid']][] = array('url' => $rr['iid'],'pagetitle' => $rr['sid'],'title' => $rr['title'],'created' => datetime_convert('UTC',date_default_timezone_get(),$rr['created']),'edited' => datetime_convert('UTC',date_default_timezone_get(),$rr['edited']));
		}
	}

	//Build the base URL for edit links
	$url = z_root() . "/editwebpage/" . $which;
	// This isn't pretty, but it works.  Until I figure out what to do with the UI, it's Good Enough(TM).
	return $o . replace_macros(get_markup_template("webpagelist.tpl"), array(
		'$baseurl' => $url,
		'$edit' => t('Edit'),
		'$pages' => $pages,
		'$channel' => $which,
		'$view' => t('View'),
		'$preview' => t('Preview'),
		'$actions_txt' => t('Actions'),
		'$pagelink_txt' => t('Page Link'),
		'$title_txt' => t('Title'),
		'$created_txt' => t('Created'),
		'$edited_txt' => t('Edited')

	));

}
