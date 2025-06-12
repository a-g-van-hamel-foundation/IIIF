<?php

/**
 * @todo Move to appropriate class
 */

use MediaWiki\MediaWikiServices;
use IIIF\ParserFunctions\IIIFGetCanvases;
use IIIF\IIIFParsers\IIIFCanvasParsers;
use IIIF\IIIFParsers\IIIFParserUtils;
use IIIFUtils;

class IIIFJson {

	public static function convertArrayToWikiInstances(
		array $templateArr,
		string $targetType,
		string $targetName,
		string $userParam1,
		string $userParam2,
		string $userParam3
	) {
		$str = "";
		if ($targetType == "template") {
			$prefix = "{{";
		} elseif ($targetType == "widget") {
			$prefix = "{{#widget:";
		} elseif ($targetType == "module") {
			$prefix = "{{#invoke:";
		} elseif ($targetType == "subobject") {
			// Afterthought not implemented, but note they're nameless
			$prefix = "{{#subobject:";
		}
		foreach ( $templateArr as $instance ) {
			$str .= $prefix . $targetName . "\n";
			foreach ($instance as $k => $v) {
				$str .= "|{$k}={$v}" . "\n";
			}
			$str .= "|userparam1={$userParam1}";
			$str .= "|userparam2={$userParam2}";
			$str .= "|userparam3={$userParam3}";
			$str .= "}}";
		}
		return $str;
	}

	public static function appendCustomToArray(
		array $arr,
		string $userParam1,
		string $userParam2,
		string $userParam3
	) {
		$newArr = [];
		foreach ( $arr as $k => $instance ) {
			$newInstance = [];
			foreach ($instance as $k => $v) {
				$newInstance[$k] = $v;
			}
			$newInstance['userparam1'] = ( $userParam1 !== "") ? $userParam1 : "";
			$newInstance['userparam2'] = ( $userParam2 !== "") ? $userParam2 : "";
			$newInstance['userparam3'] = ( $userParam3 !== "") ? $userParam3 : "";
			$newArr[] = $newInstance;
		}
		return $newArr;
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
