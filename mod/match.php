<?php


function match_content(&$a) {

	$o = '';
	if(! local_user())
		return;

	$o .= '<h2>' . t('Profile Match') . '</h2>';

	$r = q("SELECT `pub_keywords`, `prv_keywords` FROM `profile` WHERE `is-default` = 1 AND `uid` = %d LIMIT 1",
		intval(local_user())
	);
	if(! count($r))
		return; 
	if(! $r[0]['pub_keywords'] && (! $r[0]['prv_keywords'])) {
		notice( t('No keywords to match. Please add keywords to your default profile.') . EOL);
		return;

	}

	$params = array();
	$tags = trim($r[0]['pub_keywords'] . ' ' . $r[0]['prv_keywords']);
	if($tags) {
		$params['s'] = $tags;
		if($a->pager['page'] != 1)
			$params['p'] = $a->pager['page'];
			
		if(strlen(get_config('system','directory_submit_url')))
			$x = post_url('http://dir.friendika.com/msearch', $params);
		else
			$x = post_url($a->get_baseurl() . '/msearch', $params);

		$j = json_decode($x);

		if($j->total) {
			$a->set_pager_total($j->total);
			$a->set_pager_itemspage($j->items_page);
		}

		if(count($j->results)) {
			foreach($j->results as $jj) {

				$o .= '<div class="profile-match-wrapper"><div class="profile-match-photo">';
				$o .= '<a href="' . $jj->url . '">' . '<img src="' . $jj->photo . '" alt="' . $jj->name . '" title="' . $jj->name . '[' . $jj->url . ']' . '" /></a></div>';
				$o .= '<div class="profile-match-break"></div>';
				$o .= '<div class="profile-match-name"><a href="' . $jj->url . '" title="' . $jj->name . '[' . $jj->url .']' . '">' . $jj->name . '</a></div>';
				$o .= '<div class="profile-match-end"></div></div>';
			}
			$o .= '<div id="profile-match-wrapper-end"></div>';
		}
		else {
			info( t('No matches') . EOL);
		}		

	}

	$o .= paginate($a);
	return $o;
}
