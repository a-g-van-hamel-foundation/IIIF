<?php

/**
 * Utility methods for working with local MediaWiki pages
 */

namespace IIIF\IIIFParsers;

use Title;
use MediaWiki\MediaWikiServices;
use IIIF\IIIFUtils;
use File;
use LocalFile;
use IIIF\IIIFParsers\IIIFImageUtils;

class IIIFMwImageUtils {

	public function construct() {
		//
	}

	/**
	 * Constructs thumbnail on the basis of the subpage
	 * /{repo}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
	 * Special:IIIFServ/image/local/18814/full/1024,270/0/default.jpg
	 * Example subpage: local/imgidentifier/full/1024,270/0/default.jpg
	 * local/125,15,200,200/max/0/default.jpg
	 * 
	 * Helper for IIIFSpecialServ
	 * @return string|bool
	 */
	public static function getIIRImage( $urlParts ) {
		// example: /Special:IIIFServ
		// purpose: /image
		// repo: /local
		// identifier: /44736
		// region: /923.5431214151888,708.4897398147448,338.87043189368774,258.0287929125137
		// size: /,250
		// rotation: /0
		// quality: /default.jpg
		$request = [
			"purpose" => $urlParts[0],
			"repo" => $urlParts[1],
			"identifier" => $urlParts[2],
			// assuming full or max - we don't deal with regions just yet
			"region" => $urlParts[3],
			 // size: comma-separated
			"size" => $urlParts[4],
			// rotation: assuming 0:
			"rotation" => $urlParts[5],
			"quality" => "default"
		];

		// default
		$repo = $request["repo"];

		$prefixAndIdArr = explode( ":", $request["identifier"] );
		if ( count( $prefixAndIdArr ) < 2 ) {
			$pageidArr = explode( "-", $request["identifier"] );
		} else {
			// has prefix for repo - should not be necessary though
			$repo = $prefixAndIdArr[0];
			$pageidArr = explode( "-", $prefixAndIdArr[1] );
		}

		// identifier: get file
		$pageid = $pageidArr[0];
		// @todo: deal with possible revid = $pageidArr[1] ?
		$titleObj = Title::newFromID( $pageid, 0 );
		$localRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
		$file = $localRepo->findFile( $titleObj );
		if ( $file === false ) {
			return "";
		}
		$fullWidth = $file->getWidth();
		$fullHeight = $file->getHeight();

		// size
		[ $width, $height ] = IIIFImageUtils::getDisplayWidthHeight( $request["size"], $fullWidth, $fullHeight );
		if ( $width === null ) {
			$width = round( $fullWidth * ( $height / $fullHeight ) );
			//$height = null;
		} elseif( $height === null ) {
			$height = LocalFile::scaleHeight( $fullWidth, $fullHeight, $width );
		}

		// region:
		// @todo currently unused because MW (on its own) does not support cropping
		// [ $x, $y, $w, $h ] = IIIFImageUtils::translateXYWHRegion( $request["region"], $width, $height );

		// Create and return thumbnail
		$baseUrl = IIIFUtils::getUrlBase();
		$thumbnailPath = self::createThumbnail( $file, $width, $height );		
		if ( $thumbnailPath !== "" ) {
			return $baseUrl . $thumbnailPath;
		}
		return false;
	}

	/**
	 * Create thumbnail
	 * cf. ApiQueryImageInfo : $finalThumbParams = $this->mergeThumbParams( $img, $scale, $params['urlparam'] );
	 * @todo maybe check if we can make the requested thumbnail, and get transform parameters.
	 * @link https://phabricator.wikimedia.org/source/mediawiki/browse/master/includes/api/ApiQueryImageInfo.php
	 * @link https://phabricator.wikimedia.org/source/mediawiki/browse/REL1_39/includes/filerepo/file/File.php
	 * note: createThumb: keeps aspect ratio and contrains
	 * 
	 * @param File $img
	 * @param int $width
	 * @param int $height
	 * @return string - string is empty if requested thumbnail could not be created
	 */
	public static function createThumbnail( File $img, $width, $height = -1 ): string {
		//$thumb = $img->createThumb( $width, $height );
		//return $thumb;
		$params = [];
		if ( $width !== null ) {
			$params["width"] = $width;
		}
		if ( $height !== null || $height !== -1 ) {
			$params["height"] = $height;
		}
		$thumb = $img->transform( $params );
		if ( !$thumb || $thumb->isError() ) {
			return "";
		}
		return $thumb->getUrl();
	}

	/**
	 * Get PHP image processing library used for MediaWiki
	 * @return bool|string returns false if undetermined
	 */
	private static function getImageLibrary() {
		global $wgUseImageMagick;
		if( $wgUseImageMagick && extension_loaded( 'imagick' ) ) {
			return "ImageMagick";
		}
		if( extension_loaded('gd' ) ) {
			return "GD";
		}
		return false;
	}

	/**
	 * Get the file data required to create a Canvas resource
	 * Supports both V2 and V3
	 * @param string $baseUrl
	 * @param mixed $pageid
	 * @param string|null $fileName
	 * @param mixed $revid - currently unused
	 * @return array
	 */
	public function getFileDataForCanvas(
		string $baseUrl,
		mixed $pageid = null,
		mixed $fileName = null,
		mixed $revid = null
	) {		
		if ( $pageid !== null ) {
			$titleObj = Title::newFromID( $pageid, 0 );
			$fileName = $titleObj->getText();
		} elseif ( $fileName !== null ) {
			$titleObj = Title::newFromText( $fileName, NS_FILE );
			$pageid = $titleObj->getId();
		}
		// $fileUrl = $titleObj->getFullURL(); // maybe
		$localRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
		$fileObj = $localRepo->findFile( $titleObj );
		// @todo maybe allow for getting file by revision ID
		// $fileObj = IIIFMwImgAPI::getFile( $pageid, "pageid", $revid );
		if ( $fileObj === false ) {
			print_r( "No file object.<br>" );
			return [];
		}

		$sourceHeight = $fileObj->getHeight();
		$sourceWidth = $fileObj->getWidth();
		$uploader = $fileObj->getUploader();
		// $mediaType = $fileObj->getMediaType();
		$mimeType = $fileObj->getMimeType();
		// $metadataArr = $fileObj->getMetaDataArray();
		$thumbnailUrl = $fileObj->createThumb( $sourceWidth, $sourceHeight  ); // largest thumbnail URL
		$imageResourceId = $baseUrl . $thumbnailUrl;
		$smallThumb = $baseUrl . $fileObj->createThumb( 120 );

		return [
			"pageid" => $pageid,
			"fileName" => $fileName,
			"sourceWidth" => $sourceWidth,
			"sourceHeight" => $sourceHeight,
			"uploader" => $uploader,
			"mediaType" => $mimeType,
			"thumbnailUrl" => $thumbnailUrl,
			"imageResourceId" => $imageResourceId,
			"smallThumb" => $smallThumb
		];
	}

	/**
	 * Variant that uses fixed sizes
	 * @param int $fullWidth
	 * @param int $fullHeight
	 * @return array
	 */
	public static function calculateLevel0SizesFixed(
		int $sourceWidth,
		int $sourceHeight
	) {
		// $wgImageLimits
		$thumbWidths = [ 320, 640, 800, 1024, 1280, 2560 ];
		foreach( $thumbWidths as $thumbWidth ) {
			if ( $thumbWidth >= $sourceWidth ) {
				// Don't use scales larger than the original?
				break;
			}
			$thumbHeight = LocalFile::scaleHeight( $sourceWidth, $sourceHeight, $thumbWidth );
			$scaleArr[] = [ "width" => $thumbWidth, "height" => $thumbHeight ];
		}
		$scaleArr[] = [ "width" => $sourceWidth, "height" => $sourceHeight ];
		return $scaleArr;
	}

	/**
	 * Calculate sizes appropriate for level0, tile-less zoom
	 * by 'halving one's way downward'
	 * @deprecated
	 * @param int $fullWidth
	 * @param int $fullHeight
	 * @return array
	 */
	public static function calculateLevel0Sizes(
		int $fullWidth,
		int $fullHeight
	) {
		$scales = [
			[ "width" => $fullWidth, "height" => $fullHeight ]
		];
		[ $w, $h ] = [ $fullWidth, $fullHeight ];
		while ( $i < 5 ) {
			$w = round($w / 2);
			if ( $w < 100 ) {
				// No need to get even smaller thumbs
				break;
			}
			$h = LocalFile::scaleHeight( $fullWidth, $fullHeight, $w );
			$scales[] = [ "width" => $w, "height" => $h ];
			$i++;			
		}
		return array_reverse( $scales );
	}

	/**
	 * @todo GD-based method to crop a png image
	 * @param mixed $image
	 * @param mixed $xywh
	 * @return void
	 */
	private function cropPngImageForGDTest( $image, $type, array $xywh ) {
		// GD, get GdImage object from file
		$gdImg = imagecreatefrompng('example.png');
		// get a minimum size
		// $size = min(imagesx($gdImg ), imagesy($gdImg ));

		// imagecrop accepts a GdImage or resource
		$croppedImage = imagecrop( $gdImg, [
			'x' => $xywh["x"],
			'y' => $xywh["y"],
			'width' => $xywh["w"],
			'height' => $xywh["h"]
		]);
		if ($croppedImage !== false ) {
			// out
    		imagepng(
				$croppedImage,
				'example-cropped.png'
			);
		}
	}

}
