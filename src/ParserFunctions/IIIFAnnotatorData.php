<?php

namespace IIIF\ParserFunctions;

use Parser;
use Title;
use IIIF\IIIFUtils;
use IIIF\ParserFunctions\IIIFParserFunctionUtils;
use IIIF\IIIFParsers\IIIFAnnotoriousParsers;
use IIIF\IIIFParsers\IIIFImageUtils;

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
			"profileid" => null,
			// W3C or Annotorious
			"datamodel" => "W3C",
			"sep" => ";"
		];

		[ $page, $slot, $targetTemplate, $target, $data, $manifest, $profileId, $dataModel, $sep ] = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );
		// associative array
		$userParams = IIIFParserFunctionUtils::extractUserParams( $frame, $params );

		$jsonStr = IIIFUtils::getRawContentFromPageName( $page, $slot );
		$jsonArr = json_decode( $jsonStr, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		if( $manifest !== null && $manifest !== "" ) {
			$manifestArr = IIIFUtils::getArrayFromJsonUrl( $manifest );
			if ( $manifestArr === null ) {
				// @todo i18n error message
				return [ "", "noparse" => false, "isHTML" => false ];
			}
			$imageMimeType = IIIFImageUtils::getImageMimeType( $manifestArr, "image/jpeg" );
		} else {
			$imageMimeType = "image/jpeg";
		}

		$IIIFAnnotoriousParser = new IIIFAnnotoriousParsers();
		$IIIFAnnotoriousParser->setOptions( $sep );
		// 'base' array
		$newArray = $IIIFAnnotoriousParser->convertAnnotationPages(
			$jsonArr,
			$data,
			$dataModel,
			$manifest,
			$profileId,
			$imageMimeType
		);

		$res = "";
		if ( $targetTemplate !== null && $targetTemplate !== "" ) {
			$res = IIIFUtils::convertArrayToWikiInstances( $newArray, "template", $targetTemplate, $userParams );
		} else {
			//$res = "<pre>" . var_export( $newArray, true ) . "</pre>";
			foreach( $newArray as $instance ) {
				foreach( $userParams as $k => $v ) {
					$instance[$k] = $v;					
				}
				$dataset[] = $instance;
			}
			$res = "<pre>" . json_encode( $dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "</pre>";
		}

		return [ $res, "noparse" => false, "isHTML" => false ];
	}

	/**
	 * @todo Deprecated???
	 * @param \Parser $parser
	 * @param mixed $page
	 * @param mixed $title
	 * @param mixed $text
	 */
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
