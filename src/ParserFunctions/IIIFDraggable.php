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

class IIIFDraggable {
	public function run( Parser $parser, PPFrame $frame, $params ) {
		$paramsAllowed = [
			"lib" => null
		];
		[ $lib ] = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );

		if ( $lib === "vue-draggable-plus" ) {
			$parser->getOutput()->addModules( [ "ext.iiif.draggableplus" ] );
			$class = "iiif-draggableplus-widget";
		} else {
			$parser->getOutput()->addModules( [ "ext.iiif.draggable" ] );
			$class = "iiif-draggable-widget";
		}

		$res = Html::rawElement(
			"div",
			[ "class" => $class ],
			"..."
		);
		return $res;
	}
}
