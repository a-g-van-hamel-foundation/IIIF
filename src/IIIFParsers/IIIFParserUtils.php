<?php

/**
 * General convertors and utility methods not covered 
 * by dedicated clsses
 */

namespace IIIF\IIIFParsers;

use IIIFUtils;

class IIIFParserUtils {

	/**
	 * Get Presentation API version number from IIIF manifest
	 * @dev Originally in IIIFJson
	 * @param array $obj The manifest as an array
	 * 
	 * @return string
	 */
	public static function getPresentationApiVersion( $obj ) {
		//$obj = self::getArrayFromManifest( $manifest );
		//if ( $obj == null ) {
		//  return;
		//}
		$context = $obj['@context'];
		$context = preg_replace( "(^https?://)", "", $context);
		if ( $context == "iiif.io/api/presentation/3/context.json" ) {
			$version = '3';
		} elseif ( $context == "iiif.io/api/presentation/2/context.json" ) {
			$version = '2';
		} elseif ( $context == "iiif.io/api/presentation/1/context.json" ) {
			//No support for annotations
			$version = '1';
		} else {
			// Assumed default is questionable
			$version = '2';
		}
		return $version;
	}

	/**
	 * Breaking changes in IIIF v3
	 * @param mixed $str
	 * @param mixed $apiVersion
	 * @param mixed $language
	 */
	public static function formatLabelOrValue( mixed $v, string $apiVersion, string $language = "none" ) {
		return $apiVersion === "3"
			? [ $language => [ $v ] ]
			: $v;
	}

}
