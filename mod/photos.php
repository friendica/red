<?php
require_once('include/photo/photo_driver.php');
require_once('include/photos.php');
require_once('include/items.php');
require_once('include/acl_selectors.php');
require_once('include/bbcode.php');
require_once('include/security.php');
require_once('include/Contact.php');


function photos_init(&$a) {


	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	$o = '';

	if(argc() > 1) {
		$nick = argv(1);

		profile_load($a,$nick);

		$channelx = channelx_by_nick($nick);

		if(! $channelx)
			return;

		$a->data['channel'] = $channelx[0];

		$observer = $a->get_observer();
		$a->data['observer'] = $observer;

		$observer_xchan = (($observer) ? $observer['xchan_hash'] : '');

		head_set_icon($a->data['channel']['xchan_photo_s']);

		$a->page['htmlhead'] .= "<script> var ispublic = '" . t('everybody') . "'; var profile_uid = " . (($a->data['channel']) ? $a->data['channel']['channel_id'] : 0) . "; </script>" ;

	}

	return;
}



function photos_post(&$a) {

	logger('mod-photos: photos_post: begin' , LOGGER_DEBUG);


	logger('mod_photos: REQUEST ' . print_r($_REQUEST,true), LOGGER_DATA);
	logger('mod_photos: FILES '   . print_r($_FILES,true), LOGGER_DATA);

	$ph = photo_factory('');

	$phototypes = $ph->supportedTypes();

	$can_post  = false;

	$page_owner_uid = $a->data['channel']['channel_id'];

	if(perm_is_allowed($page_owner_uid,get_observer_hash(),'post_photos'))
		$can_post = true;

	if(! $can_post) {
		notice( t('Permission denied.') . EOL );
		if(is_ajax())
			killme();
		return;
	}

	$s = abook_self($page_owner_uid);

	if(! $s) {
		notice( t('Page owner information could not be retrieved.') . EOL);
		logger('mod_photos: post: unable to locate contact record for page owner. uid=' . $page_owner_uid);
		if(is_ajax())
			killme();
		return;
	}

	$owner_record = $s[0];	


	if((argc() > 3) && (argv(2) === 'album')) {

		$album = hex2bin(argv(3));

		if($album === t('Profile Photos')) {
			// not allowed
			goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		}

		if(! photos_album_exists($page_owner_uid,$album)) {
			notice( t('Album not found.') . EOL);
			goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		}


		/*
		 * RENAME photo album
		 */

		$newalbum = notags(trim($_REQUEST['albumname']));
		if($newalbum != $album) {
			$x = photos_album_rename($page_owner_uid,$album,$newalbum);
			if($x) {
				$newurl = str_replace(bin2hex($album),bin2hex($newalbum),$_SESSION['photo_return']);
				goaway($a->get_baseurl() . '/' . $newurl);
			}
		}

		/*
		 * DELETE photo album and all its photos
		 */

		if($_REQUEST['dropalbum'] == t('Delete Album')) {

			$res = array();

			// get the list of photos we are about to delete

			if(remote_user() && (! local_user())) {
				$str = photos_album_get_db_idstr($page_owner_uid,$album,remote_user());
			}
			elseif(local_user()) {
				$str = photos_album_get_db_idstr(local_user(),$album);
			}
			else {
				$str = null;
			}
			if(! $str) {
				goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
			}

			$r = q("select id, item_restrict from item where resource_id in ( $str ) and resource_type = 'photo' and uid = %d",
				intval($page_owner_uid)
			);
			if($r) {
				foreach($r as $i) {
					drop_item($i['id'],false);
					if(! $item_restrict)
						proc_run('php','include/notifier.php','drop',$i['id']);
				}
			}

			// remove the associated photos in case they weren't attached to an item

			q("delete from photo where resource_id in ( $str ) and uid = %d",
				intval($page_owner_uid)
			);
		}
		
		goaway($a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address']);
	}

	if((argc() > 2) && (x($_REQUEST,'delete')) && ($_REQUEST['delete'] === t('Delete Photo'))) {

		// same as above but remove single photo

		$ob_hash = get_observer_hash();
		if(! $ob_hash)
			goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

		$r = q("SELECT `id`, `resource_id` FROM `photo` WHERE ( xchan = '%s' or `uid` = %d ) AND `resource_id` = '%s' LIMIT 1",
			dbesc($ob_hash),
			intval(local_user()),
			dbesc($a->argv[2])
		);

		if($r) {
			q("DELETE FROM `photo` WHERE `uid` = %d AND `resource_id` = '%s'",
				intval($page_owner_uid),
				dbesc($r[0]['resource_id'])
			);
			$i = q("SELECT * FROM `item` WHERE `resource_id` = '%s' AND resource_type = 'photo' and `uid` = %d LIMIT 1",
				dbesc($r[0]['resource_id']),
				intval($page_owner_uid)
			);
			if(count($i)) {
				q("UPDATE `item` SET item_restrict = (item_restrict | %d), `edited` = '%s', `changed` = '%s' WHERE `parent_mid` = '%s' AND `uid` = %d",
					intval(ITEM_DELETED),
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc($i[0]['mid']),
					intval($page_owner_uid)
				);

				$url = $a->get_baseurl();
				$drop_id = intval($i[0]['id']);

				if($i[0]['visible'])
					proc_run('php',"include/notifier.php","drop","$drop_id");
			}
		}

		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
	}

	if(($a->argc > 2) && ((x($_POST,'desc') !== false) || (x($_POST,'newtag') !== false)) || (x($_POST,'albname') !== false)) {


		$desc        = ((x($_POST,'desc'))    ? notags(trim($_POST['desc']))    : '');
		$rawtags     = ((x($_POST,'newtag'))  ? notags(trim($_POST['newtag']))  : '');
		$item_id     = ((x($_POST,'item_id')) ? intval($_POST['item_id'])       : 0);
		$albname     = ((x($_POST,'albname')) ? notags(trim($_POST['albname'])) : '');
		$str_group_allow   = perms2str($_POST['group_allow']);
		$str_contact_allow = perms2str($_POST['contact_allow']);
		$str_group_deny    = perms2str($_POST['group_deny']);
		$str_contact_deny  = perms2str($_POST['contact_deny']);

		$resource_id = $a->argv[2];

		if(! strlen($albname))
			$albname = datetime_convert('UTC',date_default_timezone_get(),'now', 'Y');


		if((x($_POST,'rotate') !== false) && 
		   ( (intval($_POST['rotate']) == 1) || (intval($_POST['rotate']) == 2) )) {
			logger('rotate');

			$r = q("select * from photo where `resource_id` = '%s' and uid = %d and scale = 0 limit 1",
				dbesc($resource_id),
				intval($page_owner_uid)
			);
			if(count($r)) {
				$ph = photo_factory($r[0]['data'], $r[0]['type']);
				if($ph->is_valid()) {
					$rotate_deg = ( (intval($_POST['rotate']) == 1) ? 270 : 90 );
					$ph->rotate($rotate_deg);

					$width  = $ph->getWidth();
					$height = $ph->getHeight();

					$x = q("update photo set data = '%s', height = %d, width = %d where `resource_id` = '%s' and uid = %d and scale = 0 limit 1",
						dbesc($ph->imageString()),
						intval($height),
						intval($width),
						dbesc($resource_id),
						intval($page_owner_uid)
					);

					if($width > 640 || $height > 640) {
						$ph->scaleImage(640);
						$width  = $ph->getWidth();
						$height = $ph->getHeight();
		
						$x = q("update photo set data = '%s', height = %d, width = %d where `resource_id` = '%s' and uid = %d and scale = 1 limit 1",
							dbesc($ph->imageString()),
							intval($height),
							intval($width),
							dbesc($resource_id),
							intval($page_owner_uid)
						);
					}

					if($width > 320 || $height > 320) {
						$ph->scaleImage(320);
						$width  = $ph->getWidth();
						$height = $ph->getHeight();

						$x = q("update photo set data = '%s', height = %d, width = %d where `resource_id` = '%s' and uid = %d and scale = 2 limit 1",
							dbesc($ph->imageString()),
							intval($height),
							intval($width),
							dbesc($resource_id),
							intval($page_owner_uid)
						);
					}	
				}
			}
		}

		$p = q("SELECT * FROM `photo` WHERE `resource_id` = '%s' AND `uid` = %d and ( photo_flags = %d or photo_flags = %d ) ORDER BY `scale` DESC",
			dbesc($resource_id),
			intval($page_owner_uid),
			intval(PHOTO_NORMAL),
			intval(PHOTO_PROFILE)
		);
		if(count($p)) {
			$ext = $phototypes[$p[0]['type']];
			$r = q("UPDATE `photo` SET `description` = '%s', `album` = '%s', `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s' WHERE `resource_id` = '%s' AND `uid` = %d",
				dbesc($desc),
				dbesc($albname),
				dbesc($str_contact_allow),
				dbesc($str_group_allow),
				dbesc($str_contact_deny),
				dbesc($str_group_deny),
				dbesc($resource_id),
				intval($page_owner_uid)
			);
		}

		$item_private = (($str_contact_allow || $str_group_allow || $str_contact_deny || $str_group_deny) ? true : false);


		/* Don't make the item visible if the only change was the album name */

		$visibility = 0;
		if($p[0]['description'] !== $desc || strlen($rawtags))
			$visibility = 1;

		if(! $item_id) {
			$item_id = photos_create_item($a->data['channel'],get_observer_hash(),$p[0],$visibility);

		}

		if($item_id) {
			$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($item_id),
				intval($page_owner_uid)
			);
		}
		if($r) {
			$old_tag    = $r[0]['tag'];
			$old_inform = $r[0]['inform'];
		}

		// make sure the linked item has the same permissions as the photo regardless of any other changes
		$x = q("update item set allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', item_private = %d
			where id = %d limit 1",
				dbesc($str_contact_allow),
				dbesc($str_group_allow),
				dbesc($str_contact_deny),
				dbesc($str_group_deny),
				intval($item_private),
				intval($item_id)
		);


		if(strlen($rawtags)) {

			$str_tags = '';
			$inform   = '';

			// if the new tag doesn't have a namespace specifier (@foo or #foo) give it a hashtag

			$x = substr($rawtags,0,1);
			if($x !== '@' && $x !== '#')
				$rawtags = '#' . $rawtags;

			$taginfo = array();
			$tags = get_tags($rawtags);

			if(count($tags)) {
				foreach($tags as $tag) {
					if(isset($profile))
						unset($profile);
					if(strpos($tag,'@') === 0) {
						$name = substr($tag,1);
						if((strpos($name,'@')) || (strpos($name,'http://'))) {
							$newname = $name;
							$links = @lrdd($name);
							if(count($links)) {
								foreach($links as $link) {
									if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page')
        		            			$profile = $link['@attributes']['href'];
									if($link['@attributes']['rel'] === 'salmon') {
										$salmon = '$url:' . str_replace(',','%sc',$link['@attributes']['href']);
										if(strlen($inform))
											$inform .= ',';
                    					$inform .= $salmon;
									}
								}
							}
							$taginfo[] = array($newname,$profile,$salmon);
						}
						else {
							$newname = $name;
							$alias = '';
							$tagcid = 0;
							if(strrpos($newname,'+'))
								$tagcid = intval(substr($newname,strrpos($newname,'+') + 1));

							if($tagcid) {
								$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
									intval($tagcid),
									intval($profile_uid)
								);
							}
							else {
								$newname = str_replace('_',' ',$name);

								//select someone from this user's contacts by name
								$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
										dbesc($newname),
										intval($page_owner_uid)
								);

								if(! $r) {
									//select someone by attag or nick and the name passed in
									$r = q("SELECT * FROM `contact` WHERE `attag` = '%s' OR `nick` = '%s' AND `uid` = %d ORDER BY `attag` DESC LIMIT 1",
											dbesc($name),
											dbesc($name),
											intval($page_owner_uid)
									);
								}
							}
/*							elseif(strstr($name,'_') || strstr($name,' ')) {
								$newname = str_replace('_',' ',$name);
								$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
									dbesc($newname),
									intval($page_owner_uid)
								);
							}
							else {
								$r = q("SELECT * FROM `contact` WHERE `attag` = '%s' OR `nick` = '%s' AND `uid` = %d ORDER BY `attag` DESC LIMIT 1",
									dbesc($name),
									dbesc($name),
									intval($page_owner_uid)
								);
							}*/
							if(count($r)) {
								$newname = $r[0]['name'];
								$profile = $r[0]['url'];
								$notify = 'cid:' . $r[0]['id'];
								if(strlen($inform))
									$inform .= ',';
								$inform .= $notify;
							}
						}
						if($profile) {
							if(substr($notify,0,4) === 'cid:')
								$taginfo[] = array($newname,$profile,$notify,$r[0],'@[zrl=' . str_replace(',','%2c',$profile) . ']' . $newname	. '[/zrl]');
							else
								$taginfo[] = array($newname,$profile,$notify,null,$str_tags .= '@[url=' . $profile . ']' . $newname	. '[/url]');
							if(strlen($str_tags))
								$str_tags .= ',';
							$profile = str_replace(',','%2c',$profile);
							$str_tags .= '@[zrl=' . $profile . ']' . $newname	. '[/zrl]';
						}
					}
				}
			}

			$newtag = $old_tag;
			if(strlen($newtag) && strlen($str_tags)) 
				$newtag .= ',';
			$newtag .= $str_tags;

			$newinform = $old_inform;
			if(strlen($newinform) && strlen($inform))
				$newinform .= ',';
			$newinform .= $inform;
//FIXME - inform is gone
//			$r = q("UPDATE `item` SET `tag` = '%s', `inform` = '%s', `edited` = '%s', `changed` = '%s' WHERE `id` = %d AND `uid` = %d LIMIT 1",
//				dbesc($newtag),
//				dbesc($newinform),
//				dbesc(datetime_convert()),
//				dbesc(datetime_convert()),
//				intval($item_id),
//				intval($page_owner_uid)
//			);

			$best = 0;
			foreach($p as $scales) {
				if(intval($scales['scale']) == 2) {
					$best = 2;
					break;
				}
				if(intval($scales['scale']) == 4) {
					$best = 4;
					break;
				}
			}

			if(count($taginfo)) {
				foreach($taginfo as $tagged) {
		
					$mid = item_message_id();

					$arr = array();

					$arr['uid']           = $page_owner_uid;
					$arr['mid']           = $mid;
					$arr['parent_mid']    = $mid;
					$arr['type']          = 'activity';
					$arr['wall']          = 1;
					$arr['contact-id']    = $owner_record['id'];
					$arr['owner-name']    = $owner_record['name'];
					$arr['owner-link']    = $owner_record['url'];
					$arr['owner-avatar']  = $owner_record['thumb'];
					$arr['author-name']   = $owner_record['name'];
					$arr['author-link']   = $owner_record['url'];
					$arr['author-avatar'] = $owner_record['thumb'];
					$arr['title']         = '';
					$arr['allow_cid']     = $p[0]['allow_cid'];
					$arr['allow_gid']     = $p[0]['allow_gid'];
					$arr['deny_cid']      = $p[0]['deny_cid'];
					$arr['deny_gid']      = $p[0]['deny_gid'];
					$arr['visible']       = 1;
					$arr['verb']          = ACTIVITY_TAG;
					$arr['obj_type']   = ACTIVITY_OBJ_PERSON;
					$arr['tgt_type']   = ACTIVITY_OBJ_PHOTO;
					$arr['tag']           = $tagged[4];
					$arr['inform']        = $tagged[2];
					$arr['origin']        = 1;
					$arr['body']          = sprintf( t('%1$s was tagged in %2$s by %3$s'), '[zrl=' . $tagged[1] . ']' . $tagged[0] . '[/zrl]', '[zrl=' . $a->get_baseurl() . '/photos/' . $owner_record['nickname'] . '/image/' . $p[0]['resource-id'] . ']' . t('a photo') . '[/zrl]', '[zrl=' . $owner_record['url'] . ']' . $owner_record['name'] . '[/zrl]') ;

					$arr['body'] .= "\n\n" . '[zrl=' . $a->get_baseurl() . '/photos/' . $owner_record['nickname'] . '/image/' . $p[0]['resource_id'] . ']' . '[zmg]' . $a->get_baseurl() . "/photo/" . $p[0]['resource_id'] . '-' . $best . '.' . $ext . '[/zmg][/zrl]' . "\n" ;

					$arr['object'] = '<object><type>' . ACTIVITY_OBJ_PERSON . '</type><title>' . $tagged[0] . '</title><id>' . $tagged[1] . '/' . $tagged[0] . '</id>';
					$arr['object'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $tagged[1] . '" />' . "\n");
					if($tagged[3])
						$arr['object'] .= xmlify('<link rel="photo" type="'.$p[0]['type'].'" href="' . $tagged[3]['photo'] . '" />' . "\n");
					$arr['object'] .= '</link></object>' . "\n";

					$arr['target'] = '<target><type>' . ACTIVITY_OBJ_PHOTO . '</type><title>' . $p[0]['description'] . '</title><id>'
						. $a->get_baseurl() . '/photos/' . $owner_record['nickname'] . '/image/' . $p[0]['resource_id'] . '</id>';
					$arr['target'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $a->get_baseurl() . '/photos/' . $owner_record['nickname'] . '/image/' . $p[0]['resource_id'] . '" />' . "\n" . '<link rel="preview" type="'.$p[0]['type'].'" href="' . $a->get_baseurl() . "/photo/" . $p[0]['resource_id'] . '-' . $best . '.' . $ext . '" />') . '</link></target>';

					$post = item_store($arr);
					$item_id = $post['item_id'];

					if($item_id) {
						q("UPDATE `item` SET `plink` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
							dbesc($a->get_baseurl() . '/display/' . $owner_record['nickname'] . '/' . $item_id),
							intval($page_owner_uid),
							intval($item_id)
						);

						proc_run('php',"include/notifier.php","tag","$item_id");
					}
				}

			}

		}
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		return; // NOTREACHED
	}


	/**
	 * default post action - upload a photo
	 */

	$_REQUEST['source'] = 'photos';

	$r = photo_upload($a->channel,$a->get_observer(), $_REQUEST);
	if(! $r['success']) {
		notice($r['message'] . EOL);
	}		

	goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

}



function photos_content(&$a) {

	// URLs:
	// photos/name
	// photos/name/upload
	// photos/name/upload/xxxxx (xxxxx is album name)
	// photos/name/album/xxxxx
	// photos/name/album/xxxxx/edit
	// photos/name/image/xxxxx
	// photos/name/image/xxxxx/edit


	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}
	
	
	require_once('include/bbcode.php');
	require_once('include/security.php');
	require_once('include/conversation.php');

	if(! x($a->data,'channel')) {
		notice( t('No photos selected') . EOL );
		return;
	}

	$ph = photo_factory('');
	$phototypes = $ph->supportedTypes();

	$_SESSION['photo_return'] = $a->cmd;

	//
	// Parse arguments 
	//

	$can_comment = perm_is_allowed($a->profile['profile_uid'],get_observer_hash(),'post_comments');

	if(argc() > 3) {
		$datatype = argv(2);
		$datum = argv(3);
	}
	elseif((argc() > 2) && (argv(2) === 'upload'))
		$datatype = 'upload';
	else
		$datatype = 'summary';

	if(argc() > 4)
		$cmd = argv(4);
	else
		$cmd = 'view';

	//
	// Setup permissions structures
	//

	$can_post       = false;
	$visitor        = 0;


	$owner_uid = $a->data['channel']['channel_id'];
	$owner_aid = $a->data['channel']['channel_account_id'];

	$observer = $a->get_observer();

	$can_post = perm_is_allowed($owner_uid,$observer['xchan_hash'],'post_photos');
	$can_view = perm_is_allowed($owner_uid,$observer['xchan_hash'],'view_photos');


	if(! $can_view) {
		notice( t('Access to this item is restricted.') . EOL);
		return;
	}

	$sql_extra = permissions_sql($owner_uid);

	$o = "";

	// tabs

	$_is_owner = (local_user() && (local_user() == $owner_uid));
	$o .= profile_tabs($a,$_is_owner, $a->data['channel']['channel_address']);	

	//
	// dispatch request
	//

	/**
	 * Display upload form
	 */

	if($datatype === 'upload') {
		if(! ($can_post)) {
			notice( t('Permission denied.'));
			return;
		}

		$selname = (($datum) ? hex2bin($datum) : '');
		$albumselect = '<select id="photos-upload-album-select" name="album" size="4">';
		
		$albumselect .= '<option value="" ' . ((! $selname) ? ' selected="selected" ' : '') . '>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
		if(count($a->data['albums'])) {
			foreach($a->data['albums'] as $album) {
				if($album['text'] === '') 
					continue;
				$selected = (($selname === $album['text']) ? ' selected="selected" ' : '');
				$albumselect .= '<option value="' . $album['text'] . '"' . $selected . '>' . $album['text'] . '</option>';
			}
		}

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$albumselect .= '</select>';

		$uploader = '';

		$ret = array('post_url' => $a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'],
				'addon_text' => $uploader,
				'default_upload' => true);


		call_hooks('photo_upload_form',$ret);

		$default_upload = '<input id="photos-upload-choose" type="file" name="userfile" /> 	<div class="photos-upload-submit-wrapper" >
		<input type="submit" name="submit" value="' . t('Submit') . '" id="photos-upload-submit" /> </div>';

		/* Show space usage */

		$r = q("select sum(size) as total from photo where uid = %d and scale = 0 ",
			intval($a->data['channel']['channel_id'])
		);


		$limit = service_class_fetch($a->data['channel']['channel_id'],'photo_upload_limit');
		if($limit !== false) {
			$usage_message = sprintf( t("You have used %1$.2f Mbytes of %2$.2f Mbytes photo storage."), $r[0]['total'] / 1024000, $limit / 1024000 );
		}
		else {
			$usage_message = sprintf( t('You have used %1$.2f Mbytes of photo storage.'), $r[0]['total'] / 1024000 );
 		}

		if($_is_owner) {
			$channel = $a->get_channel();

			$channel_acl = array(
				'allow_cid' => $channel['channel_allow_cid'], 
				'allow_gid' => $channel['channel_allow_gid'], 
				'deny_cid' => $channel['channel_deny_cid'], 
				'deny_gid' => $channel['channel_deny_gid']
			);
		} 

		$albumselect_e = $albumselect;
		$aclselect_e = (($_is_owner) ? populate_acl($channel_acl) : '');

		$tpl = get_markup_template('photos_upload.tpl');
		$o .= replace_macros($tpl,array(
			'$pagename' => t('Upload Photos'),
			'$sessid' => session_id(),
			'$usage' => $usage_message,
			'$nickname' => $a->data['channel']['channel_address'],
			'$newalbum' => t('New album name: '),
			'$existalbumtext' => t('or existing album name: '),
			'$nosharetext' => t('Do not show a status post for this upload'),
			'$albumselect' => $albumselect_e,
			'$permissions' => t('Permissions'),
			'$aclselect' => $aclselect_e,
			'$uploader' => $ret['addon_text'],
			'$default' => (($ret['default_upload']) ? $default_upload : ''),
			'$uploadurl' => $ret['post_url']

		));

		return $o; 
	}

	/*
	 * Display a single photo album
	 */

	if($datatype === 'album') {

		$album = hex2bin($datum);

		$r = q("SELECT `resource_id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` = '%s' 
			AND `scale` <= 4 and (photo_flags = %d or photo_flags = %d ) $sql_extra GROUP BY `resource_id`",
			intval($owner_uid),
			dbesc($album),
			intval(PHOTO_NORMAL),
			intval(PHOTO_PROFILE)
		);
		if(count($r)) {
			$a->set_pager_total(count($r));
			$a->set_pager_itemspage(40);
		}

		if($_GET['order'] === 'posted')
			$order = 'ASC';
		else
			$order = 'DESC';

		$r = q("SELECT `resource_id`, `id`, `filename`, type, max(`scale`) AS `scale`, `description` FROM `photo` WHERE `uid` = %d AND `album` = '%s' 
			AND `scale` <= 4 and (photo_flags = %d or photo_flags = %d ) $sql_extra GROUP BY `resource_id` ORDER BY `created` $order LIMIT %d , %d",
			intval($owner_uid),
			dbesc($album),
			intvaL(PHOTO_NORMAL),
			intval(PHOTO_PROFILE),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);

		$o .= '<h3>' . $album . '</h3>';
		
		if($cmd === 'edit') {		
			if(($album !== t('Profile Photos')) && ($album !== 'Contact Photos') && ($album !== t('Contact Photos'))) {
				if($can_post) {
					if($a->get_template_engine() === 'internal') {
						$album_e = template_escape($album);
					}
					else {
						$album_e = $album;
					}

					$edit_tpl = get_markup_template('album_edit.tpl');
					$o .= replace_macros($edit_tpl,array(
						'$nametext' => t('New album name: '),
						'$nickname' => $a->data['channel']['channel_address'],
						'$album' => $album_e,
						'$hexalbum' => bin2hex($album),
						'$submit' => t('Submit'),
						'$dropsubmit' => t('Delete Album')
					));
				}
			}
		}
		else {
			if(($album !== t('Profile Photos')) && ($album !== 'Contact Photos') && ($album !== t('Contact Photos'))) {
				if($can_post) {
					$o .= '<div id="album-edit-link"><a href="'. $a->get_baseurl() . '/photos/' 
						. $a->data['channel']['channel_address'] . '/album/' . bin2hex($album) . '/edit' . '">' 
						. t('Edit Album') . '</a></div>';
 				}
			}
		}

		if($_GET['order'] === 'posted')
			$o .=  '<div class="photos-upload-link" ><a href="' . $a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'] . '/album/' . bin2hex($album) . '" >' . t('Show Newest First') . '</a></div>';
		else
			$o .= '<div class="photos-upload-link" ><a href="' . $a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'] . '/album/' . bin2hex($album) . '?f=&order=posted" >' . t('Show Oldest First') . '</a></div>';


		if($can_post) {
			$o .= '<div class="photos-upload-link" ><a href="' . $a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'] . '/upload/' . bin2hex($album) . '" >' . t('Upload New Photos') . '</a></div>';
		}

		$tpl = get_markup_template('photo_album.tpl');
		if(count($r))
			$twist = 'rotright';
			foreach($r as $rr) {

				if($twist == 'rotright')
					$twist = 'rotleft';
				else
					$twist = 'rotright';
				
				$ext = $phototypes[$rr['type']];

				$imgalt_e = $rr['filename'];
				$desc_e = $rr['description'];


// prettyphoto has potential license issues, so we can no longer include it in core
// The following lines would need to be modified so that they are provided in theme specific files
// instead of core modules for themes that wish to make use of prettyphoto. I would suggest
// the feature as a per-theme display option and putting the rel line inside a template. 
        
//				if(feature_enabled($a->data['channel']['channel_id'],'prettyphoto')){
//				      $imagelink = ($a->get_baseurl() . '/photo/' . $rr['resource_id'] . '.' . $ext );
//				      $rel=("prettyPhoto[pp_gal]");
//				}
//				else {
				      $imagelink = ($a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'] . '/image/' . $rr['resource_id']
				      . (($_GET['order'] === 'posted') ? '?f=&order=posted' : ''));
				      $rel=("photo");
//				}
      
				$o .= replace_macros($tpl,array(
					'$id' => $rr['id'],
					'$twist' => ' ' . $twist . rand(2,4),
					'$photolink' => $imagelink,
					'$rel' => $rel,
					'$phototitle' => t('View Photo'),
					'$imgsrc' => $a->get_baseurl() . '/photo/' . $rr['resource_id'] . '-' . $rr['scale'] . '.' .$ext,
					'$imgalt' => $imgalt_e,
					'$desc'=> $desc_e,
				));

		}
		$o .= '<div id="photo-album-end"></div>';
		$o .= paginate($a);

		return $o;

	}	

	/** 
	 * Display one photo
	 */

	if($datatype === 'image') {

		// fetch image, item containing image, then comments

		$ph = q("SELECT aid,uid,xchan,resource_id,created,edited,title,`description`,album,filename,`type`,height,width,`size`,scale,profile,photo_flags,allow_cid,allow_gid,deny_cid,deny_gid FROM `photo` WHERE `uid` = %d AND `resource_id` = '%s' 
			and (photo_flags = %d or photo_flags = %d ) $sql_extra ORDER BY `scale` ASC ",
			intval($owner_uid),
			dbesc($datum),
			intval(PHOTO_NORMAL),
			intval(PHOTO_PROFILE)

		);

		if(! $ph) {

			/* Check again - this time without specifying permissions */

			$ph = q("SELECT `id` FROM `photo` WHERE `uid` = %d AND `resource_id` = '%s' 
				and ( photo_flags = %d or photo_flags = %d )
				LIMIT 1",
				intval($owner_uid),
				dbesc($datum),
				intval(PHOTO_NORMAL),
				intval(PHOTO_PROFILE)
			);
			if($ph) 
				notice( t('Permission denied. Access to this item may be restricted.') . EOL);
			else
				notice( t('Photo not available') . EOL );
			return;
		}

		$prevlink = '';
		$nextlink = '';

		if($_GET['order'] === 'posted')
			$order = 'ASC';
		else
			$order = 'DESC';


		$prvnxt = q("SELECT `resource_id` FROM `photo` WHERE `album` = '%s' AND `uid` = %d AND `scale` = 0 
			and ( photo_flags = %d or photo_flags = %d ) $sql_extra ORDER BY `created` $order ",
			dbesc($ph[0]['album']),
			intval($owner_uid),
			intval(PHOTO_NORMAL),
			intval(PHOTO_PROFILE)
		); 

		if(count($prvnxt)) {
			for($z = 0; $z < count($prvnxt); $z++) {
				if($prvnxt[$z]['resource_id'] == $ph[0]['resource_id']) {
					$prv = $z - 1;
					$nxt = $z + 1;
					if($prv < 0)
						$prv = count($prvnxt) - 1;
					if($nxt >= count($prvnxt))
						$nxt = 0;
					break;
				}
			}

			$prevlink = $a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'] . '/image/' . $prvnxt[$prv]['resource_id'] . (($_GET['order'] === 'posted') ? '?f=&order=posted' : '');
			$nextlink = $a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'] . '/image/' . $prvnxt[$nxt]['resource_id'] . (($_GET['order'] === 'posted') ? '?f=&order=posted' : '');
 		}


		if(count($ph) == 1)
			$hires = $lores = $ph[0];
		if(count($ph) > 1) {
			if($ph[1]['scale'] == 2) {
				// original is 640 or less, we can display it directly
				$hires = $lores = $ph[0];
			}
			else {
			$hires = $ph[0];
			$lores = $ph[1];
			}
		}

		$album_link = $a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'] . '/album/' . bin2hex($ph[0]['album']);
 		$tools = Null;
 		$lock = Null;
 
		if($can_post && ($ph[0]['uid'] == $owner_uid)) {
			$tools = array(
				'profile'=>array($a->get_baseurl() . '/profile_photo/use/'.$ph[0]['resource_id'], t('Use as profile photo')),
			);

			// lock
			$lock = ( ( ($ph[0]['uid'] == local_user()) && (strlen($ph[0]['allow_cid']) || strlen($ph[0]['allow_gid']) 
					|| strlen($ph[0]['deny_cid']) || strlen($ph[0]['deny_gid'])) ) 
					? t('Private Message')
					: Null);
	  		
			
		}

		$a->page['htmlhead'] .= '<script>$(document).keydown(function(event) {' . "\n";
		if($prevlink)
			$a->page['htmlhead'] .= 'if(event.ctrlKey && event.keyCode == 37) { event.preventDefault(); window.location.href = \'' . $prevlink . '\'; }' . "\n";
		if($nextlink)
			$a->page['htmlhead'] .= 'if(event.ctrlKey && event.keyCode == 39) { event.preventDefault(); window.location.href = \'' . $nextlink . '\'; }' . "\n";
		$a->page['htmlhead'] .= '});</script>';

		if($prevlink)
			$prevlink = array($prevlink, '<i class="icon-backward photo-icons""></i>') ;

		$photo = array(
			'href' => $a->get_baseurl() . '/photo/' . $hires['resource_id'] . '-' . $hires['scale'] . '.' . $phototypes[$hires['type']],
			'title'=> t('View Full Size'),
			'src'  => $a->get_baseurl() . '/photo/' . $lores['resource_id'] . '-' . $lores['scale'] . '.' . $phototypes[$lores['type']] . '?f=&_u=' . datetime_convert('','','','ymdhis')
		);

		if($nextlink)
			$nextlink = array($nextlink, '<i class="icon-forward photo-icons"></i>');


		// Do we have an item for this photo?

		$linked_items = q("SELECT * FROM item WHERE resource_id = '%s' and resource_type = 'photo' 
			$sql_extra LIMIT 1",
			dbesc($datum)
		);

		if($linked_items) {

			$link_item = $linked_items[0];

			$r = q("select * from item where parent_mid = '%s' 
				and item_restrict = 0 and uid = %d $sql_extra ",
				dbesc($link_item['mid']),
				intval($link_item['uid'])

			);

			if($r) {
				xchan_query($r);
				$r = fetch_post_tags($r,true);
				$r = conv_sort($r,'commented');
			}

			if((local_user()) && (local_user() == $link_item['uid'])) {
				q("UPDATE `item` SET item_flags = (item_flags ^ %d) WHERE parent = %d and uid = %d and (item_flags & %d)",
					intval(ITEM_UNSEEN),
					intval($link_item['parent']),
					intval(local_user()),
					intval(ITEM_UNSEEN)
				);
			}
		}

		// FIXME - remove this when we move to conversation module 

		$r = $r[0]['children'];

		$edit = null;
		if($can_post) {

			$album_e = $ph[0]['album'];
			$caption_e = $ph[0]['description'];
			$aclselect_e = populate_acl($ph[0]);

			$edit = array(
				'edit' => t('Edit photo'),
				'id' => $ph[0]['id'],
				'rotatecw' => t('Rotate CW (right)'),
				'rotateccw' => t('Rotate CCW (left)'),
				'album' => $album_e,
				'newalbum' => t('New album name'), 
				'nickname' => $a->data['channel']['channel_address'],
				'resource_id' => $ph[0]['resource_id'],
				'capt_label' => t('Caption'),
				'caption' => $caption_e,
				'tag_label' => t('Add a Tag'),
				'tags' => $link_item['tag'],
				'permissions' => t('Permissions'),
				'aclselect' => $aclselect_e,
				'help_tags' => t('Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'),
				'item_id' => ((count($linked_items)) ? $link_item['id'] : 0),
				'submit' => t('Submit'),
				'delete' => t('Delete Photo')
			);
		}

		if(count($linked_items)) {

			$cmnt_tpl = get_markup_template('comment_item.tpl');
			$tpl = get_markup_template('photo_item.tpl');
			$return_url = $a->cmd;

			$like_tpl = get_markup_template('like_noshare.tpl');

			$likebuttons = '';

			if($can_post || $can_comment) {
				$likebuttons = replace_macros($like_tpl,array(
					'$id' => $link_item['id'],
					'$likethis' => t("I like this \x28toggle\x29"),
					'$nolike' => t("I don't like this \x28toggle\x29"),
					'$share' => t('Share'),
					'$wait' => t('Please wait')
				));
			}

			$comments = '';
			if(! count($r)) {
				if($can_post || $can_comment) {
					$comments .= replace_macros($cmnt_tpl,array(
						'$return_path' => '', 
						'$mode' => 'photos',
						'$jsreload' => $return_url,
						'$type' => 'wall-comment',
						'$id' => $link_item['id'],
						'$parent' => $link_item['id'],
						'$profile_uid' =>  $owner_uid,
						'$mylink' => $observer['xchan_url'],
						'$mytitle' => t('This is you'),
						'$myphoto' => $observer['xchan_photo_s'],
						'$comment' => t('Comment'),
						'$submit' => t('Submit'),
						'$preview' => t('Preview'),
						'$ww' => '',
						'$feature_encrypt' => false
					));
				}
			}

			$alike = array();
			$dlike = array();
			
			$like = '';
			$dislike = '';

			// display comments
			if($r) {

				foreach($r as $item) {
					like_puller($a,$item,$alike,'like');
					like_puller($a,$item,$dlike,'dislike');
				}

				$like    = ((isset($alike[$link_item['id']])) ? format_like($alike[$link_item['id']],$alike[$link_item['id'] . '-l'],'like',$link_item['id']) : '');
				$dislike = ((isset($dlike[$link_item['id']])) ? format_like($dlike[$link_item['id']],$dlike[$link_item['id'] . '-l'],'dislike',$link_item['id']) : '');



				foreach($r as $item) {
					$comment = '';
					$template = $tpl;
					$sparkle = '';

					if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) && ($item['id'] != $item['parent']))
						continue;

					$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;
			

					$profile_url = zid($item['author']['xchan_url']);
					$sparkle = '';


					$profile_name   = $item['author']['xchan_name'];
					$profile_avatar = $item['author']['xchan_photo_m'];

					$profile_link = $profile_url;

					$drop = '';

					if($observer['xchan_hash'] === $item['author_xchan'] || $observer['xchan_hash'] === $item['owner_xchan'])
						$drop = replace_macros(get_markup_template('photo_drop.tpl'), array('$id' => $item['id'], '$delete' => t('Delete')));


					$name_e = $profile_name;
					$title_e = $item['title'];
					unobscure($item);
					$body_e = prepare_text($item['body'],$item['mimetype']);

					$comments .= replace_macros($template,array(
						'$id' => $item['item_id'],
						'$mode' => 'photos',
						'$profile_url' => $profile_link,
						'$name' => $name_e,
						'$thumb' => $profile_avatar,
						'$sparkle' => $sparkle,
						'$title' => $title_e,
						'$body' => $body_e,
						'$ago' => relative_date($item['created']),
						'$indent' => (($item['parent'] != $item['item_id']) ? ' comment' : ''),
						'$drop' => $drop,
						'$comment' => $comment
					));

				}
			
				if($can_post || $can_comment) {
					$comments .= replace_macros($cmnt_tpl,array(
						'$return_path' => '',
						'$jsreload' => $return_url,
						'$type' => 'wall-comment',
						'$id' => $link_item['id'],
						'$parent' => $link_item['id'],
						'$profile_uid' =>  $owner_uid,
						'$mylink' => $observer['xchan_url'],
						'$mytitle' => t('This is you'),
						'$myphoto' => $observer['xchan_photo_s'],
						'$comment' => t('Comment'),
						'$submit' => t('Submit'),
						'$ww' => ''
					));
				}

			}
			$paginate = paginate($a);
		}
		
		$album_e = array($album_link,$ph[0]['album']);
		$tags_e = $tags;
		$like_e = $like;
		$dislike_e = $dislike;

		$photo_tpl = get_markup_template('photo_view.tpl');
		$o .= replace_macros($photo_tpl, array(
			'$id' => $ph[0]['id'],
			'$album' => $album_e,
			'$tools' => $tools,
			'$lock' => $lock,
			'$photo' => $photo,
			'$prevlink' => $prevlink,
			'$nextlink' => $nextlink,
			'$desc' => $ph[0]['description'],
			'$tags' => $tags_e,
			'$edit' => $edit,	
			'$likebuttons' => $likebuttons,
			'$like' => $like_e,
			'$dislike' => $dislike_e,
			'$comments' => $comments,
			'$paginate' => $paginate,
		));

		$a->data['photo_html'] = $o;
		
		return $o;
	}

	// Default - show recent photos with upload link (if applicable)
	//$o = '';

	$r = q("SELECT `resource_id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` != '%s' AND `album` != '%s' 
		and ( photo_flags = %d or photo_flags = %d ) $sql_extra GROUP BY `resource_id`",
		intval($a->data['channel']['channel_id']),
		dbesc('Contact Photos'),
		dbesc( t('Contact Photos')),
		intval(PHOTO_NORMAL),
		intval(PHOTO_PROFILE)		
	);
	if(count($r)) {
		$a->set_pager_total(count($r));
		$a->set_pager_itemspage(20);
	}

	$r = q("SELECT `resource_id`, `id`, `filename`, type, `album`, max(`scale`) AS `scale` FROM `photo`
		WHERE `uid` = %d AND `album` != '%s' AND `album` != '%s'
		and ( photo_flags = %d or photo_flags = %d )  
		$sql_extra GROUP BY `resource_id` ORDER BY `created` DESC LIMIT %d , %d",
		intval($a->data['channel']['channel_id']),
		dbesc('Contact Photos'),
		dbesc( t('Contact Photos')),
		intval(PHOTO_NORMAL),
		intval(PHOTO_PROFILE),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);



	$photos = array();
	if(count($r)) {
		$twist = 'rotright';
		foreach($r as $rr) {
			if($twist == 'rotright')
				$twist = 'rotleft';
			else
				$twist = 'rotright';
			$ext = $phototypes[$rr['type']];
			
			if($a->get_template_engine() === 'internal') {
				$alt_e = template_escape($rr['filename']);
				$name_e = template_escape($rr['album']);
			}
			else {
				$alt_e = $rr['filename'];
				$name_e = $rr['album'];
			}

			$photos[] = array(
				'id'       => $rr['id'],
				'twist'    => ' ' . $twist . rand(2,4),
				'link'  	=> $a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'] . '/image/' . $rr['resource_id'],
				'title' 	=> t('View Photo'),
				'src'     	=> $a->get_baseurl() . '/photo/' . $rr['resource_id'] . '-' . ((($rr['scale']) == 6) ? 4 : $rr['scale']) . '.' . $ext,
				'alt'     	=> $alt_e,
				'album'	=> array(
					'link'  => $a->get_baseurl() . '/photos/' . $a->data['channel']['channel_address'] . '/album/' . bin2hex($rr['album']),
					'name'  => $name_e,
					'alt'   => t('View Album'),
				),
				
			);
		}
	}
	
	$tpl = get_markup_template('photos_recent.tpl'); 
	$o .= replace_macros($tpl, array(
		'$title' => t('Recent Photos'),
		'$can_post' => $can_post,
		'$upload' => array(t('Upload New Photos'), $a->get_baseurl().'/photos/'.$a->data['channel']['channel_address'].'/upload'),
		'$photos' => $photos,
	));

	
	$o .= paginate($a);
	return $o;
}

