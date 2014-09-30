<?php

function knownhubs_init(&$a) {

	if ($a->argv[1]=="json"){
		$known_hubs = array();
		$r = q("SELECT s.site_url FROM site as s group by s.site_url");

		if(count($r)) {
			foreach($r as $rr) {
				$known_hubs[] = $rr['site_url'];
			}
		}
		sort($known_hubs);
		
		$data = Array(
			'knownhubs' => $known_hubs,
		);
		json_return_and_die($data);
	}
	
}
