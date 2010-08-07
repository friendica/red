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


}




function photos_post(&$a) {


        if(! local_user()) {
                notice( t('Permission denied.') . EOL );
                killme();
        }

	$r = q("SELECT * FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid` WHERE `user`.`uid` = %d AND `self` = 1 LIMIT 1",
		intval($_SESSION['uid'])
	);

	$contact_record = $r[0];	

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

	$str_group_allow = '';
	$group_allow = $_POST['group_allow'];
	if(is_array($group_allow)) {
		array_walk($group_allow,'sanitise_acl');
		$str_group_allow = implode('',$group_allow);
	}

	$str_contact_allow = '';
	$contact_allow = $_POST['contact_allow'];
	if(is_array($contact_allow)) {
		array_walk($contact_allow,'sanitise_acl');
		$str_contact_allow = implode('',$contact_allow);
	}

	$str_group_deny = '';
	$group_deny = $_POST['group_deny'];
	if(is_array($group_deny)) {
		array_walk($group_deny,'sanitise_acl');
		$str_group_deny = implode('',$group_deny);
	}

	$str_contact_deny = '';
	$contact_deny = $_POST['contact_deny'];
	if(is_array($contact_deny)) {
		array_walk($contact_deny,'sanitise_acl');
		$str_contact_deny = implode('',$contact_deny);
	}


	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);

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
	
	$r = $ph->store($_SESSION['uid'], 0, $photo_hash, $filename, $album, 0 , 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);

	if(! $r) {
		notice( t('Image upload failed.') . EOL );
		killme();
	}

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$ph->store($_SESSION['uid'], 0, $photo_hash, $filename, $album, 1, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 1;
	}

	if($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$ph->store($_SESSION['uid'], 0, $photo_hash, $filename, $album, 2, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 2;
	}
	
	$basename = basename($filename);

	// Create item container

	$body = '[url=' . $a->get_baseurl() . '/photos/' . $contact_record['nickname'] . '/image/' . $photo_hash . ']' 
		. '[img]' . $a->get_baseurl() . "/photo/{$photo_hash}-{$smallest}.jpg" . '[/img]' 
		. '[/url]';

			do {
			$dups = false;
			$item_hash = random_string();

			$uri = "urn:X-dfrn:" . $a->get_hostname() . ':' . $profile_uid . ':' . $item_hash;

			$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' LIMIT 1",
			dbesc($uri));
			if(count($r))
				$dups = true;
		} while($dups == true);


		$r = q("INSERT INTO `item` (`uid`, `type`, `resource-id`, `contact-id`,`owner-name`,`owner-link`,`owner-avatar`, `created`,
			`edited`, `uri`, `parent-uri`, `title`, `body`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid`)
			VALUES( %d, '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )",
			intval($_SESSION['uid']),
			dbesc('photo'),
			dbesc($photo_hash),			
			intval($contact_record['id']),
			dbesc($contact_record['name']),
			dbesc($contact_record['url']),
			dbesc($contact_record['thumb']),
			datetime_convert(),
			datetime_convert(),
			dbesc($uri),
			dbesc($uri),
			dbesc($title),
			dbesc($body),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny)

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

	// if album has no featured photo, promote one.


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
	// photos/name/album/xxxxx/drop
	// photos/name/image/xxxxx
	// photos/name/image/xxxxx/edit
	// photos/name/image/xxxxx/drop

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

	if(local_user() && ($_SESSION['uid'] == $owner_uid)) {
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
		if( ! (local_user() && ($_SESSION['uid'] == $a->data['user']['uid']))) {
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
			'$aclselect' => populate_acl(),
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
 
		$o .= '<a href="' . $a->get_baseurl() . '/photo/' 
			. $hires['resource-id'] . '-' . $hires['scale'] . '.jpg" title="' 
			. t('View Full Size') . '" ><img src="' . $a->get_baseurl() . '/photo/' 
			. $lores['resource-id'] . '-' . $lores['scale'] . '.jpg' . '" /></a>';

		// Do we have an item for this photo?

		$i1 = q("SELECT * FROM `item` WHERE `resource-id` = '%s' $sql_extra LIMIT 1",
			dbesc($datum)
		);
		if(count($i1)) {
//dbg(2);
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


			$o .= '<div id="photo-caption" >' . $ph[0]['desc'] . '</div>';

			if(strlen($i1[0]['tag'])) {
				// parse tags and add links	
				$o .= '<div id="in-this-photo-text">' . t('In this photo: ') . '</div>';
				$o .= '<div id="in-this-photo">' . $i1[0]['tag'] . '</div>';
			}

			if($cmd == 'edit') {
				$edit_tpl = file_get_contents('view/photo_edit.tpl');
				$o .= replace_macros($edit_tpl, array(
					'$id' => $ph[0]['id']
				));
			}

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


					if(local_user() && ($item['contact-uid'] == $_SESSION['uid']) && (strlen($item['dfrn-id'])) && (! $item['self'] ))
						$profile_url = $redirect_url;

 
					$profile_name = ((strlen($item['author-name'])) ? $item['author-name'] : $item['name']);
					$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $item['thumb']);
					$profile_link = $profile_url;

					$drop = '';

					if(($item['contact-id'] == $_SESSION['visitor_id']) || ($item['uid'] == $_SESSION['uid']))
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

	if( local_user() && ($_SESSION['uid'] == $a->data['user']['uid'])) {
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
