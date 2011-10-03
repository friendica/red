<?php


function tagmatch_content(&$a) {

	$search = $_REQUEST['search'];
	
	$o = '';
	if(! local_user())
		return;

	$o .= '<h2>' . t('Tag Match') . ' - ' . notags($search) . '</h2>';

	
	if($search) {
		$params['s'] = $search;
		if($a->pager['page'] != 1)
			$params['p'] = $a->pager['page'];
			
		if(strlen(get_config('system','directory_submit_url')))
			$x = fetch_url('http://dir.friendika.com/lsearch?f=&search=' . urlencode($search));
//		else
//			$x = post_url($a->get_baseurl() . '/msearch', $params);

		$j = json_decode($x);

		if($j->total) {
			$a->set_pager_total($j->total);
			$a->set_pager_itemspage($j->items_page);
		}

		if(count($j->results)) {
			
			$tpl = get_markup_template('match.tpl');
			foreach($j->results as $jj) {
				
				$o .= replace_macros($tpl,array(
					'$url' => $jj->url,
					'$name' => $jj->name,
					'$photo' => $jj->photo,
					'$tags' => $jj->tags
				));
			}
		}
		else {
			info( t('No matches') . EOL);
		}		

	}

	$o .= '<div class="clear"></div>';
	$o .= paginate($a);
	return $o;
}
