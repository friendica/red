<?php

function contacts_init(&$a) {
	require_once('include/group.php');
	$a->page['aside'] .= group_side();

	if($a->config['register_policy'] != REGISTER_CLOSED)
		$a->page['aside'] .= '<div class="side-invite-link-wrapper" id="side-invite-link-wrapper" ><a href="invite" class="side-invite-link" id="side-invite-link">' . t("Invite Friends") . '</a></div>';
}

function contacts_post(&$a) {

	
	if(! local_user())
		return;

	$contact_id = intval($a->argv[1]);
	if(! $contact_id)
		return;

	$orig_record = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($contact_id),
		intval($_SESSION['uid'])
	);

	if(! count($orig_record)) {
		notice("Could not access contact record." . EOL);
		goaway($a->get_baseurl() . '/contacts');
		return; // NOTREACHED
	}

	$profile_id = intval($_POST['profile-assign']);
	if($profile_id) {
		$r = q("SELECT `id` FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($profile_id),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			notice( t('Could not locate selected profile.') . EOL);
			return;
		}
	}
	$priority = intval($_POST['priority']);
	if($priority > 5 || $priority < 0)
		$priority = 0;

	$rating = intval($_POST['reputation']);
	if($rating > 5 || $rating < 0)
		$rating = 0;

	$reason = notags(trim($_POST['reason']));

	$r = q("UPDATE `contact` SET `profile-id` = %d, `priority` = %d , `rating` = %d, `reason` = '%s'
		WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($profile_id),
		intval($priority),
		intval($rating),
		dbesc($reason),
		intval($contact_id),
		intval($_SESSION['uid'])
	);
	if($r)
		notice( t('Contact updated.') . EOL);
	else
		notice( t('Failed to update contact record.') . EOL);
	return;

}



function contacts_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($a->argc == 3) {

		$contact_id = intval($a->argv[1]);
		if(! $contact_id)
			return;

		$cmd = $a->argv[2];

		$orig_record = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($contact_id),
			intval($_SESSION['uid'])
		);

		if(! count($orig_record)) {
			notice( t('Could not access contact record.') . EOL);
			goaway($a->get_baseurl() . '/contacts');
			return; // NOTREACHED
		}


		if($cmd == 'block') {
			$blocked = (($orig_record[0]['blocked']) ? 0 : 1);
			$r = q("UPDATE `contact` SET `blocked` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
					intval($blocked),
					intval($contact_id),
					intval($_SESSION['uid'])
			);
			if($r) {
				$msg = t('Contact has been ') . (($blocked) ? t('blocked') : t('unblocked')) . EOL ;
				notice($msg);
			}
			goaway($a->get_baseurl() ."/contacts/$contact_id");
			return; // NOTREACHED
		}

		if($cmd == 'ignore') {
			$readonly = (($orig_record[0]['readonly']) ? 0 : 1);
			$r = q("UPDATE `contact` SET `readonly` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
					intval($readonly),
					intval($contact_id),
					intval($_SESSION['uid'])
			);
			if($r) {
				$msg = t('Contact has been ') . (($readonly) ? t('ignored') : t('unignored')) . EOL ;
				notice($msg);
			}
			goaway($a->get_baseurl() ."/contacts/$contact_id");
			return; // NOTREACHED
		}

		if($cmd == 'drop') {
			$r = q("DELETE FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($contact_id),
				intval($_SESSION['uid'])
			);

			q("DELETE FROM `item` WHERE `contact-id` = %d AND `uid` = %d ",
					intval($contact_id),
					intval($_SESSION['uid'])
			);
			q("DELETE FROM `photo` WHERE `contact-id` = %d AND `uid` = %d ",
 
					intval($contact_id),
					intval($_SESSION['uid'])
			);
	
			notice( t('Contact has been removed.') . EOL );
			goaway($a->get_baseurl() . '/contacts');
			return; // NOTREACHED
		}
	}

	if(($a->argc == 2) && intval($a->argv[1])) {

		$contact_id = intval($a->argv[1]);
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d and `id` = %d LIMIT 1",
			$_SESSION['uid'],
			intval($contact_id)
		);
		if(! count($r)) {
			notice( t('Contact not found.') . EOL);
			return;
		}

		require_once('view/contact_selectors.php');

		$tpl = file_get_contents("view/contact_edit.tpl");

		$direction = '';
		if(strlen($r[0]['issued-id'])) {
			if(strlen($r[0]['dfrn-id'])) {
				$direction = DIRECTION_BOTH;
				$dir_icon = 'images/lrarrow.gif';
				$alt_text = t('Mutual Friendship');
			}
			else {
				$direction = DIRECTION_IN;
				$dir_icon = 'images/larrow.gif';
				$alt_text = t('is a fan of yours');
			}
		}
		else {
			$direction = DIRECTION_OUT;
			$dir_icon = 'images/rarrow.gif';
			$alt_text = t('you are a fan of');
		}

		$o .= replace_macros($tpl,array(
			'$poll_interval' => contact_poll_interval($r[0]['priority']),
			'$last_update' => (($r[0]['last-update'] == '0000-00-00 00:00:00') 
				? t('Never') 
				: datetime_convert('UTC',date_default_timezone_get(),$r[0]['last-update'],'D, j M Y, g:i A')),
			'$profile_select' => contact_profile_assign($r[0]['profile-id']),
			'$contact_id' => $r[0]['id'],
			'$block_text' => (($r[0]['blocked']) ? t('Unblock this contact') : t('Block this contact') ),
			'$ignore_text' => (($r[0]['readonly']) ? t('Unignore this contact') : t('Ignore this contact') ),
			'$blocked' => (($r[0]['blocked']) ? '<div id="block-message">' . t('Currently blocked') . '</div>' : ''),
			'$ignored' => (($r[0]['readonly']) ? '<div id="ignore-message">' . t('Currently ignored') . '</div>' : ''),
			'$rating' => contact_reputation($r[0]['rating']),
			'$reason' => $r[0]['reason'],
			'$groups' => '', // group_selector(),
			'$photo' => $r[0]['photo'],
			'$name' => $r[0]['name'],
			'$dir_icon' => $dir_icon,
			'$alt_text' => $alt_text,
			'$url' => (($direction != DIRECTION_IN) ? "redir/{$r[0]['id']}" : $r[0]['url'] )

		));

		return $o;

	}


	if(($a->argc == 2) && ($a->argv[1] == 'all'))
		$sql_extra = '';
	else
		$sql_extra = " AND `blocked` = 0 ";

	$search = ((x($_GET,'search')) ? notags(trim($_GET['search'])) : '');

	$tpl = file_get_contents("view/contacts-top.tpl");
	$o .= replace_macros($tpl,array(
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


	switch($sort_type) {
		case DIRECTION_BOTH :
			$sql_extra2 = " AND `dfrn-id` != '' AND `issued-id` != '' ";
			break;
		case DIRECTION_IN :
			$sql_extra2 = " AND `dfrn-id` = '' AND `issued-id` != '' ";
			break;
		case DIRECTION_OUT :
			$sql_extra2 = " AND `dfrn-id` != '' AND `issued-id` = '' ";
			break;
		case DIRECTION_NONE :
		default:
			$sql_extra2 = '';
			break;
	}

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

		$tpl = file_get_contents("view/contact_template.tpl");

		foreach($r as $rr) {
			if($rr['self'])
				continue;
			$direction = '';
			if(strlen($rr['issued-id'])) {
				if(strlen($rr['dfrn-id'])) {
					$direction = DIRECTION_BOTH;
					$dir_icon = 'images/lrarrow.gif';
					$alt_text = t('Mutual Friendship');
				}
				else {
					$direction = DIRECTION_IN;
					$dir_icon = 'images/larrow.gif';
					$alt_text = t('is a fan of yours');
				}
			}
			else {
				$direction = DIRECTION_OUT;
				$dir_icon = 'images/rarrow.gif';
				$alt_text = t('you are a fan of');
			}

			$o .= replace_macros($tpl, array(
				'$img_hover' => t('Visit ') . $rr['name'] . t('\'s profile'),
				'$edit_hover' => t('Edit contact'),
				'$id' => $rr['id'],
				'$alt_text' => $alt_text,
				'$dir_icon' => $dir_icon,
				'$thumb' => $rr['thumb'], 
				'$name' => $rr['name'],
				'$url' => (($direction != DIRECTION_IN) ? "redir/{$rr['id']}" : $rr['url'] )
			));
		}
		$o .= '<div id="contact-edit-end"></div>';

	}
	$o .= paginate($a);
	return $o;
}