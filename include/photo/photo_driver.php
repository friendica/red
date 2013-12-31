<?php /** @file */

function photo_factory($data, $type = null) {
	$ph = null;

	if(class_exists('Imagick')) {
		require_once('include/photo/photo_imagick.php');
		$ph = new photo_imagick($data,$type);
	}
	else {
		require_once('include/photo/photo_gd.php');
		$ph = new photo_gd($data,$type);
	}

	return $ph;
}




abstract class photo_driver {

	protected $image;
	protected $width;
	protected $height;
	protected $valid;
	protected $type;
	protected $types;

	abstract function supportedTypes();

	abstract function load($data,$type);

	abstract function destroy();

	abstract function setDimensions();

	abstract function getImage();

	abstract function doScaleImage($new_width,$new_height);

	abstract function rotate($degrees);

	abstract function flip($horiz = true, $vert = false);

	abstract function cropImage($max,$x,$y,$w,$h);

	abstract function imageString();


	public function __construct($data, $type='') {
		$this->types = $this->supportedTypes();
		if (! array_key_exists($type,$this->types)){
			$type='image/jpeg';
		}
		$this->type = $type;
		$this->valid = false;
		$this->load($data,$type);
	}

	public function __destruct() {
		if($this->is_valid())
			$this->destroy();
	}

	public function is_valid() {
		return $this->valid;
	}

	public function getWidth() {
		if(!$this->is_valid())
			return FALSE;
		return $this->width;
	}

	public function getHeight() {
		if(!$this->is_valid())
			return FALSE;
		return $this->height;
	}


	public function saveImage($path) {
		if(!$this->is_valid())
			return FALSE;
		file_put_contents($path, $this->imageString());
	}


	public function getType() {
		if(!$this->is_valid())
			return FALSE;

		return $this->type;
	}

	public function getExt() {
		if(!$this->is_valid())
			return FALSE;

		return $this->types[$this->getType()];
	}

	public function scaleImage($max) {
		if(!$this->is_valid())
			return FALSE;

		$width = $this->width;
		$height = $this->height;

		$dest_width = $dest_height = 0;

		if((! $width)|| (! $height))
			return FALSE;

		if($width > $max && $height > $max) {

			// very tall image (greater than 16:9)
			// constrain the width - let the height float.

			if((($height * 9) / 16) > $width) {
				$dest_width = $max;
	 			$dest_height = intval(( $height * $max ) / $width);
			}

			// else constrain both dimensions

			elseif($width > $height) {
				$dest_width = $max;
				$dest_height = intval(( $height * $max ) / $width);
			}
			else {
				$dest_width = intval(( $width * $max ) / $height);
				$dest_height = $max;
			}
		}
		else {
			if( $width > $max ) {
				$dest_width = $max;
				$dest_height = intval(( $height * $max ) / $width);
			}
			else {
				if( $height > $max ) {

					// very tall image (greater than 16:9)
					// but width is OK - don't do anything

					if((($height * 9) / 16) > $width) {
						$dest_width = $width;
	 					$dest_height = $height;
					}
					else {
						$dest_width = intval(( $width * $max ) / $height);
						$dest_height = $max;
					}
				}
				else {
					$dest_width = $width;
					$dest_height = $height;
				}
			}
		}
		$this->doScaleImage($dest_width,$dest_height);
	}

	public function scaleImageUp($min) {
		if(!$this->is_valid())
			return FALSE;


		$width = $this->width;
		$height = $this->height;

		$dest_width = $dest_height = 0;

		if((! $width)|| (! $height))
			return FALSE;

		if($width < $min && $height < $min) {
			if($width > $height) {
				$dest_width = $min;
				$dest_height = intval(( $height * $min ) / $width);
			}
			else {
				$dest_width = intval(( $width * $min ) / $height);
				$dest_height = $min;
			}
		}
		else {
			if( $width < $min ) {
				$dest_width = $min;
				$dest_height = intval(( $height * $min ) / $width);
			}
			else {
				if( $height < $min ) {
					$dest_width = intval(( $width * $min ) / $height);
					$dest_height = $min;
				}
				else {
					$dest_width = $width;
					$dest_height = $height;
				}
			}
		}
		$this->doScaleImage($dest_width,$dest_height);
	}

	public function scaleImageSquare($dim) {
		if(!$this->is_valid())
			return FALSE;
		$this->doScaleImage($dim,$dim);
	}




	public function orient($filename) {

		/**
		 * This function is a bit unusual, because it is operating on a file, but you must
		 * first create an image from that file to initialise the type and check validity
		 * of the image.
		 */

		if(! $this->is_valid())
			return FALSE;

		if((! function_exists('exif_read_data')) || ($this->getType() !== 'image/jpeg'))
			return;

		$exif = @exif_read_data($filename);
		if($exif) {
			$ort = $exif['Orientation'];

			switch($ort)
			{
				case 1: // nothing
					break;

				case 2: // horizontal flip
					$this->flip();
					break;

				case 3: // 180 rotate left
					$this->rotate(180);
					break;

				case 4: // vertical flip
					$this->flip(false, true);
					break;

				case 5: // vertical flip + 90 rotate right
					$this->flip(false, true);
					$this->rotate(-90);
					break;

				case 6: // 90 rotate right
					$this->rotate(-90);
					break;

				case 7: // horizontal flip + 90 rotate right
					$this->flip();
					$this->rotate(-90);
					break;

				case 8:	// 90 rotate left
					$this->rotate(90);
					break;
			}
		}
	}


	public function save($arr) {

		$p = array();

		$p['aid'] = ((intval($arr['aid'])) ? intval($arr['aid']) : 0);
		$p['uid'] = ((intval($arr['uid'])) ? intval($arr['uid']) : 0);
		$p['xchan'] = (($arr['xchan']) ? $arr['xchan'] : '');
		$p['resource_id'] = (($arr['resource_id']) ? $arr['resource_id'] : '');
		$p['filename'] = (($arr['filename']) ? $arr['filename'] : '');
		$p['album'] = (($arr['album']) ? $arr['album'] : '');
		$p['scale'] = ((intval($arr['scale'])) ? intval($arr['scale']) : 0);
		$p['photo_flags'] = ((intval($arr['photo_flags'])) ? intval($arr['photo_flags']) : 0);
		$p['allow_cid'] = (($arr['allow_cid']) ? $arr['allow_cid'] : '');
		$p['allow_gid'] = (($arr['allow_gid']) ? $arr['allow_gid'] : '');
		$p['deny_cid'] = (($arr['deny_cid']) ? $arr['deny_cid'] : '');
		$p['deny_gid'] = (($arr['deny_gid']) ? $arr['deny_gid'] : '');

		// temporary until we get rid of photo['profile'] and just use photo['photo_flags']
		// but this will require updating all existing photos in the DB.

		$p['profile'] = (($p['photo_flags'] & PHOTO_PROFILE) ? 1 : 0);
			

		$x = q("select id from photo where resource_id = '%s' and uid = %d and xchan = '%s' and `scale` = %d limit 1",
				dbesc($p['resource_id']),
				intval($p['uid']),
				dbesc($p['xchan']),
				intval($p['scale'])
		);
		if($x) {
			$r = q("UPDATE `photo` set
				`aid` = %d,
				`uid` = %d,
				`xchan` = '%s',
				`resource_id` = '%s',
				`created` = '%s',
				`edited` = '%s',
				`filename` = '%s',
				`type` = '%s',
				`album` = '%s',
				`height` = %d,
				`width` = %d,
				`data` = '%s',
				`size` = %d,
				`scale` = %d,
				`profile` = %d,
				`photo_flags` = %d,
				`allow_cid` = '%s',
				`allow_gid` = '%s',
				`deny_cid` = '%s',
				`deny_gid` = '%s'
				where id = %d limit 1",

				intval($p['aid']),
				intval($p['uid']),
				dbesc($p['xchan']),
				dbesc($p['resource_id']),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(basename($p['filename'])),
				dbesc($this->getType()),
				dbesc($p['album']),
				intval($this->getHeight()),
				intval($this->getWidth()),
				dbesc($this->imageString()),
				intval(strlen($this->imageString())),
				intval($p['scale']),
				intval($p['profile']),
				intval($p['photo_flags']),
				dbesc($p['allow_cid']),
				dbesc($p['allow_gid']),
				dbesc($p['deny_cid']),
				dbesc($p['deny_gid']),
				intval($x[0]['id'])
			);
		}
		else {
			$r = q("INSERT INTO `photo`
				( `aid`, `uid`, `xchan`, `resource_id`, `created`, `edited`, `filename`, type, `album`, `height`, `width`, `data`, `size`, `scale`, `profile`, `photo_flags`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid` )
				VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', %d, %d, %d, %d, '%s', '%s', '%s', '%s' )",
				intval($p['aid']),
				intval($p['uid']),
				dbesc($p['xchan']),
				dbesc($p['resource_id']),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(basename($filename)),
				dbesc($this->getType()),
				dbesc($p['album']),
				intval($this->getHeight()),
				intval($this->getWidth()),
				dbesc($this->imageString()),
				intval(strlen($this->imageString())),
				intval($p['scale']),
				intval($p['profile']),
				intval($p['photo_flags']),
				dbesc($p['allow_cid']),
				dbesc($p['allow_gid']),
				dbesc($p['deny_cid']),
				dbesc($p['deny_gid'])
			);
		}
		return $r;
	}

	public function store($aid, $uid, $xchan, $rid, $filename, $album, $scale, $profile = 0, $allow_cid = '', $allow_gid = '', $deny_cid = '', $deny_gid = '') {

		$x = q("select id from photo where `resource_id` = '%s' and uid = %d and `xchan` = '%s' and `scale` = %d limit 1",
				dbesc($rid),
				intval($uid),
				dbesc($xchan),
				intval($scale)
		);
		if(count($x)) {
			$r = q("UPDATE `photo`
				set `aid` = %d,
				`uid` = %d,
				`xchan` = '%s',
				`resource_id` = '%s',
				`created` = '%s',
				`edited` = '%s',
				`filename` = '%s',
				`type` = '%s',
				`album` = '%s',
				`height` = %d,
				`width` = %d,
				`data` = '%s',
				`size` = %d,
				`scale` = %d,
				`profile` = %d,
				`allow_cid` = '%s',
				`allow_gid` = '%s',
				`deny_cid` = '%s',
				`deny_gid` = '%s'
				where id = %d limit 1",

				intval($aid),
				intval($uid),
				dbesc($xchan),
				dbesc($rid),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(basename($filename)),
				dbesc($this->getType()),
				dbesc($album),
				intval($this->getHeight()),
				intval($this->getWidth()),
				dbesc($this->imageString()),
				intval(strlen($this->imageString())),
				intval($scale),
				intval($profile),
				dbesc($allow_cid),
				dbesc($allow_gid),
				dbesc($deny_cid),
				dbesc($deny_gid),
				intval($x[0]['id'])
			);
		}
		else {
			$r = q("INSERT INTO `photo`
				( `aid`, `uid`, `xchan`, `resource_id`, `created`, `edited`, `filename`, type, `album`, `height`, `width`, `data`, `size`, `scale`, `profile`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid` )
				VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', %d, %d, %d, '%s', '%s', '%s', '%s' )",
				intval($aid),
				intval($uid),
				dbesc($xchan),
				dbesc($rid),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(basename($filename)),
				dbesc($this->getType()),
				dbesc($album),
				intval($this->getHeight()),
				intval($this->getWidth()),
				dbesc($this->imageString()),
				intval(strlen($this->imageString())),
				intval($scale),
				intval($profile),
				dbesc($allow_cid),
				dbesc($allow_gid),
				dbesc($deny_cid),
				dbesc($deny_gid)
			);
		}
		return $r;
	}

}








/**
 * Guess image mimetype from filename or from Content-Type header
 *
 * @arg $filename string Image filename
 * @arg $fromcurl boolean Check Content-Type header from curl request
 */

function guess_image_type($filename, $headers = '') {
	logger('Photo: guess_image_type: '.$filename . ($fromcurl?' from curl headers':''), LOGGER_DEBUG);
	$type = null;
	if ($headers) {
		$a = get_app();
		$hdrs=array();
		$h = explode("\n",$headers);
		foreach ($h as $l) {
			list($k,$v) = array_map("trim", explode(":", trim($l), 2));
			$hdrs[$k] = $v;
		}
		if (array_key_exists('Content-Type', $hdrs))
			$type = $hdrs['Content-Type'];
	}
	if (is_null($type)){
// FIXME!!!!
		// Guessing from extension? Isn't that... dangerous?
		if(class_exists('Imagick') && file_exists($filename) && is_readable($filename)) {
			/**
			 * Well, this not much better,
			 * but at least it comes from the data inside the image,
			 * we won't be tricked by a manipulated extension
			 */
			$image = new Imagick($filename);
			$type = $image->getImageMimeType();
		} else {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$ph = photo_factory('');
			$types = $ph->supportedTypes();
			$type = "image/jpeg";
			foreach ($types as $m=>$e){
				if ($ext==$e) $type = $m;
			}
		}
	}
	logger('Photo: guess_image_type: type='.$type, LOGGER_DEBUG);
	return $type;

}

function import_profile_photo($photo,$xchan,$thing = false) {

	$a = get_app();

	$flags = (($thing) ? PHOTO_THING : PHOTO_XCHAN);
	$album = (($thing) ? 'Things' : 'Contact Photos');

	logger('import_profile_photo: updating channel photo from ' . $photo . ' for ' . $xchan, LOGGER_DEBUG);

	if($thing)
		$hash = photo_new_resource();
	else {
		$r = q("select resource_id from photo where xchan = '%s' and (photo_flags & %d ) scale = 4 limit 1",
			dbesc($xchan),
			intval(PHOTO_XCHAN)
		);
		if($r) {
			$hash = $r[0]['resource_id'];
		}
		else {
			$hash = photo_new_resource();
		}
	}

	$photo_failure = false;


	$filename = basename($photo);
	$type = guess_image_type($photo,true);
	$result = z_fetch_url($photo,true);

	if($result['success'])
		$img_str = $result['body'];

	$img = photo_factory($img_str, $type);
	if($img->is_valid()) {
		$width = $img->getWidth();
		$height = $img->getHeight();
	
		if($width && $height) {
			if(($width / $height) > 1.2) {
				// crop out the sides
				$margin = $width - $height;
				$img->cropImage(175,($margin / 2),0,$height,$height); 
			}
			elseif(($height / $width) > 1.2) {
				// crop out the bottom
				$margin = $height - $width;
				$img->cropImage(175,0,0,$width,$width);

			}
			else {
				$img->scaleImageSquare(175);
			}

		}
		else 
			$photo_failure = true;

		$p = array('xchan' => $xchan,'resource_id' => $hash, 'filename' => basename($photo), 'album' => $album, 'photo_flags' => $flags, 'scale' => 4);

		$r = $img->save($p);

		if($r === false)
			$photo_failure = true;

		$img->scaleImage(80);
		$p['scale'] = 5;

		$r = $img->save($p);

		if($r === false)
			$photo_failure = true;

		$img->scaleImage(48);
		$p['scale'] = 6;

		$r = $img->save($p);

		if($r === false)
			$photo_failure = true;

		$photo = $a->get_baseurl() . '/photo/' . $hash . '-4';
		$thumb = $a->get_baseurl() . '/photo/' . $hash . '-5';
		$micro = $a->get_baseurl() . '/photo/' . $hash . '-6';
	}
	else {
		logger('import_profile_photo: invalid image from ' . $photo);	
		$photo_failure = true;
	}
	if($photo_failure) {
		$photo = $a->get_baseurl() . '/' . get_default_profile_photo();
		$thumb = $a->get_baseurl() . '/' . get_default_profile_photo(80);
		$micro = $a->get_baseurl() . '/' . get_default_profile_photo(48);
		$type = 'image/jpeg';
	}

	return(array($photo,$thumb,$micro,$type));

}



function import_channel_photo($photo,$type,$aid,$uid) {

	$a = get_app();

	logger('import_channel_photo: importing channel photo for ' . $uid, LOGGER_DEBUG);

	$hash = photo_new_resource();

	$photo_failure = false;


	$filename = $hash;

	$img = photo_factory($photo, $type);
	if($img->is_valid()) {

		$img->scaleImageSquare(175);

		$p = array('aid' => $aid, 'uid' => $uid, 'resource_id' => $hash, 'filename' => $filename, 'album' => t('Profile Photos'), 'photo_flags' => PHOTO_PROFILE, 'scale' => 4);

		$r = $img->save($p);

		if($r === false)
			$photo_failure = true;

		$img->scaleImage(80);
		$p['scale'] = 5;

		$r = $img->save($p);

		if($r === false)
			$photo_failure = true;

		$img->scaleImage(48);
		$p['scale'] = 6;

		$r = $img->save($p);

		if($r === false)
			$photo_failure = true;

	}
	else {
		logger('import_channel_photo: invalid image.');
		$photo_failure = true;
	}

	return(($photo_failure)? false : true);

}
