<?php

function manage_content(&$a) {

	if((! get_account_id()) || ($_SESSION['delegate'])) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	require_once('include/security.php');

	$change_channel = ((argc() > 1) ? intval(argv(1)) : 0);

	if((argc() > 2) && (argv(2) === 'default')) {
		$r = q("select channel_id from channel where channel_id = %d and channel_account_id = %d limit 1",
			intval($change_channel),
			intval(get_account_id())
		);
		if($r) {
			q("update account set account_default_channel = %d where account_id = %d",
				intval($change_channel),
				intval(get_account_id())
			);
		}
		goaway(z_root() . '/manage');
	}

	if($change_channel) {
		$r = change_channel($change_channel);

		if((argc() > 2) && !(argv(2) === 'default')) {
			goaway(z_root() . '/' . implode('/',array_slice($a->argv,2))); // Go to whatever is after /manage/, but with the new channel
		}
		else {
			if($r && $r['channel_startpage'])
				goaway(z_root() . '/' . $r['channel_startpage']); // If nothing extra is specified, go to the default page
		}
		goaway(z_root());
	}

	$channels = null;

	if(local_channel()) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel.channel_account_id = %d and not ( channel_pageflags & %d )>0 order by channel_name ",
			intval(get_account_id()),
			intval(PAGE_REMOVED)
		);

		$account = get_app()->get_account();

		if($r && count($r)) {
			$channels = $r;
			for($x = 0; $x < count($channels); $x ++) {
				$channels[$x]['link'] = 'manage/' . intval($channels[$x]['channel_id']);
				$channels[$x]['default'] = (($channels[$x]['channel_id'] == $account['account_default_channel']) ? "1" : ''); 
				$channels[$x]['default_links'] = '1';


				$c = q("SELECT id, item_restrict, item_flags FROM item
					WHERE item_restrict = 0 and item_unseen = 1 and uid = %d",
					intval($channels[$x]['channel_id'])
				);

				if($c) {	
					foreach ($c as $it) {
						if($it['item_flags'] & ITEM_WALL)
							$channels[$x]['home'] ++;
						else
							$channels[$x]['network'] ++;
					}
				}


				$intr = q("SELECT COUNT(abook.abook_id) AS total FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash where abook_channel = %d and (abook_flags & %d)>0 and not ((abook_flags & %d)>0 or (xchan_flags & %d)>0)",
					intval($channels[$x]['channel_id']),
					intval(ABOOK_FLAG_PENDING),
					intval(ABOOK_FLAG_SELF|ABOOK_FLAG_IGNORED),
					intval(XCHAN_FLAGS_DELETED|XCHAN_FLAGS_ORPHAN)
				);

				if($intr)
					$channels[$x]['intros'] = intval($intr[0]['total']);


				$mails = q("SELECT count(id) as total from mail WHERE channel_id = %d AND not (mail_flags & %d)>0 and from_xchan != '%s' ",
					intval($channels[$x]['channel_id']),
					intval(MAIL_SEEN),		
					dbesc($channels[$x]['channel_hash'])
				);

				if($mails)
					$channels[$x]['mail'] = intval($mails[0]['total']);
		

				$events = q("SELECT type, start, adjust FROM `event`
					WHERE `event`.`uid` = %d AND start < '%s' AND start > '%s' and `ignore` = 0
					ORDER BY `start` ASC ",
					intval($channels[$x]['channel_id']),
					dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now + 7 days')),
					dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now - 1 days'))
				);

				if($events) {
					$channels[$x]['all_events'] = count($events);

					if($channels[$x]['all_events']) {
						$str_now = datetime_convert('UTC', date_default_timezone_get(), 'now', 'Y-m-d');
						foreach($events as $e) {
							$bd = false;
							if($e['type'] === 'birthday') {
								$channels[$x]['birthdays'] ++;
								$bd = true;
							}
							else {
								$channels[$x]['events'] ++;
							}
							if(datetime_convert('UTC', ((intval($e['adjust'])) ? date_default_timezone_get() : 'UTC'), $e['start'], 'Y-m-d') === $str_now) {
								$channels[$x]['all_events_today'] ++;
								if($bd)
									$channels[$x]['birthdays_today'] ++;
								else
									$channels[$x]['events_today'] ++;
							}
						}
					}
				}
			}
		}
		
	    $r = q("select count(channel_id) as total from channel where channel_account_id = %d and not ( channel_pageflags & %d )>0",
			intval(get_account_id()),
			intval(PAGE_REMOVED)
		);
		$limit = account_service_class_fetch(get_account_id(),'total_identities');
		if($limit !== false) {
			$channel_usage_message = sprintf( t("You have created %1$.0f of %2$.0f allowed channels."), $r[0]['total'], $limit);
		}
		else {
			$channel_usage_message = '';
 		}
	}

	$links = array(
		array( 'new_channel', t('Create a new channel'), t('Create a new channel'))
	);

	$delegates = q("select * from abook left join xchan on abook_xchan = xchan_hash where 
		abook_channel = %d and (abook_their_perms & %d) > 0",
		intval(local_channel()),
		intval(PERMS_A_DELEGATE)
	);
	if(! $delegates)
		$delegates = null;

	if($delegates) {
		for($x = 0; $x < count($delegates); $x ++) {
				$delegates[$x]['link'] = 'magic?f=&dest=' . urlencode($delegates[$x]['xchan_url']) . '&delegate=' . urlencode($delegates[$x]['xchan_addr']);
		}
	}



	$o = replace_macros(get_markup_template('channels.tpl'), array(
		'$header'           => t('Channel Manager'),
		'$msg_selected'     => t('Current Channel'),
		'$selected'         => local_channel(),
		'$desc'             => t('Switch to one of your channels by selecting it.'),
		'$msg_default'      => t('Default Channel'),
		'$msg_make_default' => t('Make Default'),
		'$links'            => $links,
		'$all_channels'     => $channels,
		'$mail_format'      => t('%d new messages'),
		'$intros_format'    => t('%d new introductions'),
		'$channel_usage_message' => $channel_usage_message,
		'$delegate_header'  => t('Delegated Channels'),
		'$delegates'        => $delegates,

	));


	return $o;

}
