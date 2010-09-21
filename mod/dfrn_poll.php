<?php

require_once('include/items.php');
require_once('include/auth.php');


function dfrn_poll_init(&$a) {

	$dfrn_id = '';

	if(x($_GET,'dfrn_id'))
		$dfrn_id = $_GET['dfrn_id'];
	if(x($_GET,'type'))
		$type = $_GET['type'];
	if(x($_GET,'last_update'))
		$last_update = $_GET['last_update'];
	$dfrn_version    = ((x($_GET,'dfrn_version'))    ? $_GET['dfrn_version']    : '1.0');
	$destination_url = ((x($_GET,'destination_url')) ? $_GET['destination_url'] : '');


	$direction = (-1);


	if(strpos($dfrn_id,':') == 1) {
		$direction = intval(substr($dfrn_id,0,1));
		$dfrn_id = substr($dfrn_id,2);
	}

	if(($dfrn_id == '') && (! x($_POST,'dfrn_id')) && ($a->argc > 1)) {
		$o = get_feed_for($a, '*', $a->argv[1],$last_update);
		echo $o;
		killme();
	}

	if((x($type)) && ($type == 'profile')) {

		$sql_extra = '';
		switch($direction) {
			case (-1):
				$sql_extra = sprintf(" AND `dfrn-id` = '%s' ", dbesc($dfrn_id));
				$my_id = $dfrn_id;
				break;
			case 0:
				$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				goaway($a->get_baseurl());
				break; // NOTREACHED
		}

		$r = q("SELECT `contact`.*, `user`.`nickname` 
			FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`blocked` = 0 AND `contact`.`pending` = 0 $sql_extra LIMIT 1");
		
		if(count($r)) {

			$s = fetch_url($r[0]['poll'] . '?dfrn_id=' . $my_id . '&type=profile-check');

			if(strlen($s)) {

				$xml = simplexml_load_string($s);

				if((int) $xml->status == 1) {
					$_SESSION['authenticated'] = 1;
					$_SESSION['visitor_id'] = $r[0]['id'];
					notice( t('Hi ') . $r[0]['name'] . EOL);
					// Visitors get 1 day session.
					$session_id = session_id();
					$expire = time() + 86400;
					q("UPDATE `session` SET `expire` = '%s' WHERE `sid` = '%s' LIMIT 1",
						dbesc($expire),
						dbesc($session_id)
					); 
				}
			}
			$profile = $r[0]['nickname'];
			goaway((strlen($destination_url)) ? $destination_url : $a->get_baseurl() . '/profile/' . $profile);
		}
		goaway($a->get_baseurl());

	}

	if((x($type)) && ($type == 'profile-check')) {

		switch($direction) {
			case 1:
				$dfrn_id = '0:' . $dfrn_id;
				break;
			case 0:
				$dfrn_id = '1:' . $dfrn_id;
				break;
			default:
				break;
		}
		q("DELETE FROM `profile_check` WHERE `expire` < " . intval(time()));
		$r = q("SELECT * FROM `profile_check` WHERE `dfrn_id` = '%s' ORDER BY `expire` DESC",
			dbesc($dfrn_id));
		if(count($r)) {
			xml_status(1);
			return; // NOTREACHED
		}
		xml_status(0);
		return; // NOTREACHED
	}


}



function dfrn_poll_post(&$a) {

	$dfrn_id = $_POST['dfrn_id'];
	$challenge = $_POST['challenge'];
	$url = $_POST['url'];

	$direction = (-1);
	if(strpos($dfrn_id,':') == 1) {
		$direction = intval(substr($dfrn_id,0,1));
		$dfrn_id = substr($dfrn_id,2);
	}


	$r = q("SELECT * FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		dbesc($dfrn_id),
		dbesc($challenge)
	);

	if(! count($r))
		killme();

	$type = $r[0]['type'];
	$last_update = $r[0]['last_update'];

	$r = q("DELETE FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		dbesc($dfrn_id),
		dbesc($challenge)
	);


	$sql_extra = '';
	switch($direction) {
		case (-1):
			$sql_extra = sprintf(" AND `issued-id` = '%s' ", dbesc($dfrn_id));
			$my_id = $dfrn_id;
			break;
		case 0:
			$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
			$my_id = '1:' . $dfrn_id;
			break;
		case 1:
			$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
			$my_id = '0:' . $dfrn_id;
			break;
		default:
			goaway($a->get_baseurl());
			break; // NOTREACHED
	}


	$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 $sql_extra LIMIT 1");


	if(! count($r))
		killme();

	$owner_uid = $r[0]['uid'];
	$contact_id = $r[0]['id']; 


	if($type == 'reputation' && strlen($url)) {
		$r = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($url),
			intval($owner_uid)
		);
		$reputation = 0;
		$text = '';

		if(count($r)) {
			$reputation = $r[0]['rating'];
			$text = $r[0]['reason'];

			if($r[0]['id'] == $contact_id) {	// inquiring about own reputation not allowed
				$reputation = 0;
				$text = '';
			}
		}

		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
		<reputation>
			<url>$url</url>
			<rating>$reputation</rating>
			<description>$text</description>
		</reputation>
		";
		killme();
		return; // NOTREACHED
	}
	else {
		$o = get_feed_for($a,$dfrn_id, $a->argv[1], $last_update, $direction);
		echo $o;
		killme();

	}
}

function dfrn_poll_content(&$a) {


	$dfrn_id = '';
	$type = 'data';

	if(x($_GET,'dfrn_id'))
		$dfrn_id = $_GET['dfrn_id'];
	if(x($_GET,'type'))
		$type = $_GET['type'];
	if(x($_GET,'last_update'))
		$last_update = $_GET['last_update'];

	$direction = (-1);
	if(strpos($dfrn_id,':') == 1) {
		$direction = intval(substr($dfrn_id,0,1));
		$dfrn_id = substr($dfrn_id,2);
	}


	if($dfrn_id != '') {
		// initial communication from external contact
		$hash = random_string();

		$status = 0;

		$r = q("DELETE FROM `challenge` WHERE `expire` < " . intval(time()));

		$r = q("INSERT INTO `challenge` ( `challenge`, `dfrn-id`, `expire` , `type`, `last_update` )
			VALUES( '%s', '%s', '%s', '%s', '%s' ) ",
			dbesc($hash),
			dbesc($dfrn_id),
			intval(time() + 60 ),
			dbesc($type),
			dbesc($last_update)
		);


		$sql_extra = '';
		switch($direction) {
			case (-1):
				$sql_extra = sprintf(" AND `issued-id` = '%s' ", dbesc($dfrn_id));
				$my_id = $dfrn_id;
				break;
			case 0:
				$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				goaway($a->get_baseurl());
				break; // NOTREACHED
		}




		$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 $sql_extra LIMIT 1");

		if(count($r)) {

			$challenge = '';
			$encrypted_id = '';
			$id_str = $my_id . '.' . mt_rand(1000,9999);


			if($r[0]['duplex'] && strlen($r[0]['pubkey'])) {
				openssl_public_encrypt($hash,$challenge,$r[0]['pubkey']);
				openssl_public_encrypt($id_str,$encrypted_id,$r[0]['pubkey']);
			}
			else {
				openssl_private_encrypt($hash,$challenge,$r[0]['prvkey']);
				openssl_private_encrypt($id_str,$encrypted_id,$r[0]['prvkey']);
			}

			$challenge = bin2hex($challenge);
			$encrypted_id = bin2hex($encrypted_id);
		}
		else {
			$status = 1;
		}

		echo '<?xml version="1.0" encoding="UTF-8"?><dfrn_poll><status>' .$status . '</status><dfrn_version>2.0</dfrn_version><dfrn_id>' . $encrypted_id . '</dfrn_id>'
			. '<challenge>' . $challenge . '</challenge></dfrn_poll>' . "\r\n" ;
		session_write_close();
		exit;		
	}
}


