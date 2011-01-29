<?php
require_once("boot.php");

function update_queue_time($id) {
	logger('queue: requeue item ' . $id);
	q("UPDATE `queue` SET `last` = '%s' WHERE `id` = %d LIMIT 1",
		dbesc(datetime_convert()),
		intval($id)
	);
}

function remove_queue_item($id) {
	logger('queue: remove queue item ' . $id);
	q("DELETE FROM `queue` WHERE `id` = %d LIMIT 1",
		intval($id)
	);
}

function queue_run($argv, $argc){
  global $a, $db;

  if(is_null($a)){
    $a = new App;
  }
  
  if(is_null($db)){
    @include(".htconfig.php");
    require_once("dba.php");
    $db = new dba($db_host, $db_user, $db_pass, $db_data);
    unset($db_host, $db_user, $db_pass, $db_data);
  };


	require_once("session.php");
	require_once("datetime.php");
	require_once('include/items.php');
	require_once('include/bbcode.php');

	$a->set_baseurl(get_config('system','url'));

	$deadguys = array();

	logger('queue: start');

	$r = q("SELECT `queue`.*, `contact`.`name`, `contact`.`uid` FROM `queue` 
		LEFT JOIN `contact` ON `queue`.`cid` = `contact`.`id` 
		WHERE `queue`.`created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
	if(count($r)) {
		foreach($r as $rr) {
			logger('Removing expired queue item for ' . $rr['name'] . ', uid=' . $rr['uid']);
			logger('Expired queue data :' . $rr['content'], LOGGER_DATA);
		}
		q("DELETE FROM `queue` WHERE `created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
	}
		
	$r = q("SELECT `id` FROM `queue` WHERE `last` < UTC_TIMESTAMP() - INTERVAL 15 MINUTE ");

	if(! count($r)){
		return;
	}
	// delivery loop

	require_once('include/salmon.php');

	foreach($r as $q_item) {
		$qi = q("SELECT * FROM `queue` WHERE `id` = %d LIMIT 1",
			intval($q_item['id'])
		);
		if(! count($qi))
			continue;

		$c = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($qi[0]['cid'])
		);
		if(! count($c)) {
			remove_queue_item($q_item['id']);
			continue;
		}
		if(in_array($c[0]['notify'],$deadguys)) {
				logger('queue: skipping known dead url: ' . $c[0]['notify']);
				update_queue_time($q_item['id']);
				continue;
		}

		$u = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($c[0]['uid'])
		);
		if(! count($u)) {
			remove_queue_item($q_item['id']);
			continue;
		}

		$data      = $qi[0]['content'];
		$contact   = $c[0];
		$owner     = $u[0];

		$deliver_status = 0;

		switch($contact['network']) {
			case 'dfrn':
				logger('queue: dfrndelivery: item ' . $q_item['id'] . ' for ' . $contact['name']);
				$deliver_status = dfrn_deliver($owner,$contact,$data);

				if($deliver_status == (-1)) {
					update_queue_time($q_item['id']);
					$deadguys[] = $contact['notify'];
				}
				else {
					remove_queue_item($q_item['id']);
				}
				break;
			default:
				if($contact['notify']) {
					logger('queue: slapdelivery: item ' . $q_item['id'] . ' for ' . $contact['name']);
					$deliver_status = slapper($owner,$contact['notify'],$data);

					if($deliver_status == (-1))
						update_queue_time($q_item['id']);
					else
						remove_queue_item($q_item['id']);
				}
				break;
		}
	}
		
	return;

}

if (array_search(__file__,get_included_files())===0){
  queue_run($argv,$argc);
  killme();
}
