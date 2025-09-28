<?php

/**
 * Parser fnction for the TIFY viewer.
 * Added in v0.6
 */

namespace IIIF\ParserFunctions;

use Parser;
use PPFrame;
use Html;
use IIIF\ParserFunctions\IIIFParserFunctionUtils;

class IIIFTify {

	/**
	 * {{#iiif-tify: manifest=.... }}
	 * @param mixed $parser
	 * @param mixed $frame
	 * @param mixed $params
	 * @return 
	 */
	public static function run( Parser $parser, PPFrame $frame, $params ) {
		$paramsAllowed = [
			"manifest" => null,
			"canvasindex" => "",
			"annotationsvisible" => "true",// annotationsVisible
			"setview" => null
		];
		[ $manifestUrl, $canvasIndex, $annotationsVisible, $setView ] = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );

		$attributes = [
			"id" => "iiif-tify-viewer-" . rand(10000,99999),
			"class" => "iiif-tify-viewer",
			"data-manifest" => $manifestUrl,
			"data-annotations-visible" => $annotationsVisible,
			"data-canvas-index" => $canvasIndex,
			"style" => "height: 640px"
		];
		if ( $setView !== null ) {
			$attributes["data-setview"] = $setView;
		}

		$res = Html::rawElement(
			"div",
			$attributes,
			""
		);

		$parser->getOutput()->addModules( [ "ext.iiif.tify" ] );
		return $res;
	}

}
