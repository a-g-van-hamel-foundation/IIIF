<?php

/**
 * @deprecated
 */

use MediaWiki\MediaWikiServices;
use IIIF\ParserFunctions\IIIFGetCanvases;
use IIIF\IIIFParsers\IIIFCanvasParsers;
use IIIF\IIIFParsers\IIIFParserUtils;
use IIIFUtils;

class IIIFJson {

	/**
	 * @deprecated Moved to IIIFUtils
	 * @param array $templateArr
	 * @param string $targetType
	 * @param string $targetName
	 * @param array $userParams
	 * @return string
	 */
	public static function convertArrayToWikiInstances(
		array $templateArr,
		string $targetType,
		string $targetName,
		array $userParams = []
	) {
		return IIIFUtils::convertArrayToWikiInstances($templateArr, $targetType, $targetName, $userParams );
	}

	/**
	 * @deprecated Moved to IIIFUtils
	 * @param array $arr
	 * @param array $userParams
	 * @return array[]
	 */
	public static function appendCustomToArray(
		array $arr,
		array $userParams
	) {
		return IIIFUtils::appendCustomToArray( $arr, $userParams );
	}

	/**
	 * @deprecated Moved to IIIFGetCanvases class
	 */
	public static function runGetCanvasDataForTemplate( $parser, $frame, $params ) {
			return IIIFGetCanvases::runGetCanvasDataForTemplate( $parser, $frame, $params );
	}

	/**
	 * @deprecated Moved to IIIFCanvasParsers class
	 * @param mixed $manifestArr
	 * @return array<array>
	 */
	public static function getCanvasesV2( $manifestArr ): array {
		return IIIFCanvasParsers::getCanvasesV2( $manifestArr );
	}
	
	/**
	 * @deprecated Moved to IIIFUtils::getArrayFromJsonUrl()
	 */
	public static function getArrayFromManifest( $manifestUrl ) {
		return IIIFUtils::getArrayFromJsonUrl( $manifestUrl );
	}

	/**
	 * @deprecated Moved to IIIFParsers
	 * Get Presentation API version number from IIIF manifest
	 * @param array $obj
	 * 
	 * @return string
	 */
	public static function getPresentationApiVersion( $obj ) {
		return IIIFParserUtils::getPresentationApiVersion( $obj );
	}

}
