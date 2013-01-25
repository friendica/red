<?php

require_once('include/Contact.php');
require_once('include/zot.php');

function chanview_content(&$a) {

	$observer = $a->get_observer();
	$xchan = null;

	$r = null;

	if($_REQUEST['hash']) {
		$r = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($_REQUEST['hash'])
		);
	}
	if($_REQUEST['address']) {
		$r = q("select * from xchan where xchan_addr = '%s' limit 1",
			dbesc($_REQUEST['address'])
		);
	}
	elseif(local_user() && intval($_REQUEST['cid'])) {
		$r = q("SELECT abook.*, xchan.* 
			FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_channel = %d and abook_id = %d LIMIT 1",
			intval(local_user()),
			intval($_REQUEST['cid'])
		);
	}	
	elseif($_REQUEST['url']) {
		$r = q("select * from xchan where xchan_url = '%s' limit 1",
			dbesc($_REQUEST['url'])
		);
	}
	if($r) {
		$xchan = $r[0];
	}



	// Here, let's see if we have an xchan. If we don't, how we proceed is determined by what
	// info we do have. If it's a URL, we can offer to visit it directly. If it's a webbie or 
	// address, we can and should try to import it. If it's just a hash, we can't continue, but we 
	// probably wouldn't have a hash if we don't already have an xchan for this channel.

	if(! $xchan) {
		logger('mod_chanview: fallback');
		// This is hackish - construct a zot address from the url
		if($_REQUEST['url']) {
			if(preg_match('/https?\:\/\/(.*?)(\/channel\/|\/profile\/)(.*?)$/ism',$_REQUEST['url'],$matches)) {
				$_REQUEST['address'] = $matches[3] . '@' . $matches[1];
			}
			logger('mod_chanview: constructed address ' . print_r($matches,true)); 
		}

		if($_REQUEST['address']) {
			$ret = zot_finger($_REQUEST['address'],null);
			if($ret['success']) {
				$j = json_decode($ret['body'],true);
				if($j)
					import_xchan($j);
				$r = q("select * from xchan where xchan_addr = '%s' limit 1",
					dbesc($_REQUEST['address'])
				);
				if($r)
					$xchan = $r[0];
			}

		}
	}

	if(! $xchan) {
		notice( t('Channel not found.') . EOL);
		return;
	}

	if($xchan['xchan_hash'])
		$a->set_widget('vcard',vcard_from_xchan($xchan,$observer,'chanview'));
				
	$url = (($observer) 
		? z_root() . '/magic?f=&dest=' . $xchan['xchan_url'] . '&addr=' . $xchan['xchan_addr'] 
		: $xchan['xchan_url']
	);
				


	$o = replace_macros(get_markup_template('chanview.tpl'),array(
		'$url' => $url,
		'$full' => t('toggle full screen mode')
	));

	return $o;

}