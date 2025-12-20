<?php

/**
 * Utility methods for handling Image Requests.
 */

namespace IIIF\IIIFParsers;

use IIIF\IIIFUtils;
use IIIF\IIIFParsers\IIIFParserUtils;

class IIIFImageUtils {

	/**
	 * Translate notation in xywh fragment
	 * Currently unused because on its own, MediaWiki does not 
	 * support cropping.
	 * @link https://iiif.io/api/image/3.0/#41-region
	 * 
	 * @return 
	 */
	private static function translateXYWHRegion( $xywh, $fullWidth, $fullHeight ) {
		if ( $xywh === "full" ) {
			[ $x, $y, $w, $h ] = [0, 0, $fullWidth, $fullHeight];
		} elseif( $xywh === "square" ) {
			[ $x, $y, $w, $h ] = [0, 0, $fullWidth, $fullHeight];
			$shorterDimension = min( intval($w), intval($h) );
			[ $x, $y, $w, $h ] = [
				$x/2 - $shorterDimension/2,
				$y/2 - $shorterDimension/2,
				$shorterDimension,
				$shorterDimension
			];
		} elseif( str_starts_with( $xywh, "pct:" ) ) {
			$pct = str_replace( "pct:", "", $xywh );
			[ $pctX, $pctY, $pctW, $pctH ] = explode( ",", $pct );
			$x = ($fullWidth/100) * intval($pctX);
			$y = ($fullHeight/100) * intval($pctY);
			$w = ($fullWidth/100) * intval($pctW);
			$h = ($fullHeight/100) * intval($pctH);
		} else {
			$values = [];
			foreach( explode( ",", $xywh ) as $val ) {
				$values[] = intval($val);
			}
			[ $x, $y, $w, $h ] = $values;
		}
		return [ $x, $y, $w, $h ];
	}

	/**
	 * Translate size (w,h) specification in Image Request 
	 * to appropriate width and height.
	 * Unspecified width or height is explicitly returned as null.
	 * 
	 * Unsupported: aspect ratio distortion
	 * Unsupported: upscaling
	 * 
	 * @link https://iiif.io/api/image/3.0/#42-size
	 * 
	 * @param string $size - requested size
	 * @param mixed $fullWidth
	 * @param mixed $fullHeight
	 * @return array width and height
	 */
	public static function getDisplayWidthHeight(
		string $size,
		mixed $fullWidth,
		mixed $fullHeight
	) {
		// caret (^) allows for upscaling
		// unless ! is set, caret allows for aspect ratio distortion
		// ( e.g. ^! disallows aspect ratio distortion)
		$hasCaret = str_starts_with( $size, "^" );
		$allowsForUpscaling = $hasCaret;
		$size = str_replace( "^", "", $size );
		// excl mark (!) imposes proportional constraint on w,h
		$hasMaxWHConstraint = str_starts_with( $size, "!" );
		$size = str_replace( "!", "", $size );
		$allowsForAspectRatioDistortion = $hasMaxWHConstraint ? false : $hasCaret;

		// Max
		if ( $size === "max" || $size === "full" ) {
			// V3 has "max", V2 has "full"
			// Because we don't have maxWidth, maxHeight or maxArea defined
			// in a 'technical properties' section, this should do.
			return [ $fullWidth, $fullHeight ];
		}

		// Percentages not pixels
		if( str_starts_with( $size, "pct:" ) ) {
			$pct = explode( ",", str_replace( "pct:", "", $size ) );
			$w = $pct[0] !== "" && $pct[0] !== null
				? ($fullWidth/100) * intval( $pct[0] )
				: null;
			$h = ( $pct[1] ?? "" ) !== "" && $pct[1] !== null
				? ($fullHeight/100) * intval( $pct[1] )
				: null;
			if ( $hasMaxWHConstraint && $h !== null && $w !== null ) {
				[ $w, $h ] = self::constrainWHToMaxArea( $w, $h,$fullWidth, $fullHeight, $allowsForUpscaling );
			}
			return [ $w, $h ];
		}

		// Pixels
		$wh = explode( ",", $size );
		//return [ null, 250 ];

		$w = $wh[0] !== ""
			? intval($wh[0])
			: null;
		$h = ( array_key_exists( 1, $wh ) && $wh[1] !== "" )
			? intval($wh[1])
			: null;
		if ( $hasMaxWHConstraint && $w !== null && $h !== null  ) {
			[ $w, $h ] = self::constrainWHToMaxArea( $w, $h, $fullWidth, $fullHeight, $allowsForUpscaling );
		}
		return [ $w, $h ];
	}

	/**
	 * Handler for !w,h, returning new width and height based on 
	 * proportional constraint, preserving aspect ratio.
	 * ^!w,h = MAY be larger than the extracted region, upscaling allowed
	 * @return array
	 */
	public static function constrainWHToMaxArea( int $w, int $h, int $maxWidth, int $maxHeight, $allowsForUpscaling = false ) {
		$widthPercent = $w / $maxWidth;
		$heightPercent = $h / $maxHeight;
		$smallestPercent = min( $widthPercent, $heightPercent );
		if ( $allowsForUpscaling ) {
			return [ $maxWidth * $smallestPercent, $maxHeight * $smallestPercent ];
		} else {
			// @todo
			return $smallestPercent > 1
				? [ $maxWidth, $maxHeight ]
				: [ $maxWidth * $smallestPercent, $maxHeight * $smallestPercent ];
		}
	}

	/**
	 * Image API 3: "Size and region parameters in pixels must be non-negative integers"
	 * @param array|string $xywh
	 * @return array|string
	 */
	public static function roundXywhToIntegers( mixed $xywh ) {
		$newXywh = [];
		if( gettype( $xywh ) === "array" ) {
			foreach( $xywh as $n ) {
				$newXywh[] = round($n);
			}
			return $newXywh;
		} else {
			$xywhArr = explode( ",", $xywh);
			foreach( $xywhArr as $n ) {
				$newXywh[] = round($n);
			}
			return implode( ",", $newXywh );
		}
	}

	/**
	 * Imperfect method to get the image format.
	 * Assuming, potentially incorrectly, that every image requested
	 * requires the same image format.
	 * 
	 * @param mixed $manifestArr
	 * @return
	 */
	public static function getImageMimeType( array $manifestArr, string $default ) {
		$apiVersion = IIIFParserUtils::getPresentationApiVersion( $manifestArr );
		if( $apiVersion === "2" ) {
			return IIIFUtils::getArrayPath( [ "sequences", 0, "canvases", 0, "images", 0, "resource", "format" ], $manifestArr, $default );
		}
		if( $apiVersion === "3" ) {
			return IIIFUtils::getArrayPath( [ "items", 0, "items", 0, "body", "format" ], $manifestArr, $default );
		}
		return $default;
	}

	/**
	 * Translate mime type to file extension
	 */
	public static function translateMimetypeToFileExtension( $mimeType, $default = "jpg" ) {
		$map = [
			"image/jpeg" => "jpg",
			"image/jpg" => "jpg",
			"image/tiff" => "tif",
			"image/png" => "png",
			"image/gif" => "gif",
			"image/jp2" => "jp2",
			"image/webp" => "webp",
			"application/pdf" => "pdf"
		];
		return $map[$mimeType] ?? $default;
	}

}
