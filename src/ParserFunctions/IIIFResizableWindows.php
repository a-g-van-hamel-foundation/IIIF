<?php

/**
 *
 */

namespace IIIF\ParserFunctions;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Html\Html;
use IIIF\ParserFunctions\IIIFParserFunctionUtils;

class IIIFResizableWindows {

	/**
	 * #iiif-resizable-windows
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param mixed $params
	 */
	public function run( Parser $parser, PPFrame $frame, $params ) {
		$paramsAllowed = [
			"window1" => "",
			"window2" => "",
			"orientation" => "EW"
		];
		[ $window1, $window2, $orientation ] = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );

		$window1El = Html::rawElement( "div", [ "class" => "window-1" ], $window1 );
		$window2El = Html::rawElement( "div", [ "class" => "window-2" ], $window2 );
		$mountElement = Html::rawElement( "div", [ 
			"class" => "iiif-resizable-windows",
			"data-orientation" => strtoupper( $orientation )
		], $window1El  . $window2El  );

		$parser->getOutput()->addModules( [ "ext.iiif.resizablewindows" ] );
		$parser->getOutput()->addModuleStyles( [ "ext.iiif.styles.less" ] );

		return $mountElement;
	}

}
