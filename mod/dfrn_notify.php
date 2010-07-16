<?php

require_once('simplepie/simplepie.inc');


function get_atom_elements($item) {

	$res = array();

	$author = $item->get_author();
	$res['remote-name'] = $author->get_name();
	$res['remote-link'] = $author->get_link();
	$res['remote-avatar'] = $author->get_avatar();
	$res['remote-id'] = $item->get_id();
	$res['title'] = $item->get_title();
	$res['body'] = $item->get_content();

	if(strlen($res['body']) > 100000)
		$res['body'] = substr($res['body'],0,10000) . "\r\n[Extremely large post truncated.]\r\n"  ;

	$allow = $item->get_item_tags('http://purl.org/macgirvin/dfrn/1.0','comment-allow');
	if($allow && $allow[0]['data'] == 1)
		$res['last-child'] = 1;
	else
		$res['last-child'] = 0;

	$rawcreated = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'published');
	if($rawcreated)
		$res['created'] = $rawcreated[0]['data'];

	$rawedited = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'updated');
	if($rawedited)
		$res['edited'] = $rawcreated[0]['data'];



	return $res;

}

function post_remote($arr) {

	$arr['hash'] = random_string();
	$arr['type'] = 'remote';
	$arr['remote-name'] = notags(trim($arr['remote-name']));
	$arr['remote-link'] = notags(trim($arr['remote-link']));
	$arr['remote-avatar'] = notags(trim($arr['remote-avatar']));
	if(! strlen($arr['remote-avatar']))
		$arr['remote-avatar'] = $a->get_baseurl() . '/images/default-profile-sm.jpg';
	$arr['created'] = datetime_convert('UTC','UTC',$arr['created'],'Y-m-d H:i:s');
	$arr['edited'] = datetime_convert('UTC','UTC',$arr['edited'],'Y-m-d H:i:s');
	$arr['title'] = notags(trim($arr['title']));
	$arr['body'] = escape_tags(trim($arr['body']));
	$arr['last-child'] = intval($arr['last_child']);
	$arr['visible'] = 1;
	$arr['deleted'] = 0;

	$parent = $arr['parent_urn'];

	unset($arr['parent_urn']);

	$parent_id = 0;

	dbesc_array($arr);

	$r = q("INSERT INTO `item` (`" 
			. implode("`, `", array_keys($arr)) 
			. "`) VALUES ('" 
			. implode("', '", array_values($arr)) 
			. "')" );


	$r = q("SELECT `id` FROM `item` WHERE `remote-id` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($parent),
		intval($arr['uid'])
	);
	if(count($r))
		$parent_id = $r[0]['id'];
	

	$r = q("SELECT `id` FROM `item` WHERE `remote-id` = '%s' AND `uid` = %d LIMIT 1",
		$arr['remote-id'],
		intval($arr['uid'])
	);
	if(count($r))
		$current_post = $r[0]['id'];

	$r = q("UPDATE `item` SET `parent` = %d WHERE `id` = %d LIMIT 1",
		intval($parent_id),
		intval($current_post)
	);

}

function dfrn_notify_post(&$a) {
dbg(3);
	$dfrn_id = notags(trim($_POST['dfrn_id']));
	$challenge = notags(trim($_POST['challenge']));
	$data = $_POST['data'];
	$r = q("SELECT * FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		dbesc($dfrn_id),
		dbesc($challenge)
	);
	if(! count($r))
		xml_status(3);

	$r = q("DELETE FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		dbesc($dfrn_id),
		dbesc($challenge)
	);

	// find the local user who owns this relationship.

	$r = q("SELECT `id`, `uid` FROM `contact` WHERE `issued-id` = '%s' LIMIT 1",
		dbesc($dfrn_id)
	);
	if(! count($r))
		xml_status(3);


	$importer = $r[0];

	$feed = new SimplePie();
	$feed->set_raw_data($data);
	$feed->enable_order_by_date(false);
	$feed->init();

	foreach($feed->get_items() as $item) {

		$rawdelete = $item->get_item_tags("http://purl.org/atompub/tombstones/1.0", 'deleted-entry');
		print_r($rawdelete);
		if($deleted) {
			// pick out ref and when from attribs
			// check hasn't happened already, verify ownership and then process it


			continue;
		}

		$is_reply = false;		
		$item_id = $item->get_id();
		$rawthread = $item->get_item_tags("http://purl.org/syndication/thread/1.0",'in-reply-to');
		if(isset($rawthread[0]['attribs']['']['ref'])) {
			$is_reply = true;
			$parent_urn = $rawthread[0]['attribs']['']['ref'];
		}


		if($is_reply) {
			if($x == ($total_items - 1)) {
				// remote reply to our post. Import and then notify everybody else.
			}
			else {
				// regular comment that is part of this total conversation. Have we seen it? If not, import it.

				$item_id = $item->get_id();

				$r = q("SELECT `uid`, `last-child` FROM `item` WHERE `remote-id` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['uid'])
				);
				if(count($r)) {
					$allow = $item->get_item_tags('http://purl.org/macgirvin/dfrn/1.0','comment-allow');
					if($allow && $allow[0]['data'] != $r[0]['last-child']) {
						$r = q("UPDATE `item` SET `last-child` = %d WHERE `remote-id` = '%s' AND `uid` = %d LIMIT 1",
							intval($allow[0]['data']),
							dbesc($item_id)
						);
					}
					continue;
				}
				$datarray = get_atom_elements($item);
				$datarray['parent_urn'] = $parent_urn;
				$datarray['uid'] = $importer['uid'];
				$datarray['contact-id'] = $importer['id'];
				$r = post_remote($datarray);
				continue;
			}
		}
		else {
			// Head post of a conversation. Have we seen it? If not, import it.

			$item_id = $item->get_id();
			$r = q("SELECT `uid` FROM `item` WHERE `remote-id` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($item_id),
				intval($importer['uid'])
			);
			if(count($r)) {
				$allow = $item->get_item_tags('http://purl.org/macgirvin/dfrn/1.0','comment-allow');
				if($allow && $allow[0]['data'] != $r[0]['last-child']) {
					$r = q("UPDATE `item` SET `last-child` = %d WHERE `remote-id` = '%s' AND `uid` = %d LIMIT 1",
						intval($allow[0]['data']),
						dbesc($item_id)
					);
				}
				continue;
			}


			$datarray = get_atom_elements($item);
			$datarray['parent_urn'] = $item_id;
			$datarray['uid'] = $importer['uid'];
			$datarray['contact-id'] = $importer['id'];
			$r = post_remote($datarray);
			continue;

		}
	
	}


	killme();

}










function dfrn_notify_content(&$a) {

	if(x($_GET,'dfrn_id')) {
		// initial communication from external contact
		$hash = random_string();

		$status = 0;

		$r = q("DELETE FROM `challenge` WHERE `expire` < " . intval(time()));

		$r = q("INSERT INTO `challenge` ( `challenge`, `dfrn-id`, `expire` )
			VALUES( '%s', '%s', '%s') ",
			dbesc($hash),
			dbesc(notags(trim($_GET['dfrn_id']))),
			intval(time() + 60 )
		);

		$r = q("SELECT * FROM `contact` WHERE `issued-id` = '%s' AND `blocked` = 0 LIMIT 1",
			dbesc($_GET['dfrn_id']));
		if((! count($r)) || (! strlen($r[0]['prvkey'])))
			$status = 1;

		$challenge = '';

		openssl_private_encrypt($hash,$challenge,$r[0]['prvkey']);
		$challenge = bin2hex($challenge);
		echo '<?xml version="1.0" encoding="UTF-8"?><dfrn_notify><status>' .$status . '</status><dfrn_id>' . $_GET['dfrn_id'] . '</dfrn_id>'
			. '<challenge>' . $challenge . '</challenge></dfrn_notify>' . "\r\n" ;
		session_write_close();
		exit;
		
	}

}