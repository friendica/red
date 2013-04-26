<?php /** @file */


require_once('include/photo/photo_driver.php');


class photo_gd extends photo_driver {

	function supportedTypes() {
		$t = array();
		$t['image/jpeg'] ='jpg';
		if (imagetypes() & IMG_PNG) $t['image/png'] = 'png';

		return $t;

	}

	function load($data, $type) {
		$this->valid = false;
		$this->image = @imagecreatefromstring($data);
		if($this->image !== FALSE) {
			$this->valid  = true;
			$this->setDimensions();
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
		}
	}

	function setDimensions() {
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}


	public function destroy() {
		if($this->is_valid()) {
			imagedestroy($this->image);
		}
	}

	public function getImage() {
		if(!$this->is_valid())
			return FALSE;

		return $this->image;
	}

	public function doScaleImage($dest_width,$dest_height) {

		$dest = imagecreatetruecolor( $dest_width, $dest_height );
		$width = imagesx($this->image);
		$height = imagesy($this->image);

		imagealphablending($dest, false);
		imagesavealpha($dest, true);
		if ($this->type=='image/png') imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127)); // fill with alpha
		imagecopyresampled($dest, $this->image, 0, 0, 0, 0, $dest_width, $dest_height, $width, $height);
		if($this->image)
			imagedestroy($this->image);
		$this->image = $dest;
		$this->setDimensions();
	}

	public function rotate($degrees) {
		if(!$this->is_valid())
			return FALSE;

		$this->image  = imagerotate($this->image,$degrees,0);
		$this->setDimensions();
	}

	public function flip($horiz = true, $vert = false) {
		if(!$this->is_valid())
			return FALSE;

		$w = imagesx($this->image);
		$h = imagesy($this->image);
		$flipped = imagecreate($w, $h);
		if($horiz) {
			for ($x = 0; $x < $w; $x++) {
				imagecopy($flipped, $this->image, $x, 0, $w - $x - 1, 0, 1, $h);
			}
		}
		if($vert) {
			for ($y = 0; $y < $h; $y++) {
				imagecopy($flipped, $this->image, 0, $y, 0, $h - $y - 1, $w, 1);
			}
		}
		$this->image = $flipped;
		$this->setDimensions(); // Shouldn't really be necessary
	}

	public function cropImage($max,$x,$y,$w,$h) {
		if(!$this->is_valid())
			return FALSE;

		$dest = imagecreatetruecolor( $max, $max );
		imagealphablending($dest, false);
		imagesavealpha($dest, true);
		if ($this->type=='image/png') imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127)); // fill with alpha
		imagecopyresampled($dest, $this->image, 0, 0, $x, $y, $max, $max, $w, $h);
		if($this->image)
			imagedestroy($this->image);
		$this->image = $dest;
		$this->setDimensions();
	}

	public function imageString() {
		if(!$this->is_valid())
			return FALSE;

		$quality = FALSE;

		ob_start();

		switch($this->getType()){
			case "image/png":
				$quality = get_config('system','png_quality');
				if((! $quality) || ($quality > 9))
					$quality = PNG_QUALITY;
				imagepng($this->image,NULL, $quality);
				break;
			case "image/jpeg":
			default:
				$quality = get_config('system','jpeg_quality');
				if((! $quality) || ($quality > 100))
					$quality = JPEG_QUALITY;
				imagejpeg($this->image,NULL,$quality);
				break;
		}
		$string = ob_get_contents();
		ob_end_clean();

		return $string;
	}

}