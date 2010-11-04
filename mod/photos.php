<?php

require_once('Photo.php');
require_once('include/items.php');
require_once('view/acl_selectors.php');
require_once('include/bbcode.php');

function photos_init(&$a) {

	$o = '';

	if($a->argc > 1) {
		$nick = $a->argv[1];
		$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
			dbesc($nick)
		);

		if(! count($r))
			return;

		$a->data['user'] = $r[0];

		$albums = q("SELECT distinct(`album`) AS `album` FROM `photo` WHERE `uid` = %d",
			intval($a->data['user']['uid'])
		);

		if(count($albums)) {
			$a->data['albums'] = $albums;

			$o .= '<h4><a href="' . $a->get_baseurl() . '/profile/' . $a->data['user']['nickname'] . '">' . $a->data['user']['username'] . '</a></h4>';
			$o .= '<h4>' . '<a href="' . $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '">' . t('Photo Albums') . '</a></h4>';
		
			$o .= '<ul>';
			foreach($albums as $album) {
				if((! strlen($album['album'])) || ($album['album'] == t('Contact Photos')))
					continue;
				$o .= '<li>' . '<a href="photos/' . $a->argv[1] . '/album/' . bin2hex($album['album']) . '" />' . $album['album'] . '</a></li>'; 
			}
			$o .= '</ul>';
		}

		if(! x($a->page,'aside'))
			$a->page['aside'] = '';
		$a->page['aside'] .= $o;
	}
	return;
}




function photos_post(&$a) {


	if(! local_user()) {
		notice( t('Permission denied.') . EOL );
		killme();
	}

	$r = q("SELECT `contact`.*, `user`.`nickname` FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid` 
		WHERE `user`.`uid` = %d AND `self` = 1 LIMIT 1",
		intval(local_user())
	);

	if(! count($r)) {
		notice( t('Contact information unavailable') . EOL);
		logger('photos_post: unable to locate contact record for logged in user. uid=' . local_user());
		killme();
	}

	$contact_record = $r[0];	


	if(($a->argc > 2) && ($a->argv[1] === 'album')) {
		$album = hex2bin($a->argv[2]);

		if($album == t('Profile Photos') || $album == t('Contact Photos')) {
			goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
			return; // NOTREACHED
		}

		$r = q("SELECT count(*) FROM `photo` WHERE `album` = '%s' AND `uid` = %d",
			dbesc($album),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Album not found.') . EOL);
			goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
			return; // NOTREACHED
		}

		$newalbum = notags(trim($_POST['albumname']));
		if($newalbum != $album) {
			q("UPDATE `photo` SET `album` = '%s' WHERE `album` = '%s' AND `uid` = %d",
				dbesc($newalbum),
				dbesc($album),
				intval(local_user())
			);
			$newurl = str_replace(bin2hex($album),bin2hex($newalbum),$_SESSION['photo_return']);
			goaway($a->get_baseurl() . '/' . $newurl);
			return; // NOTREACHED
		}

		if($_POST['dropalbum'] == t('Delete Album')) {

			$res = array();
			$r = q("SELECT distinct(`resource-id`) as `rid` FROM `photo` WHERE `uid` = %d AND `album` = '%s'",
				intval(local_user()),
				dbesc($album)
			);
			if(count($r)) {
				foreach($r as $rr) {
					$res[] = "'" . dbesc($rr['rid']) . "'" ;
				}
			}
			else {
				goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
				return; // NOTREACHED
			}
			$str_res = implode(',', $res);

			q("DELETE FROM `photo` WHERE `resource-id` IN ( $str_res ) AND `uid` = %d",
				intval(local_user())
			);
			$r = q("SELECT `parent-uri` FROM `item` WHERE `resource-id` IN ( $str_res ) AND `uid` = %d",
				intval(local_user())
			);
			if(count($r)) {
				foreach($r as $rr) {
					q("UPDATE `item` SET `deleted` = 1, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
						dbesc(datetime_convert()),
						dbesc($rr['parent-uri']),
						intval(local_user())
					);

					$drop_id = intval($rr['id']);
					$php_path = ((strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
					$proc_debug = get_config('system','proc_debug');

					// send the notification upstream/downstream as the case may be

					if($rr['visible'])
						proc_close(proc_open("\"$php_path\" \"include/notifier.php\" \"drop\" \"$drop_id\" $proc_debug & ",
							array(),$foo));

				}
			}
		}
		goaway($a->get_baseurl() . '/photos/' . $a->data['user']['nickname']);
		return; // NOTREACHED
	}

	if(($a->argc > 1) && (x($_POST,'delete')) && ($_POST['delete'] == t('Delete Photo'))) {
		$r = q("SELECT `id` FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s' LIMIT 1",
			intval(local_user()),
			dbesc($a->argv[1])
		);
		if(count($r)) {
			q("DELETE FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s'",
				intval(local_user()),
				dbesc($r[0]['resource-id'])
			);
			$i = q("SELECT * FROM `item` WHERE `resource-id` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($r[0]['resource-id']),
				intval(local_user())
			);
			if(count($i)) {
				q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc($i[0]['uri']),
					intval(local_user())
				);

				$url = $a->get_baseurl();
				$drop_id = intval($i[0]['id']);
				$php_path = ((strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
				
				$proc_debug = get_config('system','proc_debug');

				// send the notification upstream/downstream as the case may be

				if($i[0]['visible'])
					proc_close(proc_open("\"$php_path\" \"include/notifier.php\" \"drop\" \"$drop_id\" $proc_debug & ",
						array(),$foo));
			}
		}

		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		return; // NOTREACHED
	}

	if(($a->argc > 1) && ((x($_POST,'desc') !== false) || (x($_POST,'newtag') !== false))) {

		$desc        = ((x($_POST,'desc'))    ? notags(trim($_POST['desc']))   : '');
		$rawtags     = ((x($_POST,'newtag'))  ? notags(trim($_POST['newtag'])) : '');
		$item_id     = ((x($_POST,'item_id')) ? intval($_POST['item_id'])      : 0);
		$resource_id = $a->argv[1];

		$p = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d ORDER BY `scale` DESC",
			dbesc($resource_id),
			intval(local_user())
		);
		if((count($p)) && ($p[0]['desc'] !== $desc)) {
			$r = q("UPDATE `photo` SET `desc` = '%s' WHERE `resource-id` = '%s' AND `uid` = %d",
				dbesc($desc),
				dbesc($resource_id),
				intval(local_user())
			);
		}
		if(! $item_id) {

			// Create item container

			$title = '';
			$basename = basename($filename);
			$uri = item_new_uri($a->get_hostname(),local_user());

			$arr = array();

			$arr['uid']           = local_user();
			$arr['uri']           = $uri;
			$arr['parent-uri']    = $uri; 
			$arr['type']          = 'photo';
			$arr['wall']          = 1;
			$arr['resource-id']   = $p[0]['resource-id'];
			$arr['contact-id']    = $contact_record['id'];
			$arr['owner-name']    = $contact_record['name'];
			$arr['owner-link']    = $contact_record['url'];
			$arr['owner-avatar']  = $contact_record['thumb'];
			$arr['author-name']   = $contact_record['name'];
			$arr['author-link']   = $contact_record['url'];
			$arr['author-avatar'] = $contact_record['thumb'];
			$arr['title']         = $title;
			$arr['allow_cid']     = $p[0]['allow_cid'];
			$arr['allow_gid']     = $p[0]['allow_gid'];
			$arr['deny_cid']      = $p[0]['deny_cid'];
			$arr['deny_gid']      = $p[0]['deny_gid'];
			$arr['last-child']    = 1;
			$arr['body']          = '[url=' . $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '/image/' . $p[0]['resource-id'] . ']' 
						. '[img]' . $a->get_baseurl() . '/photo/' . $p[0]['resource-id'] . '-' . $p[0]['scale'] . '.jpg' . '[/img]' 
						. '[/url]';
		
			$item_id = item_store($arr);

		}

		if($item_id) {
			$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($item_id),
				intval(local_user())
			);
		}
		if(count($r)) {
			$old_tag    = $r[0]['tag'];
			$old_inform = $r[0]['inform'];
		}

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
							if(strstr($name,'_')) {
								$newname = str_replace('_',' ',$name);
								$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
									dbesc($newname),
									intval(local_user())
								);
							}
							else {
								$r = q("SELECT * FROM `contact` WHERE `nick` = '%s' AND `uid` = %d LIMIT 1",
									dbesc($name),
									intval(local_user())
								);
							}
							if(count($r)) {
								$profile = $r[0]['url'];
								$notify = 'cid:' . $r[0]['id'];
								if(strlen($inform))
									$inform .= ',';
								$inform .= $notify;
							}
						}
						if($profile) {
							$taginfo[] = array($newname,$profile,$notify);
							if(strlen($str_tags))
								$str_tags .= ',';
							$profile = str_replace(',','%2c',$profile);
							$str_tags .= '@[url=' . $profile . ']' . $newname	. '[/url]';
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

			$r = q("UPDATE `item` SET `tag` = '%s', `inform` = '%s', `edited` = '%s', `changed` = '%s' WHERE `id` = %d AND `uid` = %d LIMIT 1",
				dbesc($newtag),
				dbesc($newinform),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				intval($item_id),
				intval(local_user())
			);

			if(count($taginfo)) {
				foreach($taginfo as $tagged) {
//					$slap = create_photo_tag(local_user(),$item_id, $tagged);


//					
				}
				// call notifier on new tag activity
			}
			
//				$php_path = ((strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
				
//				$proc_debug = get_config('system','proc_debug');

				// send the notification upstream/downstream as the case may be

//				if($i[0]['visible'])
//					proc_close(proc_open("\"$php_path\" \"include/notifier.php\" \"drop\" \"$drop_id\" $proc_debug & ",
//						array(),$foo));



		}
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		return; // NOTREACHED
	}


	// default post action - upload a photo

	if(! x($_FILES,'userfile'))
		killme();

	if($_POST['partitionCount'])
		$java_upload = true;
	else
		$java_upload = false;

	$album =  notags(trim($_POST['album']));
	$newalbum = notags(trim($_POST['newalbum']));

	if(! strlen($album)) {
		if(strlen($newalbum))
			$album = $newalbum;
		else
			$album = datetime_convert('UTC',date_default_timezone_get(),'now', 'Y');
	}

	$r = q("SELECT * FROM `photo` WHERE `album` = '%s' AND `uid` = %d",
		dbesc($album),
		intval(local_user())
	);
	if((! count($r)) || ($album == t('Profile Photos')))
		$visible = 1;
	else
		$visible = 0;


	$str_group_allow   = perms2str($_POST['group_allow']);
	$str_contact_allow = perms2str($_POST['contact_allow']);
	$str_group_deny    = perms2str($_POST['group_deny']);
	$str_contact_deny  = perms2str($_POST['contact_deny']);

	$src               = $_FILES['userfile']['tmp_name'];
	$filename          = basename($_FILES['userfile']['name']);
	$filesize          = intval($_FILES['userfile']['size']);

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata);

	if(! $ph->is_valid()) {
		notice( t('Unable to process image.') . EOL );
		@unlink($src);
		killme();
	}

	@unlink($src);

	$width = $ph->getWidth();
	$height = $ph->getHeight();

	$smallest = 0;

	$photo_hash = photo_new_resource();

	$r = $ph->store(local_user(), 0, $photo_hash, $filename, $album, 0 , 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);

	if(! $r) {
		notice( t('Image upload failed.') . EOL );
		killme();
	}

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$ph->store(local_user(), 0, $photo_hash, $filename, $album, 1, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 1;
	}

	if($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$ph->store(local_user(), 0, $photo_hash, $filename, $album, 2, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 2;
	}
	
	$basename = basename($filename);
	$uri = item_new_uri($a->get_hostname(), local_user());

	// Create item container


	$arr = array();

	$arr['uid']           = local_user();
	$arr['uri']           = $uri;
	$arr['parent-uri']    = $uri;
	$arr['type']          = 'photo';
	$arr['wall']          = 1;
	$arr['resource-id']   = $photo_hash;
	$arr['contact-id']    = $contact_record['id'];
	$arr['owner-name']    = $contact_record['name'];
	$arr['owner-link']    = $contact_record['url'];
	$arr['owner-avatar']  = $contact_record['thumb'];
	$arr['author-name']   = $contact_record['name'];
	$arr['author-link']   = $contact_record['url'];
	$arr['author-avatar'] = $contact_record['thumb'];
	$arr['title']         = '';
	$arr['allow_cid']     = $str_contact_allow;
	$arr['allow_gid']     = $str_group_allow;
	$arr['deny_cid']      = $str_contact_deny;
	$arr['deny_gid']      = $str_group_deny;
	$arr['last-child']    = 1;
	$arr['visible']       = $visible;
	$arr['body']          = '[url=' . $a->get_baseurl() . '/photos/' . $contact_record['nickname'] . '/image/' . $photo_hash . ']' 
				. '[img]' . $a->get_baseurl() . "/photo/{$photo_hash}-{$smallest}.jpg" . '[/img]' 
				. '[/url]';

	$item_id = item_store($arr);

	if(! $java_upload) {
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		return; // NOTREACHED
	}

	killme();
	return; // NOTREACHED

}



function photos_content(&$a) {

	// URLs:
	// photos/name
	// photos/name/upload
	// photos/name/album/xxxxx
	// photos/name/album/xxxxx/edit
	// photos/name/image/xxxxx
	// photos/name/image/xxxxx/edit


	if(! x($a->data,'user')) {
		notice( t('No photos selected') . EOL );
		return;
	}

	$_SESSION['photo_return'] = $a->cmd;

	//
	// Parse arguments 
	//

	if($a->argc > 3) {
		$datatype = $a->argv[2];
		$datum = $a->argv[3];
	}
	elseif(($a->argc > 2) && ($a->argv[2] === 'upload'))
		$datatype = 'upload';
	else
		$datatype = 'summary';

	if($a->argc > 4)
		$cmd = $a->argv[4];
	else
		$cmd = 'view';

	//
	// Setup permissions structures
	//

	$owner_uid = $a->data['user']['uid'];



	$contact = null;
	$remote_contact = false;

	if(remote_user()) {
		$contact_id = $_SESSION['visitor_id'];
		$groups = init_groups_visitor($contact_id);
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($contact_id),
			intval($owner_uid)
		);
		if(count($r)) {
			$contact = $r[0];
			$remote_contact = true;
		}
	}

	if(! $remote_contact) {
		if(local_user()) {
			$contact_id = $_SESSION['cid'];
			$contact = $a->contact;
		}
	}


	// default permissions - anonymous user

	$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = '' ";

	// Profile owner - everything is visible

	if(local_user() && (local_user() == $owner_uid)) {
		$sql_extra = ''; 	
	}
	elseif(remote_user()) {
		// authenticated visitor - here lie dragons
		$gs = '<<>>'; // should be impossible to match
		if(count($groups)) {
			foreach($groups as $g)
				$gs .= '|<' . intval($g) . '>';
		} 
		$sql_extra = sprintf(
			" AND ( `allow_cid` = '' OR `allow_cid` REGEXP '<%d>' ) 
			  AND ( `deny_cid`  = '' OR  NOT `deny_cid` REGEXP '<%d>' ) 
			  AND ( `allow_gid` = '' OR `allow_gid` REGEXP '%s' )
			  AND ( `deny_gid`  = '' OR NOT `deny_gid` REGEXP '%s') ",

			intval($_SESSION['visitor_id']),
			intval($_SESSION['visitor_id']),
			dbesc($gs),
			dbesc($gs)
		);
	}

	//
	// dispatch request
	//


	if($datatype === 'upload') {
		if( ! (local_user() && (local_user() == $a->data['user']['uid']))) {
			notice( t('Permission denied.'));
			return;
		}
		$albumselect = '<select id="photos-upload-album-select" name="album" size="4">';

		$albumselect .= '<option value="" selected="selected" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
		if(count($a->data['albums'])) {
			foreach($a->data['albums'] as $album) {
				if(($album['album'] === '') || ($album['album'] == t('Contact Photos')))
					continue;
				$albumselect .= '<option value="' . $album['album'] . '">' . $album['album'] . '</option>';
			}
		}

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$albumselect .= '</select>';
		$tpl = load_view_file('view/photos_upload.tpl');
		$o .= replace_macros($tpl,array(
			'$pagename' => t('Upload Photos'),
			'$sessid' => session_id(),
			'$newalbum' => t('New album name: '),
			'$existalbumtext' => t('or existing album name: '),
			'$filestext' => t('Select files to upload: '),
			'$albumselect' => $albumselect,
			'$permissions' => t('Permissions'),
			'$aclselect' => populate_acl($a->user, $celeb),
			'$archive' => $a->get_baseurl() . '/jumploader_z.jar',
			'$nojava' => t('Use the following controls only if the Java uploader [above] fails to launch.'),
			'$uploadurl' => $a->get_baseurl() . '/photos',
			'$submit' => t('Submit')
		));

		return $o; 

	}

	if($datatype === 'album') {

		$album = hex2bin($datum);

		$r = q("SELECT `resource-id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` = '%s' 
			$sql_extra GROUP BY `resource-id`",
			intval($a->data['user']['uid']),
			dbesc($album)
		);
		if(count($r))
			$a->set_pager_total(count($r));


		$r = q("SELECT `resource-id`, `id`, `filename`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` = '%s' 
			$sql_extra GROUP BY `resource-id` ORDER BY `created` DESC LIMIT %d , %d",
			intval($a->data['user']['uid']),
			dbesc($album),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);

		$o .= '<h3>' . $album . '</h3>';
		
		if($cmd === 'edit') {		
			if(($album != t('Profile Photos')) && ($album != t('Contact Photos'))) {
				if(local_user() && (local_user() == $a->data['user']['uid'])) {
					$edit_tpl = load_view_file('view/album_edit.tpl');
					$o .= replace_macros($edit_tpl,array(
						'$nametext' => t('New album name: '),
						'$album' => $album,
						'$hexalbum' => bin2hex($album),
						'$submit' => t('Submit'),
						'$dropsubmit' => t('Delete Album')
					));
				}
			}
		}
		else {
			if(($album != t('Profile Photos')) && ($album != t('Contact Photos'))) {
				if(local_user() && (local_user() == $a->data['user']['uid'])) {
					$o .= '<div id="album-edit-link"><a href="'. $a->get_baseurl() . '/photos/' 
						. $a->data['user']['nickname'] . '/album/' . bin2hex($album) . '/edit' . '">' 
						. t('Edit Album') . '</a></div>';
 				}
			}
		}
		$tpl = load_view_file('view/photo_album.tpl');
		if(count($r))
			foreach($r as $rr) {
				$o .= replace_macros($tpl,array(
					'$id' => $rr['id'],
					'$photolink' => $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '/image/' . $rr['resource-id'],
					'$phototitle' => t('View Photo'),
					'$imgsrc' => $a->get_baseurl() . '/photo/' . $rr['resource-id'] . '-' . $rr['scale'] . '.jpg',
					'$imgalt' => $rr['filename']
				));

		}
		$o .= '<div id="photo-album-end"></div>';
		return $o;

	}	


	if($datatype === 'image') {

		require_once('security.php');
		require_once('bbcode.php');

		$o = '<div id="live-display"></div>' . "\r\n";
		// fetch image, item containing image, then comments

		$ph = q("SELECT * FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s' 
			$sql_extra ORDER BY `scale` ASC ",
			intval($a->data['user']['uid']),
			dbesc($datum)
		);

		if(! count($ph)) {
			notice( t('Photo not available') . EOL );
			return;
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

		
		$o .= '<h3>' . '<a href="' . $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($ph[0]['album']) . '">' . $ph[0]['album'] . '</a></h3>';
 
		if(local_user() && ($ph[0]['uid'] == local_user())) {
			$o .= '<div id="photo-edit-link-wrap" ><a id="photo-edit-link" href="' . $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '/image/' . $datum . '/edit' . '">' . t('Edit photo') . '</a></div>';
		}


		$o .= '<a href="' . $a->get_baseurl() . '/photo/' 
			. $hires['resource-id'] . '-' . $hires['scale'] . '.jpg" title="' 
			. t('View Full Size') . '" ><img src="' . $a->get_baseurl() . '/photo/' 
			. $lores['resource-id'] . '-' . $lores['scale'] . '.jpg' . '" /></a>';


		// Do we have an item for this photo?

		$i1 = q("SELECT * FROM `item` WHERE `resource-id` = '%s' $sql_extra LIMIT 1",
			dbesc($datum)
		);
		if(count($i1)) {

			$r = q("SELECT COUNT(*) AS `total`
				FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `parent-uri` = '%s' AND `uri` != '%s' AND `item`.`deleted` = 0
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`uid` = %d 
				$sql_extra ",
				dbesc($i1[0]['uri']),
				dbesc($i1[0]['uri']),
				intval($i1[0]['uid'])

			);

			if(count($r))
				$a->set_pager_total($r[0]['total']);


			$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`network`, 
				`contact`.`rel`, `contact`.`thumb`, `contact`.`self`, 
				`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
				FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `parent-uri` = '%s' AND `uri` != '%s' AND `item`.`deleted` = 0
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`uid` = %d
				$sql_extra
				ORDER BY `parent` DESC, `id` ASC LIMIT %d ,%d ",
				dbesc($i1[0]['uri']),
				dbesc($i1[0]['uri']),
				intval($i1[0]['uid']),
				intval($a->pager['start']),
				intval($a->pager['itemspage'])

			);
		}

		$o .= '<div id="photo-caption" >' . $ph[0]['desc'] . '</div>';

		if(count($i1) && strlen($i1[0]['tag'])) {
			$arr = explode(',',$i1[0]['tag']);
			// parse tags and add links	
			$o .= '<div id="in-this-photo-text">' . t('Tags: ') . '</div>';
			$o .= '<div id="in-this-photo">';
			$tag_str = '';
			foreach($arr as $t) {
				if(strlen($tag_str))
					$tag_str .= ', ';
				$tag_str .= bbcode($t);
			} 
			$o .= $tag_str . '</div>';
		}

		if($cmd === 'edit') {
			$edit_tpl = load_view_file('view/photo_edit.tpl');
			$o .= replace_macros($edit_tpl, array(
				'$id' => $ph[0]['id'],
				'$resource_id' => $ph[0]['resource-id'],
				'$capt_label' => t('Caption'),
				'$caption' => $ph[0]['desc'],
				'$tag_label' => t('Add a Tag'),
				'$tags' => $i1[0]['tag'],
				'$help_tags' => t('Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'),
				'$item_id' => ((count($i1)) ? $i1[0]['id'] : 0),
				'$submit' => t('Submit'),
				'$delete' => t('Delete Photo')

			));
		}

		if(count($i1)) {

			$cmnt_tpl = load_view_file('view/comment_item.tpl');
			$tpl = load_view_file('view/photo_item.tpl');
			$return_url = $a->cmd;

			$like_tpl = load_view_file('view/like.tpl');

			$likebuttons = '';

			if(can_write_wall($a,$a->data['user']['uid']))
				$likebuttons = replace_macros($like_tpl,array('$id' => $i1[0]['id']));

			if(! count($r)) {
				$o .= '<div id="photo-like-div">';
				$o .= $likebuttons;
				$o .= '</div>';
			}

			if(can_write_wall($a,$a->data['user']['uid'])) {
				if($i1[0]['last-child']) {
					$o .= replace_macros($cmnt_tpl,array(
						'$return_path' => $return_url,
						'$type' => 'wall-comment',
						'$id' => $i1[0]['id'],
						'$parent' => $i1[0]['id'],
						'$profile_uid' =>  $a->data['user']['uid'],
						'$mylink' => $contact['url'],
						'$mytitle' => t('This is you'),
						'$myphoto' => $contact['thumb'],
						'$ww' => ''
					));
				}
			}

			$alike = array();
			$dlike = array();

			// display comments
			if(count($r)) {

				foreach($r as $item) {
					like_puller($a,$item,$alike,'like');
					like_puller($a,$item,$dlike,'dislike');
				}

	            $like    = ((isset($alike[$i1[0]['id']])) ? format_like($alike[$i1[0]['id']],$alike[$i1[0]['id'] . '-l'],'like',$i1[0]['id']) : '');
				$dislike = ((isset($dlike[$i1[0]['id']])) ? format_like($dlike[$i1[0]['id']],$dlike[$i1[0]['id'] . '-l'],'dislike',$i1[0]['id']) : '');

				$o .= '<div id="photo-like-div">';
				$o .= $likebuttons;
				$o .= $like;
				$o .= $dislike;
				$o .= '</div>';


				foreach($r as $item) {
					$comment = '';
					$template = $tpl;
					$sparkle = '';

					if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) && ($item['id'] != $item['parent']))
						continue;

					$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;
			
					if(can_write_wall($a,$a->data['user']['uid'])) {

						if($item['last-child']) {
							$comment = replace_macros($cmnt_tpl,array(
								'$return_path' => $return_url,
								'$type' => 'wall-comment',
								'$id' => $item['item_id'],
								'$parent' => $item['parent'],
								'$profile_uid' =>  $a->data['user']['uid'],
								'$mylink' => $contact['url'],
								'$mytitle' => t('This is you'),
								'$myphoto' => $contact['thumb'],
								'$ww' => ''
							));
						}
					}


					if(local_user() && ($item['contact-uid'] == local_user()) 
						&& ($item['network'] == 'dfrn') && (! $item['self'] )) {
						$profile_url = $redirect_url;
						$sparkle = ' sparkle';
					}
					else {
						$profile_url = $item['url'];
						$sparkle = '';
					}
 
					$profile_name = ((strlen($item['author-name'])) ? $item['author-name'] : $item['name']);
					$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $item['thumb']);
					$profile_link = $profile_url;

					$drop = '';

					if(($item['contact-id'] == $_SESSION['visitor_id']) || ($item['uid'] == local_user()))
						$drop = replace_macros(load_view_file('view/wall_item_drop.tpl'), array('$id' => $item['id']));


					$o .= replace_macros($template,array(
						'$id' => $item['item_id'],
						'$profile_url' => $profile_link,
						'$name' => $profile_name,
						'$thumb' => $profile_avatar,
						'$sparkle' => $sparkle,
						'$title' => $item['title'],
						'$body' => bbcode($item['body']),
						'$ago' => relative_date($item['created']),
						'$indent' => (($item['parent'] != $item['item_id']) ? ' comment' : ''),
						'$drop' => $drop,
						'$comment' => $comment
					));
				}
			}

			$o .= paginate($a);
		}
		return $o;
	}

	// Default - show recent photos with upload link (if applicable)
	$o = '';

	$r = q("SELECT `resource-id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` != '%s' 
		$sql_extra GROUP BY `resource-id`",
		intval($a->data['user']['uid']),
		dbesc( t('Contact Photos'))
	);
	if(count($r))
		$a->set_pager_total(count($r));


	$r = q("SELECT `resource-id`, `id`, `filename`, `album`, max(`scale`) AS `scale` FROM `photo` 
		WHERE `uid` = %d AND `album` != '%s' 
		$sql_extra GROUP BY `resource-id` ORDER BY `created` DESC LIMIT %d , %d",
		intval($a->data['user']['uid']),
		dbesc( t('Contact Photos')),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$o .= '<h3>' . t('Recent Photos') . '</h3>';

	if( local_user() && (local_user() == $a->data['user']['uid'])) {
		$o .= '<div id="photo-top-links"><a id="photo-top-upload-link" href="'. $a->get_baseurl() . '/photos/' 
			. $a->data['user']['nickname'] . '/upload' . '">' . t('Upload New Photos') . '</a></div>';
	}

	$tpl = load_view_file('view/photo_top.tpl');
	if(count($r)) {
		foreach($r as $rr) {
			$o .= replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$photolink' => $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] 
					. '/image/' . $rr['resource-id'],
				'$phototitle' => t('View Photo'),
				'$imgsrc' => $a->get_baseurl() . '/photo/' 
					. $rr['resource-id'] . '-' . $rr['scale'] . '.jpg',
				'$albumlink' => $a->get_baseurl() . '/photos/' 
					. $a->data['user']['nickname'] . '/album/' . bin2hex($rr['album']),
				'$albumname' => $rr['album'],
				'$albumalt' => t('View Album'),
				'$imgalt' => $rr['filename']
			));

		}
		$o .= '<div id="photo-top-end"></div>';
	}
	return $o;
}
