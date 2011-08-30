<?php

require_once('include/Contact.php');

function contacts_init(&$a) {
	if(! local_user())
		return;

	$contact_id = 0;
	if(($a->argc == 2) && intval($a->argv[1])) {
		$contact_id = intval($a->argv[1]);
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d and `id` = %d LIMIT 1",
			intval(local_user()),
			intval($contact_id)
		);
		if(! count($r)) {
			$contact_id = 0;
		}
	}

	require_once('include/group.php');
	if(! x($a->page,'aside'))
		$a->page['aside'] = '';
	$a->page['aside'] .= group_side('contacts','group',false,0,$contact_id);

	$inv = '<div class="side-link" id="side-invite-link" ><a href="invite" >' . t("Invite Friends") . '</a></div>';

	if(get_config('system','invitation_only')) {
		$x = get_pconfig(local_user(),'system','invites_remaining');
		if($x || is_site_admin()) {
			$a->page['aside'] .= '<div class="side-link" id="side-invite-remain">' 
			. sprintf( tt('%d invitation available','%d invitations available',$x), $x) 
			. '</div>' . $inv;
		}
	}
	elseif($a->config['register_policy'] != REGISTER_CLOSED)
		$a->page['aside'] .= $inv;


	$a->page['aside'] .= '<div class="side-link" id="side-match-link"><a href="match" >' 
		. t('Find People With Shared Interests') . '</a></div>';

	$tpl = get_markup_template('follow.tpl');
	$a->page['aside'] .= replace_macros($tpl,array(
		'$label' => t('Connect/Follow'),
		'$hint' => t('Example: bob@example.com, http://example.com/barbara'),
		'$follow' => t('Follow')
	));



}

function contacts_post(&$a) {
	
	if(! local_user())
		return;

	$contact_id = intval($a->argv[1]);
	if(! $contact_id)
		return;

	$orig_record = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($contact_id),
		intval(local_user())
	);

	if(! count($orig_record)) {
		notice( t('Could not access contact record.') . EOL);
		goaway($a->get_baseurl() . '/contacts');
		return; // NOTREACHED
	}

	call_hooks('contact_edit_post', $_POST);

	$profile_id = intval($_POST['profile-assign']);
	if($profile_id) {
		$r = q("SELECT `id` FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($profile_id),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Could not locate selected profile.') . EOL);
			return;
		}
	}


	$priority = intval($_POST['poll']);
	if($priority > 5 || $priority < 0)
		$priority = 0;

	$info = escape_tags(trim($_POST['info']));

	$r = q("UPDATE `contact` SET `profile-id` = %d, `priority` = %d , `info` = '%s'
		WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($profile_id),
		intval($priority),
		dbesc($info),
		intval($contact_id),
		intval(local_user())
	);
	if($r)
		info( t('Contact updated.') . EOL);
	else
		notice( t('Failed to update contact record.') . EOL);
	return;

}



function contacts_content(&$a) {

	$sort_type = 0;
	$o = '';
	nav_set_selected('contacts');

	$_SESSION['return_url'] = $a->get_baseurl() . '/' . $a->cmd;

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($a->argc == 3) {

		$contact_id = intval($a->argv[1]);
		if(! $contact_id)
			return;

		$cmd = $a->argv[2];

		$orig_record = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d AND `self` = 0 LIMIT 1",
			intval($contact_id),
			intval(local_user())
		);

		if(! count($orig_record)) {
			notice( t('Could not access contact record.') . EOL);
			goaway($a->get_baseurl() . '/contacts');
			return; // NOTREACHED
		}

		if($cmd === 'update') {

			// pull feed and consume it, which should subscribe to the hub.
			proc_run('php',"include/poller.php","$contact_id");
			goaway($a->get_baseurl() . '/contacts/' . $contact_id);
			// NOTREACHED
		}

		if($cmd === 'block') {
			$blocked = (($orig_record[0]['blocked']) ? 0 : 1);
			$r = q("UPDATE `contact` SET `blocked` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($blocked),
				intval($contact_id),
				intval(local_user())
			);
			if($r) {
				//notice( t('Contact has been ') . (($blocked) ? t('blocked') : t('unblocked')) . EOL );
				info( (($blocked) ? t('Contact has been blocked') : t('Contact has been unblocked')) . EOL );
			}
			goaway($a->get_baseurl() . '/contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if($cmd === 'ignore') {
			$readonly = (($orig_record[0]['readonly']) ? 0 : 1);
			$r = q("UPDATE `contact` SET `readonly` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($readonly),
				intval($contact_id),
				intval(local_user())
			);
			if($r) {
				info( (($readonly) ? t('Contact has been ignored') : t('Contact has been unignored')) . EOL );
			}
			goaway($a->get_baseurl() . '/contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if($cmd === 'drop') {

			// create an unfollow slap

			if($orig_record[0]['network'] === NETWORK_OSTATUS) {
				$tpl = get_markup_template('follow_slap.tpl');
				$slap = replace_macros($tpl, array(
					'$name' => $a->user['username'],
					'$profile_page' => $a->get_baseurl() . '/profile/' . $a->user['nickname'],
					'$photo' => $a->contact['photo'],
					'$thumb' => $a->contact['thumb'],
					'$published' => datetime_convert('UTC','UTC', 'now', ATOM_TIME),
					'$item_id' => 'urn:X-dfrn:' . $a->get_hostname() . ':unfollow:' . random_string(),
					'$title' => '',
					'$type' => 'text',
					'$content' => t('stopped following'),
					'$nick' => $a->user['nickname'],
					'$verb' => 'http://ostatus.org/schema/1.0/unfollow', // ACTIVITY_UNFOLLOW,
					'$ostat_follow' => '' // '<as:verb>http://ostatus.org/schema/1.0/unfollow</as:verb>' . "\r\n"
				));

				if((x($orig_record[0],'notify')) && (strlen($orig_record[0]['notify']))) {
					require_once('include/salmon.php');
					slapper($a->user,$orig_record[0]['notify'],$slap);
				}
			}
			elseif($orig_record[0]['network'] === NETWORK_DIASPORA) {
				require_once('include/diaspora.php');
				diaspora_unshare($a->user,$orig_record[0]);
			}
			elseif($orig_record[0]['network'] === NETWORK_DFRN) {
				require_once('include/items.php');
				dfrn_deliver($a->user,$orig_record[0],'placeholder', 1);
			}

			contact_remove($orig_record[0]['id']);
			info( t('Contact has been removed.') . EOL );
			goaway($a->get_baseurl() . '/contacts');
			return; // NOTREACHED
		}
	}

	if(($a->argc == 2) && intval($a->argv[1])) {

		$contact_id = intval($a->argv[1]);
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d and `id` = %d LIMIT 1",
			intval(local_user()),
			intval($contact_id)
		);
		if(! count($r)) {
			notice( t('Contact not found.') . EOL);
			return;
		}

		$tpl = get_markup_template('contact_head.tpl');
		$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));

		require_once('include/contact_selectors.php');

		$tpl = get_markup_template("contact_edit.tpl");

		switch($r[0]['rel']) {
			case CONTACT_IS_FRIEND:
				$dir_icon = 'images/lrarrow.gif';
				$alt_text = t('Mutual Friendship');
				break;
			case CONTACT_IS_FOLLOWER;
				$dir_icon = 'images/larrow.gif';
				$alt_text = t('is a fan of yours');
				break;
	
			case CONTACT_IS_SHARING;
				$dir_icon = 'images/rarrow.gif';
				$alt_text = t('you are a fan of');
				break;
			default:
				break;
		}

		if(($r[0]['network'] === 'dfrn') && ($r[0]['rel'])) {
			$url = "redir/{$r[0]['id']}";
			$sparkle = ' class="sparkle" ';
		}
		else { 
			$url = $r[0]['url'];
			$sparkle = '';
		}

		$insecure = '<div id="profile-edit-insecure"><p><img src="images/unlock_icon.gif" alt="' . t('Privacy Unavailable') . '" />&nbsp;'
			. t('Private communications are not available for this contact.') . '</p></div>';

		$last_update = (($r[0]['last-update'] == '0000-00-00 00:00:00') 
				? t('Never') 
				: datetime_convert('UTC',date_default_timezone_get(),$r[0]['last-update'],'D, j M Y, g:i A'));

		if($r[0]['last-update'] !== '0000-00-00 00:00:00')
			$last_update .= ' ' . (($r[0]['last-update'] == $r[0]['success_update']) ? t("\x28Update was successful\x29") : t("\x28Update was not successful\x29"));

		$lblsuggest = (($r[0]['network'] === NETWORK_DFRN) 
			? '<div id="contact-suggest-wrapper"><a href="fsuggest/' . $r[0]['id'] . '" id="contact-suggest">' . t('Suggest friends') . '</a></div>' : '');

		$poll_enabled = (($r[0]['network'] !== NETWORK_DIASPORA) ? true : false);

		$nettype = '<div id="contact-edit-nettype">' . sprintf( t('Network type: %s'),network_to_name($r[0]['network'])) . '</div>';

		$o .= replace_macros($tpl,array(
			'$header' => t('Contact Editor'),
			'$submit' => t('Submit'),
			'$lbl_vis1' => t('Profile Visibility'),
			'$lbl_vis2' => sprintf( t('Please choose the profile you would like to display to %s when viewing your profile securely.'), $r[0]['name']),
			'$lbl_info1' => t('Contact Information / Notes'),
			'$lbl_rep1' => t('Online Reputation'),
			'$lbl_rep2' => t('Occasionally your friends may wish to inquire about this person\'s online legitimacy.'),
			'$lbl_rep3' => t('You may help them choose whether or not to interact with this person by providing a <em>reputation</em> to guide them.'),
			'$lbl_rep4' => t('Please take a moment to elaborate on this selection if you feel it could be helpful to others.'),
			'$visit' => sprintf( t('Visit %s\'s profile [%s]'),$r[0]['name'],$r[0]['url']),
			'$blockunblock' => t('Block/Unblock contact'),
			'$ignorecont' => t('Ignore contact'),
			'$altcrepair' => t('Repair contact URL settings'),
			'$lblcrepair' => t("Repair contact URL settings \x28WARNING: Advanced\x29"),
			'$lblrecent' => t('View conversations'),
			'$lblsuggest' => $lblsuggest,
			'$delete' => t('Delete contact'),
			'$nettype' => $nettype,
			'$poll_interval' => contact_poll_interval($r[0]['priority'],(! $poll_enabled)),
			'$poll_enabled' => $poll_enabled,
			'$lastupdtext' => t('Last updated: '),
			'$updpub' => t('Update public posts: '),
			'$last_update' => $last_update,
			'$udnow' => t('Update now'),
			'$profile_select' => contact_profile_assign($r[0]['profile-id'],(($r[0]['network'] !== 'dfrn') ? true : false)),
			'$contact_id' => $r[0]['id'],
			'$block_text' => (($r[0]['blocked']) ? t('Unblock this contact') : t('Block this contact') ),
			'$ignore_text' => (($r[0]['readonly']) ? t('Unignore this contact') : t('Ignore this contact') ),
			'$insecure' => (($r[0]['network'] !== NETWORK_DFRN && $r[0]['network'] !== NETWORK_MAIL && $r[0]['network'] !== NETWORK_FACEBOOK && $r[0]['network'] !== NETWORK_DIASPORA) ? $insecure : ''),
			'$info' => $r[0]['info'],
			'$blocked' => (($r[0]['blocked']) ? '<div id="block-message">' . t('Currently blocked') . '</div>' : ''),
			'$ignored' => (($r[0]['readonly']) ? '<div id="ignore-message">' . t('Currently ignored') . '</div>' : ''),
			'$photo' => $r[0]['photo'],
			'$name' => $r[0]['name'],
			'$dir_icon' => $dir_icon,
			'$alt_text' => $alt_text,
			'$sparkle' => $sparkle,
			'$url' => $url

		));

		$arr = array('contact' => $r[0],'output' => $o);

		call_hooks('contact_edit', $arr);

		return $arr['output'];

	}


	if(($a->argc == 2) && ($a->argv[1] === 'all'))
		$sql_extra = '';
	else
		$sql_extra = " AND `blocked` = 0 ";

	$search = ((x($_GET,'search')) ? notags(trim($_GET['search'])) : '');

	$tpl = get_markup_template("contacts-top.tpl");
	$o .= replace_macros($tpl,array(
		'$header' => t('Contacts'),
		'$hide_url' => ((strlen($sql_extra)) ? 'contacts/all' : 'contacts' ),
		'$hide_text' => ((strlen($sql_extra)) ? t('Show Blocked Connections') : t('Hide Blocked Connections')),
		'$search' => $search,
		'$finding' => (strlen($search) ? '<h4>' . t('Finding: ') . "'" . $search . "'" . '</h4>' : ""),
		'$submit' => t('Find'),
		'$cmd' => $a->cmd


	)); 

	if($search)
		$search = dbesc($search.'*');
	$sql_extra .= ((strlen($search)) ? " AND MATCH `name` AGAINST ('$search' IN BOOLEAN MODE) " : "");

	$sql_extra2 = ((($sort_type > 0) && ($sort_type <= CONTACT_IS_FRIEND)) ? sprintf(" AND `rel` = %d ",intval($sort_type)) : ''); 

	
	$r = q("SELECT COUNT(*) AS `total` FROM `contact` 
		WHERE `uid` = %d AND `pending` = 0 $sql_extra $sql_extra2 ",
		intval($_SESSION['uid']));
	if(count($r))
		$a->set_pager_total($r[0]['total']);

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `pending` = 0 $sql_extra $sql_extra2 ORDER BY `name` ASC LIMIT %d , %d ",
		intval($_SESSION['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	if(count($r)) {

		$tpl = get_markup_template("contact_template.tpl");

		foreach($r as $rr) {
			if($rr['self'])
				continue;

			switch($rr['rel']) {
				case CONTACT_IS_FRIEND:
					$dir_icon = 'images/lrarrow.gif';
					$alt_text = t('Mutual Friendship');
					break;
				case  CONTACT_IS_FOLLOWER;
					$dir_icon = 'images/larrow.gif';
					$alt_text = t('is a fan of yours');
					break;
				case CONTACT_IS_SHARING;
					$dir_icon = 'images/rarrow.gif';
					$alt_text = t('you are a fan of');
					break;
				default:
					break;
			}
			if(($rr['network'] === 'dfrn') && ($rr['rel'])) {
				$url = "redir/{$rr['id']}";
				$sparkle = ' class="sparkle" ';
			}
			else { 
				$url = $rr['url'];
				$sparkle = '';
			}


			$o .= replace_macros($tpl, array(
				'$img_hover' => sprintf( t('Visit %s\'s profile [%s]'),$rr['name'],$rr['url']),
				'$edit_hover' => t('Edit contact'),
				'$contact_photo_menu' => contact_photo_menu($rr),
				'$id' => $rr['id'],
				'$alt_text' => $alt_text,
				'$dir_icon' => $dir_icon,
				'$thumb' => $rr['thumb'], 
				'$name' => $rr['name'],
				'$username' => $rr['name'],
				'$sparkle' => $sparkle,
				'$url' => $url
			));
		}

		$o .= '<div id="contact-edit-end"></div>';

	}
	$o .= paginate($a);
	return $o;
}
