<?php

// This is the POST destination for most all locally posted
// text stuff. This function handles status, wall-to-wall status, 
// local comments, and remote coments - that are posted on this site 
// (as opposed to being delivered in a feed).
// All of these become an "item" which is our basic unit of 
// information. 

function item_post(&$a) {

	if((! local_user()) && (! remote_user()))
		return;

	require_once('include/security.php');

	$uid = local_user();

	$parent = ((x($_POST,'parent')) ? intval($_POST['parent']) : 0);

	$parent_item = null;

	if($parent) {
		$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
			intval($parent)
		);
		if(! count($r)) {
			notice( t('Unable to locate original post.') . EOL);
			goaway($a->get_baseurl() . "/" . $_POST['return'] );
		}
		$parent_item = $r[0];
	}

	$profile_uid = ((x($_POST,'profile_uid')) ? intval($_POST['profile_uid']) : 0);


	if(! can_write_wall($a,$profile_uid)) {
		notice( t('Permission denied.') . EOL) ;
		return;
	}

	$user = null;

	$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($profile_uid)
	);
	if(count($r))
		$user = $r[0];
	

	$str_group_allow   = perms2str($_POST['group_allow']);
	$str_contact_allow = perms2str($_POST['contact_allow']);
	$str_group_deny    = perms2str($_POST['group_deny']);
	$str_contact_deny  = perms2str($_POST['contact_deny']);

	$title             = notags(trim($_POST['title']));
	$body              = escape_tags(trim($_POST['body']));
	$location          = notags(trim($_POST['location']));
	$coord             = notags(trim($_POST['coord']));
	$verb              = notags(trim($_POST['verb']));

	if(! strlen($body)) {
		notice( t('Empty post discarded.') . EOL );
		goaway($a->get_baseurl() . "/" . $_POST['return'] );

	}

	// get contact info for poster

	$author = null;

	if(($_SESSION['uid']) && ($_SESSION['uid'] == $profile_uid)) {
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($_SESSION['uid'])
		);
	}
	else {
		if((x($_SESSION,'visitor_id')) && (intval($_SESSION['visitor_id']))) {
			$r = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
				intval($_SESSION['visitor_id'])
			);
		}
	}

	if(count($r)) {
		$author = $r[0];
		$contact_id = $author['id'];
	}

	// get contact info for owner
	
	if($profile_uid == $_SESSION['uid']) {
		$contact_record = $author;
	}
	else {
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($profile_uid)
		);
		if(count($r))
			$contact_record = $r[0];
	}

	$post_type = notags(trim($_POST['type']));

	if($post_type === 'net-comment') {
		if($parent_item !== null) {
			if($parent_item['type'] === 'remote') {
				$post_type = 'remote-comment';
			} 
			else {		
				$post_type = 'wall-comment';
			}
		}
	}

	$str_tags = '';
	$inform   = '';

	$tags = get_tags($body);


	if($tags) {
		foreach($tags as $tag) {
			if(strpos($tag,'@') === 0) {
				$name = substr($tag,1);
				if(strpos($name,'@')) {
					$newname = $name;
					$links = @webfinger($name);
					if(count($links)) {
						foreach($links as $link) {
							if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page')
                    			$profile = $link['@attributes']['href'];
							if($link['@attributes']['rel'] === 'salmon') {
								if(strlen($inform))
									$inform .= ',';
                    			$inform .= 'url:' . str_replace(',','%2c',$link['@attributes']['href']);
							}
						}
					}
				}
				else {
					$newname = $name;
					if(strstr($name,'_')) {
						$newname = str_replace('_',' ',$name);
						$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($newname),
							intval($profile_uid)
						);
					}
					else {
						$r = q("SELECT * FROM `contact` WHERE `nick` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($name),
							intval($profile_uid)
						);
					}
					if(count($r)) {
						$profile = $r[0]['url'];
						if(strlen($inform))
							$inform .= ',';
						$inform .= 'cid:' . $r[0]['id'];
					}
				}
				if($profile) {
					$body = str_replace($name,'[url=' . $profile . ']' . $newname	. '[/url]', $body);
					if(strlen($str_tags))
						$str_tags .= ',';
					$profile = str_replace(',','%2c',$profile);
					$str_tags .= '@[url=' . $profile . ']' . $newname	. '[/url]';
				}
			}
		}
	}

	$wall = 0;
	if($post_type === 'wall' || $post_type === 'wall-comment')
		$wall = 1;

	if(! strlen($verb))
		$verb = ACTIVITY_POST ;

	$gravity = (($parent) ? 6 : 0 );
 
	$notify_type = (($parent) ? 'comment-new' : 'wall-new' );

	$uri = item_new_uri($a->get_hostname(),$profile_uid);

	$r = q("INSERT INTO `item` (`uid`,`type`,`wall`,`gravity`,`contact-id`,`owner-name`,`owner-link`,`owner-avatar`, 
		`author-name`, `author-link`, `author-avatar`, `created`, `edited`, `changed`, `uri`, `title`, `body`, `location`, `coord`, 
		`tag`, `inform`, `verb`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid`)
		VALUES( %d, '%s', %d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )",
		intval($profile_uid),
		dbesc($post_type),
		intval($wall),
		intval($gravity),
		intval($contact_id),
		dbesc($contact_record['name']),
		dbesc($contact_record['url']),
		dbesc($contact_record['thumb']),
		dbesc($author['name']),
		dbesc($author['url']),
		dbesc($author['thumb']),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc($uri),
		dbesc($title),
		dbesc($body),
		dbesc($location),
		dbesc($coord),
		dbesc($str_tags),
		dbesc($inform),
		dbesc($verb),
		dbesc($str_contact_allow),
		dbesc($str_group_allow),
		dbesc($str_contact_deny),
		dbesc($str_group_deny)
	);
	$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' LIMIT 1",
		dbesc($uri));
	if(count($r)) {
		$post_id = $r[0]['id'];

		if($parent) {

			// This item is the last leaf and gets the comment box, clear any ancestors
			$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent` = %d ",
				dbesc(datetime_convert()),
				intval($parent)
			);

			// Inherit ACL's from the parent item.
			// TODO merge with subsequent UPDATE operation and save a db write 

			$r = q("UPDATE `item` SET `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s'
				WHERE `id` = %d LIMIT 1",
				dbesc($parent_item['allow_cid']),
				dbesc($parent_item['allow_gid']),
				dbesc($parent_item['deny_cid']),
				dbesc($parent_item['deny_gid']),
				intval($post_id)
			);

			// Send a notification email to the conversation owner, unless the owner is me and I wrote this item
			if(($user['notify-flags'] & NOTIFY_COMMENT) && ($contact_record != $author)) {
				require_once('bbcode.php');
				$from = $author['name'];
				$tpl = load_view_file('view/cmnt_received_eml.tpl');			
				$email_tpl = replace_macros($tpl, array(
					'$sitename' => $a->config['sitename'],
					'$siteurl' =>  $a->get_baseurl(),
					'$username' => $user['username'],
					'$email' => $user['email'],
					'$from' => $from,
					'$body' => strip_tags(bbcode($body))
				));

				$res = mail($user['email'], $from . t(" commented on your item at ") . $a->config['sitename'],
					$email_tpl,t("From: Administrator@") . $a->get_hostname() );
			}
		}
		else {
			$parent = $post_id;

			// let me know if somebody did a wall-to-wall post on my profile

			if(($user['notify-flags'] & NOTIFY_WALL) && ($contact_record != $author)) {
				require_once('bbcode.php');
				$from = $author['name'];
				$tpl = load_view_file('view/wall_received_eml.tpl');			
				$email_tpl = replace_macros($tpl, array(
					'$sitename' => $a->config['sitename'],
					'$siteurl' =>  $a->get_baseurl(),
					'$username' => $user['username'],
					'$email' => $user['email'],
					'$from' => $from,
					'$body' => strip_tags(bbcode($body))
				));

				$res = mail($user['email'], $from . t(" posted on your profile wall at ") . $a->config['sitename'],
					$email_tpl,t("From: Administrator@") . $a->get_hostname() );
			}
		}

		$r = q("UPDATE `item` SET `parent` = %d, `parent-uri` = '%s', `changed` = '%s', `last-child` = 1, `visible` = 1
			WHERE `id` = %d LIMIT 1",
			intval($parent),
			dbesc(($parent == $post_id) ? $uri : $parent_item['uri']),
			dbesc(datetime_convert()),
			intval($post_id)
		);

		// photo comments turn the corresponding item visible to the profile wall
		// This way we don't see every picture in your new photo album posted to your wall at once.
		// They will show up as people comment on them.

		if(! $parent_item['visible']) {
			$r = q("UPDATE `item` SET `visible` = 1 WHERE `id` = %d LIMIT 1",
				intval($parent_item['id'])
			);
		}
	}

	$php_path = ((strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
	$proc_debug = get_config('system','proc_debug');

	proc_close(proc_open("\"$php_path\" \"include/notifier.php\" \"$notify_type\" \"$post_id\" $proc_debug &",
		array(),$foo));

	goaway($a->get_baseurl() . "/" . $_POST['return'] );
	return; // NOTREACHED
}

function item_content(&$a) {

	if((! local_user()) && (! remote_user()))
		return;

	require_once('include/security.php');

	$uid = $_SESSION['uid'];

	if(($a->argc == 3) && ($a->argv[1] === 'drop') && intval($a->argv[2])) {

		// locate item to be deleted

		$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
			intval($a->argv[2])
		);

		if(! count($r)) {
			notice( t('Item not found.') . EOL);
			goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
		}
		$item = $r[0];

		// check if logged in user is either the author or owner of this item

		if(($_SESSION['visitor_id'] == $item['contact-id']) || ($_SESSION['uid'] == $item['uid'])) {

			// delete the item

			$r = q("UPDATE `item` SET `deleted` = 1, `body` = '', `edited` = '%s', `changed` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				intval($item['id'])
			);

			// If item is a link to a photo resource, nuke all the associated photos 
			// (visitors will not have photo resources)
			// This only applies to photos uploaded from the photos page. Photos inserted into a post do not
			// generate a resource-id and therefore aren't intimately linked to the item. 

			if(strlen($item['resource-id'])) {
				$q("DELETE FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d ",
					dbesc($item['resource-id']),
					intval($item['uid'])
				);
				// ignore the result
			}

			// If it's the parent of a comment thread, kill all the kids

			if($item['uri'] == $item['parent-uri']) {
				$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s', `body` = '' 
					WHERE `parent-uri` = '%s' AND `uid` = %d ",
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc($item['parent-uri']),
					intval($item['uid'])
				);
				// ignore the result
			}
			else {
				// ensure that last-child is set in case the comment that had it just got wiped.
				q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
					dbesc(datetime_convert()),
					dbesc($item['parent-uri']),
					intval($item['uid'])
				);
				// who is the last child now? 
				$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `uid` = %d ORDER BY `edited` DESC LIMIT 1",
					dbesc($item['parent-uri']),
					intval($item['uid'])
				);
				if(count($r)) {
					q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d LIMIT 1",
						intval($r[0]['id'])
					);
				}	
			}
			$drop_id = intval($item['id']);
			$php_path = ((strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
			$proc_debug = get_config('system','proc_debug');

			
			// send the notification upstream/downstream as the case may be

			proc_close(proc_open("\"$php_path\" \"include/notifier.php\" \"drop\" \"$drop_id\" $proc_debug &",
				array(), $foo));

			goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
			return; //NOTREACHED
		}
		else {
			notice( t('Permission denied.') . EOL);
			goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
			//NOTREACHED
		}
	}
}