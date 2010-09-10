<?php

require_once('Photo.php');
require_once('view/acl_selectors.php');

function photos_init(&$a) {

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
		$a->page['aside'] .= $o;
	}
	return;
}




function photos_post(&$a) {


        if(! local_user()) {
                notice( t('Permission denied.') . EOL );
                killme();
        }



	$r = q("SELECT `contact`.* `user`.`nickname` FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid` 
		WHERE `user`.`uid` = %d AND `self` = 1 LIMIT 1",
		intval($_SESSION['uid'])
	);

	$contact_record = $r[0];	


	if(($a->argc > 2) && ($a->argv[1] == 'album')) {
		$album = hex2bin($a->argv[2]);

		if($album == t('Profile Photos') || $album == t('Contact Photos')) {
			goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
			return; // NOTREACHED
		}

		$r = q("SELECT count(*) FROM `photo` WHERE `album` = '%s' AND `uid` = %d",
			dbesc($album),
			intval($_SESSION['uid'])
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
				intval($_SESSION['uid'])
			);
			$newurl = str_replace(bin2hex($album),bin2hex($newalbum),$_SESSION['photo_return']);
			goaway($a->get_baseurl() . '/' . $newurl);
			return; // NOTREACHED
		}

		if($_POST['dropalbum'] == t('Delete Album')) {

			$res = array();
			$r = q("SELECT distinct(`resource-id`) as `rid` FROM `photo` WHERE `uid` = %d AND `album` = '%s'",
				intval(get_uid()),
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
				intval(get_uid())
			);
			$r = q("SELECT `parent-uri` FROM `item` WHERE `resource-id` IN ( $str_res ) AND `uid` = %d",
				intval(get_uid())
			);
			if(count($r)) {
				foreach($r as $rr) {
					q("UPDATE `item` SET `deleted` = 1, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
						dbesc(datetime_convert()),
						dbesc($rr['parent-uri']),
						intval(get_uid())
					);

					$drop_id = intval($rr['id']);
					$php_path = ((strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');

					// send the notification upstream/downstream as the case may be

					if($rr['visible'])
						proc_close(proc_open("\"$php_path\" \"include/notifier.php\" \"drop\" \"$drop_id\" & ",
							array(),$foo));

				}
			}
		}
		goaway($a->get_baseurl() . '/photos/' . $a->data['user']['nickname']);
		return; // NOTREACHED
	}

	if(($a->argc > 1) && (x($_POST,'delete')) && ($_POST['delete'] == t('Delete Photo'))) {
		$r = q("SELECT `id` FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s' LIMIT 1",
			intval(get_uid()),
			dbesc($a->argv[1])
		);
		if(count($r)) {
			q("DELETE FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s'",
				intval(get_uid()),
				dbesc($r[0]['resource-id'])
			);
			$i = q("SELECT * FROM `item` WHERE `resource-id` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($r[0]['resource-id']),
				intval(get_uid())
			);
			if(count($i)) {
				q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc($i[0]['uri']),
					intval(get_uid())
				);

				$url = $a->get_baseurl();
				$drop_id = intval($i[0]['id']);
				$php_path = ((strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
				
				// send the notification upstream/downstream as the case may be

				if($i[0]['visible'])
					proc_close(proc_open("\"$php_path\" \"include/notifier.php\" \"drop\" \"$drop_id\" & ",
						array(),$foo));
			}
		}

		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		return; // NOTREACHED
	}



	if(($a->argc > 1) && (x($_POST,'desc') !== false)) {
		$desc = notags(trim($_POST['desc']));
		$tags = notags(trim($_POST['tags']));
		$item_id = intval($_POST['item_id']);
		$resource_id = $a->argv[1];

		$p = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d ORDER BY `scale` DESC",
			dbesc($resource_id),
			intval(get_uid())
		);
		if(count($r)) {
			$r = q("UPDATE `photo` SET `desc` = '%s' WHERE `resource-id` = '%s' AND `uid` = %d",
				dbesc($desc),
				dbesc($resource_id),
				intval(get_uid())
			);
		}
		if(! $item_id) {

			$title = '';
			$basename = basename($filename);

			// Create item container

			$body = '[url=' . $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '/image/' . $p[0]['resource-id'] . ']' 
				. '[img]' . $a->get_baseurl() . '/photo/' . $p[0]['resource-id'] . '-' . $p[0]['scale'] . '.jpg' . '[/img]' 
				. '[/url]';

			$uri = item_new_uri($a->get_hostname(),get_uid());

			$r = q("INSERT INTO `item` (`uid`, `type`, `wall`, `resource-id`, `contact-id`,
				`owner-name`,`owner-link`,`owner-avatar`, `created`,
				`edited`, `changed`, `uri`, `parent-uri`, `title`, `body`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid`)
				VALUES( %d, '%s', %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )",
				intval(get_uid()),
				dbesc('photo'),
				intval(1),
				dbesc($p[0]['resource-id']),			
				intval($contact_record['id']),
				dbesc($contact_record['name']),
				dbesc($contact_record['url']),
				dbesc($contact_record['thumb']),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc($uri),
				dbesc($uri),
				dbesc($title),
				dbesc($body),
				dbesc($p[0]['allow_cid']),
				dbesc($p[0]['allow_gid']),
				dbesc($p[0]['deny_cid']),
				dbesc($p[0]['deny_gid'])

			);
			if($r) {
	
				$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' LIMIT 1",
					dbesc($uri)
				);
				if(count($r))
					$item_id = $r[0]['id'];
					q("UPDATE `item` SET `parent` = %d, `last-child` = 1 WHERE `id` = %d LIMIT 1",
					intval($r[0]['id']),
					intval($r[0]['id'])
				);
			}
		}

		$r = q("UPDATE `item` SET `tag` = '%s', `edited` = '%s', `changed` = '%s' WHERE `id` = %d AND `uid` = %d LIMIT 1",
			dbesc($tags),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($item_id),
			intval(get_uid())
		);

		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		return; // NOTREACHED
	}




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
		intval(get_uid())
	);
	if((! count($r)) || ($album == t('Profile Photos')))
		$visible = 1;
	else
		$visibile = 0;


	$str_group_allow   = perms2str($_POST['group_allow']);
	$str_contact_allow = perms2str($_POST['contact_allow']);
	$str_group_deny    = perms2str($_POST['group_deny']);
	$str_contact_deny  = perms2str($_POST['contact_deny']);

	$src               = $_FILES['userfile']['tmp_name'];
	$filename          = basename($_FILES['userfile']['name']);
	$filesize          = intval($_FILES['userfile']['size']);

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata);

	if(! ($image = $ph->getImage())) {
		notice( t('Unable to process image.') . EOL );
		@unlink($src);
		killme();
	}

	@unlink($src);

	$width = $ph->getWidth();
	$height = $ph->getHeight();

	$smallest = 0;

	$photo_hash = hash('md5',uniqid(mt_rand(),true));
	
	$r = $ph->store(get_uid(), 0, $photo_hash, $filename, $album, 0 , 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);

	if(! $r) {
		notice( t('Image upload failed.') . EOL );
		killme();
	}

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$ph->store(get_uid(), 0, $photo_hash, $filename, $album, 1, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 1;
	}

	if($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$ph->store(get_uid(), 0, $photo_hash, $filename, $album, 2, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 2;
	}
	
	$basename = basename($filename);

	// Create item container

	$body = '[url=' . $a->get_baseurl() . '/photos/' . $contact_record['nickname'] . '/image/' . $photo_hash . ']' 
		. '[img]' . $a->get_baseurl() . "/photo/{$photo_hash}-{$smallest}.jpg" . '[/img]' 
		. '[/url]';

	$uri = item_new_uri($a->get_hostname(), get_uid());

	$r = q("INSERT INTO `item` (`uid`, `type`, `wall`, `resource-id`, `contact-id`,`owner-name`,`owner-link`,`owner-avatar`, `created`,
		`edited`, `changed`, `uri`, `parent-uri`, `title`, `body`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid`, `visible`)
		VALUES( %d, '%s', %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d )",
		intval(get_uid()),
		dbesc('photo'),
		intval(1),
		dbesc($photo_hash),			
		intval($contact_record['id']),
		dbesc($contact_record['name']),
		dbesc($contact_record['url']),
		dbesc($contact_record['thumb']),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc($uri),
		dbesc($uri),
		dbesc($title),
		dbesc($body),
		dbesc($str_contact_allow),
		dbesc($str_group_allow),
		dbesc($str_contact_deny),
		dbesc($str_group_deny),
		intval($visible)
	);
	if($r) {

		$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' LIMIT 1",
			dbesc($uri)
		);
		if(count($r))
			q("UPDATE `item` SET `parent` = %d, `last-child` = 1 WHERE `id` = %d LIMIT 1",
			intval($r[0]['id']),
			intval($r[0]['id'])
		);
	
	}

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
	elseif(($a->argc > 2) && ($a->argv[2] == 'upload'))
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

	if(remote_user()) {
		$contact_id = $_SESSION['visitor_id'];
		$groups = init_groups_visitor($contact_id);
	}

	// default permissions - anonymous user

	$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = '' ";

	// Profile owner - everything is visible

	if(local_user() && (get_uid() == $owner_uid)) {
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


	if($datatype == 'upload') {
		if( ! (local_user() && (get_uid() == $a->data['user']['uid']))) {
			notice( t('Permission denied.'));
			return;
		}
		$albumselect = '<select id="photos-upload-album-select" name="album" size="4">';

		$albumselect .= '<option value="" selected="selected" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
		if(count($a->data['albums'])) {
			foreach($a->data['albums'] as $album) {
				if(($album['album'] == '') || ($album['album'] == t('Contact Photos')))
					continue;
				$albumselect .= '<option value="' . $album['album'] . '">' . $album['album'] . '</option>';
			}
		}
		$albumselect .= '</select>';
		$tpl = file_get_contents('view/photos_upload.tpl');
		$o .= replace_macros($tpl,array(
			'$pagename' => t('Upload Photos'),
			'$sessid' => session_id(),
			'$newalbum' => t('New album name: '),
			'$existalbumtext' => t('or existing album name: '),
			'$filestext' => t('Select files to upload: '),
			'$albumselect' => $albumselect,
			'$permissions' => t('Permissions'),
			'$aclselect' => populate_acl($a->user),
			'$archive' => $a->get_baseurl() . '/jumploader_z.jar',
			'$nojava' => t('Use the following controls only if the Java uploader (above) fails to launch.'),
			'$uploadurl' => $a->get_baseurl() . '/photos',
			'$submit' => t('Submit')
		));

		return $o; 

	}

	if($datatype == 'album') {

		$album = hex2bin($datum);

		$r = q("SELECT `resource-id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` = '%s' 
			$sql_extra GROUP BY `resource-id`",
			intval($a->data['user']['uid']),
			dbesc($album)
		);
		if(count($r))
			$a->set_pager_total(count($r));


		$r = q("SELECT `resource-id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` = '%s' 
			$sql_extra GROUP BY `resource-id` ORDER BY `created` DESC LIMIT %d , %d",
			intval($a->data['user']['uid']),
			dbesc($album),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);

		$o .= '<h3>' . $album . '</h3>';
		
		if($cmd == 'edit') {		
			if(($album != t('Profile Photos')) && ($album != t('Contact Photos'))) {
				if(local_user() && (get_uid() == $a->data['user']['uid'])) {
					$edit_tpl = file_get_contents('view/album_edit.tpl');
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
				if(local_user() && (get_uid() == $a->data['user']['uid'])) {
					$o .= '<div id="album-edit-link"><a href="'. $a->get_baseurl() . '/photos/' 
						. $a->data['user']['nickname'] . '/album/' . bin2hex($album) . '/edit' . '">' 
						. t('Edit Album') . '</a></div>';
 				}
			}
		}
		$tpl = file_get_contents('view/photo_album.tpl');
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


	if($datatype == 'image') {

		require_once('security.php');
		require_once('bbcode.php');

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
 
		if(local_user() && ($ph[0]['uid'] == get_uid())) {
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
				AND NOT `item`.`type` IN ( 'remote', 'net-comment') 
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 
				$sql_extra ",
				dbesc($i1[0]['uri']),
				dbesc($i1[0]['uri'])

			);

			if(count($r))
				$a->set_pager_total($r[0]['total']);


			$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, 
				`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`, 
				`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
				FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `parent-uri` = '%s' AND `uri` != '%s' AND `item`.`deleted` = 0
				AND NOT `item`.`type` IN ( 'remote', 'net-comment') 
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				$sql_extra
				ORDER BY `parent` DESC, `id` ASC LIMIT %d ,%d ",
				dbesc($i1[0]['uri']),
				dbesc($i1[0]['uri']),
				intval($a->pager['start']),
				intval($a->pager['itemspage'])

			);
		}

		$o .= '<div id="photo-caption" >' . $ph[0]['desc'] . '</div>';

		if(count($i1) && strlen($i1[0]['tag'])) {
			// parse tags and add links	
			$o .= '<div id="in-this-photo-text">' . t('In this photo: ') . '</div>';
			$o .= '<div id="in-this-photo">' . $i1[0]['tag'] . '</div>';
		}

		if($cmd == 'edit') {
			$edit_tpl = file_get_contents('view/photo_edit.tpl');
			$o .= replace_macros($edit_tpl, array(
				'$id' => $ph[0]['id'],
				'$resource_id' => $ph[0]['resource-id'],
				'$capt_label' => t('Caption'),
				'$caption' => $ph[0]['desc'],
				'$tag_label' => t('Tags'),
				'$tags' => $i1[0]['tag'],
				'$item_id' => ((count($i1)) ? $i1[0]['id'] : 0),
				'$submit' => t('Submit'),
				'$delete' => t('Delete Photo')

			));
		}

		if(count($i1)) {
			// pull out how many people like the photo

			$cmnt_tpl = file_get_contents('view/comment_item.tpl');
			$tpl = file_get_contents('view/photo_item.tpl');
			$return_url = $a->cmd;

			if(can_write_wall($a,$a->data['user']['uid'])) {
				if($i1[0]['last-child']) {
					$o .= replace_macros($cmnt_tpl,array(
						'$return_path' => $return_url,
						'$type' => 'wall-comment',
						'$id' => $i1[0]['id'],
						'$parent' => $i1[0]['id'],
						'$profile_uid' =>  $a->data['user']['uid'],
						'$ww' => ''
					));
				}
			}


			// display comments
			if(count($r)) {
				foreach($r as $item) {
					$comment = '';
					$template = $tpl;
			
					$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;
			
					if(can_write_wall($a,$a->data['user']['uid'])) {
						if($item['last-child']) {
							$comment = replace_macros($cmnt_tpl,array(
								'$return_path' => $return_url,
								'$type' => 'wall-comment',
								'$id' => $item['item_id'],
								'$parent' => $item['parent'],
								'$profile_uid' =>  $a->data['user']['uid'],
								'$ww' => ''
							));
						}
					}

					$profile_url = $item['url'];

					if(local_user() && ($item['contact-uid'] == get_uid()) 
						&& ($item['rel'] == DIRECTION_IN || $item['rel'] == DIRECTION_BOTH) && (! $item['self'] ))
						$profile_url = $redirect_url;
 
					$profile_name = ((strlen($item['author-name'])) ? $item['author-name'] : $item['name']);
					$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $item['thumb']);
					$profile_link = $profile_url;

					$drop = '';

					if(($item['contact-id'] == $_SESSION['visitor_id']) || ($item['uid'] == get_uid()))
						$drop = replace_macros(file_get_contents('view/wall_item_drop.tpl'), array('$id' => $item['id']));


					$o .= replace_macros($template,array(
						'$id' => $item['item_id'],
						'$profile_url' => $profile_link,
						'$name' => $profile_name,
						'$thumb' => $profile_avatar,
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

	$r = q("SELECT `resource-id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` != '%s' 
		$sql_extra GROUP BY `resource-id`",
		intval($a->data['user']['uid']),
		dbesc( t('Contact Photos'))
	);
	if(count($r))
		$a->set_pager_total(count($r));


	$r = q("SELECT `resource-id`, `album`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` != '%s' 
		$sql_extra GROUP BY `resource-id` ORDER BY `created` DESC LIMIT %d , %d",
		intval($a->data['user']['uid']),
		dbesc( t('Contact Photos')),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$o .= '<h3>' . t('Recent Photos') . '</h3>';

	if( local_user() && (get_uid() == $a->data['user']['uid'])) {
		$o .= '<div id="photo-top-links"><a id="photo-top-upload-link" href="'. $a->get_baseurl() . '/photos/' 
			. $a->data['user']['nickname'] . '/upload' . '">' . t('Upload New Photos') . '</a></div>';
	}

	$tpl = file_get_contents('view/photo_top.tpl');
	if(count($r)) {
		foreach($r as $rr) {
			$o .= replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$photolink' => $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] 
					. '/image/' . $rr['resource-id'],
				'$phototitle' => t('View Photo'),
				'$imgsrc' => $a->get_baseurl() . '/photo/' 
					. $rr['resource-id'] . '-' . $rr['scale'] . '.jpg',
				'$albumlink' => $a->get_baseurl . '/photos/' 
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
