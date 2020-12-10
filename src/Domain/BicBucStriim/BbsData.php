<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 
require_once 'data_constants.php';
require_once 'calibre_thing.php';

class BbsData {

	# Thumbnail dimension (they are square)
	const THUMB_RES = 160;

	protected $data_dir;
	protected $authors_dir;

	function __construct($db, $data_dir) {
    	R::setup('sqlite:'.$db); 
    	$this->data_dir = $data_dir;
    	$this->authors_dir = $this->data_dir . '/authors';
    	if (!file_exists($this->authors_dir))
			mkdir($this->authors_dir);	
	}

	public function getCalibreThing($calibreType, $calibreId){
		return R::findOne('calibrething', 
			' ctype = :type and cid = :id',
			array(
				':type' => $calibreType,
				'id' => $calibreId
				)
			);
	}

	public function addCalibreThing($calibreType, $calibreId, $calibreName) {
		$calibreThing = R::dispense('calibrething');
		$calibreThing->ctype = $calibreType;
		$calibreThing->cid = $calibreId;
		$calibreThing->cname = $calibreName;
		$calibreThing->ownArtefact = array();
		$id = R::store($calibreThing);
		return $calibreThing;
	}

	public function deleteAuthorThumbnail($authorId) {
		$ret = true;
		$calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
		if (!is_null($calibreThing)) {
			$artefact = $calibreThing->getAuthorThumbnail();
			if (!is_null($artefact)) {
				$ret = unlink($artefact->url);
				unset($calibreThing->ownArtefact[$artefact->id]);
				R::store($calibreThing);
			}
		}
		return $ret;
	}

	public function editAuthorThumbnail($authorId, $authorName, $clipped, $file) {
		$calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
		if (is_null($calibreThing))
			$calibreThing = $this->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId, $authorName);		

		$fname = $this->authors_dir. '/author_'.$calibreThing->id.'_thm.png';
		if ($clipped) 
			$created = $this->thumbnailClipped($file, self::THUMB_RES, self::THUMB_RES, $fname);
		else 
			$created = $this->thumbnailStuffed($file, self::THUMB_RES, self::THUMB_RES, $fname);

		$artefact = $calibreThing->getAuthorThumbnail();
		if (is_null($artefact)) {
			$artefact = R::dispense('artefact');
			$artefact->atype = DataConstants::AUTHOR_THUMBNAIL_ARTEFACT;
			$artefact->url = $fname;
			$calibreThing->ownArtefact[] = $artefact;			
			R::store($calibreThing);
		}
		return $created;
	}

	public function getAuthorThumbnail($authorId) {
		$calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
		if (is_null($calibreThing)) {
			return null;
		} else {
			return $calibreThing->getAuthorThumbnail();
		}
	}


	/**
	 * Create a square thumbnail by clipping the largest possible square from the cover
	 * @param  string $cover      path to book cover image
	 * @param  int 	 	$newwidth   required thumbnail width
	 * @param  int 		$newheight  required thumbnail height
	 * @param  string $thumb_path path for thumbnail storage
	 * @return bool             	true = thumbnail created
	 */
	private function thumbnailClipped($cover, $newwidth, $newheight, $thumb_path) {
		list($width, $height) = getimagesize($cover);
		$thumb = imagecreatetruecolor($newwidth, $newheight);
		$source = imagecreatefromjpeg($cover);
		$minwh = min(array($width, $height));
		$newx = ($width / 2) - ($minwh / 2);
		$newy = ($height / 2) - ($minwh / 2);
		$inbetween = imagecreatetruecolor($minwh, $minwh);
		imagecopy($inbetween, $source, 0, 0, $newx, $newy, $minwh, $minwh);				
		imagecopyresized($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $minwh, $minwh);
		$created = imagepng($thumb, $thumb_path);				
		return $created;
	}

	/**
	 * Create a square thumbnail by stuffing the cover at the edges
	 * @param  string $cover      path to book cover image
	 * @param  int 	 	$newwidth   required thumbnail width
	 * @param  int 		$newheight  required thumbnail height
	 * @param  string $thumb_path path for thumbnail storage
	 * @return bool             	true = thumbnail created
	 */
	private function thumbnailStuffed($cover, $newwidth, $newheight, $thumb_path) {
		list($width, $height) = getimagesize($cover);
		$thumb = Utilities::transparentImage($newwidth, $newheight);
		$source = imagecreatefromjpeg($cover);
		$dstx = 0;
		$dsty = 0;
		$maxwh = max(array($width, $height));
		if ($height > $width) {
			$diff = $maxwh - $width;
			$dstx = (int) $diff/2;
		} else {
			$diff = $maxwh - $height;
			$dsty = (int) $diff/2;
		}
		$inbetween = $this->transparentImage($maxwh, $maxwh);
		imagecopy($inbetween, $source, $dstx, $dsty, 0, 0, $width, $height);				
		imagecopyresampled($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $maxwh, $maxwh);
		$created = imagepng($thumb, $thumb_path);				
		imagedestroy($thumb);
		imagedestroy($inbetween);
		imagedestroy($source);
		return $created;
	}

	/**
	 * Create an image with transparent background. 
	 *
	 * see http://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php#279310
	 * 
	 * @param  int 	$width  
	 * @param  int 	$height 
	 * @return image        
	 */
	private function transparentImage($width, $height) {
		$img = imagecreatetruecolor($width, $height);
		imagealphablending($img, false);
		imagesavealpha($img, true); 				
		$backgr = imagecolorallocatealpha($img, 255, 255, 255, 127);
		imagefilledrectangle($img, 0, 0, $width, $height, $backgr);
		return $img;
	}
}

?>