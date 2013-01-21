<?php

@require_once('include/zot.php');

function magic_init(&$a) {

	$url = ((x($_REQUEST,'url')) ? $_REQUEST['url'] : '');
	$addr = ((x($_REQUEST,'addr')) ? $_REQUEST['addr'] : '');
	$hash = ((x($_REQUEST,'hash')) ? $_REQUEST['hash'] : '');
	$dest = ((x($_REQUEST,'dest')) ? $_REQUEST['dest'] : '');


	if(local_user()) { 

		if($hash) {
			$x = q("select xchan.xchan_url, hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash
				where hublock_hash = '%s' and (hubloc_flags & %d) limit 1",
				intval(HUBLOC_FLAGS_PRIMARY)
			);
		}
		elseif($addr) {
			$x = q("select hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash 
				where xchan_addr = '%s' and (hubloc_flags & %d) limit 1",
				dbesc($addr),
				intval(HUBLOC_FLAGS_PRIMARY)
			);
		}

		if(! $x) {
			notice( t('Channel not found.') . EOL);
			return;
		}

		if($x[0]['hubloc_url'] === z_root()) {
			$webbie = substr($x[0]['hubloc_addr'],0,strpos('@',$x[0]['hubloc_addr']));
			switch($dest) {
				case 'channel':
					$desturl = z_root() . '/channel/' . $webbie;
					break;
				case 'photos':
					$desturl = z_root() . '/photos/' . $webbie;
					break;
				case 'profile':
					$desturl = z_root() . '/profile/' . $webbie;
					break;
				default:
					$desturl = $dest;
					break;
			}
			// We are already authenticated on this site and a registered observer.
			// Just redirect.
			goaway($desturl);
		}

		$token = random_string();
		$token_sig = rsa_sign($token,$channel['channel_prvkey']);
 
 		$channel = $a->get_channel();
		$channel['token'] = $token;
		$channel['token_sig'] = $token_sig;


		$recip = array(array('guid' => $x[0]['hubloc_guid'],'guid_sig' => $x[0]['hubloc_guid_sig']));

		$hash = random_string();

		$r = q("insert into verify ( type, channel, token, meta, created) values ('%s','%d','%s','%s','%s')",
			dbesc('auth'),
			intval($channel['channel_id']),
			dbesc($token),
			dbesc($hubloc['hubloc_hash']),
			dbesc(datetime_convert())
		);

		$packet = zot_build_packet($channel,'auth',$recip,$x[0]['hubloc_sitekey'],$hash);
		$result = zot_zot($x[0]['hubloc_callback'],$packet);
		if($result['success']) {
			$j = json_decode($result['body'],true);
			if($j['iv']) {
				$y = aes_unencapsulate($j,$channel['prvkey']);
				$j = json_decode($y,true);
			}
			if($j['token'] && $j['ticket'] && $j['token'] === $token) {
				$r = q("delete from verify where token = '%s' and type = '%s' and channel = %d limit 1",
					dbesc($token),
					dbesc('auth'),
					intval($channel['channel_id'])
				);				
				goaway($x[0]['callback'] . '?f=&ticket=' . $ticket . '&dest=' . $dest);
			}
		}
		goaway($dest);
	}

	goaway(z_root());
}
