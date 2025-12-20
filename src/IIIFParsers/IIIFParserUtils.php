<?php

/**
 * General convertors and utility methods not covered 
 * by dedicated classes.
 */

namespace IIIF\IIIFParsers;

use IIIF\IIIFUtils;
use IIIF\IIIFParsers\IIIFImageUtils;

class IIIFParserUtils {

	/**
	 * Get Presentation API version number from IIIF manifest
	 * @param array $obj The manifest as an array
	 * 
	 * @return string
	 */
	public static function getPresentationApiVersion( $obj ) {
		$context = $obj['@context'] ?? "";
		$context = preg_replace( "(^https?://)", "", $context);
		if ( $context == "iiif.io/api/presentation/3/context.json" ) {
			$version = "3";
		} elseif ( $context == "iiif.io/api/presentation/2/context.json" ) {
			$version = "2";
		} elseif ( $context == "iiif.io/api/presentation/1/context.json" ) {
			//No support for annotations
			$version = "1";
		} else {
			// @todo Assumed default is questionable
			$version = "2";
		}
		return $version;
	}

	/**
	 * Breaking changes in IIIF v3
	 * 
	 * @param mixed $str
	 * @param mixed $apiVersion
	 * @param mixed $language
	 */
	public static function formatLabelOrValue( mixed $v, string $apiVersion, string $language = "none" ) {
		return $apiVersion === "3"
			? [ $language => [ $v ] ]
			: $v;
	}

	/**
	 * For labels and descriptions, V3 works with language
	 * codes and arrays. 
	 * @param mixed $v
	 * @param mixed $language
	 * @return string
	 */
	public static function getV3ValueAsString( mixed $v, $languageCode = false, $valueSep = ", " ) {
		if ( $v === null ) {
			return "";
		}
		if ( $languageCode === false || $languageCode === null ) {
			// Get first one regardless of language code
			$firstKey = array_keys($v )[0];
			return implode( $valueSep, $v[$firstKey] );
		}
		return implode( $valueSep, $v[$languageCode] );
	}

	/**
	 * @deprecated - Moved to IIIFImageUtils
	 */
	public static function roundXywhToIntegers( mixed $xywh ) {
		return IIIFImageUtils::roundXywhToIntegers( $xywh );
	}

}
