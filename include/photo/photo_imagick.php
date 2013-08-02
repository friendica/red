<?php /** @file */


require_once('include/photo/photo_driver.php');


class photo_imagick extends photo_driver {


	function supportedTypes() {
		return array(
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif'
		);
	}

	public function get_FormatsMap() {
		return array(
			'image/jpeg' => 'JPG',
			'image/png' => 'PNG',
			'image/gif' => 'GIF'
		);
	}


	function load($data, $type) {
		$this->valid = false;
		$this->image = new Imagick();

		if(! $data)
			return;

		$this->image->readImageBlob($data);


		/**
		 * Setup the image to the format it will be saved to
		 */

		$map = $this->get_FormatsMap();
		$format = $map[$type];

		if($this->image) {
			$this->image->setFormat($format);

			// Always coalesce, if it is not a multi-frame image it won't hurt anyway
			$this->image = $this->image->coalesceImages();


			$this->valid = true;
			$this->setDimensions();

			/**
			 * setup the compression here, so we'll do it only once
			 */
			switch($this->getType()) {
				case "image/png":
					$quality = get_config('system','png_quality');
					if((! $quality) || ($quality > 9))
						$quality = PNG_QUALITY;
					/**
					 * From http://www.imagemagick.org/script/command-line-options.php#quality:
					 *
					 * 'For the MNG and PNG image formats, the quality value sets
					 * the zlib compression level (quality / 10) and filter-type (quality % 10).
					 * The default PNG "quality" is 75, which means compression level 7 with adaptive PNG filtering,
					 * unless the image has a color map, in which case it means compression level 7 with no PNG filtering'
					 */
					$quality = $quality * 10;
					$this->image->setCompressionQuality($quality);
					break;
				case "image/jpeg":
					$quality = get_config('system','jpeg_quality');
					if((! $quality) || ($quality > 100))
						$quality = JPEG_QUALITY;
					$this->image->setCompressionQuality($quality);
				default:
					break;

			}
		}
	}

	public function destroy() {
		if($this->is_valid()) {
			$this->image->clear();
			$this->image->destroy();
		}
	}


	public function setDimensions() {
		$this->width = $this->image->getImageWidth();
		$this->height = $this->image->getImageHeight();
	}


	public function getImage() {
		if(!$this->is_valid())
			return FALSE;

		$this->image = $this->image->deconstructImages();
		return $this->image;
	}

	public function doScaleImage($dest_width,$dest_height) {

		/**
		 * If it is not animated, there will be only one iteration here,
		 * so don't bother checking
		 */
		// Don't forget to go back to the first frame
		$this->image->setFirstIterator();
		do {
			$this->image->scaleImage($dest_width, $dest_height);
		} while ($this->image->nextImage());

		$this->setDimensions();
	}

	public function rotate($degrees) {
		if(!$this->is_valid())
			return FALSE;

		$this->image->setFirstIterator();
		do {
			// ImageMagick rotates in the opposite direction of imagerotate()
			$this->image->rotateImage(new ImagickPixel(), -$degrees); 
		} while ($this->image->nextImage());

		$this->setDimensions();
	}

	public function flip($horiz = true, $vert = false) {
		if(!$this->is_valid())
			return FALSE;

		$this->image->setFirstIterator();
		do {
			if($horiz) $this->image->flipImage();
			if($vert) $this->image->flopImage();
		} while ($this->image->nextImage());

		$this->setDimensions(); // Shouldn't really be necessary
	}

	public function cropImage($max,$x,$y,$w,$h) {
		if(!$this->is_valid())
			return FALSE;

		$this->image->setFirstIterator();
		do {
			$this->image->cropImage($w, $h, $x, $y);
			/**
			 * We need to remove the canvas,
			 * or the image is not resized to the crop:
			 * http://php.net/manual/en/imagick.cropimage.php#97232
			 */
			$this->image->setImagePage(0, 0, 0, 0);
		} while ($this->image->nextImage());

		$this->doScaleImage($max,$max);
	}

	public function imageString() {
		if(!$this->is_valid())
			return FALSE;

		/* Clean it */
		$this->image = $this->image->deconstructImages();
		return $this->image->getImagesBlob();
	}



}