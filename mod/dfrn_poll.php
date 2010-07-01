<?php


function dfrn_poll_init(&$a) {

	if(x($_GET,'dfrn_id'))
		$dfrn_id = $a->config['dfrn_poll_dfrn_id'] = $_GET['dfrn_id'];
	if(x($_GET,'type'))
		$type = $a->config['dfrn_poll_type'] = $_GET['type'];
	if(x($_GET,'last_update'))
		$last_update = $a->config['dfrn_poll_last_update'] = $_GET['last_update'];



	if(! x($dfrn_id))
		return;


	if((x($type)) && ($type == 'profile')) {

		$r = q("SELECT `contact`.*, `user`.`nickname` 
			FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `issued-id` = '%s' LIMIT 1",
			dbesc($dfrn_id));
		if(count($r)) {
			$s = fetch_url($r[0]['poll'] . '?dfrn_id=' . $dfrn_id . '&type=profile-check');
			if(strlen($s)) {
				$xml = simplexml_load_string($s);
				if((int) $xml->status == 1) {
					$_SESSION['authenticated'] = 1;
					$_SESSION['visitor_id'] = $r[0]['id'];
					$_SESSION['sysmsg'] .= "Hi {$r[0]['name']}" . EOL;
					// Visitors get 1 day session.
					$session_id = session_id();
					$expire = time() + 86400;
					q("UPDATE `session` SET `expire` = '%s' WHERE `sid` = '%s' LIMIT 1",
						dbesc($expire),
						dbesc($session_id)); 
				}
			}
			$profile = ((strlen($r[0]['nickname'])) ? $r[0]['nickname'] : $r[0]['uid']);
			goaway($a->get_baseurl() . "/profile/$profile");
		}
		goaway($a->get_baseurl());
	}

	if((x($type)) && ($type == 'profile-check')) {

		q("DELETE FROM `expire` WHERE `expire` < " . time());
		$r = q("SELECT * FROM `profile_check` WHERE `dfrn_id` = '%s' ORDER BY `expire` DESC",
			dbesc($dfrn_id));
		if(count($r))
			xml_status(1);
		xml_status(0);
		return; // NOTREACHED
	}

}
