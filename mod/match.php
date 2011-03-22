<?php


function match_content(&$a) {

	if(! local_user())
		return;

	$r = q("SELECT `pub_keywords`, `prv_keywords` FROM `profile` WHERE `is-default` = 1 AND `uid` = %d LIMIT 1",
		intval(local_user())
	);
	if(! count($r))
		return; 
	if(! $r[0]['pub_keywords'] && (! $r[0]['prv_keywords'])) {
		notice('No keywords to match. Please add keywords to your default profile.');
		return;

	}

	$params = array();
	$tags = trim($r[0]['pub_keywords'] . ' ' . $r[0]['prv_keywords']);
	if($tags) {
		$params['s'] = $tags;


		$x = post_url('http://dir.friendika.com/msearch', $params);

		$j = json_decode($x);

		if(count($j)) {
			foreach($j as $jj) {

				$o .= '<a href="' . $jj->url . '">' . '<img src="' . $jj->photo . '" alt="' . $jj->name . '" />' . $jj->name . '</a>';
			}
		}
		else {
			notice( t('No matches') . EOL);
		}		

	}
	return $o;
}