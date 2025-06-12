<?php

namespace IIIF\ParserFunctions;

use Parser;
use Title;
use IIIFUtils;
use IIIFJson;
use IIIF\ParserFunctions\IIIFParserFunctionUtils;
use IIIF\IIIFParsers\IIIFAnnotoriousParsers;

class IIIFAnnotatorData {

	/**
	 * Parser function (`#iiif-annotator-data`) for turning
	 * annotation data into template data the wiki can work with
	 * Cf. TemplateFunc
	 * 
	 * @param mixed $parser
	 * @param mixed $frame
	 * @param mixed $params
	 * @return
	 */
	public static function runGetAnnotationDataForTemplate( $parser, $frame, $params ) {
		if ( $params == null || $params == 'undefined' ) {
			print_r( "nothing to see here" );
			return false;
		}

		$paramsAllowed = [
			"page" => null,
			"slot" => "main",
			"targettemplate" => null,
			/* To be be considered
			"targetwidget" => null,
			"targetmodule" => null,
			"targetmustache" => null,
			"targetmustachedir" => null,
			*/
			"target" => null,
			"data" => "all",
			// "mode" => "normal",
			"manifest" => null,
			// W3C or Annotorious
			"datamodel" => "W3C"
		];

		[ $page, $slot, $targetTemplate, $target, $data, $manifest, $dataModel ] = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );

		$jsonStr = IIIFUtils::getRawContentFromPageName( $page, $slot );
		$jsonArr = json_decode( $jsonStr, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		if( $manifest !== null && $manifest !== "" ) {
			$manifestArr = IIIFUtils::getArrayFromJsonUrl( $manifest );
			if ( $manifestArr == null ) {
				//...
			}
		}

		$IIIFAnnotoriousParser = new IIIFAnnotoriousParsers();
		$newArray = $IIIFAnnotoriousParser->convertAnnotationPages( $jsonArr, $data, $dataModel );

		$res = "";
		if ( $targetTemplate !== null ) {
			$res = IIIFJson::convertArrayToWikiInstances( $newArray, 'template', $targetTemplate, "", "", "" );
		} elseif( $target = "raw" ) {
			$res = "<pre>" . var_export( $newArray, true ) . "</pre>";
		}

		return [ $res, 'noparse' => false, 'isHTML' => false ];
	}

	private static function getTemplateText( Parser $parser, $page, $title, $text ) {
		$title = $title ?? Title::newFromText( $page );
		if ( $title === null || $title->isExternal() ) {
			return false;
		} else {
			[ $text, $title ] = $parser->fetchTemplateAndTitle( $title );
		}
		return $text ?? false;
	}

}
