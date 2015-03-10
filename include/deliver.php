<?php /** @file */

require_once('include/cli_startup.php');
require_once('include/zot.php');


function deliver_run($argv, $argc) {

	cli_startup();

	$a = get_app();

	if($argc < 2)
		return;

	logger('deliver: invoked: ' . print_r($argv,true), LOGGER_DATA);

	for($x = 1; $x < $argc; $x ++) {
		$r = q("select * from outq where outq_hash = '%s' limit 1",
			dbesc($argv[$x])
		);
		if($r) {

			/**
			 * Check to see if we have any recent communications with this hub (in the last month).
			 * If not, reduce the outq_priority.
			 */

			$h = parse_url($r[0]['outq_posturl']);
			if($h) {
				$base = $h['scheme'] . '://' . $h['host'] . (($h['port']) ? ':' . $h['port'] : '');
				if($base !== z_root()) {
					$y = q("select site_update from site where site_url = '%s' ",
						dbesc($base)
					);
					if($y && $y[0]['site_update'] < datetime_convert('UTC','UTC','now - 1 month')) {
						q("update outq set outq_priority = %d where outq_hash = '%s'",
							intval($r[0]['outq_priority'] + 10),
							dbesc($r[0]['outq_hash'])
						);
						logger('immediate delivery deferred for site ' . $base);
						continue;
					}
				}
			} 

			// "post" queue driver - used for diaspora and friendica-over-diaspora communications.

			if($r[0]['outq_driver'] === 'post') {
				$result = z_post_url($r[0]['outq_posturl'],$r[0]['outq_msg']); 
				if($result['success'] && $result['return_code'] < 300) {
					logger('deliver: queue post success to ' . $r[0]['outq_posturl'], LOGGER_DEBUG);
					$y = q("delete from outq where outq_hash = '%s'",
						dbesc($argv[$x])
					);
				}
				else {
					logger('deliver: queue post returned ' . $result['return_code'] . ' from ' . $r[0]['outq_posturl'],LOGGER_DEBUG);
					$y = q("update outq set outq_updated = '%s' where outq_hash = '%s'",
						dbesc(datetime_convert()),
						dbesc($argv[$x])
					);
				}
				continue;
			}

			$notify = json_decode($r[0]['outq_notify'],true);

			// Check if this is a conversation request packet. It won't have outq_msg
			// but will be an encrypted packet - so will need to be handed off to
			// web delivery rather than processed inline. 

			$sendtoweb = false;
			if(array_key_exists('iv',$notify) && (! $r[0]['outq_msg']))
				$sendtoweb = true;

			if(($r[0]['outq_posturl'] === z_root() . '/post') && (! $sendtoweb)) {
				logger('deliver: local delivery', LOGGER_DEBUG);
				// local delivery
				// we should probably batch these and save a few delivery processes

				if($r[0]['outq_msg']) {
					$m = json_decode($r[0]['outq_msg'],true);
					if(array_key_exists('message_list',$m)) {
						foreach($m['message_list'] as $mm) {
							$msg = array('body' => json_encode(array('pickup' => array(array('notify' => $notify,'message' => $mm)))));
							zot_import($msg,z_root());
						}
					}	
					else {	
						$msg = array('body' => json_encode(array('pickup' => array(array('notify' => $notify,'message' => $m)))));
						zot_import($msg,z_root());
					}
					$r = q("delete from outq where outq_hash = '%s'",
						dbesc($argv[$x])
					);
				}
			}
			else {
				logger('deliver: dest: ' . $r[0]['outq_posturl'], LOGGER_DEBUG);
				$result = zot_zot($r[0]['outq_posturl'],$r[0]['outq_notify']); 
				if($result['success']) {
					logger('deliver: remote zot delivery succeeded to ' . $r[0]['outq_posturl']);
					zot_process_response($r[0]['outq_posturl'],$result, $r[0]);				
				}
				else {
					logger('deliver: remote zot delivery failed to ' . $r[0]['outq_posturl']);
					$y = q("update outq set outq_updated = '%s' where outq_hash = '%s'",
						dbesc(datetime_convert()),
						dbesc($argv[$x])
					);
				}
			}
		}
	}
}

if (array_search(__file__,get_included_files())===0){
  deliver_run($argv,$argc);
  killme();
}
